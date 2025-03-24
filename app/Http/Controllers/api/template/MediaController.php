<?php

namespace App\Http\Controllers\api\template;

use App\Http\Controllers\Controller;
use App\Traits\Helper\HelperTrait;
use Illuminate\Http\Request;

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
    public function downloadPublicFile(Request $request)
    {
        $filePath = $request->input('path');
        $path = $this->getPublicPath($filePath);

        if (!file_exists($path)) {
            return response()->json([
                'message' => __('app_translation.not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        return response()->file($path);
    }
}
