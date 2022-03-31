<?php namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = "users";
    protected $primaryKey = "id";
    protected $returnType    = "object";

    protected $useSoftDeletes = false;

    protected $allowedFields = ["email", "password", "firstName", "lastName"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
}