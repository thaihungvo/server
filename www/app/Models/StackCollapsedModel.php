<?php namespace App\Models;

use CodeIgniter\Model;

class StackCollapsedModel extends Model
{
    protected $table      = "stacks_collapsed";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = false;

    protected $allowedFields = ["stack", "collapsed", "user"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "";
    protected $deletedField  = "";

    protected $validationRules = [
        "stack" => "required|min_length[20]",
        "collapsed" => "required|integer",
        "user" => "required|integer"
    ];
}