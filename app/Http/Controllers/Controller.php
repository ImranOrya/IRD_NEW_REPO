<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Contact;
use App\Models\Translate;
use Illuminate\Support\Str;
use App\Models\NgoTypeTrans;
use Illuminate\Http\Request;
use App\Models\StatusTypeTran;
use App\Models\UserLoginLog;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

abstract class Controller
{
    public function storeProfile(Request $request, $dynamic_path = 'user-profile', $columnName = 'profile')
    {
        // 1. If storage not exist create it.
        $path = storage_path() . '/app/private/' . $dynamic_path . '/';
        // Checks directory exist if not will be created.
        !is_dir($path) && mkdir($path, 0777, true);

        // 2. Store image in filesystem
        $fileName = null;
        if ($request->hasFile($columnName)) {
            $file = $request->file($columnName);
            if ($file != null) {
                $fileName = Str::uuid() . '.' . $file->extension();
                $file->move($path, $fileName);

                return 'private/' . $dynamic_path . '/' . $fileName;
            }
        }
        return null;
    }
    // public function storeDocument(Request $request, $access = 'private', $folder, $docName = 'document')
    // {
    //     // 1. If storage not exist create it.
    //     $path = storage_path() . "/app/{$access}/documents/{$folder}/";
    //     // Checks directory exist if not will be created.
    //     !is_dir($path) && mkdir($path, 0777, true);

    //     // 2. Store image in filesystem
    //     $fileName = null;
    //     if ($request->hasFile($docName)) {
    //         $file = $request->file($docName);
    //         $fileExtention = $file->extension();
    //         if ($file != null) {
    //             $fileName = Str::uuid() . '.' . $fileExtention;
    //             $file->move($path, $fileName);

    //             return [
    //                 'path' => "{$access}/documents/{$folder}/" . $fileName,
    //                 'name' => $file->getClientOriginalName(),
    //                 'extintion' => $fileExtention,
    //             ];
    //         }
    //     }
    //     return null;
    // }
    // public function getTableTranslations($className, $locale, $order, $columns = ['value as name', 'translable_id as id', 'created_at as createdAt'])
    // {
    //     return Translate::where('translable_type', '=', $className)->where('language_name', '=', $locale)->select($columns)->orderBy('id', $order)->get();
    // }
    // public function getTableTranslationsWithJoin($className, $locale, $order, $columns = ['value as name', 'translable_id as id', 'created_at as createdAt'])
    // {
    //     // Dynamically get the related model's table (e.g., 'destinations' for Destination model)
    //     $relatedTable = (new $className())->getTable();

    //     // Perform the query to join the Translate table with the related model table
    //     return Translate::where('translable_type', '=', $className)
    //         ->where('language_name', '=', $locale)
    //         ->join($relatedTable, function ($join) use ($relatedTable) {
    //             // Join Translate table with the related model (e.g., 'destinations') based on translable_id
    //             $join->on('translates.translable_id', '=', "{$relatedTable}.id");
    //         })
    //         ->select($columns)
    //         ->orderBy('translates.id', $order)
    //         ->get();
    // }
    // public function getTranslationWithNameColumn($model, $className)
    // {
    //     $item = null;
    //     $locale = App::getLocale();
    //     if ($model->name) {
    //         if ($locale === 'en') {
    //             $item = $model->name;
    //         } else {
    //             $data = Translate::where('translable_id', '=', $model->id)->where('translable_type', '=', $className)->where('language_name', '=', $locale)->select('value')->first();
    //             if ($data) {
    //                 $item = $data->value;
    //             }
    //         }
    //     }
    //     return $item;
    // }
    // public function TranslateFarsi($value, $translable_id, $translable_type): void
    // {
    //     Translate::create([
    //         'value' => $value,
    //         'language_name' => 'fa',
    //         'translable_type' => $translable_type,
    //         'translable_id' => $translable_id,
    //     ]);
    // }
    // public function TranslatePashto($value, $translable_id, $translable_type): void
    // {
    //     Translate::create([
    //         'value' => $value,
    //         'language_name' => 'ps',
    //         'translable_type' => $translable_type,
    //         'translable_id' => $translable_id,
    //     ]);
    // }



    // /**
    //  * Retrieve status translations.
    //  * 
    //  * @return \Illuminate\Support\Collection
    //  */
    // protected function getStatusTrans()
    // {
    //     $locale = App::getLocale();
    //     $cacheKey = 'status_type_tran_' . $locale;

    //     return Cache::remember($cacheKey, 86400, function () use ($locale) {
    //         return StatusTypeTran::select('name', 'status_type_id')
    //             ->where('language_name', $locale)
    //             ->get();
    //     });
    // }

    // /**
    //  * Retrieve NGO type translations.
    //  * 
    //  * @return \Illuminate\Support\Collection
    //  */
    // protected function getNgoTypeTrans()
    // {
    //     $locale = App::getLocale();
    //     $cacheKey = 'ngo_type_tran_' . $locale;

    //     return Cache::remember($cacheKey, 86400, function () use ($locale) {
    //         return NgoTypeTrans::select('value as name', 'ngo_type_id')
    //             ->where('language_name', $locale)
    //             ->get();
    //     });
    // }

    protected function userLoginLog($request, $user_id, $userType, $result)
    {


        UserLoginLog::create([
            'user_id'            => $user_id,
            'action'             => 'login',
            'user_type'          => $userType, // Make sure this field exists in the table and model
            'local_ip_address'   => $request->ip(), // Corrected spelling
            'public_ip_address'  => $request->header('host') ?? $request->ip(),
            'computer_name'      => $request->header('user-agent') ?? '', // Assuming client passes host_name in header
            'result'             => $result,
        ]);
    }
}
