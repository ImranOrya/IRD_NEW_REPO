<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Enums\LanguageEnum;
use App\Enums\RoleEnum;
use App\Enums\Type\StatusTypeEnum;
use App\Enums\Type\TaskTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\app\ngo\NgoRegisterRequest;
use App\Models\Address;
use App\Models\AddressTran;
use App\Models\Contact;
use App\Models\Email;
use App\Models\Ngo;
use App\Models\NgoStatus;
use App\Models\NgoTran;
use App\Models\PendingTask;
use App\Models\PendingTaskContent;
use App\Models\StatusTypeTran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            $maxStep = PendingTaskContent::where('pending_task_id', $task->id)
                ->max('step'); // Get the maximum step value

            // 1. Check if current step is same as prevous
            if ($maxStep == $request->step) {
                return response()->json(
                    [
                        'message' => __('app_translation.success'),
                    ],
                    200,
                    [],
                    JSON_UNESCAPED_UNICODE
                );
            }
            if (!$maxStep) {
                PendingTaskContent::create([
                    'step' => 1,
                    'content' => $request->contents,
                    'pending_task_id' => $task->id
                ]);

                return response()->json(
                    [
                        'message' => __('app_translation.success'),
                    ],
                    200,
                    [],
                    JSON_UNESCAPED_UNICODE
                );
            } else {

                PendingTaskContent::create([
                    'step' => $maxStep + 1,
                    'content' => $request->contents,
                    'pending_task_id' => $task->id

                ]);

                return response()->json(
                    [
                        'message' => __('app_translation.success'),
                    ],

                    200,
                    [],
                    JSON_UNESCAPED_UNICODE
                );
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
}
