<?php namespace App\Models;

use CodeIgniter\Model;

class ProjectOptionsModel extends Model
{
    protected $table      = "projects_options";
    protected $primaryKey = "project";
    protected $returnType = "object";

    protected $useSoftDeletes = false;

    protected $allowedFields = ["project", "hourlyFee", "feeCurrency", "archived_order"];

    protected $useTimestamps = false;
    protected $createdField  = "";
    protected $updatedField  = "";
    protected $deletedField  = "";

    protected $validationRules = [
        "project" => "required|min_length[35]",
        "archived_order" => "required|string"
    ];

    protected $validationMessages = [
        "project" => [
            "required" => "Missing required field `project`",
            "min_length" => "Invalid field `project`",
        ],
        "archived_order" => [
            "required" => "Missing required field `archived_order`",
        ],
    ];
}