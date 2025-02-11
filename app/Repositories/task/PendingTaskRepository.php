<?php

namespace App\Repositories\Task;

use App\Models\PendingTask;
use App\Models\PendingTaskContent;
use App\Models\PendingTaskDocument;
use App\Repositories\Task\PendingTaskRepositoryInterface;

class PendingTaskRepository implements PendingTaskRepositoryInterface
{
    public function storeTask($authUser, $task_type, $task_type_id, $step, $content)
    {
        $user_id = $authUser->id;
        $role = $authUser->role_id;

        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $task_type_id)
            ->first(); // Retrieve the first matching record

        if ($task) {
            $pendingContent = PendingTaskContent::where('pending_task_id', $task->id)
                ->first(); // Get the maximum step value
            if ($pendingContent) {
                // Update prevouis content
                $pendingContent->step = $step;
                $pendingContent->content = $content;
                $pendingContent->save();
            } else {
                // If no content found
                PendingTaskContent::create([
                    'step' => $step,
                    'content' => $content,
                    'pending_task_id' => $task->id
                ]);
            }
        } else {
            $task =  PendingTask::create([
                'user_id' => $user_id,
                'user_type' => $role,
                'task_type' => $task_type,
                'task_id' => $task_type_id
            ]);
            PendingTaskContent::create([
                'step' => 1,
                'content' => $content,
                'pending_task_id' => $task->id
            ]);
        }

        return $task;
    }
    public function deletePendingTask($authUser, $task_type, $task_type_id)
    {
        $user_id = $authUser->id;
        $role = $authUser->role_id;

        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $task_type_id)
            ->first();
        if (!$task) {
            return false;
        } else {
            PendingTaskContent::where('pending_task_id', $task->id)->delete();
            $task->delete();
        }

        return true;
    }

    public function pendingTaskExist($authUser, $task_type, $task_type_id)
    {
        $user_id = $authUser->id;
        $role = $authUser->role_id;
        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $task_type_id)
            ->first();

        return $task;
    }

    public function pendingTaskDocumentQuery($pending_task_id)
    {
        return PendingTaskDocument::where('pending_task_id', $pending_task_id);
    }
}
