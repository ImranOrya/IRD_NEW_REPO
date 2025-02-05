<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckList extends Model
{
    protected $guarded = [];

    // Mutator for accepted_file_types to store exactly as entered
    public function setAcceptedFileTypesAttribute($value)
    {
        // Store the value as it is (with quotes and commas)
        $this->attributes['accepted_file_types'] = $value;
    }

    // Accessor to clean the value when retrieving (optional)
    public function getAcceptedFileTypesAttribute($value)
    {
        // This is optional: strip unwanted escape characters if needed
        return stripslashes($value); // Or simply return $value if no cleaning is needed
    }
}
