<?php

namespace App\Traits\Address;

use App\Enums\LanguageEnum;
use App\Models\Address;
use App\Models\AddressTran;
use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\User;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use PHPUnit\Framework\Constraint\Count;

trait AddressTrait
{

    public function getCompleteAddress($address_id, $lang)
    {


        $address =     Address::with([
            'addressTrans' => function ($query) use ($lang) {
                $query->select('address_id', 'area')->where('language_name', $lang);
            },



        ])->select('id', 'province_id', 'district_id')->where('id', $address_id)->first();
        $province_id = $address->province_id;
        $district_id = $address->district_id;
        $province = '';

        if ($lang != LanguageEnum::default->value) {
            $province =   Province::join('translates', 'translable_id', 'provinces.id')
                ->where('translable_type', Province::class)
                ->where('provinces.id', $province_id)
                ->where('language_name', $lang)
                ->select('value as name', 'country_id')->first();
        } else {

            $province =   Province::select('country_id', 'name')->where('id', $province_id)->first();
        }
        $district = '';
        if ($lang != LanguageEnum::default->value) {
            $district =     District::join('translates', 'translable_id', 'districts.id')
                ->where('translable_type', District::class)
                ->where('districts.id', $district_id)
                ->where('language_name', $lang)
                ->select('value as name')->first();
        } else {

            $district = District::select('name')->where('id', $district_id)->first();
        }

        $country = '';
        if ($lang != LanguageEnum::default->value) {


            $country =     Country::join('translates', 'translable_id', 'countries.id')
                ->where('translable_type', Country::class)
                ->where('countries.id', $province->country_id)
                ->where('language_name', $lang)
                ->select('value as name')->first();
        } else {

            $country = Country::select('name')->where('id', $province->country_id)->first();
        }




        return [
            'complete_address' => $address->addressTrans[0]->area . ',' . $district->name . ',' . $province->name . ',' . $country->name,
            'area' => $address->addressTrans[0]->area ?? '',
            'district' => $district->name ?? '',
            'province' => $province->name,
            'country' => $country->name
        ];
    }

    public function getCountry($country_id, $lang)
    {


        $country = '';
        if ($lang == LanguageEnum::default->value) {
            $country = Country::select('name')->where('id', $country_id)->first();
        } else {

            $country =     Country::join('translates', 'translable_id', 'countries.id')
                ->where('translable_type', Country::class)
                ->where('countries.id', $country_id)
                ->where('language_name', $lang)
                ->select('value as name')->first();
        }

        return $country->name ?? '';
    }


    public function getProvince($province_id, $lang)
    {




        if ($lang != LanguageEnum::default->value) {
            $province =   Province::join('translates', 'translable_id', 'provinces.id')
                ->where('translable_type', Province::class)
                ->where('provinces.id', $province_id)
                ->where('language_name', $lang)
                ->select('value as name', 'country_id')->first();
        } else {

            $province =   Province::select('country_id', 'name')->where('id', $province_id)->first();
        }


        return $province->name;
    }



    public function getDistrict($district_id, $lang)
    {




        $district = '';
        if ($lang != LanguageEnum::default->value) {
            $district =     District::join('translates', 'translable_id', 'districts.id')
                ->where('translable_type', District::class)
                ->where('districts.id', $district_id)
                ->where('language_name', $lang)
                ->select('value as name')->first();
        } else {

            $district = District::select('name')->where('id', $district_id)->first();
        }


        return $district->name;
    }



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
