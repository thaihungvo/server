<?php namespace App\Models;

use CodeIgniter\Model;

class ActivityModel extends Model
{
    protected $table      = "activities";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = false;

    protected $allowedFields = ["user", "instance", "parent", "item", "action", "section"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "";
    protected $deletedField  = "";
}