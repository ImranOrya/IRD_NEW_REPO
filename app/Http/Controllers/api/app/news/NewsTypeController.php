<?php

namespace App\Http\Controllers\api\app\news;

use App\Models\NewsType;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;

class NewsTypeController extends Controller
{
    public function newsTypes()
    {
        $locale = App::getLocale();
        $tr = NewsType::join("news_type_trans", function ($join) use ($locale) {
            // Join Translate table with the related model (e.g., 'destinations') based on translable_id
            $join->on('news_type_trans.news_type_id', '=', "news_types.id")
                ->where('news_type_trans.language_name', '=', $locale);
        })
            ->select("news_type_trans.value AS name", 'news_types.id',)
            ->get();
        return response()->json($tr, 200, [], JSON_UNESCAPED_UNICODE);
    }
    // public function store(NewsStoreRequest $request)
    // {
    //     $payload = $request->validated();
    //     // 1. Create
    //     $job = ModelJob::create([
    //         "name" => $payload["english"]
    //     ]);
    //     if ($job) {
    //         // 1. Translate
    //         $this->TranslateFarsi($payload["farsi"], $job->id, ModelJob::class);
    //         $this->TranslatePashto($payload["pashto"], $job->id, ModelJob::class);
    //         // Get local
    //         $locale = App::getLocale();
    //         if ($locale === LanguageEnum::default->value) {
    //             return response()->json([
    //                 'message' => __('app_translation.success'),
    //                 'job' => [
    //                     "id" => $job->id,
    //                     "name" => $job->name,
    //                     "createdAt" => $job->created_at
    //                 ],
    //             ], 200, [], JSON_UNESCAPED_UNICODE);
    //         } else if ($locale === LanguageEnum::pashto->value) {
    //             return response()->json([
    //                 'message' => __('app_translation.success'),
    //                 'job' => [
    //                     "id" => $job->id,
    //                     "name" => $payload["pashto"],
    //                     "createdAt" => $job->created_at
    //                 ]
    //             ], 200, [], JSON_UNESCAPED_UNICODE);
    //         } else {
    //             return response()->json([
    //                 'message' => __('app_translation.success'),
    //                 'job' => [
    //                     "id" => $job->id,
    //                     "name" => $payload["farsi"],
    //                     "createdAt" => $job->created_at
    //                 ]
    //             ], 200, [], JSON_UNESCAPED_UNICODE);
    //         }

    //         return response()->json([
    //             'message' => __('app_translation.success'),
    //         ], 200, [], JSON_UNESCAPED_UNICODE);
    //     } else
    //         return response()->json([
    //             'message' => __('app_translation.failed'),
    //         ], 400, [], JSON_UNESCAPED_UNICODE);
    // }
    // public function destroy($id)
    // {
    //     $job = ModelJob::find($id);
    //     if ($job) {
    //         // 1. Delete Translation
    //         Translate::where("translable_id", "=", $id)
    //             ->where('translable_type', '=', ModelJob::class)->delete();
    //         $job->delete();
    //         return response()->json([
    //             'message' => __('app_translation.success'),
    //         ], 200, [], JSON_UNESCAPED_UNICODE);
    //     } else
    //         return response()->json([
    //             'message' => __('app_translation.failed'),
    //         ], 400, [], JSON_UNESCAPED_UNICODE);
    // }
    // public function news($id)
    // {
    //     $job = ModelJob::find($id);
    //     if ($job) {
    //         $data = [
    //             "id" => $job->id,
    //             "en" => $job->name,
    //         ];
    //         $translations = Translate::where("translable_id", "=", $id)
    //             ->where('translable_type', '=', ModelJob::class)->get();
    //         foreach ($translations as $translation) {
    //             $data[$translation->language_name] = $translation->value;
    //         }
    //         return response()->json([
    //             'job' =>  $data,
    //         ], 200, [], JSON_UNESCAPED_UNICODE);
    //     } else
    //         return response()->json([
    //             'message' => __('app_translation.failed'),
    //         ], 400, [], JSON_UNESCAPED_UNICODE);
    // }
    // public function update(NewsStoreRequest $request)
    // {
    //     $payload = $request->validated();
    //     // This validation not exist in JobStoreRequest
    //     $request->validate([
    //         "id" => "required"
    //     ]);
    //     // 1. Find
    //     $job = ModelJob::find($request->id);
    //     if ($job) {
    //         $locale = App::getLocale();
    //         // 1. Update
    //         $job->name = $payload['english'];
    //         $job->save();
    //         $translations = Translate::where("translable_id", "=", $job->id)
    //             ->where('translable_type', '=', ModelJob::class)->get();
    //         foreach ($translations as $translation) {
    //             if ($translation->language_name === LanguageEnum::farsi->value) {
    //                 $translation->value = $payload['farsi'];
    //             } else if ($translation->language_name === LanguageEnum::pashto->value) {
    //                 $translation->value = $payload['pashto'];
    //             }
    //             $translation->save();
    //         }
    //         if ($locale === LanguageEnum::pashto->value) {
    //             $job->name = $payload['pashto'];
    //         } else if ($locale === LanguageEnum::farsi->value) {
    //             $job->name = $payload['farsi'];
    //         }
    //         return response()->json([
    //             'message' => __('app_translation.success'),
    //             'job' => $job,
    //         ], 200, [], JSON_UNESCAPED_UNICODE);
    //     } else
    //         return response()->json([
    //             'message' => __('app_translation.failed'),
    //         ], 400, [], JSON_UNESCAPED_UNICODE);
    // }
}
