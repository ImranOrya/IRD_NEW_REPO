<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Enums\CheckListTypeEnum;
use App\Enums\Type\StatusTypeEnum;
use App\Enums\Type\TaskTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\app\ngo\ExtendNgoRequest;
use App\Models\Address;
use App\Models\AddressTran;
use App\Models\Agreement;
use App\Models\AgreementDirector;
use App\Models\AgreementDocument;
use App\Models\CheckList;
use App\Models\CheckListTrans;
use App\Models\Contact;
use App\Models\Director;
use App\Models\DirectorTran;
use App\Models\Document;
use App\Models\Email;
use App\Models\Ngo;
use App\Models\NgoStatus;
use App\Models\NgoTran;
use App\Models\PendingTask;
use App\Models\PendingTaskDocument;
use App\Repositories\Task\PendingTaskRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExtendNgoController extends Controller
{
    //
    protected $pendingTaskRepository;

    public function __construct(PendingTaskRepositoryInterface $pendingTaskRepository)
    {
        $this->pendingTaskRepository = $pendingTaskRepository;
    }

    public function extendNgoAgreement(ExtendNgoRequest $request)
    {
        // return $request;
        $id = $request->ngo_id;
        $validatedData =  $request->validated();

        $agreement = Agreement::where('ngo_id', $id)
            ->latest('end_date') // Order by end_date descending
            ->first();           // Get the first record (most recent)

        // 1. If agreement exists do not allow process furtherly.
        if ($agreement->end_date >= now()) {
            return response()->json([
                'message' => __('app_translation.agreement_exists')
            ], 409);
        }

        // 2. Check NGO exist
        $ngo = Ngo::find($id);
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // 3. Ensure task exists before proceeding
        $task = $this->pendingTaskRepository->pendingTaskExist(
            $request->user(),
            TaskTypeEnum::ngo_agreement_extend,
            $id
        );
        if (!$task) {
            return response()->json([
                'error' => __('app_translation.task_not_found')
            ], 404);
        }
        // 4. Check task exists
        $errors = $this->validateCheckList($task);
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

        // **Fix agreement creation**
        $agreement = Agreement::create([
            'ngo_id' => $id,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addYear()->toDateString(),
        ]);
        $agreement->agreement_no = "AG" . '-' . Carbon::now()->year . '-' . $agreement->id;
        $agreement->save();

        // Make prevous state to false
        NgoStatus::where('ngo_id', $id)->update(['is_active' => false]);
        NgoStatus::create([
            'ngo_id' => $id,
            "is_active" => true,
            'status_type_id' => StatusTypeEnum::register_form_submited,
            'comment' => 'Register Form Complete',
        ]);

        $document =  $this->documentStore($request, $agreement->id, $id, $validatedData["name_english"]);
        if ($document) {
            return $document;
        }
        $this->directorStore($validatedData, $id, $agreement->id);

        $this->pendingTaskRepository->deletePendingTask(
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

    protected function validateCheckList($task)
    {
        // Get checklist IDs
        $checkListIds = CheckList::where('check_list_type_id', CheckListTypeEnum::externel)
            ->pluck('id')
            ->toArray();

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
    protected function documentStore($request, $agreement_id, $ngo_id, $ngo_name)
    {
        $user = $request->user();
        $user_id = $user->id;
        $role = $user->role_id;
        $task_type = TaskTypeEnum::ngo_registeration;

        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $ngo_id)
            ->first();


        if (!$task) {
            return response()->json(['error' => 'No pending task found'], 404);
        }
        // Get checklist IDs

        $documents = PendingTaskDocument::join('check_lists', 'check_lists.id', 'pending_task_documents.check_list_id')
            ->select('size', 'path', 'acceptable_mimes', 'check_list_id', 'actual_name', 'extension')
            ->where('pending_task_id', $task->id)
            ->get();

        foreach ($documents as $checklist) {

            $oldPath = storage_path("app/" . $checklist['path']); // Absolute path of temp file

            $newDirectory = storage_path() . "/app/private/ngos/{$ngo_name}/{$agreement_id}/";

            if (!file_exists($newDirectory)) {
                mkdir($newDirectory, 0775, true);
            }

            $newPath = $newDirectory . basename($checklist['path']); // Keep original filename

            $dbStorePath = "private/ngos/{$ngo_name}/{$agreement_id}/"
                . basename($checklist['path']);
            // Ensure the new directory exists

            // Move the file
            if (file_exists($oldPath)) {
                rename($oldPath, $newPath);
            } else {
                return response()->json(['error' => __('app_translation.not_found') . $oldPath], 404);
            }


            $document = Document::create([
                'actual_name' => $checklist['actual_name'],
                'size' => $checklist['size'],
                'path' => $dbStorePath,
                'type' => $checklist['extension'],
                'check_list_id' => $checklist['check_list_id'],
            ]);

            // **Fix whitespace issue in keys**
            AgreementDocument::create([
                'document_id' => $document->id,
                'agreement_id' => $agreement_id,
            ]);
        }
    }
    protected function directorStore($validatedData, $ngo_id, $agreement_id)
    {
        $email = Email::create(['value' => $validatedData['director_email']]);
        $contact = Contact::create(['value' => $validatedData['director_contact']]);

        // **Fix address creation**
        $address = Address::create([
            'province_id' => $validatedData['director_province']['id'],
            'district_id' => $validatedData['director_dis']['id'],
        ]);

        AddressTran::insert([
            ['language_name' => 'en', 'address_id' => $address->id, 'area' => $validatedData['director_area_english']],
            ['language_name' => 'ps', 'address_id' => $address->id, 'area' => $validatedData['director_area_pashto']],
            ['language_name' => 'fa', 'address_id' => $address->id, 'area' => $validatedData['director_area_farsi']],
        ]);

        $director = Director::create([
            'ngo_id' => $ngo_id,
            'nid_no' => $validatedData['nid'] ?? '',
            'nid_type_id' => $validatedData['identity_type']['id'],
            'is_Active' => 1,
            'gender_id' => $validatedData['gender']['id'],
            'country_id' => $validatedData['nationality']['id'],
            'address_id' => $address->id,
            'email_id' => $email->id,
            'contact_id' => $contact->id,
        ]);



        DirectorTran::insert([
            ['director_id' => $director->id, 'language_name' => 'en', 'name' => $validatedData['director_name_english'], 'last_name' => $validatedData['surname_english']],
            ['director_id' => $director->id, 'language_name' => 'ps', 'name' => $validatedData['director_name_pashto'], 'last_name' => $validatedData['surname_pashto']],
            ['director_id' => $director->id, 'language_name' => 'fa', 'name' => $validatedData['director_name_farsi'], 'last_name' => $validatedData['surname_farsi']],
        ]);

        AgreementDirector::create([
            'agreement_id' => $agreement_id,
            'director_id' => $director->id
        ]);
    }
}
