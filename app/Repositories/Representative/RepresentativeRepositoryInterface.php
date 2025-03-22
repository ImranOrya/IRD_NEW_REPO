<?php

namespace App\Repositories\Representative;

interface RepresentativeRepositoryInterface
{
    /**
     * Creates a approval.
     * 
     *
     * @param Illuminate\Http\Request request
     * @param string ngo_id
     * @param string agreement_id
     * @param array DocumentsId
     * @param boolean is_active
     * @param string userable_id
     * @param string userable_type
     * @return App\Models\Representer
     */
    public function storeRepresentative(
        $request,
        $ngo_id,
        $agreement_id,
        $DocumentsId,
        $is_active,
        $userable_id,
        $userable_type
    );
}
