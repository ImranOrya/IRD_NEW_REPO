<?php

namespace App\Traits\Helper;

use App\Models\CheckList;
use Illuminate\Support\Str;
use App\Models\UserLoginLog;
use App\Enums\PermissionEnum;
use App\Models\NgoPermission;
use App\Enums\SubPermissionEnum;
use App\Jobs\LogUserLoginJob;
use App\Models\NgoPermissionSub;
use Illuminate\Http\UploadedFile;
use Sway\Utils\StringUtils;

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

    public function deleteFile($filePath)
    {
        $deletePath = storage_path('app/' . "{$filePath}");
        if (is_file($deletePath)) {
            return unlink($deletePath);
        }
        return false;
    }

    public function getPublicPath($filePath)
    {
        return storage_path() . "/app/public/{$filePath}";
    }
    public function getProfilePath($filePath)
    {
        return storage_path() . "/app/private/profile/{$filePath}";
    }
    public function deleteTempFile($filePath)
    {
        return unlink(storage_path() . "/app/{$filePath}");
    }
    public function ngoRegisterFolder($ngo_id, $agreement_id, $check_list_id)
    {
        return storage_path() . "/app/private/ngos/ngo_{$ngo_id}/register/agreement_{$agreement_id}/checlist_{$check_list_id}/";
    }
    public function ngoRegisterDBPath($ngo_id, $agreement_id, $check_list_id, $fileName)
    {
        return "private/ngos/ngo_{$ngo_id}/register/agreement_{$agreement_id}/checlist_{$check_list_id}/" . $fileName;
    }
    public function checkFileWithList($file, $checklist_id)
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
    /**
     * Converts a string to title case.
     *
     * @param string $model
     * @return string
     */
    public static function getModelName(string $model): string
    {
        // Generate a unique key for the access token, e.g., access_token:<user_id>
        $firstSlashPos = strpos($model, '\\');
        $secondSlashPos = strpos($model, '\\', $firstSlashPos + 1);

        // Get the part after the second backslash
        $className = substr($model, $secondSlashPos + 1);
        return $className;
    }
    public function ngoPermissions($ngo_id)
    {
        NgoPermission::create([
            "view" => true,
            "edit" => true,
            "delete" => true,
            "add" => true,
            "ngo_id" => $ngo_id,
            "permission" => PermissionEnum::dashboard->value,
        ]);

        $ngoPermission = NgoPermission::create([
            "visible" => false,
            "view" => true,
            "edit" => true,
            "delete" => true,
            "add" => true,
            "ngo_id" => $ngo_id,
            "permission" => PermissionEnum::ngo->value,
        ]);
        foreach (SubPermissionEnum::NGO as $id => $role) {
            if ($id == SubPermissionEnum::ngo_status->value) {
                continue;
            }
            if ($id == SubPermissionEnum::ngo_update_account_password->value) {
                continue;
            }
            NgoPermissionSub::factory()->create([
                "edit" => true,
                "delete" => true,
                "add" => true,
                "view" => true,
                "ngo_permission_id" => $ngoPermission->id,
                "sub_permission_id" => $id,
            ]);
        }
    }
    public function storeUserLog($request, $userable_id, $userable_type, $action)
    {
        $userAgent = $request->header('User-Agent');
        $browser = StringUtils::extractBrowserInfo($userAgent);
        $device = StringUtils::extractDeviceInfo($userAgent);
        LogUserLoginJob::dispatch(
            $userAgent,
            $userable_id,
            $userable_type,
            $action,
            $request->ip(),
            $browser,
            $device,
        );
    }
}
