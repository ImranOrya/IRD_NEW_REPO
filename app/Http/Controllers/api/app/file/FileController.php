<?php

namespace App\Http\Controllers\api\app\file;

use Illuminate\Http\Request;
use App\Traits\Helper\HelperTrait;
use App\Http\Controllers\Controller;
use App\Repositories\ngo\NgoRepositoryInterface;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use App\Repositories\Task\PendingTaskRepositoryInterface;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;

class FileController extends Controller
{
    use HelperTrait;
    protected $pendingTaskRepository;
    protected $ngoRepository;

    public function __construct(
        PendingTaskRepositoryInterface $pendingTaskRepository,
        NgoRepositoryInterface $ngoRepository
    ) {
        $this->pendingTaskRepository = $pendingTaskRepository;
        $this->ngoRepository = $ngoRepository;
    }
    public function checklistUploadFile(Request $request)
    {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) {
            throw new UploadMissingFileException();
        }

        $save = $receiver->receive();

        if ($save->isFinished()) {
            $task_type = $request->task_type;;
            $ngo_id = $request->ngo_id;
            $checklist_id = $request->checklist_id;
            $file = $save->getFile();
            // 1. Validate checklist
            $validationResult = $this->checkFileWithList($file, $request->checklist_id);
            if ($validationResult !== true) {
                $filePath = $file->getRealPath();
                unlink($filePath);
                return $validationResult; // Return validation errors
            }
            // 2. Store document
            return $this->pendingTaskRepository->fileStore(
                $file,
                $request,
                $task_type,
                $checklist_id,
                $ngo_id
            );
        }

        // If not finished, send current progress.
        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            "status" => true,
        ]);
    }

    // 1. Upload files in case does not have task_id
    public function singleChecklistFileUpload(Request $request)
    {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) {
            throw new UploadMissingFileException();
        }

        $save = $receiver->receive();

        if ($save->isFinished()) {
            $task_type = $request->task_type;
            $check_list_id = $request->checklist_id;
            $file = $save->getFile();

            // 1. Validate checklist
            $validationResult = $this->checkFileWithList($file, $request->checklist_id);
            if ($validationResult !== true) {
                $filePath = $file->getRealPath();
                unlink($filePath);
                return $validationResult; // Return validation errors
            }
            // 2. Delete all previous PendingTask for current user_id, user_type and task_type
            $this->pendingTaskRepository->destroyPendingTask($request->user(), $task_type, null);
            // 3. Store new Pendding Document Task
            return $this->pendingTaskRepository->fileStore(
                $save->getFile(),
                $request,
                $task_type,
                $check_list_id,
                null
            );
        }

        // If not finished, send current progress.
        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            "status" => true,
        ]);
    }
}
