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
use App\Models\Document;
use App\Models\Agreement;
use App\Models\NgoStatus;
use App\Enums\CountryEnum;
use App\Enums\SettingEnum;
use App\Enums\LanguageEnum;
use App\Enums\NotifierEnum;
use App\Models\AddressTran;
use App\Models\StatusTrans;
use App\Models\AgreementStatus;
use App\Enums\Status\StatusEnum;
use App\Enums\Type\TaskTypeEnum;
use App\Models\AgreementDirector;
use App\Models\AgreementDocument;
use App\Traits\Helper\HelperTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Enums\CheckList\CheckListEnum;
use App\Enums\CheckListTypeEnum;
use App\Http\Requests\app\ngo\NgoRegisterRequest;
use App\Http\Requests\app\ngo\NgoInitStoreRequest;
use App\Repositories\Storage\StorageRepositoryInterface;
use App\Repositories\Approval\ApprovalRepositoryInterface;
use App\Repositories\Director\DirectorRepositoryInterface;
use App\Http\Requests\app\ngo\StoreSignedRegisterFormRequest;
use App\Models\CheckList;
use App\Models\CheckListTrans;
use App\Repositories\PendingTask\PendingTaskRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\Representative\RepresentativeRepositoryInterface;

class StoresNgoController extends Controller
{
    use HelperTrait;
    protected $pendingTaskRepository;
    protected $notificationRepository;
    protected $approvalRepository;
    protected $directorRepository;
    protected $representativeRepository;
    protected $storageRepository;

    public function __construct(
        PendingTaskRepositoryInterface $pendingTaskRepository,
        NotificationRepositoryInterface $notificationRepository,
        ApprovalRepositoryInterface $approvalRepository,
        DirectorRepositoryInterface $directorRepository,
        RepresentativeRepositoryInterface $representativeRepository,
        StorageRepositoryInterface $storageRepository
    ) {
        $this->pendingTaskRepository = $pendingTaskRepository;
        $this->notificationRepository = $notificationRepository;
        $this->approvalRepository = $approvalRepository;
        $this->directorRepository = $directorRepository;
        $this->representativeRepository = $representativeRepository;
        $this->storageRepository = $storageRepository;
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
            'userable_id' => $authUser->id,
            'userable_type' => $this->getModelName(get_class($authUser)),
            "is_active" => true,
            "status_id" => StatusEnum::active->value,
            "comment" => "Intial"
        ]);

        // **Fix agreement creation**
        $agreement = Agreement::create([
            'ngo_id' => $newNgo->id,
            "agreement_no" => ""
        ]);
        $agreement->agreement_no = "AG" . '-' . Carbon::now()->year . '-' . $agreement->id;
        $agreement->save();

        $task = $this->pendingTaskRepository->pendingTaskExist(
            $request->user(),
            TaskTypeEnum::ngo_registeration->value,
            null
        );
        if (!$task) {
            return response()->json([
                'message' => __('app_translation.task_not_found')
            ], 404);
        }
        $documentsId = [];
        $this->storageRepository->documentStore($agreement->id, $newNgo->id, $task->id, function ($documentData) use (&$documentsId) {
            $checklist_id = $documentData['check_list_id'];
            $document = Document::create([
                'actual_name' => $documentData['actual_name'],
                'size' => $documentData['size'],
                'path' => $documentData['path'],
                'type' => $documentData['type'],
                'check_list_id' => $checklist_id,
            ]);
            array_push($documentsId, $document->id);
            AgreementDocument::create([
                'document_id' => $document->id,
                'agreement_id' => $documentData['agreement_id'],
            ]);
        });

        // Representative with agreement
        $this->representativeRepository->storeRepresentative(
            $request,
            $newNgo->id,
            $agreement->id,
            $documentsId,
            true,
            $authUser->id,
            $this->getModelName(get_class($authUser))
        );

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

        $this->pendingTaskRepository->destroyPendingTask(
            $authUser,
            TaskTypeEnum::ngo_registeration->value,
            null
        );
        // If everything goes well, commit the transaction
        $agreementStatus = AgreementStatus::create([
            'agreement_id' => $agreement->id,
            'userable_id' => $authUser->id,
            'userable_type' => $this->getModelName(get_class($authUser)),
            "is_active" => true,
            'status_id' => StatusEnum::registration_incomplete->value,
        ]);

        $this->storeNgoAgreementWithTrans($agreementStatus->id, [
            'pashto' => 'د موسسه یوزر په بریالیتوب سره جوړ شو.',
            'farsi' => 'کاربر موسسه با موفقیت ایجاد شد.',
            'english' => 'Ngo user created successfully.'
        ]);
        DB::commit();

        $status = StatusTrans::where('status_id', StatusEnum::registration_incomplete->value)
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
                    "status_id" => StatusEnum::registration_incomplete->value,
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
        $authUser = $request->user();

        $agreement = Agreement::where('ngo_id', $id)
            ->where('end_date', null) // Order by end_date descending
            ->first();           // Get the first record (most recent)

        // 1. If agreement does not exists no further process.
        if (!$agreement) {
            return response()->json([
                'message' => __('app_translation.agreement_not_exists')
            ], 409);
        }

        $agreementStatus = AgreementStatus::where('agreement_id', $agreement->id)
            ->where('is_active', true)
            ->first();
        // 2. Allow If agreement is in 
        if ($agreementStatus && $agreementStatus->status_id != StatusEnum::registration_incomplete->value) {
            return response()->json([
                'message' => __('app_translation.register_form_alre_submi'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
        // 3. CheckListEnum:: NGO exist
        $ngo = Ngo::find($id);
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // 4. Ensure task exists before proceeding
        $task = $this->pendingTaskRepository->pendingTaskExist(
            $authUser,
            TaskTypeEnum::ngo_registeration->value,
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

        $checlklistValidat = $this->validateCheckList($task, $exclude, CheckListTypeEnum::ngo_registeration);
        if ($checlklistValidat) {
            return response()->json([
                'errors' => $checlklistValidat,
                'message' => __('app_translation.ngo_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        DB::beginTransaction();
        $email = Email::where('value', $validatedData['email'])
            ->select('id')->first();
        // Email Is taken by someone
        if ($email) {
            if ($email->id == $ngo->email_id) {
                $email->value = $validatedData['email'];
                $email->save();
            } else {
                return response()->json([
                    'message' => __('app_translation.email_exist'),
                ], 409, [], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $email = Email::where('id', $ngo->email_id)->first();
            $email->value = $validatedData['email'];
            $email->save();
        }
        $contact = Contact::where('value', $validatedData['contact'])
            ->select('id')->first();
        if ($contact) {
            if ($contact->id == $ngo->contact_id) {
                $contact->value = $validatedData['contact'];
                $contact->save();
            } else {
                return response()->json([
                    'message' => __('app_translation.contact_exist'),
                ], 409, [], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $contact = Contact::where('id', $ngo->contact_id)->first();
            $contact->value = $validatedData['contact'];
            $contact->save();
        }

        // store ngo transalation
        $ngoTrans = NgoTran::where('ngo_id', $id)->get();
        if (!$ngoTrans) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        foreach (LanguageEnum::LANGUAGES as $code => $name) {
            $tran =  $ngoTrans->where('language_name', $code)->first();
            $tran->name = $validatedData["name_{$name}"];
            $tran->vision = $validatedData["vision_{$name}"];
            $tran->mission = $validatedData["mission_{$name}"];
            $tran->general_objective = $validatedData["general_objes_{$name}"];
            $tran->objective = $validatedData["objes_in_afg_{$name}"];
            $tran->save();
        }

        // store ngo Address
        $ngo_addres = Address::find($ngo->address_id);

        $ngo_addres->province_id  = $validatedData["province"]["id"];
        $ngo_addres->district_id  = $validatedData["district"]["id"];
        $ngo_addres_trans = AddressTran::where('address_id', $ngo->address_id)->get();

        foreach (LanguageEnum::LANGUAGES as $code => $name) {
            $tran =  $ngo_addres_trans->where('language_name', $code)->first();
            $tran->area = $validatedData["area_{$name}"];
            $tran->save();
        }

        $ngo->abbr =  $validatedData["abbr"];
        $ngo->ngo_type_id  = $validatedData['type']['id'];
        // $ngo->ngo_type_id  = $validatedData["type.id"];
        $ngo->moe_registration_no  = $validatedData["moe_registration_no"];
        $ngo->place_of_establishment   = $validatedData["country"]["id"];
        $ngo->date_of_establishment  = $validatedData["establishment_date"];
        $ngo_addres->save();
        $ngo->save();

        // Make prevous state to false
        AgreementStatus::where('agreement_id', $agreement->id,)->update(['is_active' => false]);
        $agreementStatus = AgreementStatus::create([
            'agreement_id' => $agreement->id,
            'userable_id' => $authUser->id,
            'userable_type' => $this->getModelName(get_class($authUser)),
            "is_active" => true,
            'status_id' => StatusEnum::document_upload_required->value,
        ]);

        $this->storeNgoAgreementWithTrans($agreementStatus->id, [
            'pashto' => 'موږ د اسنادو د پورته کولو په تمه یو.',
            'farsi' => 'منتظر آپلود مدارک هستیم.',
            'english' => 'Waiting for document upload.'
        ]);

        $directorDocumentsId = [];
        $document =  $this->storageRepository->documentStore($agreement->id, $id, $task->id, function ($documentData) use (&$directorDocumentsId) {
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
            true,
            $authUser->id,
            $this->getModelName(get_class($authUser))
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
        $authUser = $request->user();

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
        }
        $start_date = Carbon::parse($request->start_date);

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
            $authUser,
            TaskTypeEnum::ngo_registeration,
            $ngo_id
        );
        if (!$task) {
            return response()->json([
                'message' => __('app_translation.task_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        DB::beginTransaction();
        $approval = $this->approvalRepository->storeApproval(
            $ngo_id,
            Ngo::class,
            NotifierEnum::ngo_submitted_register_form->value,
            $request->request_comment
        );
        $document = $this->storageRepository->documentStore($agreement->id, $ngo_id, $task->id, function ($documentData) use (&$approval) {
            $this->approvalRepository->storeApprovalDocument(
                $approval->id,
                $documentData
            );
        });
        if ($document) {
            return $document;
        }

        $this->pendingTaskRepository->destroyPendingTask(
            $authUser,
            TaskTypeEnum::ngo_registeration->value,
            $ngo_id
        );

        // 7. Create a notification
        $this->notificationRepository->SendNotification($request, [
            "userable_type" => User::class,
            "notifier_type_id" => NotifierEnum::ngo_submitted_register_form->value,
            "message" => ""
        ]);
        $agreement->start_date = $start_date;
        $agreement->save();
        // Update ngo status
        AgreementStatus::where('agreement_id', $agreement->id,)->update(['is_active' => false]);
        $agreementStatus = AgreementStatus::create([
            'agreement_id' => $agreement->id,
            'userable_id' => $authUser->id,
            'userable_type' => $this->getModelName(get_class($authUser)),
            "is_active" => true,
            'status_id' => StatusEnum::pending_approval->value,
        ]);

        $this->storeNgoAgreementWithTrans($agreementStatus->id, [
            'pashto' => 'سند د تصویب لپاره په تمه دی.',
            'farsi' => 'سند در انتظار تأیید است.',
            'english' => 'Document is pending for approval.'
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
    // protected function validateCheckList($task)
    // {

    //     // Get checklist IDs
    //     $checkListIds = CheckList::where('check_list_type_id', CheckListTypeEnum::ngoRegister)
    //         ->pluck('id')
    //         ->toArray();

    //     // Get checklist IDs from documents
    //     $documentCheckListIds = $this->pendingTaskRepository->pendingTaskDocumentQuery(
    //         $task->id
    //     )->pluck('check_list_id')
    //         ->toArray();

    //     // Find missing checklist IDs
    //     $missingCheckListIds = array_diff($checkListIds, $documentCheckListIds);

    //     if (count($missingCheckListIds) > 0) {
    //         // Retrieve missing checklist names
    //         $missingCheckListNames = CheckListTrans::whereIn('check_list_id', $missingCheckListIds)
    //             ->where('language_name', app()->getLocale()) // If multilingual, get current language
    //             ->pluck('value');


    //         $errors = [];
    //         foreach ($missingCheckListNames as $item) {
    //             array_push($errors, [__('app_translation.checklist_not_found') . ' ' . $item]);
    //         }

    //         return $errors;
    //     }

    //     return null;
    // }

}
