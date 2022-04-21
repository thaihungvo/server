<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BaseModel;
use App\Models\TaskExtensionModel;
use App\Models\AttachmentModel;
use App\Models\TaskAssigneeModel;
use App\Models\PermissionModel;

class TaskModel extends BaseModel
{
    protected $table      = "tasks";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = true;

    protected $allowedFields = ["id", "title", "description", "showDescription", "tags", "status", "duedate", "startdate", "cover", "done", "altTags", "estimate", "spent", "progress", "user", "hourlyFee", "owner", "priority", "repeats", "project", "stack", "position", "public", "owner", "archived", "created", "updated"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
    protected $deletedField  = "deleted";

    protected $afterFind = ["formatTasks"];

    protected $validationRules = [
        "id" => "required|min_length[20]",
        "title" => "required",
        "position" => "required"
    ];

    protected function formatTask(&$task)
    {
        $task->cover = boolval($task->cover);
        $task->done = boolval($task->done);
        $task->altTags = boolval($task->altTags);
        $task->showDescription = boolval($task->showDescription);
        $task->progress = intval($task->progress);
        $task->position = intval($task->position);
        $task->public = boolval($task->public);
        $task->isOwner = $task->owner == $this->user->id;
        unset($task->order);
        unset($task->deleted);

        if (is_string($task->tags)) {
            $task->tags = json_decode($task->tags);
        } else {
            unset($task->tags);
        }

        if (is_string($task->repeats)) {
            $task->repeats = json_decode($task->repeats);
        } else {
            unset($task->repeats);
        }

        if (!$task->startdate) unset($task->startdate);
        if (!$task->duedate) unset($task->duedate);
        if (!$task->completed) unset($task->completed);
        if (!$task->archived) $task->archived = "";
        if (!$task->priority) unset($task->priority);
        if (!$task->estimate) unset($task->estimate);
        if (!$task->spent) unset($task->spent);
        if (!$task->hourlyFee) unset($task->hourlyFee);
        if (!$task->feeCurrency) unset($task->feeCurrency);
        if (!$task->status) unset($task->status);

        $task->extensions = array();
        $task->assignees = array();
    }

    protected function formatTasks(array $data)
    {
        // format single task
        if ($data["singleton"] && $data["data"]) {
            $this->formatTask($data["data"]);

            // connect assignees to task
            $taskAssigneesModel = new TaskAssigneeModel();
            $assignees = $taskAssigneesModel->getTaskAssignees($data["data"]->id);
            $data["data"]->assignees = array_map(fn($assignee) => [
                "id" => $assignee->id,
                "name" => $assignee->firstName ." ". $assignee->lastName
            ], $assignees);

            // load extensions
            $taskExtensionModel = new TaskExtensionModel();
            $data["data"]->extensions = $taskExtensionModel->getTaskExtensions($data["data"]->id);


            // load task attachments
            $attachments = array();
            $attachmentModel = new AttachmentModel();
            $attachments = $attachmentModel
                ->where("resource", $data["data"]->id)
                ->findAll();

            // insert the attachments in the task extension
            foreach ($attachments as $attachment) {
                if ($attachment->resource == $data["data"]->id && isset($data["data"]->extensions)) {
                    unset($attachment->resource);

                    foreach ($data["data"]->extensions as &$extension) {
                        if ($extension->type == "attachments") {
                            if (!is_array($extension->content)) {
                                $extension->content = array();
                            }

                            $extension->content[] = $attachment;
                        }
                    }
                }
            }

            if (!count($data["data"]->assignees)) {
                unset($data["data"]->assignees);
            }
            if (!count($data["data"]->extensions)) {
                unset($data["data"]->extensions);
            }
        }

        // format list of tasks
        if (!$data["singleton"] && $data["data"]) {
            foreach ($data["data"] as $key => &$task) {
                $this->formatTask($task);
            }
        }

        return $data;
    }

    protected function getUserPermissions($permission, $task)
    {
        $can = "";
        $can .= $permission === "FULL" || $permission === "EDIT" ? "A" : "";
        $can .= $permission === "FULL" || $permission === "EDIT" || ($permission === "LIMITED" && $task->isOwner) ? "U" : "";
        $can .= $permission === "FULL" || $permission === "EDIT" ? "D" : "";
        return $can;
    }

    protected function getFindQuery()
    {
        /*
            Retrieving stacks that match the following criteria:
            - stack is not deleted
            - stack is public
            - stack is not public and the current user is owner
            - stack is not public and the current user is not owner but it has a permission
        */
        $db = db_connect();
        return $this
            ->select("tasks.*")
            ->join("permissions", "permissions.resource = tasks.id AND permissions.user = ".$db->escape($this->user->id), 'left')
            ->groupStart()
                ->where("public", 1)
                ->orGroupStart()
                    ->where("public", 0)
                    ->where("owner", $this->user->id)
                ->groupEnd()
                ->orWhere("permissions.permission IS NOT NULL", null)
            ->groupEnd();
    }

    public function formatData(&$data)
    {
        // enforce an id in case there"s none
        if (!isset($data->id)) {
            helper("uuid");
            $data->id = uuid();
        }

        // convert tags to string
        if (isset($data->tags)) {
            $data->tags = json_encode($data->tags);
        }

        // convert public boolean to int
        if (isset($data->public)) {
            $data->public = intval($data->public);
        }

        // convert repeats to string
        if (isset($data->repeats)) {
            $data->repeats = json_encode($data->repeats);
        }

        // fix start date formatting
        if (isset($data->startdate)) {
            $data->startdate = substr(str_replace("T", " ", $data->startdate), 0, 19);
        }

        // fix due date formatting
        if (isset($data->duedate)) {
            $data->duedate = substr(str_replace("T", " ", $data->duedate), 0, 19);
        }

        // fix completed date formatting
        if (isset($data->completed)) {
            $data->completed = substr(str_replace("T", " ", $data->completed), 0, 19);
        }
    }

    public function getTask($taskId)
    {
        $task = $this->getFindQuery()->find($taskId);
        
        if ($task) {
            $permissionModel = new PermissionModel($this->user);
            $task->permission = $permissionModel->getPermission($task->id, $task->owner);
            $task->permissions = $this->getUserPermissions($task->permission, $task);
        }

        return $task;
    }

    public function getTasksByStacks($stacksIds)
    {
        $tasks = $this
            ->getFindQuery()
            ->orderBy('position', 'ASC')
            ->findAll();

        if (count($tasks)) {
            $permissionModel = new PermissionModel($this->user);
            $permissionModel->getPermissions($tasks);

            foreach ($tasks as &$task) {
                $task->permissions = $this->getUserPermissions($task->permission, $task);
            }
        }

        return $tasks;
    }

    public function addExtensions($taskId, $dataExtensions)
    {
        if (!isset($dataExtensions)) return;
        helper("uuid");

        // delete the current task extensions
        $taskExtensionModel = new TaskExtensionModel();
        try {
            if ($taskExtensionModel->where("task", $task->id)->delete() === false) {
                throw new \Exception($taskExtensionModel->errors());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $extensions = array();
        foreach ($dataExtensions as $ext) {
            $extension = new \stdClass();
            $extension->task = $taskId;
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
                    throw new \Exception($taskExtensionModel->errors());
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    public function addAssignees($taskId, $dataAssignees)
    {
        if (!isset($dataAssignees)) return;

        // delete all assigned task users
        $taskAssigneeModel = new TaskAssigneeModel();
        try {
            if ($taskAssigneeModel->where("task", $task->id)->delete() === false) {
                throw new \Exception($taskAssigneeModel->errors());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $assignees = array();
        foreach ($dataAssignees as $person) {
            $assignee = new \stdClass();
            $assignee->task = $task->id;
            $assignee->person = $person;
            $assignees[] = $assignee;
        }

        // insert the assignees if any
        if (count($assignees)) {
            try {
                if ($taskAssigneeModel->insertBatch($assignees) === false) {
                    throw new \Exception($taskAssigneeModel->errors());
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }
}