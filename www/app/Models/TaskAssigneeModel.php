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

    protected $validationRules = [
        "task" => "required|min_length[20]",
        "person" => "required|min_length[20]",
    ];
}