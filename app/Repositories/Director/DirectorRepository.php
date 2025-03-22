<?php

namespace App\Repositories\Director;

use App\Models\Email;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Director;
use App\Enums\LanguageEnum;
use App\Models\AddressTran;
use App\Models\DirectorTran;
use App\Models\AgreementDirector;
use App\Models\DirectorDocuments;

class DirectorRepository implements DirectorRepositoryInterface
{
    public function storeNgoDirector(
        $request,
        $ngo_id,
        $agreement_id,
        $DocumentsId,
        $is_active,
        $userable_id,
        $userable_type
    ) {
        $email = Email::where('value', '=', $request['director_email'])->first();
        if ($email) {
            return [
                "response" =>
                response()->json([
                    'message' => __('app_translation.email_exist'),
                ], 400, [], JSON_UNESCAPED_UNICODE),
                "success" => false
            ];
        }
        $contact = Contact::where('value', '=', $request['director_contact'])->first();
        if ($contact) {
            return [
                "response" =>
                response()->json([
                    'message' => __('app_translation.contact_exist'),
                ], 400, [], JSON_UNESCAPED_UNICODE),
                "success" => false
            ];
        }
        $email = Email::create(["value" => $request['director_email']]);
        $contact = Contact::create(["value" => $request['director_contact']]);

        // 2. Create address
        $address = Address::create([
            'province_id' => $request['director_province']['id'],
            'district_id' => $request['director_dis']['id'],
        ]);
        // 3. make other directors false
        Director::where('is_active', true)
            ->where('ngo_id', $ngo_id)
            ->update(['is_active' => false]);
        // 5. Create the Director
        $director = Director::create([
            'ngo_id' => $ngo_id,
            'nid_no' => $request['nid'],
            'nid_type_id' => $request['identity_type']['id'],
            'is_active' => $is_active,
            'gender_id' => $request['gender']['id'],
            'country_id' => $request['nationality']['id'],
            'address_id' => $address->id,
            'email_id' => $email->id,
            'contact_id' => $contact->id,
            'userable_id' => $userable_id,
            'userable_type' => $userable_type,
        ]);

        foreach (LanguageEnum::LANGUAGES as $code => $name) {
            DirectorTran::create([
                'director_id' => $director->id,
                'language_name' => $code,
                'name' => $request["director_name_{$name}"],
                'last_name' => $request["surname_{$name}"],
            ]);

            AddressTran::create([
                'address_id' => $address->id,
                'language_name' => $code,
                'area' => $request["director_area_{$name}"],
            ]);
        }

        foreach ($DocumentsId as $documentId) {
            DirectorDocuments::create([
                'director_id' => $director->id,
                'document_id' => $documentId,
            ]);
        }

        return $director;
    }
}
