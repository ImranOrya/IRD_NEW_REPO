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
use App\Models\Document;
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

        $query = $this->ngoRepository->ngo();  // Start with the base query
        $this->ngoRepository->transJoin($query, $locale)
            ->statusJoin($query)
            ->statusTypeTransJoin($query, $locale)
            ->typeTransJoin($query, $locale)
            ->directorJoin($query)
            ->directorTransJoin($query, $locale)
            ->emailJoin($query)
            ->contactJoin($query);
        $query->select(
            'n.id',
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

    public function publicNgos(Request $request, $page)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page
        $locale = App::getLocale();
        $includedIds  = [StatusTypeEnum::active->value, StatusTypeEnum::active->value];

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
        // Handle NGO not found
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
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
            'type' => ['name' => $ngo->type_name, 'id' => $ngo->ngo_type_id],
            'contact' => $ngo->contact,
            'email' =>   $ngo->email,
            'registration_no' => $ngo->registration_no,
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
    public function ngoDetail($ngo_id)
    {
        $locale = App::getLocale();
        $query = $this->ngoRepository->ngo();  // Start with the base query
        $this->ngoRepository->typeTransJoin($query, $locale)
            ->emailJoin($query)
            ->contactJoin($query)
            ->addressJoin($query);
        $ngo = $query->select(
            'n.abbr',
            'n.ngo_type_id',
            'ntt.value as type_name',
            'n.registration_no',
            'n.moe_registration_no',
            'n.place_of_establishment',
            'n.date_of_establishment',
            'a.province_id',
            'a.district_id',
            'a.id as address_id',
            'e.value as email',
            'c.value as contact'
        )->where('n.id', $ngo_id)->first();

        // Handle NGO not found
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
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
            'type' => ['name' => $ngo->type_name, 'id' => $ngo->ngo_type_id],
            'establishment_date' => $ngo->date_of_establishment,
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


    public function ngoMoreInformation($id)
    {
        $ngo = Ngo::join('ngo_trans as en', function ($join) {
            $join->on('ngos.id', '=', 'en.ngo_id')->where('en.language_name', 'en');
        })
            ->join('ngo_trans as ps', function ($join) {
                $join->on('ngos.id', '=', 'ps.ngo_id')->where('ps.language_name', 'ps');
            })
            ->join('ngo_trans as fa', function ($join) {
                $join->on('ngos.id', '=', 'fa.ngo_id')->where('fa.language_name', 'fa');
            })->select(

                'en.vision as vision_english',
                'ps.vision as vision_pashto',
                'fa.vision as vision_farsi',
                'en.mission as mission_english',
                'ps.mission as mission_pashto',
                'fa.mission as mission_farsi',
                'en.general_objective as general_objes_english',
                'ps.general_objective as general_objes_pashto',
                'fa.general_objective as general_objes_farsi',
                'en.objective as objes_in_afg_english',
                'ps.objective as objes_in_afg_pashto',
                'fa.objective as objes_in_afg_farsi',
            )->where('ngos.id', $id)->get();


        return response()->json([
            'message' => __('app_translation.success'),
            'ngo' => $ngo,

        ], 200, [], JSON_UNESCAPED_UNICODE);

        // return $ngo;
    }

    public function ngoCheckListDocument($id)
    {

        $agreement = Ngo::leftJoin('agreements', function ($join) {
            $join->on('ngos.id', '=', 'agreements.ngo_id')->orderByDesc('agreements.end_date')->limit(1);
        })->where('ngos.id', $id)->select('agreements.id')->first();


        $document =   Document::join('agreement_documents', 'agreement_documents.document_id', 'documents.id')
            ->where('agreement_documents.agreement_id', $agreement->id)
            ->select('documents.path', 'documents.size', 'check_list_id', 'documents.type', 'actual_name')
            ->get();

        $checklistMap = [];

        foreach ($document as $doc) {
            $checklistMap[] = [
                (int) $doc->check_list_id,  // First item in array (checklist ID)
                [
                    'name' => $doc->actual_name,
                    'size' => $doc->size,
                    'check_list_id' => (string) $doc->check_list_id,
                    'extension' => $doc->type,
                    'path' => $doc->path,
                ],
            ];
        }

        return response()->json([
            'message' => __('app_translation.success'),
            'checklistMap' => $checklistMap,

        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getStoredNgoData1($id)
    {
        $locale = App::getLocale();
        $ngo = Ngo::join('ngo_trans as en', function ($join) {
            $join->on('ngos.id', '=', 'en.ngo_id')->where('en.language_name', 'en');
        })
            ->join('ngo_trans as ps', function ($join) {
                $join->on('ngos.id', '=', 'ps.ngo_id')->where('ps.language_name', 'ps');
            })
            ->join('ngo_trans as fa', function ($join) {
                $join->on('ngos.id', '=', 'fa.ngo_id')->where('fa.language_name', 'fa');
            })
            ->leftJoin('ngo_type_trans as ngtt', function ($join) use ($locale) {
                $join->on('ngos.ngo_type_id', '=', 'ngtt.ngo_type_id')->where('ngtt.language_name', $locale);
            })
            ->leftJoin('addresses', 'ngos.address_id', '=', 'addresses.id')
            ->leftJoin('address_trans as addr_en', function ($join) {
                $join->on('addresses.id', '=', 'addr_en.address_id')->where('addr_en.language_name', 'en');
            })
            ->leftJoin('address_trans as addr_ps', function ($join) {
                $join->on('addresses.id', '=', 'addr_ps.address_id')->where('addr_ps.language_name', 'ps');
            })
            ->leftJoin('address_trans as addr_fa', function ($join) {
                $join->on('addresses.id', '=', 'addr_fa.address_id')->where('addr_fa.language_name', 'fa');
            })
            ->leftJoin('emails', 'ngos.email_id', '=', 'emails.id')
            ->leftJoin('contacts', 'ngos.contact_id', '=', 'contacts.id')
            ->leftJoin('agreements', function ($join) {
                $join->on('ngos.id', '=', 'agreements.ngo_id')->orderByDesc('agreements.end_date')->limit(1);
            })
            ->leftJoin('directors', function ($join) {
                $join->on('ngos.id', '=', 'directors.ngo_id')->where('is_active', 1);
            })
            ->leftJoin('genders as gen', function ($join) use ($locale) {
                $join->on('gen.id', '=', 'directors.gender_id');
            })
            ->leftJoin('nid_type_trans as ntt', function ($join) use ($locale) {
                $join->on('ntt.nid_type_id', '=', 'directors.nid_type_id')->where('ntt.language_name', $locale);
            })
            ->leftJoin('director_trans as dir_en', function ($join) {
                $join->on('directors.id', '=', 'dir_en.director_id')->where('dir_en.language_name', 'en');
            })
            ->leftJoin('director_trans as dir_ps', function ($join) {
                $join->on('directors.id', '=', 'dir_ps.director_id')->where('dir_ps.language_name', 'ps');
            })
            ->leftJoin('director_trans as dir_fa', function ($join) {
                $join->on('directors.id', '=', 'dir_fa.director_id')->where('dir_fa.language_name', 'fa');
            })
            ->leftJoin('emails as dir_email', 'directors.email_id', '=', 'dir_email.id')
            ->leftJoin('contacts as dir_contact', 'directors.contact_id', '=', 'dir_contact.id')
            ->leftJoin('addresses as dir_address', 'directors.address_id', '=', 'dir_address.id')
            ->leftJoin('address_trans as dir_addr_en', function ($join) {
                $join->on('dir_address.id', '=', 'dir_addr_en.address_id')->where('dir_addr_en.language_name', 'en');
            })
            ->leftJoin('address_trans as dir_addr_ps', function ($join) {
                $join->on('dir_address.id', '=', 'dir_addr_ps.address_id')->where('dir_addr_ps.language_name', 'ps');
            })
            ->leftJoin('address_trans as dir_addr_fa', function ($join) {
                $join->on('dir_address.id', '=', 'dir_addr_fa.address_id')->where('dir_addr_fa.language_name', 'fa');
            })
            ->where('ngos.id', $id)
            ->select([
                // NGO Basic Data
                'ngos.id',
                'ngos.abbr',
                'ngos.ngo_type_id',
                'ngos.address_id',
                'ngos.moe_registration_no',
                'ngos.regisjtration_no',
                'ngos.place_of_establishment',
                'ngos.date_of_establishment',

                // ngo Type 
                'ngtt.value as ngo_type_name',

                // NGO Translations
                'en.name as name_english',
                'ps.name as name_pashto',
                'fa.name as name_farsi',
                'en.vision as vision_english',
                'ps.vision as vision_pashto',
                'fa.vision as vision_farsi',
                'en.mission as mission_english',
                'ps.mission as mission_pashto',
                'fa.mission as mission_farsi',
                'en.general_objective as general_objes_english',
                'ps.general_objective as general_objes_pashto',
                'fa.general_objective as general_objes_farsi',
                'en.objective as objes_in_afg_english',
                'ps.objective as objes_in_afg_pashto',
                'fa.objective as objes_in_afg_farsi',

                // NGO Address
                'addresses.province_id',
                'addresses.district_id',
                'addr_en.area as area_english',
                'addr_ps.area as area_pashto',
                'addr_fa.area as area_farsi',

                // NGO Contacts
                'emails.value as email',
                'contacts.value as contact',

                // Agreement
                'agreements.id as agreement_id',


                // Director Information
                'dir_en.name as director_name_english',
                'dir_ps.name as director_name_pashto',
                'dir_fa.name as director_name_farsi',
                'dir_en.last_name as director_surname_english',
                'dir_ps.last_name as director_surname_pashto',
                'dir_fa.last_name as director_surname_farsi',
                'directors.nid_no',
                'directors.nid_type_id',
                'directors.gender_id',
                "gen.name_{$locale} as gender_name",
                "ntt.value as nid_type_name",
                'directors.country_id as nationality_id',
                'dir_email.value as director_email',
                'dir_contact.value as director_contact',
                'dir_address.province_id as director_province_id',
                'dir_address.district_id as director_district_id',
                'dir_addr_en.area as director_area_english',
                'dir_addr_ps.area as director_area_pashto',
                'dir_addr_fa.area as director_area_farsi'
            ])
            ->first();

        if (!$ngo) {
            return response()->json(['message' => __('app_translation.not_found')], 404);
        }

        $document =   Document::join('agreement_documents', 'agreement_documents.document_id', 'documents.id')
            ->where('agreement_documents.agreement_id', $ngo->agreement_id)
            ->select('documents.path', 'documents.size', 'check_list_id', 'documents.type', 'actual_name')
            ->get();

        $checklistMap = [];

        foreach ($document as $doc) {
            $checklistMap[] = [
                (int) $doc->check_list_id,  // First item in array (checklist ID)
                [
                    'name' => $doc->actual_name,
                    'size' => $doc->size,
                    'check_list_id' => (string) $doc->check_list_id,
                    'extension' => $doc->type,
                    'path' => $doc->path,
                ],
            ];
        }



        // Format Response
        return response()->json(
            [
                'data' =>    [
                    'id' => $ngo->id,
                    'abbr' => $ngo->abbr,
                    'name_english' => $ngo->name_english,
                    'name_pashto' => $ngo->name_pashto,
                    'name_farsi' => $ngo->name_farsi,
                    'type' => ['id' => $ngo->ngo_type_id, 'name' => $ngo->ngo_type_name],
                    'contact' => $ngo->contact,
                    'email' => $ngo->email,
                    'registration_no' => $ngo->registration_no,
                    'province_id' => ['id' => $ngo->province_id, 'name' => $this->getProvince($ngo->province_id, $locale)],
                    'district_id' =>    ['id' => $ngo->district_id, 'name' => $this->getDistrict($ngo->district_id, $locale)],
                    'area_english' => $ngo->area_english,
                    'area_pashto' => $ngo->area_pashto,
                    'area_farsi' => $ngo->area_farsi,
                    'establishment_date' => $ngo->date_of_establishment,
                    'country' => ['id' => $ngo->place_of_establishment, 'name' => $this->getCountry($ngo->place_of_establishment, $locale)],
                    'moe_registration_no' => $ngo->moe_registration_no,
                    // ngo Complete

                    'director_name_english' => $ngo->director_name_english,
                    'director_name_pashto' => $ngo->director_name_pashto,
                    'director_name_farsi' => $ngo->director_name_farsi,
                    'surname_english' => $ngo->director_surname_english,
                    'surname_pashto' => $ngo->director_surname_pashto,
                    'surname_farsi' => $ngo->director_surname_farsi,
                    'director_email' => $ngo->director_email,
                    'director_contact' => $ngo->director_contact,
                    'gender' => ['id' => $ngo->gender_id, 'name' => $ngo->gender_name],
                    'nationality' => ['id' => $ngo->nationality_id, $this->getCountry($ngo->nationality_id, $locale)],
                    'identity_type' => ['name' => $ngo->nid_type_name, 'id' => $ngo->nid_type_id],
                    'nid' => $ngo->nid_no,

                    'director_province' => ['id' => $ngo->director_province_id, 'name' => $this->getProvince($ngo->director_province_id, $locale)],
                    'director_dis' => ['id' => $ngo->director_district_id, 'name' => $this->getDistrict($ngo->director_district_id, $locale)],
                    'director_area_english' => $ngo->director_area_english,
                    'director_area_pashto' => $ngo->director_area_pashto,
                    'director_area_farsi' => $ngo->director_area_farsi,

                    // director complete 

                    'mission_english' => $ngo->mission_english,
                    'mission_pashto' => $ngo->mission_pashto,
                    'mission_farsi' => $ngo->mission_farsi,
                    'vision_english' => $ngo->vision_english,
                    'vision_pashto' => $ngo->vision_pashto,
                    'vision_farsi' => $ngo->vision_farsi,
                    'general_objes_english' => $ngo->general_objes_english,
                    'general_objes_pashto' => $ngo->general_objes_pashto,
                    'general_objes_farsi' => $ngo->general_objes_farsi,
                    'objes_in_afg_english' => $ngo->objes_in_afg_english,
                    'objes_in_afg_farsi' => $ngo->objes_in_afg_farsi,
                    'objes_in_afg_pashto' => $ngo->objes_in_afg_pashto,

                    'checklistMap' => $checklistMap

                ],
                'message' => __('app_translation.success'),
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
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
                'id' => 'n.id',
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
            'id' => 'n.id',
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
