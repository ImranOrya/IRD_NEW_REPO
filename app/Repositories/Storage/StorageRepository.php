<?php

namespace App\Repositories\Storage;

use App\Traits\Helper\HelperTrait;
use App\Models\PendingTaskDocument;

class StorageRepository implements StorageRepositoryInterface
{
    use HelperTrait;

    public function documentStore($agreement_id, $ngo_id, $pending_task_id, ?callable $callback)
    {
        // Get checklist IDs
        $documents = PendingTaskDocument::join('check_lists', 'check_lists.id', 'pending_task_documents.check_list_id')
            ->where('pending_task_id', $pending_task_id)
            ->select('size', 'path', 'check_list_id', 'actual_name', 'extension')
            ->get();

        foreach ($documents as $checklist) {
            $baseName = basename($checklist['path']);
            $oldPath = $this->getTempFullPath() . $baseName; // Absolute path of temp file

            $newDirectory = $this->ngoRegisterFolder($ngo_id, $agreement_id, $checklist['check_list_id']);

            if (!is_dir($newDirectory)) {
                mkdir($newDirectory, 0775, true);
            }
            $newPath = $newDirectory . $baseName; // Keep original filename
            $dbStorePath = $this->ngoRegisterDBPath($ngo_id, $agreement_id, $checklist['check_list_id'], $baseName);
            // Move the file
            if (file_exists($oldPath)) {
                rename($oldPath, $newPath);
            } else {
                return response()->json([
                    'errors' => [[$checklist['actual_name'] . ": " . __('app_translation.file_not_found')]],
                ], 404);
            }

            $documentData = [
                'actual_name' => $checklist['actual_name'],
                'size' => $checklist['size'],
                'path' => $dbStorePath,
                'type' => $checklist['extension'],
                'check_list_id' => $checklist['check_list_id'],
                'agreement_id' => $agreement_id
            ];
            if ($callback) {
                $callback($documentData);
            }
        }
    }
}
