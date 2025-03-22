<?php

namespace App\Repositories\Director;

interface DirectorRepositoryInterface
{
    /**
     * Store Ngo Director.
     * 
     *
     * @param mix validatedData
     * @param string ngo_id
     * @param string agreement_id
     * @param array DocumentsId
     * @param boolean is_active
     * @return App\Models\Director
     */
    public function storeNgoDirector($validatedData, $ngo_id, $agreement_id, $DocumentsId, $is_active, $userable_id, $userable_type);
}
