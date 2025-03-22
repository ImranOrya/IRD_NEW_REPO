<?php

namespace App\Http\Controllers\api\template;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function downloadFile(Request $request)
    {
        $filePath = $request->input('path');
        $path = storage_path() . "/app/{$filePath}";
        if (!file_exists($path)) {
            return response()->json([
                'message' => __('app_translation.not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->file($path);
    }
}
