<?php namespace App\Controllers;

use CodeIgniter\Events\Events;
use App\Models\TaskModel;
use App\Models\StackModel;
use App\Models\TaskAssigneeModel;
use App\Models\TaskWatcherModel;
use App\Models\TaskExtensionModel;

class TasksController extends BaseController
{
    public function one_v1($taskID)
    {
        $user = $this->request->user;

        $taskModel = new TaskModel();
        $task = $taskModel->find($taskID);

        helper("documents");
        $document = documents_load($task->project, $user->id);

        $stackModel = new StackModel();
        $stack = $stackModel->find($task->stack);

        if (!$document || !$task || !$stack) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        helper("tasks");
        $task = task_format($task);

        return $this->reply($task);
    }

    public function add_v1($stackId)
    {
        $user = $this->request->user;
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

        helper("documents");
        $document = documents_load($stack->project, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-TASK-CREATE");
        }

        // enforce an id in case there"s none
        if (!isset($taskData->id)) {
            helper("uuid");
            $taskData->id = uuid();
        }

        $taskModel = new TaskModel();

        $taskData->updated = null;
        $taskData->archived = null;
        $taskData->owner = $user->id;
        $taskData->project = $document->id;
        $taskData->stack = $stack->id;
        $taskData->position = 1;

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

        $this->addActivity($stack->id, $task->id, $this::ACTION_CREATE, $this::SECTION_TASK);
        $this->addActivity("", $document->id, $this::ACTION_UPDATE, $this::SECTION_DOCUMENT);

        return $this->reply($task);
    }

    public function update_v1($taskID)
    {
        $this->lock($taskID);
        
        $user = $this->request->user;
        $taskModel = new TaskModel();
        $task = $taskModel->find($taskID);

        helper("documents");
        $document = documents_load($task->project, $user);

        $stackModel = new StackModel();
        $stack = $stackModel->find($task->stack);

        if (!$document || !$task || !$stack) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND");
        }

        $taskData = $this->request->getJSON();
        helper("uuid");

        // generate list of new extensions
        if (isset($taskData->extensions)) {
            // Managing extensions
            // delete the current task extensions
            $taskExtensionModel = new TaskExtensionModel();
            try {
                if ($taskExtensionModel->where("task", $taskData->id)->delete() === false) {
                    return $this->reply($taskAssigneeModel->errors(), 500, "ERR-TASK-UPDATE");
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
                if ($taskAssigneeModel->where("task", $taskData->id)->delete() === false) {
                    return $this->reply($taskAssigneeModel->errors(), 500, "ERR-TASK-UPDATE");
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-TASK-UPDATE");
            }

            $assignees = array();
            foreach ($taskData->assignees as $userID) {
                $assignee = new \stdClass();
                $assignee->task = $taskData->id;
                $assignee->user = $userID;
                $assignees[] = $assignee;
            }

            // insert the assignees if any
            if (count($assignees)) {
                try {
                    if ($taskAssigneeModel->insertBatch($assignees) === false) {
                        return $this->reply($taskOrderModel->errors(), 500, "ERR-TASK-ASSIGNEES");    
                    }
                } catch (\Exception $e) {
                    return $this->reply($e->getMessage(), 500, "ERR-TASK-ASSIGNEES");
                }
            }
        }
        
        if (isset($taskData->tags)) {
            $taskData->tags = json_encode($taskData->tags);
        }

        unset($taskData->id);
        unset($taskData->position);
        unset($taskData->project);
        unset($taskData->stack);
        unset($taskData->assignees);
        unset($taskData->info);
        $taskData->archived = null;

        if ($taskData->startdate) {
            $taskData->startdate = substr(str_replace("T", " ", $taskData->startdate), 0, 19);
        } else {
            $taskData->startdate = NULL;
        }

        if ($taskData->duedate) {
            $taskData->duedate = substr(str_replace("T", " ", $taskData->duedate), 0, 19);
        } else {
            $taskData->duedate = NULL;
        }

        if ($taskModel->update($taskID, $taskData) === false) {
            return $this->reply(null, 500, "ERR-TASK-UPDATE");
        }

        $this->addActivity($stack->id, $task->id, $this::ACTION_UPDATE, $this::SECTION_TASK);

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

        $this->addActivity($task->stack, $task->id, $this::ACTION_DELETE, $this::SECTION_TASK);

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

        $this->addActivity("", $taskID, $this::ACTION_CREATE, $this::SECTION_WATCHER);

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

        $this->addActivity("", $taskID, $this::ACTION_DELETE, $this::SECTION_WATCHER);

        return $this->reply(true);
    }
}