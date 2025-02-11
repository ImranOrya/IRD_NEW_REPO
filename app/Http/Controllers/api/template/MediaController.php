<?php

namespace App\Http\Controllers\api\template;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    // public function show($storage, $folder, $filename)
    // {
    //     $path = storage_path('app/' . "{$storage}/{$folder}/{$filename}");

    //     if (!file_exists($path)) {
    //         return response()->json("File Not found");
    //     }
    //     return response()->file($path);
    // }
    // public function showPublic($storage, $access, $folder, $filename)
    // {
    //     $path = storage_path('app/' . "{$storage}/{$access}/{$folder}/{$filename}");

    //     if (!file_exists($path)) {
    //         return response()->json("File Not found");
    //     }
    //     return response()->file($path);
    // }
    // public function downloadDoc($storage, $folder, $folderType, $filename)
    // {
    //     $path = storage_path('app/' . "{$storage}/{$folder}/{$folderType}/{$filename}");
    //     if (!file_exists($path)) {
    //         return response()->json([
    //             'message' => __('app_translation.not_found'),
    //         ], 404, [], JSON_UNESCAPED_UNICODE);
    //     }

    //     return response()->file($path);
    // }
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
