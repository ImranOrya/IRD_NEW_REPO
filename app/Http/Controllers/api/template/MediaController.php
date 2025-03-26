<?php

namespace App\Http\Controllers\api\template;

use App\Http\Controllers\Controller;
use App\Traits\Helper\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    use HelperTrait;
    public function downloadProfile(Request $request)
    {
        $filePath = $request->input('path');
        $path = $this->getProfilePath($filePath);
        if (!file_exists($path)) {
            return response()->json([
                'message' => __('app_translation.not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        return response()->file($path);
    }
    public function ngoMediadownload(Request $request)
    {
        $filePath = $request->input('path');
        $path = $this->getAppPath('private/' . $filePath);
        if (!file_exists($path)) {
            return response()->json([
                'message' => __('app_translation.not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        return response()->file($path);
    }
    public function tempMediadownload(Request $request)
    {
        $filePath = $request->input('path');
        $path = $this->getAppPath($filePath);
        if (!file_exists($path)) {
            return response()->json([
                'message' => __('app_translation.not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        return response()->file($path);
    }
    public function downloadPublicFile(Request $request)
    {
        $filePath = $request->input('path');
        $path = $this->getPublicPath($filePath);

        if (!file_exists($path)) {
            return response()->json([
                'message' => __('app_translation.not_found'),
                'path' => $path,
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        return response()->file($path);
    }
}
