<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use App\Traits\Address\AddressTrait;


class NgoPublicController extends Controller
{
    //\

    use AddressTrait;

public function ngos(Request $request, $page)
{
    $perPage = $request->input('per_page', 10); // Number of records per page
    $page = $request->input('page', 1); // Current page
    $locale = App::getLocale();

    $query = DB::table('ngos as n')
        ->join('ngo_trans as nt', function ($join) use ($locale) {
            $join->on('nt.ngo_id', '=', 'n.id')
                ->where('nt.language_name', $locale);
        })
        ->leftjoin('ngo_statuses as ns', 'ns.ngo_id', '=', 'n.id')
        ->leftjoin('status_type_trans as nstr', function ($join) use ($locale) {
            $join->on('nstr.status_type_id', '=', 'ns.status_type_id')
                ->where('nstr.language_name', $locale);
        })
        ->join('ngo_type_trans as ntt', function ($join) use ($locale) {
            $join->on('ntt.ngo_type_id', '=', 'n.ngo_type_id')
                ->where('ntt.language_name', $locale);
        })
        ->leftjoin('directors as dir', 'dir.ngo_id', '=', 'n.id')
        ->leftjoin('director_trans as dirt', function ($join) use ($locale) {
            $join->on('dir.id', '=', 'dirt.director_id')
                ->where('dirt.language_name', $locale);
        })
        ->leftjoin('addresses as add', 'add.id', '=', 'n.address_id')
        ->select(
            'n.id',
            'n.abbr',
            'n.registration_no',
            'n.date_of_establishment as establishment_date',
            'nstr.name as status',
            'nt.name as ngo_name',
            'ntt.value as type',
            'dirt.name as director_name',
            'add.province_id as province',
            'n.created_at'
        );

    // Apply the filters and pagination
    $this->applyDate($query, $request);
    $this->applyFilters($query, $request);
    $this->applySearch($query, $request);

    // Fetch data first (without pagination)
    $ngos = $query->get();

    // Modify the result by getting provinces for each item after fetching
    $ngos = $ngos->map(function ($item) use ($locale) {
        $item->province = $this->getProvince($item->province, $locale);
        return $item;
    });

    // Now paginate the result (after mapping provinces)
    $result = $this->paginate($ngos, $perPage, $page);

    return response()->json([
        'ngos' => $result
    ], 200, [], JSON_UNESCAPED_UNICODE);
}

protected function paginate($data, $perPage, $page)
{
    // Paginates manually after mapping the provinces
    $offset = ($page - 1) * $perPage;
    $paginatedData = $data->slice($offset, $perPage); // Slice the data for pagination
    return new \Illuminate\Pagination\LengthAwarePaginator(
        $paginatedData,
        $data->count(),
        $perPage,
        $page,
        ['path' => url()->current()]  // Set path for the paginator links
    );
}

        protected function applyDate($query, $request)
    {
        // Apply date filtering conditionally if provided
        $startDate = $request->input('filters.date.startDate');
        $endDate = $request->input('filters.date.endDate');

        if ($startDate) {
            $query->where('n.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('n.created_at', '<=', $endDate);
        }
    }
    // search function 
    protected function applySearch($query, $request)
    {

        $searchColumn = $request->input('filters.search.column');
        $searchValue = $request->input('filters.search.value');

        if ($searchColumn && $searchValue) {
            $allowedColumns = ['name','abbr'];

            // Ensure that the search column is allowed
            if (in_array($searchColumn, $allowedColumns)) {
                $query->where($searchColumn, 'like', '%' . $searchValue . '%');
            }
        }
    }
    // filter function
    protected function applyFilters($query, $request)
    {
        $sort = $request->input('filters.sort'); // Sorting column
        $order = $request->input('filters.order', 'asc'); // Sorting order (default 

        if ($sort && in_array($sort, [ 'name', 'type', 'status'])) {
            $query->orderBy($sort, $order);
        } else {
            // Default sorting if no sort is provided
            $query->orderBy("created_at", 'desc');
        }
    }
}
