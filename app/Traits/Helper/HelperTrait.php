<?php

namespace App\Traits\Helper;

use App\Models\CheckList;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

trait HelperTrait
{
    public function createChunkUploadFilename(UploadedFile $file)
    {
        return Str::uuid() . "." . $file->getClientOriginalExtension();
    }
    public function getTempFullPath()
    {
        return storage_path() . "/app/temp/";
    }
    public function getTempFilePath($fileName)
    {
        return "temp/{$fileName}";
    }

    public function tempFileExist($filePath)
    {
        return file_exists(storage_path() . "/app/{$filePath}");
    }

    public function deleteTempFile($filePath)
    {
        return unlink(storage_path() . "/app/{$filePath}");
    }
    public function ngoRegisterFolder($ngo_id, $agreement_id, $check_list_id)
    {
        return storage_path() . "/app/private/ngos/{$ngo_id}/register/{$agreement_id}/{$check_list_id}/";
    }
    public function ngoRegisterDBPath($ngo_id, $agreement_id, $check_list_id, $fileName)
    {
        return "private/ngos/{$ngo_id}/register/{$agreement_id}/{$check_list_id}/" . $fileName;
    }
    public function checkListCheck($file, $checklist_id)
    {
        // 1. Validate check exist
        $checklist = CheckList::find($checklist_id);
        if (!$checklist) {
            return response()->json([
                'message' => __('app_translation.checklist_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();
        $allowedExtensions = explode(',', $checklist->acceptable_extensions);
        $allowedSize = $checklist->file_size * 1024; // Converted to byte
        $found = false;
        foreach ($allowedExtensions as $allowedExtension) {
            if ($allowedExtension == $extension) {
                if ($fileSize > $allowedSize) {
                    return response()->json([
                        'message' => __('app_translation.file_size_error') . " " . $allowedSize,
                    ], 422, [], JSON_UNESCAPED_UNICODE);
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            return response()->json([
                'message' => __('app_translation.allowed_file_types') . " " . $checklist->acceptable_extensions,
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        return $found;
    }
}
