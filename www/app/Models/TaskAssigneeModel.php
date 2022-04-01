<?php namespace App\Models;

use CodeIgniter\Model;

class TaskAssigneeModel extends Model
{
    protected $table      = "tasks_assignees";
    protected $primaryKey = "task";
    protected $returnType = "object";

    protected $useSoftDeletes = false;

    protected $allowedFields = ["task", "person"];

    protected $useTimestamps = false;
    protected $createdField  = "";
    protected $updatedField  = "";
    protected $deletedField  = "";

    //protected $afterFind = ["formatAssignees"];

    protected $validationRules = [
        "task" => "required|min_length[20]",
        "person" => "required|min_length[20]",
    ];

    protected function formatAssignee(&$assignee)
    {

    }

    protected function formatAssignees(array $data)
    {
        // format list of assignees
        if (!$data["singleton"] && $data["data"]) {
            foreach ($data["data"] as $key => &$assignee) {
                $this->formatAssignees($assignee);
            }
        }
        return $data;
    }

    public function getTaskAssignees($taskId)
    {
        return $this->select("people.id, people.firstName, people.lastName, tasks_assignees.task")
            ->join('people', 'people.id = tasks_assignees.person', 'left')
            ->where('tasks_assignees.task', $taskId)
            ->findAll();
    }
}