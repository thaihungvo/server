<?php namespace App\Models;

use CodeIgniter\Model;

class StatusModel extends Model
{
    protected $table      = "statuses";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $allowedFields = ["id", "title", "color", "project"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
    protected $deletedField  = "deleted";

    protected $validationRules = [
        "id" => "required|min_length[20]",
        "title" => "required|alpha_numeric_punct",
        "color" => "required|alpha_numeric_punct"
    ];
}