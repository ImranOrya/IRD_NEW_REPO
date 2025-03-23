<?php

namespace App\Http\Controllers\api\template;

use App\Models\Ngo;
use App\Models\Email;
use App\Models\Address;
use App\Models\Contact;
use App\Models\NgoTran;
use App\Enums\LanguageEnum;
use App\Models\AddressTran;
use Illuminate\Http\Request;
use App\Traits\Helper\HelperTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Repositories\ngo\NgoRepositoryInterface;
use App\Http\Requests\Auth\ngo\NgoProfileUpdateRequest;
use App\Http\Requests\template\user\ProfileUpdateRequest;

class ProfileController extends Controller
{
    use HelperTrait;
    protected $ngoRepository;

    public function __construct(
        NgoRepositoryInterface $ngoRepository
    ) {
        $this->ngoRepository = $ngoRepository;
    }

    public function deleteProfilePicture(Request $request)
    {
        $authUser = $request->user();
        // 1. delete old profile
        $this->deleteFile($authUser->profile);
        // 2. Update the profile
        $authUser->profile = null;
        $authUser->save();
        return response()->json([
            'message' => __('app_translation.success')
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function updateUserPicture(Request $request)
    {
        $request->validate([
            'profile' => 'nullable|mimes:jpeg,png,jpg|max:2048',
        ]);
        return $this->savePicture($request, 'user-profile');
    }
    public function updateNgoPicture(Request $request)
    {
        $request->validate([
            'profile' => 'nullable|mimes:jpeg,png,jpg|max:2048',
        ]);
        return $this->savePicture($request, 'ngo-profile');
    }
    public function updateDonorPicture(Request $request)
    {
        $request->validate([
            'profile' => 'nullable|mimes:jpeg,png,jpg|max:2048',
        ]);
        return $this->savePicture($request, 'donor-profile');
    }
    public function savePicture(Request $request, $dynamic_path)
    {
        $authUser = $request->user();
        $path = $this->storeProfile($request, $dynamic_path);
        if ($path != null) {
            // 1. delete old profile
            $this->deleteFile($authUser->profile);
            // 2. Update the profile
            $authUser->profile = $path;
        }
        $authUser->save();
        return response()->json([
            'message' => __('app_translation.profile_changed'),
            "profile" => $authUser->profile
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function updateNgoProfileInfo(NgoProfileUpdateRequest $request)
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

        $name = $request->name_english;
        $locale = App::getLocale();
        if ($locale == LanguageEnum::farsi->value) {
            $name = $request->name_farsi;
        } else if ($locale == LanguageEnum::pashto->value) {
            $name = $request->name_pashto;
        }
        return response()->json([
            'message' => __('app_translation.success'),
            "fileds_update" => [
                "name" => $name,
                "email" => ['id' => $email->id, "value" => $email->value],
                "contact" => ['id' => $contact->id, "value" => $contact->value],
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function updateUserProfileInfo(ProfileUpdateRequest $request)
    {
        $request->validated();
        $authUser = $request->user();
        // Begin transaction
        DB::beginTransaction();
        // 2. Get Email
        $email = Email::where('value', $request->email)
            ->select('id')->first();
        // Email Is taken by someone
        if ($email) {
            if ($email->id == $authUser->email_id) {
                $email->value = $request->email;
                $email->save();
            } else {
                return response()->json([
                    'message' => __('app_translation.email_exist'),
                ], 409, [], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $email = Email::where('id', $authUser->email_id)->first();
            $email->value = $request->email;
            $email->save();
        }
        $contact = Contact::where('value', $request->contact)
            ->select('id')->first();
        if ($contact) {
            if ($contact->id == $authUser->contact_id) {
                $contact->value = $request->contact;
                $contact->save();
            } else {
                return response()->json([
                    'message' => __('app_translation.contact_exist'),
                ], 409, [], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $contact = Contact::where('id', $authUser->contact_id)->first();
            $contact->value = $request->contact;
            $contact->save();
        }
        $authUser->full_name = $request->full_name;
        $authUser->username = $request->username;
        $authUser->save();
        DB::commit();

        return response()->json([
            'message' => __('app_translation.profile_changed'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function ngoProfileInfo($ngo_id)
    {
        $locale = App::getLocale();

        $data = $this->ngoRepository->ngoProfileInfo($ngo_id, $locale);
        if (!$data) {
            return response()->json([
                'message' => __('app_translation.ngo_not_found'),
            ], 404);
        }

        return response()->json([
            'ngo' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
