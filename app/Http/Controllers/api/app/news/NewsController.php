<?php

namespace App\Http\Controllers\api\app\news;

use App\Enums\LanguageEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\app\news\NewsStoreRequest;
use App\Http\Requests\app\news\NewsUpdateRequest;
use App\Models\News;
use App\Models\NewsDocument;
use App\Models\NewsTran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    public function authNewses(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page
        $locale = App::getLocale();

        $query =  DB::table('news as n')
            ->join('news_trans as ntr', 'ntr.news_id', '=', 'n.id')
            ->join('news_type_trans as ntt', 'ntt.news_type_id', '=', 'n.news_type_id')
            ->join('priority_trans as pt', 'pt.priority_id', '=', 'n.priority_id')
            ->leftJoin('news_documents as nd', 'nd.news_id', '=', 'n.id')
            ->where('ntr.language_name', $locale)
            ->where('pt.language_name', $locale)
            ->where('ntt.language_name', $locale)
            ->select(
                'n.id as id',
                'n.visible',
                'n.date',
                'n.visibility_date',
                'n.news_type_id',
                'ntt.value AS news_type',
                'n.priority_id',
                'pt.value AS priority',
                'ntr.title',
                'ntr.contents',
                'nd.url AS image',  // Assuming you want the first image URL
                'n.created_at'
            );


        $this->applyDate($query, $request);
        $this->applyFilters($query, $request);
        $this->applySearch($query, $request);

        $result = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json(
            [
                "newses" => $result,
            ]
        );
    }

    public function authNews(Request $request, $id)
    {
        $locale = App::getLocale();

        // Fetch the news along with its related data using eager loading
        $news = News::with(['newsDocument', 'newsType.newsTypeTran', 'priority.priorityTran'])
            ->find($id);

        if (!$news) {
            return response()->json(['message' => 'News not found'], 404);
        }

        // Fetch translations for the news
        $translations = NewsTran::where('news_id', $id)
            ->whereIn('language_name', ['en', 'ps', 'fa'])
            ->get()
            ->keyBy('language_name');

        // Retrieve individual translations or set defaults
        $newsEnTran = $translations->get('en', (object) ['title' => '', 'contents' => '']);
        $newsPsTran = $translations->get('ps', (object) ['title' => '', 'contents' => '']);
        $newsFaTran = $translations->get('fa', (object) ['title' => '', 'contents' => '']);

        // Prepare the response
        return response()->json([
            'news' => [
                'id' => $news->id,
                'title_english' => $newsEnTran->title,
                'title_pashto' => $newsPsTran->title,
                'title_farsi' => $newsFaTran->title,
                'content_english' => $newsEnTran->contents,
                'content_pashto' => $newsPsTran->contents,
                'content_farsi' => $newsFaTran->contents,
                'type' => [
                    'id' => $news->news_type_id,
                    'value' => $news->newsType->newsTypeTran
                        ->where('language_name', $locale)
                        ->first()->value ?? 'Type not found'
                ],
                'priority' => [
                    'id' => $news->priority_id,
                    'value' => $news->priority->priorityTran
                        ->where('language_name', $locale)
                        ->first()->value ?? 'Priority not found'
                ],
                'cover_pic' => [
                    'name' => $news->newsDocument->name ?? '',
                    'path' => $news->newsDocument->url ?? '',
                ],
                'user' => User::select('id', 'username')
                    ->where('id', $news->user_id)
                    ->first()
                    ->username,
                'date' => $news->date,
                'visible' => $news->visible,
                'visibility_date' => $news->visibility_date,
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function publicNewses(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page
        $locale = App::getLocale();
        $query =  DB::table('news as n')
            ->join('news_trans as ntr', 'ntr.news_id', '=', 'n.id')
            ->join('news_type_trans as ntt', 'ntt.news_type_id', '=', 'n.news_type_id')
            ->join('priority_trans as pt', 'pt.priority_id', '=', 'n.priority_id')
            ->leftJoin('news_documents as nd', 'nd.news_id', '=', 'n.id')
            ->where('ntr.language_name', $locale)
            ->where('pt.language_name', $locale)
            ->where('ntt.language_name', $locale)
            ->where('n.visible', 1)
            ->select(
                'n.id as id',
                'n.visible',
                'n.date',
                'n.news_type_id',
                'ntt.value AS news_type',
                'n.priority_id',
                'pt.value AS priority',
                'ntr.title',
                'ntr.contents',
                'nd.url AS image',  // Assuming you want the first image URL
                'n.created_at'
            );

        $this->applyDate($query, $request);
        $this->applyFilters($query, $request);
        $this->applySearch($query, $request);

        $result = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json(
            [
                "newses" => $result,
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function publicNews(Request $request, $id)
    {
        $locale = App::getLocale();
        $query =  DB::table('news as n')
            ->join('users as u', 'u.id', '=', 'n.user_id')
            ->join('news_trans as ntr', 'ntr.news_id', '=', 'n.id')
            ->join('news_type_trans as ntt', 'ntt.news_type_id', '=', 'n.news_type_id')
            ->join('priority_trans as pt', 'pt.priority_id', '=', 'n.priority_id')
            ->leftJoin('news_documents as nd', 'nd.news_id', '=', 'n.id')
            ->where('ntr.language_name', $locale)
            ->where('pt.language_name', $locale)
            ->where('ntt.language_name', $locale)
            ->where('n.visible', 1)
            ->where('n.id', $id)
            ->select(
                'n.id',
                'n.date',
                'n.news_type_id',
                'ntt.value AS news_type',
                'n.priority_id',
                'pt.value AS priority',
                'ntr.title',
                'ntr.contents',
                'u.username as user',
                'nd.url AS image'  // Assuming you want the first image URL
            )
            ->first();
        return response()->json([
            "news" => $query

        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(NewsStoreRequest $request)
    {
        $validatedData = $request->validated();
        $authUser = $request->user();

        // Begin transaction
        DB::beginTransaction();

        $news = News::create([
            "user_id" => $authUser->id,
            "visible" => true,
            "date" => $validatedData["date"],
            "visibility_date" => $request->visibility_date,
            "priority_id" => $validatedData["priority"],
            "news_type_id" => $validatedData["type"]
        ]);
        NewsTran::create([
            "news_id" => $news->id,
            "language_name" => LanguageEnum::default->value,
            "title" => $validatedData["title_english"],
            "contents" => $validatedData["content_english"],
        ]);
        NewsTran::create([
            "news_id" => $news->id,
            "language_name" => LanguageEnum::pashto->value,
            "title" => $validatedData["title_pashto"],
            "contents" => $validatedData["content_pashto"],
        ]);
        NewsTran::create([
            "news_id" => $news->id,
            "language_name" => LanguageEnum::farsi->value,
            "title" => $validatedData["title_farsi"],
            "contents" => $validatedData["content_farsi"],
        ]);

        // 3. Store documents
        $document = $this->storeDocument($request, "public", "news", 'cover_pic');
        NewsDocument::create([
            "news_id" => $news->id,
            "url" => $document['path'],
            "extintion" => $document['extintion'],
            "name" => $document['name'],
        ]);

        // If everything goes well, commit the transaction
        DB::commit();
        // Return a success response

        $title = $validatedData["title_english"];
        $contents = $validatedData["content_english"];
        $locale = App::getLocale();
        if ($locale === LanguageEnum::farsi->value) {
            $title = $validatedData["title_farsi"];
            $contents = $validatedData["content_farsi"];
        } else if ($locale === LanguageEnum::pashto->value) {
            $title = $validatedData["title_pashto"];
            $contents = $validatedData["content_pashto"];
        }

        return response()->json(
            [
                'message' => __('app_translation.success'),
                'news' => [
                    "id" => $news->id,
                    "user" => $authUser->username,
                    "visible" => true,
                    "visibility_date" => $request->visibility_date,
                    "title" => $title,
                    "news_type" => $request->type_name,
                    "priority" => $request->priority_name,
                    "date" => $validatedData["date"],
                    "created_at" => $news->created_at,
                    "contents" => $contents,
                    "image" => $document['path'],
                ]
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function update(NewsUpdateRequest $request)
    {
        $request->validated();
        $authUser = $request->user();

        $id = $request->id;
        // Find the news record or throw an exception if not found
        $news = News::find($id);
        if (!$news) {
            return response()->json(['message' => __('app_translation.news_not_found')], 404);
        }

        // Begin transaction
        DB::beginTransaction();
        $news->update([
            "user_id" => $authUser->id,
            "visible" => $request->visible,
            "date" => $request->date,
            "visibility_date" => $request->visibility_date,
            "priority_id" => $request->priority,
            "news_type_id" => $request->type
        ]);

        // Update translations
        NewsTran::updateOrCreate(
            ["news_id" => $news->id, "language_name" => LanguageEnum::default->value],
            ["title" => $request->title_english, "contents" => $request->content_english]
        );

        NewsTran::updateOrCreate(
            ["news_id" => $news->id, "language_name" => LanguageEnum::pashto->value],
            ["title" => $request->title_pashto, "contents" => $request->content_pashto]
        );

        NewsTran::updateOrCreate(
            ["news_id" => $news->id, "language_name" => LanguageEnum::farsi->value],
            ["title" => $request->title_farsi, "contents" => $request->content_farsi]
        );

        // Update document if a new one is uploaded
        $existingDocument = NewsDocument::where('news_id', $news->id)->first();
        if (!$existingDocument) {
            return response()->json(['message' => __('app_translation.cover_pic_not_found')], 404);
        }
        // 1. Check cover_pic is changed
        if ($existingDocument->url !== $request->cover_pic) {
            $path = storage_path('app/' . $existingDocument->url);

            if (file_exists($path)) {
                unlink($path);
            }
            // 1.2 update document
            $document = $this->storeDocument($request, "public", "news", 'cover_pic');
            $existingDocument->url = $document['path'];
            $existingDocument->extintion = $document['extintion'];
            $existingDocument->name = $document['name'];
            $existingDocument->save();
        }

        // Commit transaction
        DB::commit();

        // Return a success response
        return response()->json(
            [
                'message' => __('app_translation.success')
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
    public function destroy($id)
    {
        $news = News::find($id);
        if ($news) {
            // Begin transaction
            DB::beginTransaction();
            // 1. Delete Translation
            NewsTran::where('news_id', $news->id)->delete();
            $existingDocument = NewsDocument::where('news_id', $news->id)->first();
            // Delete documents
            $path = storage_path('app/' . $existingDocument->url);

            if (file_exists($path)) {
                unlink($path);
            }
            $existingDocument->delete();
            $news->delete();

            // Commit transaction
            DB::commit();
            return response()->json([
                'message' => __('app_translation.success'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else
            return response()->json([
                'message' => __('app_translation.failed'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
    }


    // date function 
    protected function applyDate($query, $request)
    {
        // Apply date filtering conditionally if provided
        $startDate = $request->input('filters.date.startDate');
        $endDate = $request->input('filters.date.endDate');

        if ($startDate) {
            $query->where('n.date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('n.date', '<=', $endDate);
        }
    }
    // search function 
    protected function applySearch($query, $request)
    {

        $searchColumn = $request->input('filters.search.column');
        $searchValue = $request->input('filters.search.value');

        if ($searchColumn && $searchValue) {
            $allowedColumns = ['title', 'contents'];

            // Ensure that the search column is allowed
            if (in_array($searchColumn, $allowedColumns)) {
                $query->where($searchColumn, 'like', '%' . $searchValue . '%');
            }
        }
    }
    // filter function
    protected function applyFilters($query, $request)
    {
        $sort = $request->input('filters.sort'); // Sorting column
        $order = $request->input('filters.order', 'asc'); // Sorting order (default 

        if ($sort && in_array($sort, ['news_type_id', 'priority_id', 'visible', 'visibility_date', 'date'])) {
            $query->orderBy($sort, $order);
        } else {
            // Default sorting if no sort is provided
            $query->orderBy("created_at", 'desc');
        }
    }
}
