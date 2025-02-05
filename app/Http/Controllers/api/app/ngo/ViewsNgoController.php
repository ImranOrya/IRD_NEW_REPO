<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Models\Ngo;
use App\Models\PendingTask;
use App\Traits\Ngo\NgoTrait;
use Illuminate\Http\Request;
use App\Enums\Type\TaskTypeEnum;
use App\Enums\Type\StatusTypeEnum;
use App\Models\PendingTaskContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Traits\Address\AddressTrait;
use App\Repositories\ngo\NgoRepositoryInterface;

class ViewsNgoController extends Controller
{
    //
    use AddressTrait, NgoTrait;

    protected $ngoRepository;

    public function __construct(NgoRepositoryInterface $ngoRepository)
    {
        $this->ngoRepository = $ngoRepository;
    }

    public function ngos(Request $request, $page)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page
        $locale = App::getLocale();

        $query = DB::table('ngos as n')
            ->join('ngo_trans as nt', 'nt.ngo_id', '=', 'n.id')
            ->where('nt.language_name', $locale)
            ->join('ngo_type_trans as ntt', 'ntt.ngo_type_id', '=', 'n.ngo_type_id')
            ->where('ntt.language_name', $locale)
            ->leftJoin(
                DB::raw('(SELECT ns1.* FROM ngo_statuses ns1 
                         JOIN (SELECT ngo_id, MAX(created_at) as max_date 
                               FROM ngo_statuses GROUP BY ngo_id) ns2 
                         ON ns1.ngo_id = ns2.ngo_id AND ns1.created_at = ns2.max_date) as ns'),
                'ns.ngo_id',
                '=',
                'n.id'
            ) // LEFT JOIN to include NGOs without a status
            ->leftJoin('status_type_trans as nstr', 'nstr.status_type_id', '=', 'ns.status_type_id')
            ->where(function ($query) use ($locale) {
                $query->where('nstr.language_name', $locale)
                    ->orWhereNull('nstr.language_name'); // Ensure NGOs with no status are included
            })
            ->leftJoin('emails as e', 'e.id', '=', 'n.email_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'n.contact_id')
            ->orderBy('n.created_at', 'desc')
            ->select(
                'n.id',
                'n.profile',
                'n.abbr',
                'n.registration_no',
                'n.date_of_establishment as establishment_date',
                'nstr.status_type_id as status_id',
                'nstr.name as status',
                'nt.name',
                'ntt.ngo_type_id as type_id',
                'ntt.value as type',
                'e.value as email',
                'c.value as contact',
                'n.created_at'
            );






        $this->applyDate($query, $request);
        $this->applyFilters($query, $request);
        $this->applySearch($query, $request);

        $result = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'ngos' => $result
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function ngo($id)
    {
        $locale = App::getLocale();

        return response()->json(
            [
                'message' => __('app_translation.success'),
                "ngo" => []
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function ngoInit(Request $request, $ngo_id)
    {
        $locale = App::getLocale();

        $personalDetail = $this->personalDetial($request, $ngo_id);
        if ($personalDetail['content']) {
            return response()->json([
                'message' => __('app_translation.success'),
                'content' => $personalDetail['content']
            ], 200);
        }

        // Joining necessary tables to fetch the NGO data
        $ngo = $this->ngoRepository->getNgoInit($locale, $ngo_id);

        // Fetching translations using a separate query
        $translations = $this->ngoNameTrans($ngo_id);
        $areaTrans = $this->getAddressAreaTran($ngo->address_id);
        $address = $this->getCompleteAddress($ngo->address_id, $locale);


        $data = [
            'name_english' => $translations['en']->name ?? null,
            'name_pashto' => $translations['ps']->name ?? null,
            'name_farsi' => $translations['fa']->name ?? null,
            'abbr' => $ngo->abbr,
            'type' => ['name' => $ngo->type_name, 'id' => $ngo->ngo_type_id],
            'contact' => $ngo->contact,
            'email' => $ngo->email,
            'province' => ['name' => $address['province'], 'id' => $ngo->province_id],
            'district' => ['name' => $address['district'], 'id' => $ngo->district_id],
            'area_english' => $areaTrans['en']->area ?? '',
            'area_pashto' => $areaTrans['ps']->area ?? '',
            'area_farsi' => $areaTrans['fa']->area ?? '',
        ];

        return response()->json([
            'message' => __('app_translation.success'),
            'ngo' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function ngoDetail(Request $request, $ngo_id)
    {

        $locale = App::getLocale();

        // Joining necessary tables to fetch the NGO data
        $ngo = Ngo::join('contacts', 'contact_id', '=', 'contacts.id')
            ->leftJoin('emails', 'email_id', '=', 'emails.id')
            ->leftJoin('addresses', 'address_id', '=', 'addresses.id')
            ->select(
                'registration_no',
                'abbr',
                'moe_registration_no',
                'place_of_establishment',
                'province_id',
                'district_id',
                'addresses.id as address_id',
                'ngos.email_id',
                'emails.value as email',
                'contacts.value as contact',
                'ngos.contact_id',
                'date_of_establishment',


            )
            ->where('ngos.id', $ngo_id)
            ->first();

        // Handle NGO not found
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.not_found'),
            ], 404);
        }

        // Fetching translations using a separate query
        $translations = $this->ngoNameTrans($ngo_id);
        $areaTrans = $this->getAddressAreaTran($ngo->address_id);
        $address = $this->getCompleteAddress($ngo->address_id, $locale);

        $data = [
            'name_english' => $translations['en']->name ?? null,
            'name_pashto' => $translations['ps']->name ?? null,
            'name_farsi' => $translations['fa']->name ?? null,
            'abbr' => $ngo->abbr,
            'registration_no' => $ngo->registration_no,
            'moe_registration_no' => $ngo->moe_registration_no,
            'date_of_establishment' => $ngo->date_of_establishment,
            'place_of_establishment' => ['name' => $this->getCountry($ngo->place_of_establishment, $locale), 'id' => $ngo->place_of_establishment],
            'contact' => $ngo->contact,
            'email' => $ngo->email,
            'province' => ['name' => $address['province'], 'id' => $ngo->province_id],
            'district' => ['name' => $address['district'], 'id' => $ngo->district_id],
            'area_english' => $areaTrans['en']->area ?? '',
            'area_pashto' => $areaTrans['ps']->area ?? '',
            'area_farsi' => $areaTrans['fa']->area ?? '',
        ];

        return response()->json([
            'message' => __('app_translation.success'),
            'ngo' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    public function ngoCount()
    {
        $statistics = DB::select("
        SELECT
         COUNT(*) AS count,
            (SELECT COUNT(*) FROM ngos WHERE DATE(created_at) = CURDATE()) AS todayCount,
            (SELECT COUNT(*) FROM ngos n JOIN ngo_statuses ns ON n.id = ns.ngo_id WHERE ns.status_type_id = ?) AS activeCount,
         (SELECT COUNT(*) FROM ngos n JOIN ngo_statuses ns ON n.id = ns.ngo_id WHERE ns.status_type_id = ?) AS unRegisteredCount
        FROM ngos
            ", [StatusTypeEnum::active->value, StatusTypeEnum::unregistered->value]);
        return response()->json([
            'counts' => [
                "count" => $statistics[0]->count,
                "todayCount" => $statistics[0]->todayCount,
                "activeCount" => $statistics[0]->activeCount,
                "unRegisteredCount" =>  $statistics[0]->unRegisteredCount
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function ngosPublic(Request $request, $page)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page
        $locale = App::getLocale();
        $includedIds  = [StatusTypeEnum::active->value, StatusTypeEnum::active->value];

        $query = DB::table('ngos as n')
            ->join('ngo_trans as nt', function ($join) use ($locale) {
                $join->on('nt.ngo_id', '=', 'n.id')
                    ->where('nt.language_name', $locale);
            })
            ->leftjoin('ngo_statuses as ns', 'ns.ngo_id', '=', 'n.id')
            ->whereIn('ns.status_type_id', $includedIds)
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
                'n.date_of_establishment as establishment_date',
                'n.created_at',
                'nstr.name as status',
                'nt.name',
                'ntt.value as type',
                'dirt.name as director',
                'add.province_id as province',
            );

        $this->applyFiltersPublic($query, $request);
        $this->applySearchPublic($query, $request);

        // Fetch data first (without pagination)
        $ngos = $query->get();

        // Modify the result by getting provinces for each item after fetching
        $ngos = $ngos->map(function ($item) use ($locale) {
            $item->province = $this->getProvince($item->province, $locale);
            return $item;
        });

        // Now paginate the result (after mapping provinces)
        $result = $this->paginatePublic($ngos, $perPage, $page);

        return response()->json([
            'ngos' => $result
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    protected function paginatePublic($data, $perPage, $page)
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
    // search function 
    protected function applySearchPublic($query, $request)
    {

        $searchColumn = $request->input('filters.search.column');
        $searchValue = $request->input('filters.search.value');

        if ($searchColumn && $searchValue) {
            $allowedColumns = ['name', 'abbr'];

            // Ensure that the search column is allowed
            if (in_array($searchColumn, $allowedColumns)) {
                $query->where($searchColumn, 'like', '%' . $searchValue . '%');
            }
        }
    }
    // filter function
    protected function applyFiltersPublic($query, $request)
    {
        $sort = $request->input('filters.sort'); // Sorting column
        $order = $request->input('filters.order', 'asc'); // Sorting order (default 
        // Default sorting if no sort is provided
        $query->orderBy("created_at", 'desc');
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
            $allowedColumns = ['title', 'contents'];

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

        if ($sort && in_array($sort, ['id', 'name', 'type', 'contact', 'status'])) {
            $query->orderBy($sort, $order);
        } else {
            // Default sorting if no sort is provided
            $query->orderBy("created_at", 'desc');
        }
    }
    public function personalDetial(Request $request, $id): array
    {
        $user = $request->user();
        $user_id = $user->id;
        $role = $user->role_id;
        $task_type = TaskTypeEnum::ngo_registeration;

        // Retrieve the first matching pending task
        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $id)
            ->first();

        if ($task) {
            // Fetch and concatenate content
            $pendingTask = PendingTaskContent::where('pending_task_id', $task->id)
                ->select('content', 'id')
                ->orderBy('id', 'desc')
                ->first();
            return [
                // 'max_step' => $maxStep,
                'content' => $pendingTask ? $pendingTask->content : null
            ];
        }

        return [
            'content' => null
        ];
    }
}
