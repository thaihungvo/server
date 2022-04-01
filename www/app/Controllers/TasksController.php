<?php namespace App\Controllers;

use CodeIgniter\Events\Events;
use App\Models\TaskModel;
use App\Models\StackModel;
use App\Models\TaskWatcherModel;
use App\Models\UserModel;

class TasksController extends BaseController
{
    protected $permissionSection = "tasks";

    public function one_v1($taskId)
    {
        $taskModel = new TaskModel($this->request->user);
        $task = $taskModel->getTask($taskId);

        if (!$task) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        $document = $this->getDocument($task->project);

        if (!$document) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        $this->can("read", $document);
        $this->can("read", $task);

        $stackModel = new StackModel();
        $stack = $stackModel->find($task->stack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        return $this->reply($task);
    }

    public function add_v1($stackId)
    {        
        $stackModal = new StackModel();
        $stack = $stackModal->find($stackId);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-TASK-CREATE");
        }

        $document = $this->getDocument($stack->project);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-TASK-CREATE");
        }

        $this->can("add", $document);

        $data = $this->request->getJSON();
        $data->updated = null;
        $data->archived = null;
        $data->completed = null;
        $data->project = $document->id;
        $data->stack = $stack->id;
        $data->position = 1;
        // by default the owner is the user creating the task
        $data->owner = $this->request->user->id;
        $data->public = 1;

        $taskModel = new TaskModel();
        $taskModel->formatData($data);

        // getting the desired position
        $position = $this->request->getGet("position");

        if (!$position) {
            $position = "bottom";
        }

        if (!in_array($position, ["top", "bottom"])) {
            return $this->reply("Invalid task position", 500, "ERR-TASK-CREATE");
        }

        // get the last order number in from that project and stack
        if ($position === "bottom") {
            $lastPosition = $taskModel->where("project", $document->id)
                ->where("stack", $stack->id)
                ->orderBy("position", "desc")
                ->first();
            
            if ($lastPosition) {
                $data->position = intval($lastPosition->position) + 1;
            }

        // move all tasks order up by 1
        } else {
            $taskBuilder = $taskModel->builder();
            $taskBuilder->where("deleted", NULL)
                ->where("project", $document->id)
                ->where("stack", $stack->id)
                ->set("position", "`position` + 1", false)
                ->update();
        }
        
        try {
            if ($taskModel->insert($data) === false) {
                $errors = $taskModel->errors();
                return $this->reply($errors, 500, "ERR-TASK-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-CREATE");
        }

        // inserting the default document permission
        try {
            $this->addPermission($data->id, $this::PERMISSION_TYPE_TASK);
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-CREATE");
        }

        $task = $taskModel->find($data->id);

        $this->addActivity(
            $document->id,
            $stack->id, 
            $task->id, 
            $this::ACTION_CREATE, 
            $this::SECTION_TASK
        );

        return $this->reply($task);
    }

    public function update_v1($taskId)
    {
        $this->lock($taskId);
        $taskModel = new TaskModel($this->request->user);
        $task = $taskModel->getTask($taskId);

        $data = $this->request->getJSON();
        
        $taskModel = new TaskModel();
        $taskModel->formatData($data);
        unset($data->id);
        unset($data->position);
        unset($data->project);
        unset($data->stack);
        unset($data->assignees);
        unset($data->info);
        $data->archived = null;

        if (!$task) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        $document = $this->getDocument($task->project);

        if (!$document) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        $this->can("update", $document);
        $this->can("update", $task);

        $stackModel = new StackModel();
        $stack = $stackModel->find($task->stack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        // if somebody tries changing the owner and it's not the current owner then remove it
        if ($data->owner && $data->owner != $user->id) {
            return $this->reply(null, 403);
        }

        // if somebody tries changing the visibility (private, public) and it's not the owner then remove it
        if ($data->public && $task->owner != $user->id) {
            return $this->reply(null, 403);
        }

        $this->db->transStart();

        // Managing extensions
        try {
            $taskModel->addExtensions($task->id, $data->extensions);
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-UPDATE");
        }

        // generate a list of new assignees
        try {
            $taskModel->addAssignees($task->id, $data->extensions);
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-UPDATE");
        }

        // update the task
        if ($taskModel->update($taskID, $data) === false) {
            return $this->reply(null, 500, "ERR-TASK-UPDATE");
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->reply(null, 500, "ERR-TASK-UPDATE");
        }

        $this->addActivity(
            $document->id,
            $stack->id, 
            $task->id, 
            $this::ACTION_UPDATE, 
            $this::SECTION_TASK
        );

        return $this->reply(true);
    }

    public function delete_v1($taskId)
    {
        $this->lock($taskId);
        $taskModel = new TaskModel($this->request->user);
        $task = $taskModel->getTask($taskId);

        if (!$task) {
            return $this->reply(null, 404, "ERR-TASKS-DELETE");
        }

        $document = $this->getDocument($task->project);
        if (!$document) {
            return $this->reply(null, 404, "ERR-TASKS-DELETE");
        }

        $this->can("delete", $document);
        $this->can("delete", $task);

        // delete selected task
        $taskModel = new TaskModel();
        try {
            if ($taskModel->delete([$task->id]) === false) {
                return $this->reply($taskModel->errors(), 500, "ERR-TASKS-DELETE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASKS-DELETE");
        }

        $this->addActivity(
            $task->project, 
            $task->stack, 
            $task->id, 
            $this::ACTION_DELETE, 
            $this::SECTION_TASK
        );

        return $this->reply(true);
    }

    public function get_watchers_v1($taskId)
    {
        $userModel = new UserModel();
        $userBuilder = $userModel->builder();
        $usersQuery = $userBuilder->select("users.id, users.email, users.nickname, users.firstName, users.lastName, tasks_watchers.created")
            ->join('tasks_watchers', 'tasks_watchers.user = users.id', 'left')
            ->groupStart()
                ->where('tasks_watchers.task', $taskId)
                ->where('users.id !=', $this->request->user->id)
            ->groupEnd()
            ->orderBy('users.firstName', 'ASC')
            ->get();
        $watchers = $usersQuery->getResult();

        return $this->reply($watchers);
    }

    public function add_watcher_v1($taskId)
    {
        $taskWatcherModel = new TaskWatcherModel();

        $watchers = $taskWatcherModel->where("task", $taskId)
            ->where("user", $this->request->user->id)
            ->findAll();

        $watcher = array(
            "task" => $taskId,
            "user" => $this->request->user->id
        );

        if (!count($watchers)) {
            try {
                if ($taskWatcherModel->insert($watcher) === false) {
                    return $this->reply($taskWatcherModel->errors(), 500, "ERR-TASK-ADD-WATCHER");
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-TASK-ADD-WATCHER");
            }
        }

        // remove all stuck watchers        
        try {
            if ($taskWatcherModel->where("created <= DATE_SUB(NOW(), INTERVAL 2 HOUR)", NULL, false)->delete() === false) {
                return $this->reply($taskModel->errors(), 500, "ERR-TASK-ADD-WATCHER");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-ADD-WATCHER");
        }

        $this->addActivity(
            "",
            "", 
            $taskId, 
            $this::ACTION_CREATE, 
            $this::SECTION_WATCHER
        );

        return $this->reply(true);
    }

    public function remove_watcher_v1($taskId)
    {
        $taskWatcherModel = new TaskWatcherModel();

        try {
            if ($taskWatcherModel
                ->where("user", $this->request->user->id)
                ->where("task", $taskId)->delete() === false
            ) {
                return $this->reply($taskWatcherModel->errors(), 500, "ERR-TASK-DELETE-WATCHER");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-DELETE-WATCHER");
        }

        $this->addActivity(
            "",
            "", 
            $taskId, 
            $this::ACTION_DELETE, 
            $this::SECTION_WATCHER
        );

        return $this->reply(true);
    }
}