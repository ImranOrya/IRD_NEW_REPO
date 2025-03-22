<?php

namespace App\Repositories\ngo;

use App\Models\Document;
use App\Traits\Ngo\NgoTrait;
use Illuminate\Support\Facades\DB;
use App\Traits\Address\AddressTrait;

class NgoRepository implements NgoRepositoryInterface
{
    use AddressTrait, NgoTrait;

    // public function startExtendForm($query, $ngo_id, $locale)
    // {
    //     $this->typeTransJoin($query, $locale)
    //         ->emailJoin($query)
    //         ->contactJoin($query)
    //         ->addressJoin($query);
    //     $ngo = $query->select(
    //         'n.abbr',
    //         'n.ngo_type_id',
    //         'ntt.value as type_name',
    //         'n.registration_no',
    //         'n.moe_registration_no',
    //         'n.place_of_establishment',
    //         'n.date_of_establishment',
    //         'a.province_id',
    //         'a.district_id',
    //         'a.id as address_id',
    //         'e.value as email',
    //         'c.value as contact',
    //     )->first();

    //     if (!$ngo)
    //         return null;

    //     // Fetching translations using a separate query
    //     $translations = $this->ngoNameTrans($ngo_id);
    //     $areaTrans = $this->getAddressAreaTran($ngo->address_id);
    //     $address = $this->getAddressTrans(
    //         $ngo->province_id,
    //         $ngo->district_id,
    //         $locale
    //     );

    //     return [
    //         'name_english' => $translations['en']->name ?? null,
    //         'name_pashto' => $translations['ps']->name ?? null,
    //         'name_farsi' => $translations['fa']->name ?? null,
    //         'abbr' => $ngo->abbr,
    //         'type' => ['name' => $ngo->type_name, 'id' => $ngo->ngo_type_id],
    //         'contact' => $ngo->contact,
    //         'email' =>   $ngo->email,
    //         'registration_no' => $ngo->registration_no,
    //         'province' => $address['province'],
    //         'district' => $address['district'],
    //         'area_english' => $areaTrans['en']->area ?? '',
    //         'area_pashto' => $areaTrans['ps']->area ?? '',
    //         'area_farsi' => $areaTrans['fa']->area ?? '',
    //     ];
    // }
    public function startRegisterFormInfo($ngo_id, $locale)
    {
        $ngo = $this->generalQuery($ngo_id, $locale)
            ->select(
                'n.id',
                'n.registration_no',
                'n.abbr',
                'n.ngo_type_id',
                'ntt.value as ngo_type',
                'c.value as contact',
                'e.value as email',
                'dt.value as district',
                'dt.district_id',
                'pt.value as province',
                'pt.province_id',
                // Aggregating the name by conditional filtering for each language
                DB::raw("MAX(CASE WHEN nt.language_name = 'ps' THEN nt.name END) as name_pashto"),
                DB::raw("MAX(CASE WHEN nt.language_name = 'fa' THEN nt.name END) as name_farsi"),
                DB::raw("MAX(CASE WHEN nt.language_name = 'en' THEN nt.name END) as name_english"),
                DB::raw("MAX(CASE WHEN at.language_name = 'ps' THEN at.area END) as area_pashto"),
                DB::raw("MAX(CASE WHEN at.language_name = 'fa' THEN at.area END) as area_farsi"),
                DB::raw("MAX(CASE WHEN at.language_name = 'en' THEN at.area END) as area_english")
            )
            ->groupBy(
                'n.id',
                'n.registration_no',
                'n.abbr',
                'n.ngo_type_id',
                'ntt.value',
                'c.value',
                'e.value',
                'dt.value',
                'pt.value',
                'dt.district_id',
                'pt.province_id',
            )
            ->first();

        return [
            "id" => $ngo->id,
            "abbr" => $ngo->abbr,
            "name_english" => $ngo->name_english,
            "name_farsi" => $ngo->name_farsi,
            "name_pashto" => $ngo->name_pashto,
            "type" => ['id' => $ngo->ngo_type_id, 'name' => $ngo->ngo_type],
            "contact" => $ngo->contact,
            "email" => $ngo->email,
            "registration_no" => $ngo->registration_no,
            "province" => ["id" => $ngo->province_id, "name" => $ngo->province],
            "district" => ["id" => $ngo->district_id, "name" => $ngo->district],
            "area_english" => $ngo->area_english,
            "area_pashto" => $ngo->area_pashto,
            "area_farsi" => $ngo->area_farsi,
        ];
    }
    public function afterRegisterFormInfo($ngo_id, $locale)
    {
        $ngo = $this->generalQuery($ngo_id, $locale)
            ->join('country_trans as ct', function ($join) use ($locale) {
                $join->on('ct.country_id', '=', 'n.place_of_establishment')
                    ->where('ct.language_name', $locale);
            })
            ->select(
                'n.id',
                'n.registration_no',
                'n.place_of_establishment as country_id',
                'ct.value as country',
                'n.date_of_establishment as establishment_date',
                'n.moe_registration_no',
                'n.abbr',
                'n.ngo_type_id',
                'ntt.value as ngo_type',
                'c.value as contact',
                'e.value as email',
                'dt.value as district',
                'dt.district_id',
                'pt.value as province',
                'pt.province_id',
                // Aggregating the name by conditional filtering for each language
                DB::raw("MAX(CASE WHEN nt.language_name = 'ps' THEN nt.name END) as name_pashto"),
                DB::raw("MAX(CASE WHEN nt.language_name = 'fa' THEN nt.name END) as name_farsi"),
                DB::raw("MAX(CASE WHEN nt.language_name = 'en' THEN nt.name END) as name_english"),
                DB::raw("MAX(CASE WHEN at.language_name = 'ps' THEN at.area END) as area_pashto"),
                DB::raw("MAX(CASE WHEN at.language_name = 'fa' THEN at.area END) as area_farsi"),
                DB::raw("MAX(CASE WHEN at.language_name = 'en' THEN at.area END) as area_english")
            )
            ->groupBy(
                'n.id',
                'n.registration_no',
                'ct.value',
                'n.place_of_establishment',
                'ct.value',
                'n.date_of_establishment',
                'n.moe_registration_no',
                'n.abbr',
                'n.ngo_type_id',
                'ntt.value',
                'c.value',
                'e.value',
                'dt.value',
                'pt.value',
                'dt.district_id',
                'pt.province_id',
            )
            ->first();

        return [
            "id" => $ngo->id,
            "abbr" => $ngo->abbr,
            "name_english" => $ngo->name_english,
            "name_farsi" => $ngo->name_farsi,
            "name_pashto" => $ngo->name_pashto,
            "type" => ['id' => $ngo->ngo_type_id, 'name' => $ngo->ngo_type],
            "contact" => $ngo->contact,
            "email" => $ngo->email,
            "registration_no" => $ngo->registration_no,
            "province" => ["id" => $ngo->province_id, "name" => $ngo->province],
            "district" => ["id" => $ngo->district_id, "name" => $ngo->district],
            "area_english" => $ngo->area_english,
            "area_pashto" => $ngo->area_pashto,
            "area_farsi" => $ngo->area_farsi,
            "moe_registration_no" => $ngo->moe_registration_no,
            'establishment_date' => $ngo->establishment_date,
            'country' => ['id' => $ngo->country_id, 'name' => $ngo->country],
        ];
    }

    public function agreementDocuments($query, $agreement_id, $locale)
    {
        $document =  Document::join('agreement_documents as agd', 'agd.document_id', 'documents.id')
            ->where('agd.agreement_id',  $agreement_id)
            ->join('check_lists as cl', function ($join) {
                $join->on('documents.check_list_id', '=', 'cl.id');
            })
            ->join('check_list_trans as clt', function ($join) use ($locale) {
                $join->on('clt.check_list_id', '=', 'cl.id')
                    ->where('language_name', $locale);
            })
            ->select(
                'documents.path',
                'documents.id as document_id',
                'documents.size',
                'documents.check_list_id as checklist_id',
                'documents.type',
                'documents.actual_name as name',
                'clt.value as checklist_name',
                'cl.acceptable_extensions',
                'cl.acceptable_mimes'
            )
            ->get();

        return $document;
    }
    public function statuses($ngo_id, $locale)
    {
        $query = $this->ngo($ngo_id);
        $this->statusJoinAll($query)
            ->statusTypeTransJoin($query, $locale);
        return $query
            ->select(
                'n.id as ngo_id',
                'ns.id',
                'ns.comment',
                'ns.status_type_id',
                'stt.name',
                'ns.userable_type',
                'ns.is_active',
                'ns.created_at',
            )->get();
    }
    // Joins
    public function ngo($id = null)
    {
        if ($id) {
            return DB::table('ngos as n')->where('n.id', $id);
        } else {
            return DB::table('ngos as n');
        }
    }
    public function transJoin($query, $locale)
    {
        $query->join('ngo_trans as nt', function ($join) use ($locale) {
            $join->on('nt.ngo_id', '=', 'n.id')
                ->where('nt.language_name', $locale);
        });
        return $this;
    }
    public function transJoinLocales($query)
    {
        $query->join('ngo_trans as nt', function ($join) {
            $join->on('nt.ngo_id', '=', 'n.id');
        });
        return $this;
    }
    public function statusJoin($query)
    {
        $query->join('ngo_statuses as ns', function ($join) {
            $join->on('ns.ngo_id', '=', 'n.id')
                ->where('ns.is_active', true);
            // ->whereRaw('ns.created_at = (select max(ns2.created_at) from ngo_statuses as ns2 where ns2.ngo_id = n.id)');
        });
        return $this;
    }
    public function statusJoinAll($query)
    {
        $query->join('ngo_statuses as ns', function ($join) {
            $join->on('ns.ngo_id', '=', 'n.id');
        });
        return $this;
    }
    public function statusTypeTransJoin($query, $locale)
    {
        $query->join('status_type_trans as stt', function ($join) use ($locale) {
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
        $query->leftJoin('directors as d', function ($join) {
            $join->on('d.ngo_id', '=', 'n.id')
                ->where('d.is_active', true);
        });
        return $this;
    }
    public function directorTransJoin($query, $locale)
    {
        $query->leftJoin('director_trans as dt', function ($join) use ($locale) {
            $join->on('d.id', '=', 'dt.director_id')
                ->where('dt.language_name', $locale);
        });
        return $this;
    }
    public function emailJoin($query)
    {
        $query->join('emails as e', 'e.id', '=', 'n.email_id');
        return $this;
    }
    public function contactJoin($query)
    {
        $query->join('contacts as c', 'c.id', '=', 'n.contact_id');
        return $this;
    }
    public function addressJoin($query)
    {
        $query->join('addresses as a', 'a.id', '=', 'n.address_id');
        return $this;
    }
    public function agreementJoin($query)
    {
        $query->join('agreements as ag', function ($join) {
            $join->on('n.id', '=', 'ag.ngo_id')
                ->whereRaw('ag.end_date = (select max(ns2.end_date) from agreements as ns2 where ns2.ngo_id = n.id)');
        });
        return $this;
    }
    public function generalQuery($ngo_id, $locale)
    {
        return DB::table('ngos as n')
            ->where('n.id', $ngo_id)
            ->join('ngo_trans as nt', 'nt.ngo_id', '=', 'n.id')
            ->join('ngo_type_trans as ntt', function ($join) use ($locale) {
                $join->on('ntt.ngo_type_id', '=', 'n.ngo_type_id')
                    ->where('ntt.language_name', $locale);
            })
            ->join('contacts as c', 'c.id', '=', 'n.contact_id')
            ->join('emails as e', 'e.id', '=', 'n.email_id')
            ->join('addresses as a', 'a.id', '=', 'n.address_id')
            ->join('address_trans as at', 'at.address_id', '=', 'a.id')
            ->join('district_trans as dt', function ($join) use ($locale) {
                $join->on('dt.district_id', '=', 'a.district_id')
                    ->where('dt.language_name', $locale);
            })
            ->join('province_trans as pt', function ($join) use ($locale) {
                $join->on('pt.province_id', '=', 'a.province_id')
                    ->where('pt.language_name', $locale);
            });
    }
}
