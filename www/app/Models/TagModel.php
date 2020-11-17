<?php namespace App\Models;

use CodeIgniter\Model;

class TagModel extends Model
{
    protected $table      = "projects_tags";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $allowedFields = ["id", "title", "color", "project"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";

    protected $validationRules = [
        "id" => "required|min_length[35]",
        "title" => "required|alpha_numeric_punct",
        "color" => "required|alpha_numeric_punct",
        "project" => "required|min_length[35]"
    ];

    protected $validationMessages = [
        "id" => [
            "required" => "ERR-PROJECT-TAGS-ID_REQUIRED",
            "min_length" => "ERR-PROJECT-TAGS-ID_INVALID",
        ],
        "title" => [
            "required" => "ERR-PROJECT-TAGS-TITLE_REQUIRED",
        ],
        "color" => [
            "required" => "ERR-PROJECT-TAGS-COLOR_REQUIRED",
        ],
        "project" => [
            "required" => "ERR-PROJECT-TAGS-PROJECT-REQUIRED",
            "min_length" => "ERR-PROJECT-TAGS-PROJECT-INVALID"
        ],
    ];
}