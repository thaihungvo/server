<?php namespace App\Models;

use CodeIgniter\Model;

class RecordMemberModel extends Model
{
    protected $table      = "records_members";
    protected $primaryKey = "record";
    protected $returnType = "object";

    protected $useSoftDeletes = false;

    protected $allowedFields = ["record", "user"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "";
    protected $deletedField  = "";

    protected $validationRules = [
        "record" => "required|min_length[20]",
        "user" => "required"
    ];

    protected $validationMessages = [
        "record" => [
            "required" => "ERR-RECORD-ID-REQUIRED",
            "min_length" => "ERR-RECORD-ID-INVALID",
        ],
        "user" => [
            "required" => "ERR-USER-ID-REQUIRED",
        ]
    ];
}