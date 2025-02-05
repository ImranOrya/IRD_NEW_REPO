<?php

namespace App\Http\Controllers\api\app\checklist;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Models\CheckList;

class CheckListController extends Controller
{
    public function internalCheckList()
    {
        $tr =  [];

        return response()->json([
            'ngos' => $tr
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function externalCheckList()
    {
        $locale = App::getLocale();
        $tr = CheckList::join('check_list_trans as ct', 'ct.check_list_id', '=', 'check_lists.id')
            ->where('ct.language_name', $locale)
            ->select('ct.value as name', 'check_lists.id', 'check_lists.acceptable_mimes', 'check_lists.acceptable_extensions', 'check_lists.description')
            ->orderBy('check_lists.id')
            ->get();
        return response()->json([
            'checklist' => $tr
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function store(Request $request)
    {
        $locale = App::getLocale();
        $tr =  [];
        return response()->json([
            'ngos' => $tr
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function update(Request $request)
    {
        $locale = App::getLocale();
        $tr =  [];
        return response()->json([
            'ngos' => $tr
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function destroy($id)
    {
        $locale = App::getLocale();
        $tr =  [];
        return response()->json([
            'ngos' => $tr
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
