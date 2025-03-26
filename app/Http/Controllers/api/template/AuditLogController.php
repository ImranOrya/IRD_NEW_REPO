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
        $tr = [];
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page

        // Start building the query
        $query = [];

        $query = Audit::all();

        // Apply pagination (ensure you're paginating after sorting and filtering)
        $tr = $query->paginate($perPage, ['*'], 'page', $page);
        return response()->json(
            [
                "audiths" => $tr,
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function userType()
    {
        $arr = [
            ["name" => "User"],
            ["name" => "Ngo"],
            ["name" => "Donor"],
        ];
        return response()->json(
            $arr,
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }

    public function users(Request $request)
    {
        $request->validate([
            'user_type' => 'required'
        ]);
        $tr = [];
        if ($request->user_type === 'User') {
            $tr = User::select('id', 'full_name as name')->get();
        } else if ($request->user_type === 'Ngo') {
            $tr = Ngo::select('id', 'username as name')->get();
        } else if ($request->user_type === 'Donor') {
            $tr = Donor::select('id', 'username as name')->get();
        }

        return response()->json(
            $tr,
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
    public function tableList(Request $request)
    {
        $request->validate([
            'user_type' => 'required',
            'user_id' => 'required|integer'
        ]);

        // Get distinct auditable_type values
        $table = Audit::select('auditable_type')
            ->where('user_type', $request->user_type)
            ->where('user_id', $request->user_id)
            ->distinct()
            ->pluck('auditable_type')
            ->map(fn($item) => ['name' => $item]);

        return response()->json(
            $table,
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
        $formattedColumns = array_map(fn($column) => ['name' => $column], $columns);

        return response()->json(
            $formattedColumns,
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
}
