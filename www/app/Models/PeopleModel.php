<?php namespace App\Models;

use CodeIgniter\Model;

class PeopleModel extends Model
{
    protected $table      = "people";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = true;

    protected $allowedFields = ["id", "people"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
    protected $deletedField  = "deleted";

    protected $validationRules = [
        "id" => "required|min_length[35]",
        "people" => "required|min_length[35]",
    ];

    protected $validationMessages = [
        "id" => [
            "required" => "Missing required field `id`",
            "min_length" => "Invalid field `id`",
        ],
        "people" => [
            "required" => "Missing required field `people`",
            "min_length" => "Invalid field `id`",
        ],
    ];
}