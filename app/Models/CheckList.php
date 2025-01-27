<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckList extends Model
{
    protected $guarded = [];
    protected $casts = [
        'file_extensions' => 'array',
    ];
}
