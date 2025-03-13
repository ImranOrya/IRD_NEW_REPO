<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Director extends Model
{
    protected $guarded = [];
    public function directorTrans()
    {
        return $this->hasMany(DirectorTran::class);
    }
}
