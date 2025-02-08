<?php

namespace App\Http\Controllers\api\app\director;

use App\Models\Email;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Director;
use App\Enums\LanguageEnum;
use App\Models\AddressTran;
use App\Models\DirectorTran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\Address\AddressTrait;
use App\Traits\Director\DirectorTrait;
use App\Http\Requests\app\ngo\director\StoreDirectorRequest;
use App\Http\Requests\app\ngo\director\UpdateDirectorRequest;
use App\Models\Ngo;

class DirectorController extends Controller
{
    use DirectorTrait, AddressTrait;
    //

    public function store(StoreDirectorRequest $request)
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
        // 2. Transaction
        DB::beginTransaction();
        $email = Email::create(['value' => $request->email]);
        $contact = Contact::create(['value' => $request->contact]);

        // 3. Create address
        $address = Address::create([
            'province_id' => $request->province['id'],
            'district_id' => $request->district['id'],
        ]);

        // 4. If is_active is true make other directors false
        if ($request->is_active == true)
            Director::where('is_active', true)
                ->where('ngo_id', $ngo->id)
                ->update(['is_active' => false]);
        // 5. Create the Director
        $director = Director::create([
            'ngo_id' => $id,
            'nid_no' => $request->nid,
            'nid_type_id' => $request->identity_type['id'],
            'is_active' => $request->is_active,
            'gender_id' => $request->gender['id'],
            'country_id' => $request->nationality['id'],
            'address_id' => $address->id,
            'email_id' => $email->id,
            'contact_id' => $contact->id,
        ]);
        foreach (LanguageEnum::LANGUAGES as $code => $name) {
            DirectorTran::create([
                'director_id' => $director->id,
                'language_name' => $code,
                'name' => $request["name_{$name}"],
                'last_name' => $request["surname_{$name}"],
            ]);

            AddressTran::create([
                'address_id' => $address->id,
                'language_name' => $code,
                'area' => $request["area_{$name}"],
            ]);
        }

        DB::commit();
        return response()->json([
            'message' => __('app_translation.success'),
            'director' => $this->getDirectorData($request, $director),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function ngoDirector(Request $request, $id)
    {
        $locale = App::getLocale();
        // Joining necessary tables to fetch the NGO data
        $director = Director::join('contacts', 'contact_id', '=', 'contacts.id')
            ->leftJoin('emails', 'email_id', '=', 'emails.id')
            ->leftJoin('addresses', 'address_id', '=', 'addresses.id')
            ->leftjoin('nid_type_trans', 'nid_type_trans.nid_type_id', 'directors.nid_type_id')
            ->leftjoin('genders', 'genders.id', 'directors.gender_id')
            ->where('directors.id', $id)
            ->where('directors.is_active', 1)
            ->where('nid_type_trans.language_name', $locale)

            ->select(
                'directors.id',
                'directors.is_active',
                'emails.value as email',
                'contacts.value as contact',
                'directors.contact_id',
                'nid_type_trans.value as nid_type',
                'directors.nid_type_id',
                "genders.name_{$locale} as gender",
                'gender_id',
                'directors.country_id',
                'nid_no',
                'address_id',
                'province_id',
                'district_id',
            )
            ->first();

        // Handle NGO not found
        if (!$director) {
            return response()->json([
                'message' => __('app_translation.not_found'),
            ], 404);
        }


        // Fetching translations using a separate query
        $translations = $this->directorNameTrans($director->id);
        $areaTrans = $this->getAddressAreaTran($director->address_id);
        $address = $this->getCompleteAddress($director->address_id, $locale);

        $data = [
            'id' => $director->id,
            'is_active' => $director->is_active === 1 ? true : false,
            'name_english' => $translations['en']->name ?? '',
            'name_pashto' => $translations['ps']->name ?? '',
            'name_farsi' => $translations['fa']->name ?? '',
            'surname_english' => $translations['en']->last_name ?? '',
            'surname_pashto' => $translations['ps']->last_name ?? '',
            'surname_farsi' => $translations['fa']->last_name ?? '',
            'nationality' => ['name' => $this->getCountry($director->country_id, $locale), 'id' => $director->country_id],
            'contact' => $director->contact,
            'email' => $director->email,
            'gender' => ['name' => $director->gender, 'id' => $director->gender_id],
            'nid' => $director->nid_no,
            'identity_type' => ['name' => $director->nid_type, 'id' => $director->nid_type_id],
            'province' => ['name' => $address['province'], 'id' => $director->province_id],
            'district' => ['name' => $address['district'], 'id' => $director->district_id],
            'area_english' => $areaTrans['en']->area ?? '',
            'area_pashto' => $areaTrans['ps']->area ?? '',
            'area_farsi' => $areaTrans['fa']->area ?? '',
        ];

        return response()->json([
            'message' => __('app_translation.success'),
            'director' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function ngoDirectors($ngo_id)
    {
        $locale = App::getLocale();
        $directors = DB::table('directors as d')
            ->where('d.ngo_id', $ngo_id)
            ->join('director_trans as dt', function ($join) use ($locale) {
                $join->on('dt.director_id', '=', 'd.id')
                    ->where('dt.language_name', '=', $locale);
            })
            ->join('contacts as c', 'd.contact_id', '=', 'c.id')
            ->join('emails as e', 'd.email_id', '=', 'e.id')
            ->select(
                'd.id',
                'd.is_active',
                'dt.name',
                'dt.last_name as surname',
                'c.value as contact',
                'e.value as email',
            )
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'message' => __('app_translation.success'),
            'directors' => $directors,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function update(UpdateDirectorRequest $request)
    {
        $request->validated();
        $id = $request->id;
        // 1. Get director
        $director = Director::find($id);
        if (!$director) {
            return response()->json([
                'message' => __('app_translation.director_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        // 2. Get Email
        $email = Email::where('value', $request->email)
            ->select('id', 'value')->first();
        // Email Is taken by someone
        if ($email->id !== $director->email_id) {
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
        if ($contact->id !== $director->contact_id) {
            return response()->json([
                'message' => __('app_translation.contact_exist'),
            ], 409, [], JSON_UNESCAPED_UNICODE); // HTTP Status 409 Conflict
        } else {
            // Update
            $contact->value = $request->contact;
        }
        $address = Address::where('id', $director->address_id)
            ->select("district_id", "id", "province_id")
            ->first();
        if (!$address) {
            return response()->json([
                'message' => __('app_translation.address_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        DB::beginTransaction();
        // 4. If is_active is true make other directors false
        if ($request->is_active == true)
            Director::where('is_active', true)
                ->where('ngo_id', $director->ngo_id)
                ->update(['is_active' => false]);
        // 5. Update Director information
        $director->is_active = $request->is_active;
        $director->nid_no = $request->nid;
        $director->nid_type_id = $request->identity_type['id'];
        $director->gender_id = $request->gender['id'];
        $director->country_id = $request->nationality['id'];
        // Update Address translations
        $addressTrans = AddressTran::where('address_id', $address->id)->get();
        foreach ($addressTrans as $addressTran) {
            $area = $request->area_english;
            if ($addressTran->language_name == LanguageEnum::farsi->value) {
                $area = $request->area_farsi;
            } else if ($addressTran->language_name == LanguageEnum::pashto->value) {
                $area = $request->area_pashto;
            }
            $addressTran->update([
                'area' => $area,
            ]);
        }
        $address->province_id = $request->province['id'];
        $address->district_id = $request->district['id'];

        // Update Director translations
        $directorTrans = DirectorTran::where('director_id', $director->id)->get();
        foreach ($directorTrans as $directorTran) {
            $name = $request->name_english;
            $last_name = $request->surname_english;
            if ($directorTran->language_name == LanguageEnum::farsi->value) {
                $name = $request->name_farsi;
                $last_name = $request->surname_farsi;
            } else if ($directorTran->language_name == LanguageEnum::pashto->value) {
                $name = $request->name_pashto;
                $last_name = $request->surname_pashto;
            }
            $directorTran->update([
                'name' => $name,
                'last_name' => $last_name,
            ]);
        }
        // Save
        $contact->save();
        $email->save();
        $director->save();
        $address->save();
        DB::commit();

        return response()->json([
            'message' => __('app_translation.success'),
            'director' => $this->getDirectorData($request, $director),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    // Utils
    private function getDirectorData($request, $director)
    {
        $locale = App::getLocale();
        $name = $request->name_english;
        $surname = $request->surname_english;
        if ($locale == LanguageEnum::pashto->value) {
            $name = $request->name_pashto;
            $surname = $request->surname_pashto;
        } else if ($locale == LanguageEnum::farsi->value) {
            $name = $request->name_farsi;
            $surname = $request->surname_farsi;
        }

        return [
            "id" => $director->id,
            "is_active" => $request->is_active == true ? 1 : 0,
            "name" =>  $name,
            "surname" => $surname,
            "contact" => $request->contact,
            "email" => $request->email,
        ];
    }
}
