<?php

namespace App\Http\Controllers\api\app\file;

use App\Enums\CheckListTypeEnum;
use App\Enums\pdfFooter\CheckListEnum;
use App\Enums\Type\TaskTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\AgreementDocument;
use App\Models\CheckList;
use App\Models\Document;
use App\Models\Ngo;
use App\Models\NgoTran;
use App\Models\PendingTask;
use App\Models\PendingTaskDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

class FileController extends Controller
{
    /**
     * Handles the file upload.
     */

    public function uploadFile(Request $request)
    {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) {
            throw new UploadMissingFileException();
        }

        $save = $receiver->receive();

        if ($save->isFinished()) {

            return $this->genSaveFile($save->getFile(), $request);
        }

        // If not finished, send current progress.
        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            "status" => true,
        ]);
    }

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

    public function uploadNgoExtendFile(Request $request)
    {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) {
            throw new UploadMissingFileException();
        }

        $save = $receiver->receive();

        if ($save->isFinished()) {
            $task_type = TaskTypeEnum::ngo_agreement_extend;
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

        return response()->json($data, 200);
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
        if (!$task) {

            $newtask =  PendingTask::create([
                'user_id' => $user_id,
                'user_type' => $role,
                'task_type' => $task_type,
                'task_id' => $id

            ]);

            return $newtask->id;
        }


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

    protected function genSaveFile(UploadedFile $file, Request $request)
    {
        $fileActualName = $file->getClientOriginalName();
        $fileName = $this->createFilename($file);
        $fileSize = $file->getSize();
        $finalPath = $this->getTempFullPath();
        $mimetype = $file->getMimeType();
        $storePath = $this->getTempFilePath($fileName);

        $file->move($finalPath, $fileName);
        // Validate the file against checklist rules

        $validationResult = $this->checkListCheck($request, "{$finalPath}{$fileName}");

        if ($validationResult !== true) {
            return $validationResult; // Return validation errors
        }
        $checklist = CheckList::find($request->checklist_id);





        if ($checklist->check_list_type_id == CheckListTypeEnum::externel->value) {



            $ngo = NgoTran::where('ngo_id', $request->ngo_id)->where('language_name', 'en')->value('name');
            $agreement_id = AgreementDocument::where('document_id', $request->document_id)->value('agreement_id');
            $newDirectory = storage_path() . "/app/private/ngos/{$ngo}/{$agreement_id}/";
            if (!file_exists($newDirectory)) {
                mkdir($newDirectory, 0775, true);
            }

            $newPath = $newDirectory . basename($storePath); // Keep original filename

            $dbStorePath = "private/ngos/{$ngo}/{$agreement_id}/"
                . basename($storePath);
            $document = Document::find($request->document_id);

            $movefile = $this->moveFile($storePath, $newPath, $document->path);
            if ($movefile) {
                return $movefile;
            }

            $document->actual_name = $fileActualName;
            $document->size = $fileSize;
            $document->type = $mimetype;
            $document->path = $dbStorePath;
            $document->save();

            $data = [

                "name" => $fileActualName,
                "size" => $fileSize,
                "checklist_id" => $request->checklist_id,
                "extension" => $mimetype,
                "path" => $dbStorePath,
                "checklist_name" => $request->checklist_name ?? '',
                "acceptable_extensions" => $checklist->acceptable_extensions,
                "acceptable_mimes" => $checklist->acceptable_mimes,
            ];




            return response()->json($data, 200);
        }
    }

    protected function moveFile($tempPath, $permPath, $oldFilePath)
    {
        $tempPath = storage_path("app/" . ltrim(str_replace('\\', '/', $tempPath), '/'));
        $newPath = ltrim(str_replace('\\', '/', $permPath), '/');
        $oldFilePath = storage_path("app/" . ltrim(str_replace('\\', '/', $oldFilePath), '/'));
        // Delete old file if it exists

        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }


        // Move the file
        if (file_exists($tempPath)) {
            rename($tempPath, $newPath);
        } else {
            return response()->json(['error' => __('app_translation.not_found') . " " . $tempPath], 404);
        }
    }
}
