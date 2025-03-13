<?php

namespace App\Repositories\Director;

use App\Models\Email;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Director;
use App\Models\AddressTran;
use App\Models\DirectorTran;
use App\Models\AgreementDirector;
use App\Models\DirectorDocuments;

class DirectorRepository implements DirectorRepositoryInterface
{
    public function storeNgoDirector($validatedData, $ngo_id, $agreement_id, $DocumentsId, $is_active)
    {
        $email = Email::create(['value' => $validatedData['director_email']]);
        $contact = Contact::create(['value' => $validatedData['director_contact']]);

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
            'is_active' => $is_active,
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

        foreach ($DocumentsId as $documentId) {
            DirectorDocuments::create([
                'director_id' => $director->id,
                'document_id' => $documentId,
            ]);
        }

        return $director;
    }
}
