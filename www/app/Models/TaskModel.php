<?php namespace App\Models;

use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table      = "tasks";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = true;

    protected $allowedFields = ["id", "title", "description", "tags", "duedate", "startdate", "cover", "done", "altTags", "estimate", "spent", "progress", "user", "hourlyFee", "owner", "project", "stack", "position", "archived", "created", "updated"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
    protected $deletedField  = "deleted";

    protected $validationRules = [
        "id" => "required|min_length[35]",
        "title" => "required",
        "position" => "required"
    ];
}