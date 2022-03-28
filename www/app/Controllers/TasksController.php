<?php namespace App\Controllers;

use CodeIgniter\Events\Events;
use App\Models\DocumentModel;
use App\Models\TaskModel;
use App\Models\StackModel;
use App\Models\TaskAssigneeModel;
use App\Models\TaskWatcherModel;
use App\Models\TaskExtensionModel;

class TasksController extends BaseController
{
    protected $permissionSection = "tasks";

    public function one_v1($taskID)
    {
        $user = $this->request->user;

        helper("tasks");
        $task = task_load($taskID);

        if (!$task) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        helper("documents");
        $document = documents_load_document($task->project, $user);
        if (!$document) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        $stackModel = new StackModel();
        $stack = $stackModel->find($task->stack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        return $this->reply(task_format($task));
    }

    public function add_v1($stackId)
    {
        $taskData = $this->request->getJSON();
        $position = $this->request->getGet("position");

        if (!$position) {
            $position = "bottom";
        }

        if (!in_array($position, ["top", "bottom"])) {
            return $this->reply("Invalid task position", 500, "ERR-TASK-CREATE");
        }
        
        $stackModal = new StackModel();
        $stack = $stackModal->find($stackId);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-TASK-CREATE");
        }

        $user = $this->request->user;
        $documentModel = new DocumentModel();
        $documentModel->user = $this->request->user;
        $document = $documentModel->find($stack->project);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-TASK-CREATE");
        }

        $this->can("add", $document);

        // enforce an id in case there"s none
        if (!isset($taskData->id)) {
            helper("uuid");
            $taskData->id = uuid();
        }

        $taskModel = new TaskModel();

        $taskData->updated = null;
        $taskData->archived = null;
        $taskData->completed = null;
        $taskData->project = $document->id;
        $taskData->stack = $stack->id;
        $taskData->position = 1;
        // by default the owner is the user creating the task
        $taskData->owner = $user->id;
        $taskData->public = 1;

        if (isset($taskData->repeats)) {
            $taskData->repeats = \json_encode($taskData->repeats);
        }

        // get the last order number in from that project and stack
        if ($position === "bottom") {
            $lastPosition = $taskModel->where("project", $document->id)
                ->where("stack", $stack->id)
                ->orderBy("position", "desc")
                ->first();
            
            if ($lastPosition) {
                $taskData->position = intval($lastPosition->position) + 1;
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
            if ($taskModel->insert($taskData) === false) {
                $errors = $taskModel->errors();
                return $this->reply($errors, 500, "ERR-TASK-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-CREATE");
        }

        $task = $taskModel->find($taskData->id);

        helper("tasks");
        $task = task_format($task);

        $this->addActivity(
            $document->id,
            $stack->id, 
            $task->id, 
            $this::ACTION_CREATE, 
            $this::SECTION_TASK
        );

        return $this->reply($task);
    }

    public function update_v1($taskID)
    {
        $this->lock($taskID);
        
        $user = $this->request->user;
        $taskModel = new TaskModel();
        $task = $taskModel->find($taskID);

        if (!$task) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        helper("documents");
        $document = documents_load_document($task->project, $user);

        if (!$document) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        $stackModel = new StackModel();
        $stack = $stackModel->find($task->stack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        $taskData = $this->request->getJSON();
        helper("uuid");

        $db = db_connect();
        $db->transStart();

        // generate list of new extensions
        if (isset($taskData->extensions)) {
            // Managing extensions
            // delete the current task extensions
            $taskExtensionModel = new TaskExtensionModel();
            try {
                if ($taskExtensionModel->where("task", $task->id)->delete() === false) {
                    return $this->reply($taskExtensionModel->errors(), 500, "ERR-TASK-UPDATE");
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-TASK-UPDATE");
            }

            $extensions = array();
            foreach ($taskData->extensions as $ext) {
                $extension = new \stdClass();
                $extension->task = $taskID;
                $extension->title = $ext->title;
                $extension->type = $ext->type;

                if ($extension->type == "attachments") {
                    $extension->content = "[]"; // just save a simple JSON array
                } else {
                    $extension->content = json_encode($ext->content);
                }
                $extension->options = json_encode($ext->options);

                if (!isset($ext->id)) {
                    $extension->id = uuid();  
                } else {
                    $extension->id = $ext->id;
                }

                $extensions[] = $extension;
            }

            if (count($extensions)) {    
                try {
                    if ($taskExtensionModel->insertBatch($extensions) === false) {
                        return $this->reply($taskExtensionModel->errors(), 500, "ERR-TASK-UPDATE");    
                    }
                } catch (\Exception $e) {
                    return $this->reply($e->getMessage(), 500, "ERR-TASK-UPDATE");
                }
            }
        }

        // generate a list of new assignees
        if (isset($taskData->assignees)) {
            // delete all assigned task users
            $taskAssigneeModel = new TaskAssigneeModel();
            try {
                if ($taskAssigneeModel->where("task", $task->id)->delete() === false) {
                    return $this->reply($taskAssigneeModel->errors(), 500, "ERR-TASK-UPDATE");
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-TASK-UPDATE");
            }

            $assignees = array();
            foreach ($taskData->assignees as $person) {
                $assignee = new \stdClass();
                $assignee->task = $task->id;
                $assignee->person = $person;
                $assignees[] = $assignee;
            }

            // insert the assignees if any
            if (count($assignees)) {
                try {
                    if ($taskAssigneeModel->insertBatch($assignees) === false) {
                        return $this->reply($taskAssigneeModel->errors(), 500, "ERR-TASK-ASSIGNEES");    
                    }
                } catch (\Exception $e) {
                    return $this->reply($e->getMessage(), 500, "ERR-TASK-ASSIGNEES");
                }
            }
        }
        
        // convert tags to string
        if (isset($taskData->tags)) {
            $taskData->tags = json_encode($taskData->tags);
        }

        // convert repeats to string
        if (isset($taskData->repeats)) {
            $taskData->repeats = json_encode($taskData->repeats);
        }

        unset($taskData->id);
        unset($taskData->position);
        unset($taskData->project);
        unset($taskData->stack);
        unset($taskData->assignees);
        unset($taskData->info);
        $taskData->archived = null;

        // if somebody tries changing the owner and it's not the current owner then remove it
        if ($taskData->owner && $taskData->owner != $user->id) {
            return $this->reply(null, 403);
        }

        // if somebody tries changing the visibility (private, public) and it's not the owner then remove it
        if ($taskData->public && $task->owner != $user->id) {
            return $this->reply(null, 403);
        }

        // fix start date formatting
        if (isset($taskData->startdate)) {
            $taskData->startdate = substr(str_replace("T", " ", $taskData->startdate), 0, 19);
        }

        // fix due date formatting
        if (isset($taskData->duedate)) {
            $taskData->duedate = substr(str_replace("T", " ", $taskData->duedate), 0, 19);
        }

        // fix completed date formatting
        if (isset($taskData->completed)) {
            $taskData->completed = substr(str_replace("T", " ", $taskData->completed), 0, 19);
        }

        if ($taskModel->update($taskID, $taskData) === false) {
            return $this->reply(null, 500, "ERR-TASK-UPDATE");
        }

        $db->transComplete();

        $this->addActivity(
            $document->id,
            $stack->id, 
            $task->id, 
            $this::ACTION_UPDATE, 
            $this::SECTION_TASK
        );

        return $this->reply(true);
    }

    public function delete_v1($taskID)
    {
        $this->lock($taskID);

        $taskModel = new TaskModel();
        $task = $taskModel->find($taskID);

        if (!$task) {
            return $this->reply(null, 404, "ERR-TASKS-DELETE");
        }

        // delete selected task
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

    public function get_watchers_v1($taskID)
    {
        $user = $this->request->user;

        helper("watchers");
        $watchers = watchers_load($taskID, $user);

        return $this->reply($watchers);
    }

    public function add_watcher_v1($taskID)
    {
        $user = $this->request->user;

        $taskWatcherModel = new TaskWatcherModel();

        $watchers = $taskWatcherModel->where("task", $taskID)
            ->where("user", $user->id)
            ->findAll();

        $watcher = array(
            "task" => $taskID,
            "user" => $user->id
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
            $taskID, 
            $this::ACTION_CREATE, 
            $this::SECTION_WATCHER
        );

        return $this->reply(true);
    }

    public function remove_watcher_v1($taskID)
    {
        $user = $this->request->user;

        $taskWatcherModel = new TaskWatcherModel();

        try {
            if ($taskWatcherModel
                ->where("user", $user->id)
                ->where("task", $taskID)->delete() === false
            ) {
                return $this->reply($taskAssigneeModel->errors(), 500, "ERR-TASK-DELETE-WATCHER");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-DELETE-WATCHER");
        }

        $this->addActivity(
            "",
            "", 
            $taskID, 
            $this::ACTION_DELETE, 
            $this::SECTION_WATCHER
        );

        return $this->reply(true);
    }
}