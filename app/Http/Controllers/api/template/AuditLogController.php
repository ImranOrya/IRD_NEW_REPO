<?php

namespace App\Http\Controllers\api\template;

use App\Models\Ngo;
use App\Models\User;
use App\Models\Audit;
use App\Models\Donor;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{

    public function audits(Request $request, $page)
    {
        $locale = App::getLocale();
        $tr = [];
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page

        // Start building the query
        $query = [];

        $query = Audit::all()
            ->with('user'); // Eager load the user who performed the action

        // if ($locale === LanguageEnum::default->value) {
        //     $query = UsersEnView::query();
        // } else if ($locale === LanguageEnum::farsi->value) {
        //     $query = UsersFaView::query();
        // } else {
        //     $query = UsersPsView::query();
        // }
        // Apply date filtering conditionally if provided
        $startDate = $request->input('filters.date.startDate');
        $endDate = $request->input('filters.date.endDate');

        if ($startDate || $endDate) {
            // Apply date range filtering
            if ($startDate && $endDate) {
                $query->whereBetween('createdAt', [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->where('createdAt', '>=', $startDate);
            } elseif ($endDate) {
                $query->where('createdAt', '<=', $endDate);
            }
        }

        // Apply search filter if present
        $searchColumn = $request->input('filters.search.column');
        $searchValue = $request->input('filters.search.value');

        if ($searchColumn && $searchValue) {
            $allowedColumns = ['username', 'contact', 'email'];

            // Ensure that the search column is allowed
            if (in_array($searchColumn, $allowedColumns)) {
                $query->where($searchColumn, 'like', '%' . $searchValue . '%');
            }
        }

        // Apply sorting if present
        $sort = $request->input('filters.sort'); // Sorting column
        $order = $request->input('filters.order', 'asc'); // Sorting order (default is 'asc')

        // Apply sorting by provided column or default to 'created_at'
        if ($sort && in_array($sort, ['username', 'createdAt', 'status', 'job', 'destination'])) {
            $query->orderBy($sort, $order);
        } else {
            // Default sorting if no sort is provided
            $query->orderBy("createdAt", $order);
        }

        // Apply pagination (ensure you're paginating after sorting and filtering)
        $tr = $query->paginate($perPage, ['*'], 'page', $page);
        return response()->json(
            [
                "users" => $tr,
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function userType()
    {

        $arr = [
            'User',
            'NGO',
            'Donor'

        ];
        return response()->json(
            [
                $arr,
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function userList(Request $request)
    {
        $request->validate([
            'user_type' => 'required'
        ]);

        if ($request->user_type === 'User') {

            $user =   User::select('id', 'full_name as name')->get();

            return response()->json(
                [
                    'user' => $user,
                    'message' => __('app_translation.success'),

                ],
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        }
        if ($request->user_type === 'NGO') {
            $ngo = Ngo::select('id', 'username as name')->get();
            return response()->json(
                [
                    'user' => $ngo,
                    'message' => __('app_translation.success'),

                ],
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        }
        if ($request->user_type === 'Donor') {

            $donor = Donor::select('id', 'username as name')->get();
            return response()->json(
                [
                    'user' => $donor,
                    'message' => __('app_translation.success'),

                ],
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        } else {
            return response()->json(
                [
                    'message' => __('app_translation.not_found'),

                ],
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        }
    }
    public function tableList(Request $request)
    {
        $request->validate([
            'user_type' => 'required',
            'user_id' => 'required|integer'
        ]);

        $type = match ($request->user_type) {
            'User' => 'App\Models\User',
            'NGO' => 'App\Models\Ngo',
            'Donor' => 'App\Models\Donor',
            default => null
        };

        if (!$type) {
            return response()->json(
                [
                    'message' => __('app_translation.not_found'),
                ],
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        }

        // Get distinct auditable_type values
        $table = Audit::select('auditable_type')
            ->where('user_type', $type)
            ->where('user_id', $request->user_id)
            ->distinct()  // Ensure the values are distinct
            ->pluck('auditable_type');  // Return as a simple array

        // Extract model names from the fully qualified class names
        $modelNames = $table->map(function ($item) {
            return basename(str_replace('\\', '/', $item));  // Get only the model name
        });

        return response()->json(
            [
                'table' => $modelNames,  // This is the array of unique model names
                'message' => __('app_translation.success'),
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }



    public function columnList(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string'
        ]);

        // Dynamically resolve the model class name
        $modelClass = '\App\Models\\' . ucfirst($request->table_name);  // Capitalize first letter to match model name

        // Check if the model class exists
        if (!class_exists($modelClass)) {
            return response()->json(
                [
                    'message' => __('app_translation.model_not_found'),
                ],
                404,
                [],
                JSON_UNESCAPED_UNICODE
            );
        }

        // Get the table name associated with the model
        $tableName = (new $modelClass)->getTable();

        // Check if the table exists
        if (!Schema::hasTable($tableName)) {
            return response()->json(
                [
                    'message' => __('app_translation.table_not_found'),
                ],
                404,
                [],
                JSON_UNESCAPED_UNICODE
            );
        }

        // Get the column names for the table
        $columns = Schema::getColumnListing($tableName);

        return response()->json(
            [
                'columns' => $columns,  // Return the column names
                'message' => __('app_translation.success'),
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
}
