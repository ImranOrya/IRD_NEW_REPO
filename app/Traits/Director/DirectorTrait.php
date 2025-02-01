<?php

namespace App\Traits\Director;

use App\Models\Director;
use App\Models\DirectorTran;

trait DirectorTrait
{


    public function directorNameTrans($director_id){
          $translations = DirectorTran::where('director_id', $director_id)
            ->select('language_name', 'name','last_name')
            ->get()
            ->keyBy('language_name');
            return $translations;
    }

}