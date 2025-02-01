<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Enums\Type\TaskTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\PendingTask;
use App\Models\PendingTaskContent;
use Illuminate\Http\Request;

class DeletesNgoController extends Controller
{
    //



    public function destroyPersonalDetail(Request $request, $id)
    {
        $user = $request->user();
        $user_id = $user->id;
        $role = $user->role_id;
        $task_type = TaskTypeEnum::ngo_registeration;

        // Fetch the pending task
        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $id)
            ->first(); // Fetch the first matching record

        if (!$task) {
            return response()->json([
                "message" => __('app_translation.not_found'),
            ], 404);
        }

        // Delete related PendingTaskContent records
        PendingTaskContent::where('pending_task_id', $task->id)->delete();

        // Delete the task itself
        $task->delete();

        return response()->json([
            "message" => __('app_translation.success'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
