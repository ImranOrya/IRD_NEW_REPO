<?php

namespace App\Traits\Ngo;

use App\Models\NgoTran;



trait NgoTrait
{
  public function ngoNameTrans($ngo_id)
  {
    $translations = NgoTran::where('ngo_id', $ngo_id)
      ->select('language_name', 'name')
      ->get()
      ->keyBy('language_name');
    return $translations;
  }
}
