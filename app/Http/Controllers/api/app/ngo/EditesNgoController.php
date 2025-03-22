<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Models\Ngo;
use App\Models\Email;
use App\Models\Address;
use App\Models\Contact;
use App\Models\NgoTran;
use App\Models\NgoStatus;
use App\Enums\LanguageEnum;
use App\Models\AddressTran;
use Illuminate\Http\Request;
use App\Enums\Type\StatusTypeEnum;
use App\Traits\Helper\HelperTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\app\ngo\NgoInfoUpdateRequest;
use App\Http\Requests\app\ngo\NgoUpdatedMoreInformationRequest;

class EditesNgoController extends Controller
{
    use HelperTrait;
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
        $ngo->date_of_establishment = $request->establishment_date;
        $address->province_id = $request->province['id'];
        $address->district_id = $request->district['id'];

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
        $ngo->save();
        $address->save();

        DB::commit();
        return response()->json([
            'message' => __('app_translation.success'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
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
        $authUser = $request->user();

        // Deactivate previous status
        $status =  NgoStatus::where('ngo_id', $validatedData['ngo_id'])->where('is_active', 1)->value('status_type_id');

        if ($status === StatusTypeEnum::registered->value || $status === StatusTypeEnum::blocked->value) {
            $newStatus = NgoStatus::create([
                'status_type_id' => $validatedData['status_type_id'],
                'ngo_id' => $validatedData['ngo_id'],
                'comment' => $validatedData['comment'],
                'is_active' => 1,
                'userable_id' => $authUser->id,
                'userable_type' => $this->getModelName(get_class($authUser)),
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
