<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Ngo;
use App\Models\News;
use App\Models\Role;
use App\Models\User;

use App\Models\Email;
use App\Models\Staff;
use App\Models\Gender;

use App\Enums\RoleEnum;
use App\Models\Address;
use App\Models\Country;
use App\Models\NgoTran;
use App\Enums\StaffEnum;
use App\Models\Approval;
use App\Models\Director;
use App\Models\District;
use App\Models\Document;
use App\Models\Province;
use App\Models\Agreement;
use App\Models\CheckList;
use App\Models\Translate;
use App\Models\StatusType;
use App\Enums\LanguageEnum;
use App\Models\AddressTran;
use App\Models\PendingTask;
use App\Models\NidTypeTrans;
use Illuminate\Http\Request;
use App\Enums\PermissionEnum;
use App\Models\CheckListType;
use Sway\Models\RefreshToken;
use App\Models\RolePermission;
use App\Models\StatusTypeTran;
use App\Models\UserPermission;
use App\Enums\CheckListTypeEnum;
use App\Enums\SubPermissionEnum;
use App\Models\RolePermissionSub;
use App\Enums\DestinationTypeEnum;
use App\Enums\Type\StatusTypeEnum;
use App\Models\PendingTaskContent;
use App\Traits\Helper\HelperTrait;
use Illuminate\Support\Facades\DB;
use App\Models\PendingTaskDocument;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Enums\Type\ApprovalTypeEnum;
use App\Traits\Address\AddressTrait;
use function Laravel\Prompts\select;
use Illuminate\Support\Facades\Http;
use App\Enums\CheckList\CheckListEnum;
use App\Enums\Type\RepresenterTypeEnum;
use App\Enums\Type\RepresentorTypeEnum;
use App\Repositories\ngo\NgoRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;

class TestController extends Controller
{
    protected $ngoRepository;
    protected $userRepository;
    use HelperTrait;

    public function __construct(
        NgoRepositoryInterface $ngoRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->ngoRepository = $ngoRepository;
        $this->userRepository = $userRepository;
    }
    use AddressTrait;

    public function format($approvals)
    {
        return $approvals->groupBy('id')->map(function ($group) {
            $docs = $group->filter(function ($item) {
                return $item->approval_document_id !== null;
            });

            $approval = $group->first();

            $approval->approved = (bool) $approval->approved;
            if ($docs->isNotEmpty()) {
                $docs->documents = $docs->map(function ($doc) {
                    return [
                        'id' => $doc->approval_document_id,
                        'documentable_id' => $doc->documentable_id,
                        'documentable_type' => $doc->documentable_type,
                    ];
                });
            } else {
                $approval->documents = [];
            }
            unset($approval->approval_document_id);

            return $approval;
        })->values();
    }
    public function index()
    {
        $expiresAtTimestamp = Carbon::parse("2025-03-20 21:46:58")->timestamp;
        if (Carbon::now()->timestamp > $expiresAtTimestamp) {
            return 'Token has expired';
        }
        return;
        $now = Carbon::now('UTC');
        $status_type_id = [
            StatusTypeEnum::registered->value,
            StatusTypeEnum::blocked->value
        ];
        $expiredAgreements = DB::table('agreements as a')
            ->select('a.ngo_id', DB::raw('MAX(a.end_date) as max_end_date'), DB::raw('MAX(a.id) as max_id'))
            ->where('a.end_date', '<', $now->toIso8601String())
            ->groupBy('a.ngo_id')
            ->join('ngo_statuses as ns', function ($join) use (&$status_type_id) {
                $join->on('ns.ngo_id', '=', 'a.ngo_id')
                    ->where('ns.is_active', true)
                    ->whereIn('ns.status_type_id', $status_type_id);
            })
            ->pluck('a.ngo_id');
        return $expiredAgreements;

        $locale = App::getLocale(); // Current locale/language
        $ngo_id = 4;
        $userModel = $this->getModelName(User::class);
        $ngoModel = $this->getModelName(Ngo::class);

        return  $representor = DB::table('representers as r')
            ->where('r.ngo_id', $ngo_id)
            ->join('representer_trans as rt', function ($join) use ($locale) {
                $join->on('r.id', '=', 'rt.representer_id')
                    ->where('rt.language_name', $locale);
            })
            ->join('agreement_representers as ar', 'ar.representer_id', '=', 'r.id')
            ->join('agreements as a', function ($join) {
                $join->on('a.id', '=', 'ar.agreement_id');
            })
            ->leftJoin('users as u', function ($join) use ($userModel) {
                $join->on('r.userable_id', '=', 'u.id')
                    ->where('r.userable_type', $userModel);
            })
            ->leftJoin('ngos as n', function ($join) use ($ngoModel) {
                $join->on('r.userable_id', '=', 'n.id')
                    ->where('r.userable_type', $ngoModel);
            })
            ->select(
                'r.id',
                'r.is_active',
                'r.userable_id',
                'r.userable_type',
                'r.created_at',
                'rt.full_name',
                'u.username',
                'a.id as agreement_id',
                'a.agreement_no',
                'a.start_date',
                'a.end_date',
                "u.username as saved_by"
            )
            ->orderBy('r.id', 'desc')
            ->get();

        $ngo = DB::table('ngos as n')
            ->where('n.id', $ngo_id)
            ->join('ngo_trans as nt', function ($join) use ($locale) {
                $join->on('nt.ngo_id', '=', 'n.id')
                    ->where('nt.language_name', $locale);
            })
            ->join('contacts as c', 'c.id', '=', 'n.contact_id')
            ->join('emails as e', 'e.id', '=', 'n.email_id')
            ->join('addresses as a', 'a.id', '=', 'n.address_id')
            ->join('address_trans as at', function ($join) use ($locale) {
                $join->on('at.address_id', '=', 'a.id')
                    ->where('at.language_name', $locale);
            })
            ->join('district_trans as dt', function ($join) use ($locale) {
                $join->on('dt.district_id', '=', 'a.district_id')
                    ->where('dt.language_name', $locale);
            })
            ->join('province_trans as pt', function ($join) use ($locale) {
                $join->on('pt.province_id', '=', 'a.province_id')
                    ->where('pt.language_name', $locale);
            })
            ->join('country_trans as ct', function ($join) use ($locale) {
                $join->on('ct.country_id', '=', 'n.place_of_establishment')
                    ->where('ct.language_name', $locale);
            })
            ->select(
                'n.id',
                'n.registration_no',
                'n.moe_registration_no',
                'n.abbr',
                'n.date_of_establishment',
                'nt.name',
                'nt.vision',
                'nt.mission',
                'nt.general_objective',
                'nt.objective',
                'c.value as contact',
                'e.value as email',
                'dt.value as district',
                'dt.district_id',
                'at.area',
                'pt.value as province',
                'pt.province_id',
                'ct.value as country',
            )
            ->first();

        $director =  DB::table('directors as d')
            ->where('d.ngo_id', $ngo_id)
            ->where('d.is_active', true)
            ->join('director_trans as dirt', function ($join) use ($locale) {
                $join->on('dirt.director_id', '=', 'd.id')
                    ->where("dirt.language_name", $locale);
            })
            ->join('addresses as a', 'a.id', '=', 'd.address_id')
            ->join('address_trans as at', function ($join) use ($locale) {
                $join->on('at.address_id', '=', 'a.id')
                    ->where('at.language_name', $locale);
            })
            ->join('district_trans as dt', function ($join) use ($locale) {
                $join->on('dt.district_id', '=', 'a.district_id')
                    ->where('dt.language_name', $locale);
            })
            ->join('province_trans as pt', function ($join) use ($locale) {
                $join->on('pt.province_id', '=', 'a.province_id')
                    ->where('pt.language_name', $locale);
            })
            ->join('country_trans as ct', function ($join) use ($locale) {
                $join->on('ct.country_id', '=', 'd.country_id')
                    ->where('ct.language_name', $locale);
            })
            ->select(
                'dirt.name',
                'dirt.last_name',
                'dt.value as district',
                'dt.district_id',
                'pt.value as province',
                'pt.province_id',
                'ct.value as country',
                'at.area',
            )
            ->first();
        if (!$director) {
            return "Director not found";
        }
        $irdDirector = DB::table('staff as s')
            ->where('s.staff_type_id', StaffEnum::director->value)
            ->join('staff_trans as st', function ($join) use ($locale) {
                $join->on('st.staff_id', '=', 's.id')
                    ->where("st.language_name", $locale);
            })
            ->select(
                'st.name',
            )
            ->first();
        if (!$irdDirector) {
            return "IRD Director not found";
        }
        return [
            'register_number' => $ngo->registration_no,
            'date_of_sign' => '................',
            'ngo_name' =>  $ngo->name,
            'abbr' => $ngo->abbr,
            'contact' => $ngo->contact,
            'address' =>                      [
                'complete_address' => $ngo->area . ',' . $ngo->district . ',' . $ngo->province . ',' . $ngo->country,
                'area' => $ngo->area,
                'district' => $ngo->district,
                'province' => $ngo->province,
                'country' => $ngo->country
            ],
            'director' => $director->name . " " . $director->last_name,
            'director_address' => [
                'complete_address' => $director->area . ',' . $director->district . ',' . $director->province . ',' . $director->country,
                'area' => $director->area,
                'district' => $director->district,
                'province' => $director->province,
                'country' => $director->country
            ],
            'email' => $ngo->email,
            'establishment_date' => $ngo->date_of_establishment,
            'place_of_establishment' => $ngo->country,
            'ministry_economy_no' => $ngo->moe_registration_no,
            'general_objective' => $ngo->general_objective,
            'afganistan_objective' => $ngo->objective,
            'mission' => $ngo->mission,
            'vission' => $ngo->vision,
            'ird_director' => $irdDirector->name,
        ];



        // Fetch expired agreements in bulk using DB::table()
        return DB::table('agreements')
            ->where('end_date', '<', $now->toIso8601String())
            ->pluck('ngo_id'); // Fetch only ngo_id
        $approval_id = 1;
        $approval =  DB::table('approvals as a')
            ->where('a.id', $approval_id)->first();
        if (!$approval) {
            return response()->json([
                'message' => __('app_translation.approval_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        if ($approval->requester_type == Ngo::class) {
        }
        $approval = DB::table('approvals as a')
            ->where("a.id", $approval->id)
            ->leftJoin('users as u', function ($join) {
                $join->on('u.id', '=', 'a.responder_id');
            })
            ->join('ngo_trans as nt', function ($join) use ($locale) {
                $join->on('nt.ngo_id', '=', 'a.requester_id')
                    ->where('nt.language_name', $locale);
            })
            ->join('agreements as ag', function ($join) use ($locale) {
                $join->on('ag.ngo_id', '=', 'a.requester_id')
                    ->latest('ag.end_date');
            })
            ->join('notifier_type_trans as ntt', function ($join) use ($locale) {
                $join->on('ntt.notifier_type_id', '=', 'a.notifier_type_id')
                    ->where('ntt.language_name', $locale);
            })
            ->join('approval_documents as ad', 'ad.approval_id', '=', 'a.id')
            ->select(
                'a.id',
                'a.requester_id',
                'nt.name as requester_name',
                'a.request_date',
                'ag.start_date',
                'ag.end_date',
                "a.request_comment",
                'a.responder_id',
                'u.username as responder',
                'a.respond_date',
                "a.respond_comment",
                'a.notifier_type_id',
                'ntt.value as notifier_type',
                'ad.id as document_id',
                'ad.path',
                'ad.actual_name as name',
                'ad.type as extension',
                'ad.size',
            )
            ->get();

        $approvalsWithDocuments = $approval->groupBy('id')->map(function ($approvalGroup) {
            $approval = $approvalGroup->first();
            $documents = $approvalGroup->map(function ($item) {
                return [
                    'id' => $item->document_id,
                    'path' => $item->path,
                    'name' => $item->name,
                    'extension' => $item->extension,
                    'size' => $item->size,
                ];
            });

            $approval->approval_documents = $documents;
            unset($approval->document_id, $approval->path, $approval->name, $approval->extension, $approval->size);  // Clean up extra fields

            return $approval;
        })->values();


        if (count($approvalsWithDocuments) != 0)
            return $approvalsWithDocuments->first();

        $includes = [
            CheckListEnum::ngo_register_form_en->value,
            CheckListEnum::ngo_register_form_fa->value,
            CheckListEnum::ngo_register_form_ps->value
        ];
        return DB::table('check_lists as cl')
            ->where('cl.active', true)
            ->where('cl.check_list_type_id', CheckListTypeEnum::ngoRegister->value)
            ->whereIn('cl.id', $includes)
            ->join('check_list_trans as clt', 'clt.check_list_id', '=', 'cl.id')
            ->where('clt.language_name', $locale)
            ->select(
                'clt.value as name',
                'cl.id',
                'cl.acceptable_mimes',
                'cl.acceptable_extensions',
                'cl.description'
            )
            ->orderBy('cl.id')
            ->get();

        $authUser =  DB::table("users as u")
            ->join("user_permissions as up", function ($join) {
                $join->on("up.user_id", '=', 'u.id')
                    ->where('up.permission', PermissionEnum::approval->value);
            })
            ->select('up.user_id')
            ->get();

        foreach ($authUser as $user) {
            return dd($user->user_id);
        }
        $ngo_id = 8;
        $ids = [
            CheckListEnum::ngo_register_form_en->value,
            CheckListEnum::ngo_register_form_fa->value,
            CheckListEnum::ngo_register_form_ps->value
        ];
        $languageCodes = [
            CheckListEnum::ngo_register_form_en->value => "en",
            CheckListEnum::ngo_register_form_fa->value => "fa",
            CheckListEnum::ngo_register_form_ps->value => "ps"
        ];

        // Initialize an array to hold the results
        $result = [];

        // Iterate over the checklist_ids and check if they exist in the table
        foreach ($ids as $id) {
            // Check if the checklist_id exists in the table
            $exists = DB::table('agreements as a')
                ->where('a.ngo_id', $ngo_id)
                ->where("a.start_date", null)
                ->join('agreement_documents as ad', "ad.agreement_id", '=', "a.id")
                ->join('documents as d', function ($join) use ($id) {
                    $join->on('d.id', "=", "ad.document_id")
                        ->where('d.check_list_id', $id);
                })
                ->exists();

            // If the ID doesn't exist, return the corresponding language code
            if (!$exists) {
                $result[] = $languageCodes[$id];
            }
        }

        // Return the result, which will contain missing language codes
        return response()->json($result);
    }
}
