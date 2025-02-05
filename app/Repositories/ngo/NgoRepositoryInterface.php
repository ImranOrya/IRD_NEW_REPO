<?php

namespace App\Repositories\ngo;

interface NgoRepositoryInterface
{
    public function getNgoInit($locale, $ngo_id);
}
