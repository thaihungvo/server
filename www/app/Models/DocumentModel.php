<?php namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table      = "documents";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = true;

    protected $allowedFields = ["id", "title", "folder", "owner", "everyone", "type", "order"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
    protected $deletedField  = "deleted";

    protected $validationRules = [
        "id" => "required|min_length[35]",
        "title" => "required|alpha_numeric_punct",
        "owner" => "required|integer",
        "type" => "required",
        "order" => "required|numeric"
    ];

    protected $validationMessages = [
        "id" => [
            "required" => "Missing required field `id`",
            "min_length" => "Invalid field `id`",
        ],
        "title" => [
            "required" => "Missing required field `title`",
        ],
        "owner" => [
            "required" => "Missing required field `owner`",
        ],
        "type" => [
            "required" => "Missing required field `type`",
        ],
        "order" => [
            "required" => "Missing required field `order`",
        ],
    ];
}