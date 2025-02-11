<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Models\Ngo;
use App\Models\Email;
use App\Models\Address;
use App\Models\Contact;
use App\Models\NgoTran;
use App\Enums\LanguageEnum;
use App\Enums\Type\StatusTypeEnum;
use App\Models\AddressTran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Http\Requests\app\ngo\NgoInfoUpdateRequest;
use App\Http\Requests\app\ngo\NgoUpdatedMoreInformationRequest;
use App\Models\NgoStatus;

class EditesNgoController extends Controller
{
    public function updateInfo(NgoInfoUpdateRequest $request)
    {
        $request->validated();
        $id = $request->id;
        // 1. Get director
        $ngo = Ngo::find($id);
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        // Begin transaction
        DB::beginTransaction();
        // 2. Get Email
        $email = Email::where('value', $request->email)
            ->select('id', 'value')->first();
        // Email Is taken by someone
        if ($email->id !== $ngo->email_id) {
            return response()->json([
                'message' => __('app_translation.email_exist'),
            ], 409, [], JSON_UNESCAPED_UNICODE); // HTTP Status 409 Conflict
        } else {
            // Update
            $email->value = $request->email;
        }
        // 3. Get Contact
        $contact = Contact::where('value', $request->contact)
            ->select('id', 'value')->first();
        // Contact Is taken by someone
        if ($contact->id !== $ngo->contact_id) {
            return response()->json([
                'message' => __('app_translation.contact_exist'),
            ], 409, [], JSON_UNESCAPED_UNICODE); // HTTP Status 409 Conflict
        } else {
            // Update
            $contact->value = $request->contact;
        }

        $address = Address::where('id', $ngo->address_id)
            ->select("district_id", "id", "province_id")
            ->first();
        if (!$address) {
            return response()->json([
                'message' => __('app_translation.address_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        // 4. Update Ngo information
        $ngo->abbr = $request->abbr;
        $ngo->registration_no = $request->registration_no;
        $ngo->moe_registration_no = $request->moe_registration_no;
        $ngo->date_of_establishment = $request->establishment_date;
        $ngo->place_of_establishment = $request->place_of_establishment['id'];
        $address->province_id = $request->province['id'];
        $address->district_id = $request->district['id'];
        $ngo->ngo_type_id = $request->type['id'];

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
            ]);
        }

        // 5. Completed
        $email->save();
        $contact->save();
        $ngo->save();
        $address->save();

        DB::commit();
        return response()->json([
            'message' => __('app_translation.success'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'profile' => 'nullable|mimes:jpeg,png,jpg|max:2048',
            'id' => 'required',
        ]);
        $ngo = Ngo::find($request->id);
        if ($ngo) {
            $path = $this->storeProfile($request, 'ngo-profile');
            if ($path != null) {
                // 1. delete old profile
                $deletePath = storage_path('app/' . "{$ngo->profile}");
                if (file_exists($deletePath) && $ngo->profile != null) {
                    unlink($deletePath);
                }
                // 2. Update the profile
                $ngo->profile = $path;
            }
            $ngo->save();
            return response()->json([
                'message' => __('app_translation.profile_changed'),
                "profile" => $ngo->profile
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else
            return response()->json([
                'message' => __('app_translation.not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
    }

    public function UpdateMoreInformation(NgoUpdatedMoreInformationRequest $request)
    {
        $request->validated();
        $id = $request->id;
        // 1. Get NGo
        $ngo = Ngo::find($id);
        if (!$ngo) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        // 2. Find translations
        $ngoTrans = NgoTran::where('ngo_id', $id)->get();
        if (!$ngoTrans) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        // 3. Transaction
        DB::beginTransaction();

        foreach (LanguageEnum::LANGUAGES as $code => $name) {
            $tran =  $ngoTrans->where('language_name', $code)->first();
            $tran->vision = $request["vision_{$name}"];
            $tran->mission = $request["mission_{$name}"];
            $tran->general_objective = $request["general_objes_{$name}"];
            $tran->objective = $request["objes_in_afg_{$name}"];
            $tran->save();
        }


        DB::commit();
        return response()->json([
            'message' => __('app_translation.success'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function changeStatus(Request $request)
    {
        // Validate request
        $validatedData = $request->validate([
            'ngo_id' => 'required|integer',
            'status_type_id' => 'required|integer',
            'comment' => 'required|string'
        ]);

        // Deactivate previous status
        $status =  NgoStatus::where('ngo_id', $validatedData['ngo_id'])->where('is_active', 1)->value('status_type_id');

        if ($status === StatusTypeEnum::active->value || $status === StatusTypeEnum::blocked->value) {
            $newStatus = NgoStatus::create([
                'status_type_id' => $validatedData['status_type_id'],
                'ngo_id' => $validatedData['ngo_id'],
                'comment' => $validatedData['comment'],
                'is_active' => 1
            ]);
            $status->is_active = 0;
            $status->save();

            // Prepare response data
            $data = [
                'ngo_status_id' => $newStatus->id,
                'is_active' => 1,
                'created_at' => $newStatus->created_at,
            ];
            return response()->json([
                'message' => __('app_translation.success'),
                'status' => $data
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            return response()->json([
                'message' => __('app_translation.unauthorized')
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
