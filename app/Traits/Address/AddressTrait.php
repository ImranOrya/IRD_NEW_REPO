<?php

namespace App\Traits\Address;

use App\Models\AddressTran;

trait AddressTrait
{
    private function getAddressArea($address_id, $lang)
    {
        return AddressTran::where('address_id', $address_id)
            ->where('language_name', $lang)
            ->value('area');
    }

    private function getAddressAreaTran($address_id)
    {
        $translations = AddressTran::where('address_id', $address_id)
            ->select('language_name', 'area')
            ->get()
            ->keyBy('language_name');
        return $translations;
    }
}
