<?php

namespace App\Repositories\Task;

use App\Models\Agreement;
use App\Models\AgreementDocument;
use App\Models\Document;
use Exception;
use App\Models\PendingTask;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Models\PendingTaskContent;
use App\Traits\Helper\HelperTrait;
use App\Models\PendingTaskDocument;


class PendingTaskRepository implements PendingTaskRepositoryInterface
{
    use HelperTrait;

    public function storeTaskContent(PendingTask $task, $step, $content)
    {
        $pendingContent = PendingTaskContent::where('pending_task_id', $task->id)
            ->select('id', 'step', 'content', 'pending_task_id', 'created_at')
            ->first(); // Get the maximum step value
        if ($pendingContent) {
            // Update prevouis content
            $pendingContent->step = $step;
            $pendingContent->content = $content;
            $pendingContent->save();
        } else {
            // If no content found
            $pendingContent = PendingTaskContent::create([
                'step' => $step,
                'content' => $content,
                'pending_task_id' => $task->id
            ]);
        }

        return $pendingContent;
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
    public function findTask($pending_task_id)
    {
        return PendingTask::find($pending_task_id);
    }

    public function pendingTaskDocumentQuery($pending_task_id)
    {
        return PendingTaskDocument::where('pending_task_id', $pending_task_id);
    }

    public function storeTask($authUser, $task_type, $task_type_id)
    {
        $user_id = $authUser->id;
        $role = $authUser->role_id;

        $task = $this->pendingTaskExist($authUser, $task_type, $task_type_id);
        if (!$task) {
            $task =  PendingTask::create([
                'user_id' => $user_id,
                'user_type' => $role,
                'task_type' => $task_type,
                'task_id' => $task_type_id
            ]);
        }
        return $task;
    }
    public function fileStore(UploadedFile $file, Request $request, $task_type, $check_list_id, $task_type_id)
    {
        $fileActualName = $file->getClientOriginalName();
        $fileName = $this->createChunkUploadFilename($file);
        $fileSize = $file->getSize();
        $finalPath = $this->getTempFullPath();
        $mimetype = $file->getMimeType();
        $storePath = $this->getTempFilePath($fileName);

        $file->move($finalPath, $fileName);
        $user = $request->user();

        $task = $this->storeTask(
            $user,
            $task_type,
            $task_type_id
        );

        $data = [
            "pending_id" => $task->id,
            "name" => $fileActualName,
            "size" => $fileSize,
            "check_list_id" => $check_list_id,
            "extension" => $mimetype,
            "path" => $storePath,
        ];


        $this->storePendingDocument($task->id, $check_list_id, $fileActualName, $storePath, $fileSize, $mimetype);

        return response()->json($data, 200);
    }
    public function storePendingDocument($pending_id, $check_list_id, $name, $path, $size, $extension)
    {
        $pending_document = PendingTaskDocument::where(
            "pending_task_id",
            $pending_id
        )->where('check_list_id', $check_list_id)->first();

        if ($pending_document) {
            // 1. Delete prevoius record
            try {
                // To continue operation if file not exist
                $this->deleteTempFile($pending_document->path);
            } catch (Exception $err) {
            }
            // 2. Update existing record
            $pending_document->update([
                "size" => $size,
                "path" => $path,
                "check_list_id" => $check_list_id,
                "actual_name" => $name,
                "extension" => $extension
            ]);
        } else {
            // Create a new record if none exists
            PendingTaskDocument::create([
                "pending_task_id" => $pending_id,
                "size" =>  $size,
                "path" => $path,
                "check_list_id" => $check_list_id,
                "actual_name" => $name,
                "extension" => $extension,
            ]);
        }
        return true;
    }
    public function destroyPendingTask($authUser, $task_type, $task_type_id)
    {
        $user_id = $authUser->id;
        $role = $authUser->role_id;
        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $task_type_id)
            ->first();
        if ($task) {
            $pending_document = PendingTaskDocument::where(
                "pending_task_id",
                $task->id
            )->first();
            if ($pending_document) {
                // 1. Delete prevoius record
                try {
                    // To continue operation if file not exist
                    $this->deleteTempFile($pending_document->path);
                } catch (Exception $err) {
                }
            }
            $task->delete();
        }
        return true;
    }

    public function storeNgoRegisterationDocument($request, UploadedFile $file, $check_list_id)
    {

        $fileActualName = $file->getClientOriginalName();
        $fileName = $this->createChunkUploadFilename($file);
        $fileSize = $file->getSize();
        $mimetype = $file->getMimeType();
        $agreement_id = Agreement::select('id')->where('ngo_id', $request->ngo_id)
            ->where('start_data', '')->where('end_date', '')->first();
        $finalPath = $this->ngoRegisterFolder($request->ngo_id, $agreement_id->id, $check_list_id,);
        $storePath = $this->ngoRegisterDBPath($request->ngo_id, $agreement_id->id, $check_list_id, $fileName);
        $file->move($finalPath, $fileName);


        $document = Document::create([
            'actual_name' => $fileActualName,
            'size' => $fileSize,
            'path' => $storePath,
            'type' => $mimetype,
            'check_list_id' => $check_list_id,
        ]);

        // **Fix whitespace issue in keys**
        AgreementDocument::create([
            'document_id' => $document->id,
            'agreement_id' => $agreement_id,
        ]);

        $data = [

            "name" => $fileActualName,
            "size" => $fileSize,
            "extension" => $mimetype,
            "path" => $storePath,
        ];
        return response()->json($data, 200);
    }
    public function pendingTask(Request $request, $task_type, $task_type_id): array
    {
        // Retrieve the first matching pending task
        $task = $this->pendingTaskExist(
            $request->user(),
            $task_type,
            $task_type_id,
        );

        if ($task) {
            // Fetch and concatenate content
            $pendingTask = PendingTaskContent::where('pending_task_id', $task->id)
                ->select('content', 'id')
                ->orderBy('id', 'desc')
                ->first();
            return [
                'content' => $pendingTask ? $pendingTask->content : null
            ];
        }
        return [
            'content' => null
        ];
    }
}
