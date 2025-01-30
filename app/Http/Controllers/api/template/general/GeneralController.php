<?php

namespace App\Http\Controllers\api\template\general;

use App\Http\Controllers\Controller;
use App\Models\Gender;
use App\Models\NidType;
use App\Models\NidTypeTrans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class GeneralController extends Controller
{
    //

    public function gender()
    {
        $locale = App::getLocale();
        $gender = Gender::select('id', "name_{$locale}")->get();
        return response()->json($gender);
    }

    public function nidType(){


        $locale =App::getLocale();

       $nidtype =  NidTypeTrans::select('value as name','nid_type_id as id ')
         ->where('language_name',$locale)
         ->get();


        

     return response()->json($nidtype);

    }
}
