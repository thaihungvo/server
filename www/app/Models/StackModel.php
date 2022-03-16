<?php namespace App\Models;

use CodeIgniter\Model;

class StackModel extends Model
{
    protected $table      = "stacks";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = true;

    protected $allowedFields = ["id", "title", "project", "tag", "maxTasks", "automation", "position", "created", "updated"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
    protected $deletedField  = "deleted";

    protected $validationRules = [
        "id" => "required|min_length[20]",
        "title" => "required|alpha_numeric_punct",
        "project" => "required|min_length[20]",
        "position" => "required"
    ];
}