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
        $locale = App::getLocale();

        $now = Carbon::now('UTC');
        return $now;

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
