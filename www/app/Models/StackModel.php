<?php namespace App\Models;

use CodeIgniter\Model;

class StackModel extends Model
{
    protected $table      = "stacks";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = true;

    protected $allowedFields = ["id", "title", "project", "tag", "position", "created", "updated"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
    protected $deletedField  = "deleted";

    protected $validationRules = [
        "id" => "required|min_length[35]",
        "title" => "required|alpha_numeric_punct",
        "project" => "required|min_length[35]",
        "position" => "required"
    ];

    protected $validationMessages = [
        "id" => [
            "required" => "Missing required field `id`",
            "min_length" => "Invalid field `id`",
        ],
        "title" => [
            "required" => "Missing required field `title`",
        ],
        "project" => [
            "required" => "Missing required field `project`",
            "min_length" => "Invalid field `project`",
        ],
    ];
}