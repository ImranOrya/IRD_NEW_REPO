<?php

namespace App\Repositories\Task;

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
     * @param mixed $authUser
     * @param string $task_type
     * @param string $task_type_id
     * @param string $step
     * @param string $content
     * @return \App\Models\PendingTask
     */
    public function storeTask($authUser, $task_type, $task_type_id, $step, $content);

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
    public function deletePendingTask($authUser, $task_type, $task_type_id);

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
     * Returns the pending task document if not exist returns null
     * $pending_task_id: Task id
     * $task_type: Name of the task e.g. ngo_registeration
     * $task_type_id: Id of the particular table e.g. In ngo_registeration id must be ngo id
     * 
     * @param string $pending_task_id
     * @return \App\Models\PendingTaskDocument 
     */
    public function pendingTaskDocumentQuery($pending_task_id);
}
