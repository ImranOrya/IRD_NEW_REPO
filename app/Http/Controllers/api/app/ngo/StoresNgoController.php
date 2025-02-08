<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Enums\CheckListTypeEnum;
use App\Enums\LanguageEnum;
use App\Enums\pdfFooter\CheckListEnum;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\Type\StatusTypeEnum;
use App\Enums\Type\TaskTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\app\ngo\NgoInitStoreRequest;
use App\Http\Requests\app\ngo\NgoRegisterRequest;
use App\Models\Address;
use App\Models\AddressTran;
use App\Models\Agreement;
use App\Models\AgreementDocument;
use App\Models\CheckList;
use App\Models\CheckListTrans;
use App\Models\CheckListType;
use App\Models\Contact;
use App\Models\Director;
use App\Models\DirectorTran;
use App\Models\Document;
use App\Models\Email;
use App\Models\Ngo;
use App\Models\NgoPermission;
use App\Models\NgoStatus;
use App\Models\NgoTran;
use App\Models\PendingTask;
use App\Models\PendingTaskContent;
use App\Models\PendingTaskDocument;
use App\Models\StatusTypeTran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\json;

class StoresNgoController extends Controller
{
    public function store(NgoRegisterRequest $request)
    {
        $validatedData = $request->validated();
        $locale = App::getLocale();
        // Begin transaction
        DB::beginTransaction();
        // Create email
        $email = Email::create(['value' => $validatedData['email']]);
        $contact = Contact::create(['value' => $validatedData['contact']]);
        // Create address
        $address = Address::create([
            'district_id' => $validatedData['district_id'],
            'province_id' => $validatedData['province_id'],
        ]);
        AddressTran::create([
            'address_id' => $address->id,
            'area' => $validatedData['area_english'],
            'language_name' =>  LanguageEnum::default->value,
        ]);
        AddressTran::create([
            'address_id' => $address->id,
            'area' => $validatedData['area_pashto'],
            'language_name' =>  LanguageEnum::pashto->value,
        ]);
        AddressTran::create([
            'address_id' => $address->id,
            'area' => $validatedData['area_farsi'],
            'language_name' =>  LanguageEnum::farsi->value,
        ]);
        // Create NGO
        $newNgo = Ngo::create([
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
            "status_type_id" => StatusTypeEnum::not_logged_in->value,
            "comment" => "Newly Created"
        ]);
        NgoTran::create([
            'ngo_id' => $newNgo->id,
            'language_name' =>  LanguageEnum::default->value,
            'name' => $validatedData['name_english'],
        ]);

        NgoTran::create([
            'ngo_id' => $newNgo->id,
            'language_name' =>  LanguageEnum::farsi->value,
            'name' => $validatedData['name_farsi'],
        ]);
        NgoTran::create([
            'ngo_id' => $newNgo->id,
            'language_name' =>  LanguageEnum::pashto->value,
            'name' => $validatedData['name_pashto'],
        ]);

        $name =  $validatedData['name_english'];
        if ($locale == LanguageEnum::farsi->value) {
            $name = $validatedData['name_farsi'];
        } else if ($locale == LanguageEnum::pashto->value) {
            $name = $validatedData['name_pashto'];
        }
        // If everything goes well, commit the transaction
        DB::commit();

        $status = StatusTypeTran::where('status_type_id', StatusTypeEnum::not_logged_in->value)
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
                    "status_id" => StatusTypeEnum::not_logged_in->value,
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

    public function storePersonalDetial(Request $request, $id)
    {
        $request->validate([
            'contents' => 'required|string',
            'step' => 'required|string',
        ]);

        $user = $request->user();
        $user_id = $user->id;
        $role = $user->role_id;
        $task_type = TaskTypeEnum::ngo_registeration;

        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $id)
            ->first(); // Retrieve the first matching record

        if ($task) {
            $pendingContent = PendingTaskContent::where('pending_task_id', $task->id)
                ->first(); // Get the maximum step value
            if ($pendingContent) {
                // Update prevouis content
                $pendingContent->step = $request->step;
                $pendingContent->content = $request->contents;
                $pendingContent->save();
            } else {
                // If no content found
                PendingTaskContent::create([
                    'step' => $request->step,
                    'content' => $request->contents,
                    'pending_task_id' => $task->id
                ]);
            }
        } else {
            $task =  PendingTask::create([
                'user_id' => $user_id,
                'user_type' => $role,
                'task_type' => $task_type,
                'task_id' => $id
            ]);
            PendingTaskContent::create([
                'step' => 1,
                'content' => $request->contents,
                'pending_task_id' => $task->id
            ]);
        }
        return response()->json(
            [
                'message' => __('app_translation.success'),
            ],

            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }


    public function storePersonalDetialFinal(NgoInitStoreRequest $request)
    {
        // return $request;
        $id = $request->ngo_id;
        $validatedData =  $request->validated();

        Log::info($request->all());




        $agreement = Agreement::where('ngo_id', $id)
            ->latest('end_date') // Order by end_date descending
            ->first();           // Get the first record (most recent)

        // Check if agreement exists and is expired
        if (!$agreement || $agreement->end_date >= now()) {
            return response()->json([
                'message' => __('app_translation.agreement_exists'),
                'errors' => $agreement->end_date // Reset keys for cleaner JSON output
            ], 404);
        }

        // Log::error($request);
        $ngo = Ngo::findOrFail($id);
        $checklist =  $this->checkList($request, $id);
        if ($checklist) {
            return $checklist;
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

        NgoStatus::create([
            'ngo_id' => $id,
            'status_type_id' => StatusTypeEnum::register_form_submited,
            'comment' => 'Register Form Complete',
        ]);

        $this->documentStore($request, $agreement->id, $id);
        $this->directorStore($validatedData, $id);
        $this->deletePendingTask($request, $id);


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
    protected function deletePendingTask($request, $id)
    {

        $user = $request->user();
        $user_id = $user->id;
        $role = $user->role_id;
        $task_type = TaskTypeEnum::ngo_registeration;

        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $id)
            ->first();
        Log::info('Global Exception =>' . $task);

        PendingTaskContent::where('pending_task_id', $task->id)->delete();
        PendingTaskDocument::where('pending_task_id', $task->id)->delete();
        $task->delete();
    }

    protected function checkList($request, $ngo_id)
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

        // Ensure task exists before proceeding
        if (!$task) {
            return response()->json([
                'error' => __('app_translation.task_not_found')
            ], 404);
        }

        // Get checklist IDs
        $checkListIds = CheckList::where('check_list_type_id', CheckListTypeEnum::externel)
            ->pluck('id')
            ->toArray();

        // Get checklist IDs from documents
        $documentCheckListIds = PendingTaskDocument::where('pending_task_id', $task->id)
            ->pluck('check_list_id')
            ->toArray();

        // Log values properly
        // Log::info('Checklist Debug:', [
        //     'document_checklist_ids' => $documentCheckListIds,
        //     'required_checklist_ids' => $checkListIds
        // ]);

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

            return response()->json([
                'message' => __('app_translation.checklist_not_found'),
                'errors' => $errors // Reset keys for cleaner JSON output
            ], 400);
        }
    }
    protected function documentStore($request, $agreement_id, $ngo_id)
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
            $document = Document::create([
                'actual_name' => $checklist['actual_name'],
                'size' => $checklist['size'],
                'path' => $checklist['path'],
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

    protected function directorStore($validatedData, $ngo_id)
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
    }

    public function ngoPermissions($ngo_id)
    {
        NgoPermission::create([
            "view" => true,
            "edit" => true,
            "delete" => true,
            "add" => true,
            "ngo_id" => $ngo_id,
            "permission" => PermissionEnum::ngo->value,
        ]);
        NgoPermission::create([
            "view" => true,
            "edit" => true,
            "delete" => true,
            "add" => true,
            "ngo_id" => $ngo_id,
            "permission" => PermissionEnum::projects->value,
        ]);
    }
}
