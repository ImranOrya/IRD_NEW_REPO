<?php

namespace App\Http\Controllers\api\app\director;

use App\Models\Ngo;
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
use App\Http\Controllers\Controller;
use App\Traits\Address\AddressTrait;
use App\Traits\Director\DirectorTrait;
use App\Http\Requests\app\director\StoreDirectorRequest;
use App\Http\Requests\app\director\UpdateDirectorRequest;

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

        // 4. make other directors false
        Director::where('is_active', true)
            ->where('ngo_id', $ngo->id)
            ->update(['is_active' => false]);
        // 5. Create the Director
        $director = Director::create([
            'ngo_id' => $id,
            'nid_no' => $request->nid,
            'nid_type_id' => $request->identity_type['id'],
            'is_active' => 1,
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

    public function ngoDirectorsName($id)
    {
        $locale = App::getLocale();
        // Joining necessary tables to fetch the NGO data
        $director = DB::table('directors as d')
            ->where('d.ngo_id', $id)
            ->join('director_trans as dt', function ($join) use ($locale) {
                $join->on('dt.director_id', '=', 'd.id')
                    ->where("dt.language_name", $locale);
            })
            ->select(
                'd.id',
                'd.is_active',
                'd.country_id',
                DB::raw("CONCAT(dt.name, ' ', dt.last_name) as name")
            )
            ->get();

        return response()->json($director, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function ngoDirector($id)
    {
        $locale = App::getLocale();
        $director = DB::table('directors as d')
            ->where('d.ngo_id', $id)
            ->where('d.is_active', true)
            ->join('director_trans as dirt', 'dirt.director_id', '=', 'd.id')
            ->join('country_trans as ct', function ($join) use ($locale) {
                $join->on('ct.country_id', '=', 'd.country_id')
                    ->where('ct.language_name', $locale);
            })
            ->join('contacts as c', 'c.id', '=', 'd.contact_id')
            ->join('emails as e', 'e.id', '=', 'd.email_id')
            ->join('addresses as a', 'a.id', '=', 'd.address_id')
            ->join('address_trans as at', 'at.address_id', '=', 'a.id')
            ->join('district_trans as dt', function ($join) use ($locale) {
                $join->on('dt.district_id', '=', 'a.district_id')
                    ->where('dt.language_name', $locale);
            })
            ->join('province_trans as pt', function ($join) use ($locale) {
                $join->on('pt.province_id', '=', 'a.province_id')
                    ->where('pt.language_name', $locale);
            })
            ->join('nid_type_trans as ntt', function ($join) use ($locale) {
                $join->on('ntt.nid_type_id', '=', 'd.nid_type_id')
                    ->where('ntt.language_name', $locale);
            })
            ->join('genders as g', 'g.id', '=', 'd.gender_id')
            ->select(
                'd.id',
                'g.id as gender_id',
                'g.name_en',
                'g.name_ps',
                'g.name_fa',
                'd.is_active',
                'd.nid_no',
                'd.nid_type_id',
                'pt.value as province',
                'pt.province_id',
                'ntt.value as nid_type',
                'c.value as contact',
                'e.value as email',
                'dt.value as district',
                'dt.district_id',
                'ct.country_id',
                'ct.value as country',
                // Aggregating the name by conditional filtering for each language
                DB::raw("MAX(CASE WHEN dirt.language_name = 'ps' THEN dirt.name END) as name_pashto"),
                DB::raw("MAX(CASE WHEN dirt.language_name = 'fa' THEN dirt.name END) as name_farsi"),
                DB::raw("MAX(CASE WHEN dirt.language_name = 'en' THEN dirt.name END) as name_english"),
                DB::raw("MAX(CASE WHEN dirt.language_name = 'ps' THEN dirt.last_name END) as surname_pashto"),
                DB::raw("MAX(CASE WHEN dirt.language_name = 'fa' THEN dirt.last_name END) as surname_farsi"),
                DB::raw("MAX(CASE WHEN dirt.language_name = 'en' THEN dirt.last_name END) as surname_english"),
                DB::raw("MAX(CASE WHEN at.language_name = 'ps' THEN at.area END) as area_pashto"),
                DB::raw("MAX(CASE WHEN at.language_name = 'fa' THEN at.area END) as area_farsi"),
                DB::raw("MAX(CASE WHEN at.language_name = 'en' THEN at.area END) as area_english")
            )
            ->groupBy(
                'd.id',
                'g.id',
                'g.name_en',
                'g.name_ps',
                'g.name_fa',
                'd.is_active',
                'd.nid_no',
                'd.nid_type_id',
                'pt.value',
                'pt.province_id',
                'ntt.value',
                'c.value',
                'e.value',
                'dt.value',
                'dt.district_id',
                'ct.country_id',
                'ct.value',
            )
            ->first();
        $gender = $director->name_en;
        if ($locale == "fa") {
            $gender = $director->name_fa;
        } else if ($locale == "ps") {
            $gender = $director->name_ps;
        }
        $data =  [
            'id' => $director->id,
            'is_active' => $director->is_active == true,
            'name_english' => $director->name_english,
            'name_pashto' => $director->name_pashto,
            'name_farsi' => $director->name_farsi,
            'surname_english' => $director->surname_english,
            'surname_pashto' => $director->surname_pashto,
            'surname_farsi' => $director->surname_farsi,
            'nationality' => ['name' => $director->country, 'id' => $director->country_id],
            'contact' => $director->contact,
            'email' => $director->email,
            'gender' => ['name' => $gender, 'id' => $director->gender_id],
            'nid' => $director->nid_no,
            'identity_type' => ['name' => $director->nid_type, 'id' => $director->nid_type_id],
            'province' => ['name' => $director->province, 'id' => $director->province_id],
            'district' => ['name' => $director->district, 'id' => $director->district_id],
            'area_english' => $director->area_english,
            'area_pashto' => $director->area_pashto,
            'area_farsi' => $director->area_farsi,
        ];

        return response()->json([
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
        DB::beginTransaction();
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
