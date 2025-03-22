<?php

namespace App\Repositories\Representative;

use App\Enums\LanguageEnum;
use App\Models\Representer;
use App\Models\RepresenterTran;
use App\Traits\Helper\HelperTrait;
use App\Models\AgreementRepresenter;
use App\Models\RepresentorDocuments;
use Illuminate\Support\Facades\Log;

class RepresentativeRepository implements RepresentativeRepositoryInterface
{
    use HelperTrait;

    public function storeRepresentative(
        $request,
        $ngo_id,
        $agreement_id,
        $documentsId,
        $is_active,
        $userable_id,
        $userable_type
    ) {
        $representer = Representer::create([
            'userable_id' => $userable_id,
            'userable_type' => $userable_type,
            'is_active' => $is_active,
            "ngo_id" => $ngo_id,
        ]);
        AgreementRepresenter::create([
            "agreement_id" => $agreement_id,
            "representer_id" => $representer->id,
        ]);
        foreach (LanguageEnum::LANGUAGES as $code => $name) {
            RepresenterTran::create([
                'representer_id' => $representer->id,
                'language_name' =>  $code,
                'full_name' => $request["repre_name_{$name}"],
            ]);
        }
        foreach ($documentsId as $documentId) {
            RepresentorDocuments::create([
                'representor_id' => $representer->id,
                'document_id' => $documentId,
            ]);
        }
        return $representer;
    }
}
