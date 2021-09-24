<?php namespace App\Models;

use CodeIgniter\Model;

class NotepadModel extends Model
{
    protected $table      = "notepads";
    protected $primaryKey = "notepad";
    protected $returnType = "object";

    protected $useSoftDeletes = true;

    protected $allowedFields = ["notepad", "content"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
    protected $deletedField  = "deleted";
}

