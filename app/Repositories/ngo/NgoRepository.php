<?php

namespace App\Repositories\ngo;

use App\Models\Ngo;
use Illuminate\Support\Facades\DB;

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
                'ngos.moe_registration_no',
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
    public function getNgoDetail($locale, $ngo_id)
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
                'ngos.moe_registration_no',
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
    public function ngo()
    {
        return DB::table('ngos as n');
    }
    public function transJoin($query, $locale)
    {
        $query->join('ngo_trans as nt', function ($join) use ($locale) {
            $join->on('nt.ngo_id', '=', 'n.id')
                ->where('nt.language_name', $locale);
        });
        return $this;
    }
    public function statusJoin($query)
    {
        $query->leftjoin('ngo_statuses as ns', function ($join) {
            $join->on('ns.ngo_id', '=', 'n.id')
                ->whereRaw('ns.created_at = (select max(ns2.created_at) from ngo_statuses as ns2 where ns2.ngo_id = n.id)');
        });
        return $this;
    }
    public function statusTypeTransJoin($query, $locale)
    {
        $query->leftjoin('status_type_trans as stt', function ($join) use ($locale) {
            $join->on('stt.status_type_id', '=', 'ns.status_type_id')
                ->where('stt.language_name', $locale);
        });
        return $this;
    }
    public function typeTransJoin($query, $locale)
    {
        $query->join('ngo_type_trans as ntt', function ($join) use ($locale) {
            $join->on('ntt.ngo_type_id', '=', 'n.ngo_type_id')
                ->where('ntt.language_name', $locale);
        });
        return $this;
    }
    public function directorJoin($query)
    {
        $query->join('directors as d', function ($join) {
            $join->on('d.ngo_id', '=', 'n.id')
                ->where('d.is_active', true);
        });
        return $this;
    }
    public function directorTransJoin($query, $locale)
    {
        $query->join('director_trans as dt', function ($join) use ($locale) {
            $join->on('d.id', '=', 'dt.director_id')
                ->where('dt.language_name', $locale);
        });
        return $this;
    }
    public function emailJoin($query)
    {
        $query->leftJoin('emails as e', 'e.id', '=', 'n.email_id');
        return $this;
    }
    public function contactJoin($query)
    {
        $query->leftJoin('contacts as c', 'c.id', '=', 'n.contact_id');
        return $this;
    }
    public function addressJoin($query)
    {
        $query->leftJoin('addresses as a', 'a.id', '=', 'n.address_id');
        return $this;
    }
}
