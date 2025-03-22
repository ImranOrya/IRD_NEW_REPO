<?php

namespace App\Repositories\Task;

use App\Models\PendingTask;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

interface PendingTaskRepositoryInterface
{
    /**
     * Stores task as a text in database.
     * $authUser: Authticated user is needed to determine who addedd the task
     * $task_type: Name of the task e.g. ngo_registeration
     * $task_type_id: Id of the particular table e.g. In ngo_registeration id must be ngo id
     * $step: Optional In case you want to track the steps otherwise pass zero (0)
     * $content: The content you need to store must be string
     * 
     * @param \App\Models\PendingTask $task
     * @param string $step
     * @param string $content
     * @return \App\Models\PendingTaskContent
     */
    public function storeTaskContent(PendingTask $task, $step, $content);

    /**
     * Stores task as a text in database.
     * $authUser: Authticated user is needed to determine who addedd the task
     * $task_type: Name of the task e.g. ngo_registeration
     * $task_type_id: Id of the particular table e.g. In ngo_registeration id must be ngo id
     * $step: Optional In case you want to track the steps otherwise pass zero (0)
     * $content: The content you need to store must be string
     * 
     * @param mixed $authUser
     * @param string $task_type
     * @param string $task_type_id
     * @return \App\Models\PendingTask
     */
    public function storeTask($authUser, $task_type, $task_type_id);

    /**
     * Deletes existing task along with the contents and documents.
     * $authUser: Authticated user is needed to determine who addedd the task
     * $task_type: Name of the task e.g. ngo_registeration
     * $task_type_id: Id of the particular table e.g. In ngo_registeration id must be ngo id
     * 
     * @param mixed $authUser
     * @param string $task_type
     * @param string $task_type_id
     * @return boolean Returns true if task is deleted otherwise false.
     */
    public function destroyPendingTask($authUser, $task_type, $task_type_id);

    /**
     * Returns the pending task if not exist returns null
     * $authUser: Authticated user is needed to determine who addedd the task
     * $task_type: Name of the task e.g. ngo_registeration
     * $task_type_id: Id of the particular table e.g. In ngo_registeration id must be ngo id
     * 
     * @param mixed $authUser
     * @param string $task_type
     * @param string $task_type_id
     * @return \App\Models\PendingTask
     */
    public function pendingTaskExist($authUser, $task_type, $task_type_id);
    /**
     * Returns the pending task if not exist returns null
     * $pending_task_id: Primary key
     * 
     * @param string $pending_task_id
     * @return \App\Models\PendingTask
     */
    public function findTask($pending_task_id);

    /**
     * Returns the pending task document if not exist returns null
     * $pending_task_id: Task id
     * $task_type: Name of the task e.g. ngo_registeration
     * $task_type_id: Id of the particular table e.g. In ngo_registeration id must be ngo id
     * 
     * @param string $pending_task_id
     * @return \App\Models\PendingTaskDocument 
     */
    public function pendingTaskDocumentQuery($pending_task_id);
    public function fileStore(UploadedFile $file, Request $request, $task_type, $check_list_id, $task_type_id);
    public function storePendingDocument($pending_id, $check_list_id, $name, $path, $size, $extension);
    public function storeNgoRegisterationDocument($request, UploadedFile $file, $check_list_id);
    /**
     * 
     * @param Illuminate\Http\Request $request
     * @param string $task_type
     * @param string $task_type_id
     * @return \App\Models\PendingTaskDocument 
     */
    public function pendingTask(Request $request, $task_type, $task_type_id): array;
}
