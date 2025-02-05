<?php

namespace App\Repositories\ngo;

use App\Models\Ngo;

class NgoRepository implements NgoRepositoryInterface
{
    // Retrieve all tasks
    public function getNgoInit($locale, $ngo_id)
    {
        // Joining necessary tables to fetch the NGO data
        $ngo = Ngo::join('contacts', 'contact_id', '=', 'contacts.id')
            ->leftJoin('emails', 'email_id', '=', 'emails.id')
            ->leftJoin('ngo_type_trans', function ($join) use ($locale) {
                $join->on('ngos.ngo_type_id', '=', 'ngo_type_trans.ngo_type_id')
                    ->where('ngo_type_trans.language_name', '=', $locale);
            })
            ->leftJoin('addresses', 'address_id', '=', 'addresses.id')
            ->select(
                'abbr',
                'ngos.ngo_type_id',
                'ngo_type_trans.value as type_name',
                'ngos.registration_no',
                'ngos.place_of_establishment',
                'ngos.date_of_establishment',
                'province_id',
                'district_id',
                'addresses.id as address_id',
                'ngos.email_id',
                'emails.value as email',
                'contacts.value as contact',
                'ngos.contact_id'
            )
            ->where('ngos.id', $ngo_id)
            ->first();
        return $ngo;
    }
}
