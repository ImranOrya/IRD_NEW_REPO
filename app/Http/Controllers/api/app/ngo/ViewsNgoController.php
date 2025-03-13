<?php

namespace App\Http\Controllers\api\app\ngo;

use Carbon\Carbon;
use App\Models\Ngo;
use App\Models\Document;
use App\Models\Agreement;
use App\Enums\LanguageEnum;
use App\Models\PendingTask;
use App\Traits\Ngo\NgoTrait;
use Illuminate\Http\Request;
use App\Enums\Type\TaskTypeEnum;
use App\Enums\Type\StatusTypeEnum;
use App\Models\PendingTaskContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Models\Email;
use App\Traits\Address\AddressTrait;
use App\Repositories\ngo\NgoRepositoryInterface;
use App\Repositories\Task\PendingTaskRepositoryInterface;

class ViewsNgoController extends Controller
{
    //
    use AddressTrait, NgoTrait;

    protected $ngoRepository;
    protected $pendingTaskRepository;

    public function __construct(
        PendingTaskRepositoryInterface $pendingTaskRepository,
        NgoRepositoryInterface $ngoRepository
    ) {
        $this->ngoRepository = $ngoRepository;
        $this->pendingTaskRepository = $pendingTaskRepository;
    }

    public function ngos(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page
        $locale = App::getLocale();

        $query = $this->ngoRepository->ngo();  // Start with the base query
        $this->ngoRepository->transJoin($query, $locale)
            ->statusJoin($query)
            ->statusTypeTransJoin($query, $locale)
            ->typeTransJoin($query, $locale)
            ->emailJoin($query)
            ->contactJoin($query);
        $query->select(
            'n.id',
            'n.profile',
            'n.registration_no',
            'n.date_of_establishment as establishment_date',
            'stt.status_type_id as status_id',
            'stt.name as status',
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

    public function publicNgos(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page
        $locale = App::getLocale();
        $includedIds  = [StatusTypeEnum::registered->value, StatusTypeEnum::registered->value];

        $query = $this->ngoRepository->ngo();  // Start with the base query
        $this->ngoRepository->transJoin($query, $locale)
            ->statusJoin($query)
            ->statusTypeTransJoin($query, $locale)
            ->typeTransJoin($query, $locale)
            ->directorJoin($query)
            ->directorTransJoin($query, $locale)
            ->emailJoin($query)
            ->contactJoin($query);
        $query->whereIn('ns.status_type_id', $includedIds)
            ->select(
                'n.id',
                'n.abbr',
                'stt.name as status',
                'nt.name',
                'ntt.value as type',
                'dt.name as director',
            );

        $this->applyFilters($query, $request);
        $this->applySearch($query, $request);
        // Now paginate the result (after mapping provinces)
        $result = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'ngos' => $result
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function startRegisterForm(Request $request, $ngo_id)
    {
        $locale = App::getLocale();

        $pendingTaskContent = $this->pendingTask($request, $ngo_id);
        if ($pendingTaskContent['content']) {
            return response()->json([
                'message' => __('app_translation.success'),
                'content' => $pendingTaskContent['content']
            ], 200);
        }

        $query = $this->ngoRepository->ngo($ngo_id);
        $data = $this->ngoRepository->startRegisterFormInfo($query, $ngo_id, $locale);
        if (!$data) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 404);
        }

        return response()->json([
            'message' => __('app_translation.success'),
            'ngo' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function ngoDetail($ngo_id)
    {
        $locale = App::getLocale();
        $query = $this->ngoRepository->ngo($ngo_id);  // Start with the base query
        $data = $this->ngoRepository->afterRegisterFormInfo($query, $ngo_id, $locale);
        if (!$data) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 404);
        }

        return response()->json([
            'ngo' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function pendingTask(Request $request, $id): array
    {
        // Retrieve the first matching pending task
        $task = $this->pendingTaskRepository->pendingTaskExist(
            $request->user(),
            TaskTypeEnum::ngo_registeration,
            $id,
        );

        if ($task) {
            // Fetch and concatenate content
            $pendingTask = PendingTaskContent::where('pending_task_id', $task->id)
                ->select('content', 'id')
                ->orderBy('id', 'desc')
                ->first();
            return [
                'content' => $pendingTask ? $pendingTask->content : null
            ];
        }

        return [
            'content' => null
        ];
    }

    public function moreInformation($id)
    {
        $query = $this->ngoRepository->ngo($id);  // Start with the base query
        $this->ngoRepository->transJoinLocales($query);
        $ngos = $query->select(
            'nt.vision',
            'nt.mission',
            'nt.general_objective',
            'nt.objective',
            'nt.language_name'
        )->get();

        $result = [];
        foreach ($ngos as $item) {
            $language = $item->language_name;

            if ($language === LanguageEnum::default->value) {
                $result['vision_english'] = $item->vision;
                $result['mission_english'] = $item->mission;
                $result['general_objes_english'] = $item->general_objective;
                $result['objes_in_afg_english'] = $item->objective;
            } elseif ($language === LanguageEnum::farsi->value) {
                $result['vision_farsi'] = $item->vision;
                $result['mission_farsi'] = $item->mission;
                $result['general_objes_farsi'] = $item->general_objective;
                $result['objes_in_afg_farsi'] = $item->objective;
            } else {
                $result['vision_pashto'] = $item->vision;
                $result['mission_pashto'] = $item->mission;
                $result['general_objes_pashto'] = $item->general_objective;
                $result['objes_in_afg_pashto'] = $item->objective;
            }
        }

        return response()->json([
            'ngo' => $result,

        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function statuses($id)
    {
        $locale = App::getLocale();

        $result = $this->ngoRepository->statuses($id, $locale);

        return response()->json([
            'statuses' => $result,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function headerInfo($ngo_id)
    {
        // 1. Get ngo information
        $query = $this->ngoRepository->ngo($ngo_id);  // Start with the base query
        $this->ngoRepository->statusJoin($query)
            ->emailJoin($query)
            ->contactJoin($query);
        $ngo = $query->select(
            'n.profile',
            'ns.status_type_id as status_id',
            'n.username',
            'c.value as contact',
            'e.value as email'
        )->first();
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        $result = [
            "profile" => $ngo->profile,
            "status_id" => $ngo->status_id,
            "username" => $ngo->username,
            "contact" => $ngo->contact,
            "email" => $ngo->email,
        ];
        return response()->json([
            'ngo' => $result,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function ngoCount()
    {
        $statistics = DB::select("
        SELECT
         COUNT(*) AS count,
            (SELECT COUNT(*) FROM ngos WHERE DATE(created_at) = CURDATE()) AS todayCount,
            (SELECT COUNT(*) FROM ngos n JOIN ngo_statuses ns ON n.id = ns.ngo_id WHERE ns.status_type_id = ?) AS activeCount,
         (SELECT COUNT(*) FROM ngos n JOIN ngo_statuses ns ON n.id = ns.ngo_id WHERE ns.status_type_id = ? AND ns.status_type_id != ? ) AS unRegisteredCount
        FROM ngos
            ", [StatusTypeEnum::registered->value, StatusTypeEnum::registered->value, StatusTypeEnum::blocked->value]);
        return response()->json([
            'counts' => [
                "count" => $statistics[0]->count,
                "todayCount" => $statistics[0]->todayCount,
                "activeCount" => $statistics[0]->activeCount,
                "unRegisteredCount" =>  $statistics[0]->unRegisteredCount
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
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
            $allowedColumns = [
                'registration_no' => 'n.registration_no',
                'name' => 'nt.name',
                'type' => 'ntt.value',
                'contact' => 'c.value',
                'email' => 'e.value'
            ];
            // Ensure that the search column is allowed
            if (in_array($searchColumn, array_keys($allowedColumns))) {
                $query->where($allowedColumns[$searchColumn], 'like', '%' . $searchValue . '%');
            }
        }
    }
    // filter function
    protected function applyFilters($query, $request)
    {
        $sort = $request->input('filters.sort'); // Sorting column
        $order = $request->input('filters.order', 'asc'); // Sorting order (default 
        $allowedColumns = [
            'name' => 'nt.name',
            'type' => 'ntt.value',
            'contact' => 'c.value',
            'status' => 'nstr.name'
        ];
        if (in_array($sort, array_keys($allowedColumns))) {
            $query->orderBy($allowedColumns[$sort], $order);
        }
    }
}
