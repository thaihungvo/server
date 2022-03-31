<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\TaskExtensionModel;
use App\Models\AttachmentModel;
use App\Models\TaskAssigneeModel;

class TaskModel extends Model
{
    protected $table      = "tasks";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = true;

    protected $allowedFields = ["id", "title", "description", "showDescription", "tags", "status", "duedate", "startdate", "cover", "done", "altTags", "estimate", "spent", "progress", "user", "hourlyFee", "owner", "priority", "repeats", "project", "stack", "position", "archived", "created", "updated"];

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

    protected function formatTasks(array $data)
    {
        // format single task
        if ($data["singleton"] && $data["data"]) {
            $data["data"]->cover = boolval($data["data"]->cover);
            $data["data"]->done = boolval($data["data"]->done);
            $data["data"]->altTags = boolval($data["data"]->altTags);
            $data["data"]->showDescription = boolval($data["data"]->showDescription);
            $data["data"]->progress = intval($data["data"]->progress);
            $data["data"]->position = intval($data["data"]->position);
            $data["data"]->public = boolval($data["data"]->public);
            // $data["data"]->isOwner = $data["data"]->owner;
            unset($data["data"]->order);
            unset($data["data"]->stack);
            unset($data["data"]->deleted);

            if (is_string($data["data"]->tags)) {
                $data["data"]->tags = json_decode($data["data"]->tags);
            }
    
            if (is_string($data["data"]->repeats)) {
                $data["data"]->repeats = json_decode($data["data"]->repeats);
            }

            // connect assignees to task
            helper('assignees');
            $assignees = tasks_assignees([$data["data"]->id]);

            foreach ($assignees as &$assignee) {
                if (!isset($data["data"]->assignees)) {
                    $data["data"]->assignees = array();
                }

                if ($assignee->task === $data["data"]->id) {
                    unset($assignee->task);
                    $data["data"]->assignees[] = [
                        "id" => $assignee->id,
                        "name" => $assignee->firstName ." ". $assignee->lastName
                    ];
                }
            }

            // load extensions
            $extensions = array();
            $taskExtensionModel = new TaskExtensionModel();
            $extensions = $taskExtensionModel->where("task", $data["data"]->id)->findAll();

            // unwrap the extensions
            foreach ($extensions as &$extension) {
                $extension->options = json_decode($extension->options);
                $extension->content = json_decode($extension->content);
            }

            // load task attachments
            $attachments = array();
            $attachmentModel = new AttachmentModel();
            $attachments = $attachmentModel->where("resource", $data["data"]->id)->findAll();

            $data["data"]->extensions = array();
            foreach ($extensions as &$extension) {
                if ($extension->task == $data["data"]->id) {
                    unset($extension->task);
                    $data["data"]->extensions[] = $extension;
                }
            }

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

            if (!count($data["data"]->extensions)) {
                unset($data["data"]->extensions);
            }
        }

        // format list of tasks
        if (!$data["singleton"] && $data["data"]) {
        }

        return $data;
    }

    public function formatData(&$data)
    {
        // enforce an id in case there"s none
        if (!isset($data->id)) {
            helper("uuid");
            $data->id = uuid();
        }

        if (isset($data->repeats)) {
            $data->repeats = json_encode($data->repeats);
        }

        // convert tags to string
        if (isset($data->tags)) {
            $data->tags = json_encode($data->tags);
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

    public function addExtensions($taskId, $dataExtensions)
    {
        if (!isset($dataExtensions)) return;
        helper("uuid");

        // delete the current task extensions
        $taskExtensionModel = new TaskExtensionModel();
        try {
            if ($taskExtensionModel->where("task", $task->id)->delete() === false) {
                throw new ErrorException($taskExtensionModel->errors());
            }
        } catch (\Exception $e) {
            throw new ErrorException($e->getMessage());
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
                    throw new ErrorException($taskExtensionModel->errors());
                }
            } catch (\Exception $e) {
                throw new ErrorException($e->getMessage());
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
                throw new ErrorException($taskAssigneeModel->errors());
            }
        } catch (\Exception $e) {
            throw new ErrorException($e->getMessage());
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
                    throw new ErrorException($taskAssigneeModel->errors());
                }
            } catch (\Exception $e) {
                throw new ErrorException($e->getMessage());
            }
        }
    }
}