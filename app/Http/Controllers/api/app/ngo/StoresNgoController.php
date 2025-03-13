<?php

namespace App\Http\Controllers\api\app\ngo;

use Carbon\Carbon;
use App\Models\Ngo;
use App\Models\User;
use App\Models\Email;
use App\Enums\RoleEnum;
use App\Models\Address;
use App\Models\Contact;
use App\Models\NgoTran;
use App\Models\Setting;
use App\Models\Director;
use App\Models\Document;
use App\Models\Agreement;
use App\Models\CheckList;
use App\Models\NgoStatus;
use App\Enums\CountryEnum;
use App\Enums\SettingEnum;
use App\Enums\LanguageEnum;
use App\Enums\NotifierEnum;
use App\Models\AddressTran;
use App\Models\PendingTask;
use App\Models\Representer;
use App\Models\DirectorTran;
use App\Enums\PermissionEnum;
use App\Models\NgoPermission;
use App\Models\CheckListTrans;
use App\Models\StatusTypeTran;
use App\Models\RepresenterTran;
use App\Enums\CheckListTypeEnum;
use App\Enums\Type\TaskTypeEnum;
use App\Models\AgreementDirector;
use App\Models\AgreementDocument;
use App\Enums\Type\StatusTypeEnum;
use App\Traits\Helper\HelperTrait;
use Illuminate\Support\Facades\DB;
use App\Models\PendingTaskDocument;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Traits\File\PendingFileTrait;
use App\Enums\CheckList\CheckListEnum;
use Database\Factories\ApprovalFactory;
use App\Http\Requests\app\ngo\NgoRegisterRequest;
use App\Http\Requests\app\ngo\NgoInitStoreRequest;
use App\Repositories\Task\PendingTaskRepositoryInterface;
use App\Repositories\Approval\ApprovalRepositoryInterface;
use App\Repositories\Director\DirectorRepositoryInterface;
use App\Http\Requests\app\ngo\StoreSignedRegisterFormRequest;
use App\Repositories\Notification\NotificationRepositoryInterface;

class StoresNgoController extends Controller
{
    use HelperTrait, PendingFileTrait;
    protected $pendingTaskRepository;
    protected $notificationRepository;
    protected $approvalRepository;
    protected $directorRepository;

    public function __construct(
        PendingTaskRepositoryInterface $pendingTaskRepository,
        NotificationRepositoryInterface $notificationRepository,
        ApprovalRepositoryInterface $approvalRepository,
        DirectorRepositoryInterface $directorRepository
    ) {
        $this->pendingTaskRepository = $pendingTaskRepository;
        $this->notificationRepository = $notificationRepository;
        $this->approvalRepository = $approvalRepository;
        $this->directorRepository = $directorRepository;
    }
    public function store(NgoRegisterRequest $request)
    {
        $validatedData = $request->validated();
        $authUser = $request->user();
        $locale = App::getLocale();
        // Create email
        $email = Email::where('value', '=', $validatedData['email'])->first();
        if ($email) {
            return response()->json([
                'message' => __('app_translation.email_exist'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }
        $contact = Contact::where('value', '=', $validatedData['contact'])->first();
        if ($contact) {
            return response()->json([
                'message' => __('app_translation.contact_exist'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }
        // Begin transaction
        DB::beginTransaction();
        $email = Email::create(['value' => $validatedData['email']]);
        $contact = Contact::create(['value' => $validatedData['contact']]);
        // Create address
        $address = Address::create([
            'district_id' => $validatedData['district_id'],
            'province_id' => $validatedData['province_id'],
        ]);

        // * Translations
        foreach (LanguageEnum::LANGUAGES as $code => $name) {
            AddressTran::create([
                'address_id' => $address->id,
                'area' => $validatedData["area_{$name}"],
                'language_name' =>  $code,
            ]);
        }
        // Create NGO
        $newNgo = Ngo::create([
            "user_id" => $authUser->id,
            'abbr' => $validatedData['abbr'],
            'registration_no' => "",
            'role_id' => RoleEnum::ngo->value,
            'ngo_type_id' => $validatedData['ngo_type_id'],
            'address_id' => $address->id,
            'email_id' => $email->id,
            'username' => $request->username,
            'contact_id' => $contact->id,
            "password" => Hash::make($validatedData['password']),
        ]);

        // Crea a registration_no
        $newNgo->registration_no = "IRD" . '-' . Carbon::now()->year . '-' . $newNgo->id;
        $newNgo->save();
        // Set ngo status
        NgoStatus::create([
            "ngo_id" => $newNgo->id,
            "user_id" => $authUser->id,
            "is_active" => true,
            "status_type_id" => StatusTypeEnum::register_form_not_completed->value,
            "comment" => "Newly Created"
        ]);

        // **Fix agreement creation**
        $agreement = Agreement::create([
            'ngo_id' => $newNgo->id,
            "agreement_no" => ""
        ]);
        $agreement->agreement_no = "AG" . '-' . Carbon::now()->year . '-' . $agreement->id;
        $agreement->save();

        $result =  $this->singleChecklistDBDocStore(
            $request->pending_id,
            $agreement->id,
            $newNgo->id
        );
        if ($result['success'] == false) {
            return $result['error'];
        }
        AgreementDocument::create([
            'document_id' => $result['document']->id,
            'agreement_id' => $agreement->id,
        ]);
        $representer = Representer::create([
            'user_id' => $request->user()->id,
            'is_active' => true,
            "document_id" => $result['document']->id,
            "ngo_id" => $newNgo->id,
        ]);
        $agreement->representer_id = $representer->id;
        $agreement->save();
        foreach (LanguageEnum::LANGUAGES as $code => $name) {
            RepresenterTran::create([
                'representer_id' => $representer->id,
                'language_name' =>  $code,
                'full_name' => $request["full_name_{$name}"],
            ]);
        }
        foreach (LanguageEnum::LANGUAGES as $code => $name) {
            NgoTran::create([
                'ngo_id' => $newNgo->id,
                'language_name' => $code,
                'name' => $validatedData["name_{$name}"],
            ]);
        }
        // Create permissions
        $this->ngoPermissions($newNgo->id);

        $name =  $validatedData['name_english'];
        if ($locale == LanguageEnum::farsi->value) {
            $name = $validatedData['name_farsi'];
        } else if ($locale == LanguageEnum::pashto->value) {
            $name = $validatedData['name_pashto'];
        }

        // If everything goes well, commit the transaction
        DB::commit();

        $status = StatusTypeTran::where('status_type_id', StatusTypeEnum::register_form_not_completed->value)
            ->where('language_name', $locale)
            ->select('name')->first();
        return response()->json(
            [
                'message' => __('app_translation.success'),
                "ngo" => [
                    "id" => $newNgo->id,
                    "profile" => $newNgo->profile,
                    "abbr" => $newNgo->abbr,
                    "registration_no" => $newNgo->registration_no,
                    "status_id" => StatusTypeEnum::register_form_not_completed->value,
                    "status" => $status->name,
                    "type_id" => $validatedData['ngo_type_id'],
                    "establishment_date" => null,
                    "name" => $name,
                    "contact" => $validatedData['contact'],
                    "email" => $validatedData['email'],
                    "created_at" => $newNgo->created_at,
                ]
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function registerFormCompleted(NgoInitStoreRequest $request)
    {
        // return $request;
        $id = $request->ngo_id;
        $validatedData = $request->validated();

        $agreement = Agreement::where('ngo_id', $id)
            ->where('end_date', null) // Order by end_date descending
            ->first();           // Get the first record (most recent)

        // 1. If agreement does not exists no further process.
        if (!$agreement) {
            return response()->json([
                'message' => __('app_translation.agreement_not_exists')
            ], 409);
        }

        // 2. CheckListEnum:: NGO exist
        $ngo = Ngo::find($id);
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // 3. Ensure task exists before proceeding
        $task = $this->pendingTaskRepository->pendingTaskExist(
            $request->user(),
            TaskTypeEnum::ngo_registeration,
            $id
        );
        if (!$task) {
            return response()->json([
                'message' => __('app_translation.task_not_found'),
                '$request->pending_id' => $request->pending_id
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        $exclude = [
            CheckListEnum::ngo_representor_letter->value,
            CheckListEnum::ngo_register_form_en->value,
            CheckListEnum::ngo_register_form_fa->value,
            CheckListEnum::ngo_register_form_ps->value,
        ];
        // If Directory Nationality is abroad ask for Work Permit
        if ($validatedData["nationality"]["id"] == CountryEnum::afghanistan->value) {
            array_push($exclude, CheckListEnum::director_work_permit->value);
        }
        // 4. CheckListEnum:: task exists
        // Get checklist IDs
        $checkListIds = CheckList::where('check_list_type_id', CheckListTypeEnum::ngoRegister)
            ->whereNotIn('id', $exclude)
            ->pluck('id')
            ->toArray();
        $errors = $this->validateCheckList($task, $checkListIds);
        if ($errors) {
            return response()->json([
                'message' => __('app_translation.checklist_not_found'),
                'errors' => $errors // Reset keys for cleaner JSON output
            ], 400);
        }


        Email::where('id', $ngo->email_id)->update(['value' => $validatedData['email']]);
        Contact::where('id', $ngo->contact_id)->update(['value' => $validatedData['contact']]);

        $ngo_en_tran = NgoTran::where('ngo_id', $id)->where('language_name', 'en')->first();
        $ngo_ps_tran = NgoTran::where('ngo_id', $id)->where('language_name', 'ps')->first();
        $ngo_fa_tran = NgoTran::where('ngo_id', $id)->where('language_name', 'fa')->first();
        $ngo_addres = Address::find($ngo->address_id);
        $ngo_addres_en = AddressTran::where('address_id', $ngo->address_id)->where('language_name', 'en')->first();
        $ngo_addres_ps = AddressTran::where('address_id', $ngo->address_id)->where('language_name', 'ps')->first();
        $ngo_addres_fa = AddressTran::where('address_id', $ngo->address_id)->where('language_name', 'fa')->first();

        $ngo_en_tran->name  =    $validatedData["name_english"];
        $ngo_ps_tran->name  =    $validatedData["name_pashto"];
        $ngo_fa_tran->name  =    $validatedData["name_farsi"];
        $ngo->abbr =  $validatedData["abbr"];
        $ngo->ngo_type_id  = $validatedData['type']['id'];
        // $ngo->ngo_type_id  = $validatedData["type.id"];
        $ngo->moe_registration_no  = $validatedData["moe_registration_no"];
        $ngo->place_of_establishment   = $validatedData["country"]["id"];
        $ngo->date_of_establishment  = $validatedData["establishment_date"];
        $ngo_addres->province_id  = $validatedData["province"]["id"];
        $ngo_addres->district_id  = $validatedData["district"]["id"];
        $ngo_addres_en->area = $validatedData["area_english"];
        $ngo_addres_ps->area = $validatedData["area_pashto"];
        $ngo_addres_fa->area = $validatedData["area_farsi"];

        $ngo_en_tran->vision  =    $validatedData["vision_english"];
        $ngo_ps_tran->vision  =    $validatedData["vision_pashto"];
        $ngo_fa_tran->vision  =    $validatedData["vision_farsi"];
        $ngo_en_tran->mission  =    $validatedData["mission_english"];
        $ngo_ps_tran->mission  =    $validatedData["mission_pashto"];
        $ngo_fa_tran->mission  =    $validatedData["mission_farsi"];
        $ngo_en_tran->general_objective  =    $validatedData["general_objes_english"];
        $ngo_ps_tran->general_objective  =    $validatedData["general_objes_pashto"];
        $ngo_fa_tran->general_objective  =    $validatedData["general_objes_farsi"];
        $ngo_en_tran->objective  =    $validatedData["objes_in_afg_english"];
        $ngo_ps_tran->objective  =    $validatedData["objes_in_afg_pashto"];
        $ngo_fa_tran->objective  =    $validatedData["objes_in_afg_farsi"];

        DB::beginTransaction();

        $ngo_en_tran->save();
        $ngo_ps_tran->save();
        $ngo_fa_tran->save();
        $ngo_addres->save();
        $ngo_addres_en->save();
        $ngo_addres_ps->save();
        $ngo_addres_fa->save();
        $ngo->save();

        // Make prevous state to false
        NgoStatus::where('ngo_id', $id)->update(['is_active' => false]);
        NgoStatus::create([
            'ngo_id' => $id,
            'user_id' => $request->user()->id,
            "is_active" => true,
            'status_type_id' => StatusTypeEnum::register_form_completed,
            'comment' => 'Register Form Complete',
        ]);

        $directorDocumentsId = [];
        $document =  $this->documentStore($agreement->id, $id, $task->id, function ($documentData) use ($directorDocumentsId) {
            $checklist_id = $documentData['check_list_id'];
            $document = Document::create([
                'actual_name' => $documentData['actual_name'],
                'size' => $documentData['size'],
                'path' => $documentData['path'],
                'type' => $documentData['type'],
                'check_list_id' => $checklist_id,
            ]);
            if (
                $checklist_id == CheckListEnum::director_work_permit->value
                || $checklist_id == CheckListEnum::director_nid->value
            ) {
                array_push($directorDocumentsId, $document->id);
            }

            AgreementDocument::create([
                'document_id' => $document->id,
                'agreement_id' => $documentData['agreement_id'],
            ]);
        });
        if ($document) {
            return $document;
        }
        $director = $this->directorRepository->storeNgoDirector(
            $validatedData,
            $id,
            $agreement->id,
            $directorDocumentsId,
            true
        );
        AgreementDirector::create([
            'agreement_id' => $agreement->id,
            'director_id' => $director->id
        ]);
        $this->pendingTaskRepository->destroyPendingTask(
            $request->user(),
            TaskTypeEnum::ngo_registeration,
            $id
        );


        DB::commit();
        return response()->json(
            [
                'message' => __('app_translation.success'),
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function StoreSignedRegisterForm(StoreSignedRegisterFormRequest $request)
    {
        $request->validated();
        $ngo_id = $request->ngo_id;

        // 1. Validate date
        $expirationDate = Setting::where('id', SettingEnum::registeration_expire_time->value)
            ->select('id', 'value as days')
            ->first();
        if (!$expirationDate) {
            return response()->json(
                [
                    'message' => __('app_translation.setting_record_not_found'),
                ],
                404,
                [],
                JSON_UNESCAPED_UNICODE
            );
        } else {
            $start_date = Carbon::parse($request->start_date);
            $end_date = Carbon::parse($request->end_date);
            $gapInDays = ceil($start_date->diffInDays($end_date));
            if ($gapInDays < $expirationDate->days) {
                return response()->json(
                    [
                        'message' => __('app_translation.date_is_smaller') . " " . ($expirationDate->days / 365) . ' ' . __('app_translation.year') . ' => ' . $gapInDays,
                    ],
                    500,
                    [],
                    JSON_UNESCAPED_UNICODE
                );
            } else if ($gapInDays > $expirationDate->days) {
                return response()->json(
                    [
                        'message' => __('app_translation.date_is_bigger') . " " . ($expirationDate->days / 365) . ' ' . __('app_translation.year') . ' => ' . $gapInDays,
                    ],
                    500,
                    [],
                    JSON_UNESCAPED_UNICODE
                );
            }
        }

        $agreement = Agreement::where('ngo_id', $ngo_id)
            ->where('end_date', null) // Order by end_date descending
            ->first();           // Get the first record (most recent)
        if (!$agreement) {
            return response()->json(
                [
                    'message' => __('app_translation.doc_already_submitted'),
                ],
                500,
                [],
                JSON_UNESCAPED_UNICODE
            );
        }

        // 2. CheckListEnum:: NGO exist
        $ngo = Ngo::find($ngo_id);
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // 3. Ensure task exists before proceeding
        $task = $this->pendingTaskRepository->pendingTaskExist(
            $request->user(),
            TaskTypeEnum::ngo_registeration,
            $ngo_id
        );
        if (!$task) {
            return response()->json([
                'message' => __('app_translation.task_not_found'),
                'pending_id' => $request->pending_id
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        $includes = [
            CheckListEnum::ngo_register_form_en->value,
            CheckListEnum::ngo_register_form_fa->value,
            CheckListEnum::ngo_register_form_ps->value
        ];
        // 4. CheckListEnum:: task exists
        // Get checklist IDs
        $checkListIds = CheckList::where('check_list_type_id', CheckListTypeEnum::ngoRegister)
            ->whereIn('id', $includes)
            ->pluck('id')
            ->toArray();
        $errors = $this->validateCheckList($task, $checkListIds);
        if ($errors) {
            return response()->json([
                'message' => __('app_translation.checklist_not_found'),
                'errors' => $errors // Reset keys for cleaner JSON output
            ], 404);
        }

        DB::beginTransaction();
        $approval = $this->approvalRepository->storeApproval(
            $ngo_id,
            Ngo::class,
            NotifierEnum::ngo_submitted_register_form->value,
            $request->request_comment
        );
        $document = $this->documentStore($agreement->id, $ngo_id, $task->id, function ($documentData) use ($approval) {
            $this->approvalRepository->storeApprovalDocument(
                $approval->id,
                $documentData
            );
        });
        if ($document) {
            return $document;
        }

        $this->pendingTaskRepository->destroyPendingTask(
            $request->user(),
            TaskTypeEnum::ngo_registeration,
            $ngo_id
        );

        // 7. Create a notification
        $this->notificationRepository->SendNotification($request, [
            "userable_type" => User::class,
            "notifier_type_id" => NotifierEnum::ngo_submitted_register_form->value,
            "message" => ""
        ]);
        $agreement->start_date = $request->start_date;
        $agreement->end_date = $request->end_date;
        $agreement->save();
        // Update ngo status
        NgoStatus::where('ngo_id', $ngo_id)->update(['is_active' => false]);
        NgoStatus::create([
            'ngo_id' => $ngo_id,
            'user_id' => $request->user()->id,
            "is_active" => true,
            'status_type_id' => StatusTypeEnum::signed_register_form_submitted->value,
            'comment' => 'Signed Register Form Submitted',
        ]);
        DB::commit();
        return response()->json(
            [
                'message' => __('app_translation.success'),
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    protected function validateCheckList($task, $checkListIds)
    {
        // Get checklist IDs from documents
        $documentCheckListIds = $this->pendingTaskRepository->pendingTaskDocumentQuery(
            $task->id
        )->pluck('check_list_id')
            ->toArray();

        // Find missing checklist IDs
        $missingCheckListIds = array_diff($checkListIds, $documentCheckListIds);

        if (count($missingCheckListIds) > 0) {
            // Retrieve missing checklist names
            $missingCheckListNames = CheckListTrans::whereIn('check_list_id', $missingCheckListIds)
                ->where('language_name', app()->getLocale()) // If multilingual, get current language
                ->pluck('value');


            $errors = [];
            foreach ($missingCheckListNames as $item) {
                array_push($errors, [__('app_translation.checklist_not_found') . ' ' . $item]);
            }

            return $errors;
        }

        return null;
    }
    protected function documentStore($agreement_id, $ngo_id, $pending_task_id, ?callable $callback)
    {
        // Get checklist IDs
        $documents = PendingTaskDocument::join('check_lists', 'check_lists.id', 'pending_task_documents.check_list_id')
            ->where('pending_task_id', $pending_task_id)
            ->select('size', 'path', 'acceptable_mimes', 'check_list_id', 'actual_name', 'extension')
            ->get();

        foreach ($documents as $checklist) {
            $baseName = basename($checklist['path']);
            $oldPath = $this->getTempFullPath() . $baseName; // Absolute path of temp file

            $newDirectory = $this->ngoRegisterFolder($ngo_id, $agreement_id, $checklist['check_list_id']);

            if (!is_dir($newDirectory)) {
                mkdir($newDirectory, 0775, true);
            }
            $newPath = $newDirectory . $baseName; // Keep original filename
            $dbStorePath = $this->ngoRegisterDBPath($ngo_id, $agreement_id, $checklist['check_list_id'], $baseName);
            // Move the file
            if (file_exists($oldPath)) {
                rename($oldPath, $newPath);
            } else {
                return response()->json([
                    'error' => __('app_translation.file_not_found'),
                    "file" => $checklist['actual_name']
                ], 404);
            }

            $documentData = [
                'actual_name' => $checklist['actual_name'],
                'size' => $checklist['size'],
                'path' => $dbStorePath,
                'type' => $checklist['extension'],
                'check_list_id' => $checklist['check_list_id'],
                'agreement_id' => $agreement_id
            ];
            if ($callback) {
                $callback($documentData);
            }
        }
    }
    public function ngoPermissions($ngo_id)
    {
        NgoPermission::create([
            "view" => true,
            "edit" => true,
            "delete" => true,
            "add" => true,
            "ngo_id" => $ngo_id,
            "permission" => PermissionEnum::dashboard->value,
        ]);

        NgoPermission::create([
            "visible" => false,
            "view" => true,
            "edit" => true,
            "delete" => true,
            "add" => true,
            "ngo_id" => $ngo_id,
            "permission" => PermissionEnum::ngo->value,
        ]);
    }
}
