<?php

namespace App\Http\Controllers\api\app\projects;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

class ProjectManagerController extends Controller
{
    public function names($ngoId)
    {
        $locale = App::getLocale();
        $query =    DB::table('managers as pm')
            ->where('pm.ngo_id', $ngoId)
            ->join('manager_trans as pmt', function ($join) use ($locale) {
                $join->on('pm.id', '=', 'pmt.manager_id')
                    ->where('language_name', $locale);
            })
            ->select(
                'pm.id',
                'pmt.full_name as name'
            )->get();

        return response()->json(
            $query,
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
}
