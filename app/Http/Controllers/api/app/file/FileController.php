<?php

namespace App\Http\Controllers\api\app\file;

use App\Models\CheckList;
use App\Models\PendingTask;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Enums\Type\TaskTypeEnum;
use Illuminate\Http\UploadedFile;
use App\Models\PendingTaskDocument;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Validator;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;

class FileController extends Controller
{
    /**
     * Handles the file upload.
     */
    public function uploadNgoFile(Request $request)
    {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) {
            throw new UploadMissingFileException();
        }

        $save = $receiver->receive();

        if ($save->isFinished()) {
            $task_type = TaskTypeEnum::ngo_registeration;
            $ngo_id = $request->ngo_id;
            return $this->saveFile($save->getFile(), $request, $ngo_id, $task_type);
        }

        // If not finished, send current progress.
        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            "status" => true,
        ]);
    }

    public function uploadProjectFile(Request $request,)
    {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) {
            throw new UploadMissingFileException();
        }

        $save = $receiver->receive();

        if ($save->isFinished()) {
            $task_type = TaskTypeEnum::project_registeration;
            $project_id = $request->project_id;
            return $this->saveFile($save->getFile(), $request, $project_id, $task_type);
        }

        // If not finished, send current progress.
        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            "status" => true,
        ]);
    }

    /**
     * Saves the file and validates it.
     */
    protected function saveFile(UploadedFile $file, Request $request, $id, $task_type)
    {
        $fileActualName = $file->getClientOriginalName();
        $fileName = $this->createFilename($file);
        $fileSize = $file->getSize();
        $finalPath = $this->getTempFullPath();
        $mimetype = $file->getMimeType();
        $storePath = $this->getTempFilePath($fileName);
        $extension = ".{$file->getClientOriginalExtension()}";

        $file->move($finalPath, $fileName);

        // Validate the file against checklist rules
        $validationResult = $this->checkListCheck($request, "{$finalPath}{$fileName}");

        if ($validationResult !== true) {
            return $validationResult; // Return validation errors
        }
        // Process pending task and document creation

        $pending =  $this->pending($request, $id, $task_type);

        $data = [
            "pending_id" => $pending,
            "name" => $fileActualName,
            "size" => $fileSize,
            "check_list_id" => $request->checklist_id,
            "extension" => $mimetype,
            "path" => $storePath,
        ];


        $this->pendingDocument($data);

        return response()->json($data, 201);
    }

    /**
     * Validate file using checklist settings.
     */
    public function checkListCheck($request, $filePath)
    {
        // 1. Validate check exist
        $checklist = CheckList::find($request->checklist_id);

        if (!$checklist) {
            return response()->json([
                'message' => __('app_translation.checklist_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        $rules = [
            "file" => [
                "required",
                "mimes:{$checklist->acceptable_extensions}",
                "max:{$checklist->file_size}",
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            unlink($filePath);
            return response()->json(["errors" => $validator->errors()], 422);
        }

        return true;
    }

    /**
     * Create or retrieve pending task.
     */
    protected function pending(Request $request, $id, $task_type)
    {
        $user = $request->user();
        $user_id = $user->id;
        $role = $user->role_id;


        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $id)
            ->first();

        return $task->id;
    }

    /**
     * Save pending task document.
     */
    protected function pendingDocument(array $data)
    {
        $pending_document = PendingTaskDocument::where(
            "pending_task_id",
            $data["pending_id"]
        )->where('check_list_id', $data["check_list_id"])->first();

        if ($pending_document) {
            // 1. Delete prevoius record
            try {
                // To continue operation if file not exist
                $this->deleteTempFile($pending_document->path);
            } catch (Exception $err) {
            }
            // 2. Update existing record
            $pending_document->update([
                "size" => $data["size"],
                "path" => $data["path"],
                "check_list_id" => $data["check_list_id"],
                "actual_name" => $data["name"],
                "extension" => $data["extension"]
            ]);

            return; // Prevents creating a duplicate record
        }

        // Create a new record if none exists
        PendingTaskDocument::create([
            "pending_task_id" => $data["pending_id"],
            "size" => $data["size"],
            "path" => $data["path"],
            "check_list_id" => $data["check_list_id"],
            "actual_name" => $data["name"],
            "extension" => $data["extension"],
        ]);
    }


    /**
     * Generate a unique filename.
     */
    protected function createFilename(UploadedFile $file): string
    {
        return Str::uuid() . "." . $file->getClientOriginalExtension();
    }
}
