<?php

namespace App\Http\Controllers\api\template;

use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\Translate;
use App\Enums\LanguageEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;

class LocationController extends Controller
{
    public function countries()
    {
        $locale = App::getLocale();
        $tr = [];
        if ($locale == LanguageEnum::default->value) {
            $tr = Country::select('id', 'name')->get();
        } else {
            $tr = Translate::where('translable_type', Country::class)
                ->where('language_name', $locale)
                ->select('translable_id as id', 'value as name')
                ->get();
        }
        return response()->json($tr);
    }

    public function provinces(Request $request)
    {
        $request->validate([
            'country_id' => 'required',
        ]);
        $country_id = $request->input('country_id');

        $locale = App::getLocale();
        $tr = [];
        if ($locale === LanguageEnum::default->value) {
            $tr = Province::where('country_id', '=', $country_id)->select('id', 'name', 'country_id')->get();
        } else {
            $tr = Translate::join('provinces', 'translable_id', '=', 'provinces.id')
                ->where('translable_type', '=', Province::class)
                ->where('country_id', '=', $country_id)
                ->where('language_name', '=', "fa")
                ->select('translable_id as id', 'value as name')
                ->get();
        }
        return response()->json($tr);
    }

    public function districts(Request $request)
    {
        $request->validate([
            'province_id' => 'required',
        ]);
        $province_id = $request->input('province_id');
        $locale = App::getLocale();
        $tr = [];
        if ($locale === LanguageEnum::default->value)
            $tr =  District::select("name", 'id')->where('province_id', $province_id)->get();
        else {
            $tr = Translate::join('districts', 'translable_id', '=', 'districts.id')
                ->where('translable_type', '=', District::class)
                ->where('province_id', '=', $province_id)
                ->where('language_name', '=', "fa")
                ->select('translable_id as id', 'value as name')
                ->get();
        }
        return response()->json($tr, 200, [], JSON_UNESCAPED_UNICODE);
    }
}
