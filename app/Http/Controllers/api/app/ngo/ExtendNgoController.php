<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Models\Ngo;
use App\Models\Email;
use App\Models\Address;
use App\Models\Contact;
use App\Models\NgoTran;
use App\Models\Document;
use App\Models\Agreement;
use App\Models\NgoStatus;
use App\Enums\CountryEnum;
use App\Enums\LanguageEnum;
use App\Models\AddressTran;
use Illuminate\Support\Carbon;
use App\Enums\Type\TaskTypeEnum;
use App\Models\AgreementDirector;
use App\Models\AgreementDocument;
use App\Enums\Type\StatusTypeEnum;
use App\Traits\Helper\HelperTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AgreementRepresenter;
use App\Enums\CheckList\CheckListEnum;
use App\Http\Requests\app\ngo\ExtendNgoRequest;
use App\Repositories\Storage\StorageRepositoryInterface;
use App\Repositories\Task\PendingTaskRepositoryInterface;
use App\Repositories\Director\DirectorRepositoryInterface;
use App\Repositories\Representative\RepresentativeRepositoryInterface;

class ExtendNgoController extends Controller
{
    //
    use HelperTrait;
    protected $pendingTaskRepository;
    protected $notificationRepository;
    protected $approvalRepository;
    protected $directorRepository;
    protected $representativeRepository;
    protected $storageRepository;

    public function __construct(
        PendingTaskRepositoryInterface $pendingTaskRepository,
        DirectorRepositoryInterface $directorRepository,
        RepresentativeRepositoryInterface $representativeRepository,
        StorageRepositoryInterface $storageRepository
    ) {
        $this->pendingTaskRepository = $pendingTaskRepository;
        $this->directorRepository = $directorRepository;
        $this->representativeRepository = $representativeRepository;
        $this->storageRepository = $storageRepository;
    }

    public function extendNgoAgreement(ExtendNgoRequest $request)
    {
        // return $request;
        $ngo_id = $request->ngo_id;
        $request->validated();
        $authUser = $request->user();
        // Step.1
        // Email and Contact
        $ngo = Ngo::find($ngo_id);
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
        DB::beginTransaction();
        $email = Email::where('value', $request->email)
            ->select('id')->first();
        // Email Is taken by someone
        if ($email) {
            if ($email->id == $ngo->email_id) {
                $email->value = $request->email;
                $email->save();
            } else {
                return response()->json([
                    'message' => __('app_translation.email_exist'),
                ], 409, [], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $email = Email::where('id', $ngo->email_id)->first();
            $email->value = $request->email;
            $email->save();
        }
        $contact = Contact::where('value', $request->contact)
            ->select('id')->first();
        if ($contact) {
            if ($contact->id == $ngo->contact_id) {
                $contact->value = $request->contact;
                $contact->save();
            } else {
                return response()->json([
                    'message' => __('app_translation.contact_exist'),
                ], 409, [], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $contact = Contact::where('id', $ngo->contact_id)->first();
            $contact->value = $request->contact;
            $contact->save();
        }

        // Address and Ngo
        $address = Address::where('id', $ngo->address_id)
            ->select("district_id", "id", "province_id")
            ->first();
        if (!$address) {
            return response()->json([
                'message' => __('app_translation.address_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        $address->province_id = $request->province['id'];
        $address->district_id = $request->district['id'];
        // * Update Ngo information
        $ngo->abbr = $request->abbr;
        $ngo->moe_registration_no = $request->moe_registration_no;
        // * Translations
        $addressTrans = AddressTran::where('address_id', $address->id)->get();
        $ngoTrans = NgoTran::where('ngo_id', $ngo->id)->get();
        foreach (LanguageEnum::LANGUAGES as $code => $name) {
            $addressTran = $addressTrans->where('language_name', $code)->first();
            $ngoTran = $ngoTrans->where('language_name', $code)->first();
            $addressTran->update([
                'area' => $request["area_{$name}"],
            ]);
            $ngoTran->update([
                'name' => $request["name_{$name}"],
                'vision' => $request["vision_{$name}"],
                'mission' => $request["mission_{$name}"],
                'general_objective' => $request["general_objes_{$name}"],
                'objective' => $request["objes_in_afg_{$name}"],
            ]);
        }
        $ngo->save();
        $address->save();

        // Step.3 
        // Agreement
        $agreement = Agreement::create([
            'ngo_id' => $ngo->id,
            "agreement_no" => ""
        ]);
        $agreement->agreement_no = "AG" . '-' . Carbon::now()->year . '-' . $agreement->id;
        $agreement->save();
        NgoStatus::where('ngo_id', $ngo->id)->update(['is_active' => false]);
        NgoStatus::create([
            'ngo_id' => $ngo->id,
            'userable_id' => $authUser->id,
            'userable_type' => $this->getModelName(get_class($authUser)),
            "is_active" => true,
            'status_type_id' => StatusTypeEnum::register_form_completed->value,
            'comment' => 'Extend Form Complete',
        ]);

        // Step.3 Ensure task exists before proceeding
        $task = $this->pendingTaskRepository->pendingTaskExist(
            $request->user(),
            TaskTypeEnum::ngo_agreement_extend->value,
            $ngo_id
        );
        if (!$task) {
            return response()->json([
                'error' => __('app_translation.task_not_found')
            ], 404);
        }
        // step.4 Check task exists
        $exclude = [
            CheckListEnum::ngo_register_form_en->value,
            CheckListEnum::ngo_register_form_fa->value,
            CheckListEnum::ngo_register_form_ps->value,
        ];
        // If Directory Nationality is abroad ask for Work Permit
        if ($request->new_director == true) {
            if ($request->nationality['id'] == CountryEnum::afghanistan->value) {
                array_push($exclude, CheckListEnum::director_work_permit->value);
            }
        } else {
            array_push($exclude, CheckListEnum::director_nid->value);
            if ($request->country['id'] == CountryEnum::afghanistan->value) {
                array_push($exclude, CheckListEnum::director_work_permit->value);
            }
        }
        if ($request->new_represent == false) {
            array_push($exclude, CheckListEnum::ngo_representor_letter->value);
        }


        $directorDocumentsId = [];
        $representativeDocumentsId = [];
        $this->storageRepository->documentStore($agreement->id, $ngo_id, $task->id, function ($documentData) use (&$directorDocumentsId, &$representativeDocumentsId) {
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
            } else if (
                $checklist_id == CheckListEnum::ngo_representor_letter->value
            ) {
                array_push($representativeDocumentsId, $document->id);
            }

            AgreementDocument::create([
                'document_id' => $document->id,
                'agreement_id' => $documentData['agreement_id'],
            ]);
        });
        // Director with Agreement
        $director_id = null;
        if ($request->new_director == true) {
            // New director is assigned
            $director = $this->directorRepository->storeNgoDirector(
                $request,
                $ngo_id,
                $agreement->id,
                $directorDocumentsId,
                true,
                $authUser->id,
                $this->getModelName(get_class($authUser))
            );
            $director_id = $director->id;
        } else {
            $director_id = $request->prev_dire['id'];
        }
        AgreementDirector::create([
            'agreement_id' => $agreement->id,
            'director_id' => $director_id
        ]);

        // Representative with agreement
        $representer_id = null;
        if ($request->new_represent == true) {
            // New representative is assigned
            $representer = $this->representativeRepository->storeRepresentative(
                $request,
                $ngo_id,
                $agreement->id,
                $representativeDocumentsId,
                true,
                $authUser->id,
                $this->getModelName(get_class($authUser))
            );
            $representer_id = $representer->id;
        } else {
            $representer_id = $request->prev_rep['id'];
        }
        AgreementRepresenter::create([
            'agreement_id' => $agreement->id,
            'representer_id' => $representer_id
        ]);

        $this->pendingTaskRepository->destroyPendingTask(
            $authUser,
            TaskTypeEnum::ngo_agreement_extend->value,
            $ngo_id
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
}
