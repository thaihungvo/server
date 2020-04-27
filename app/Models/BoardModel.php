<?php namespace App\Models;

use CodeIgniter\Model;

class BoardModel extends Model
{
    protected $table      = 'boards';
    protected $primaryKey = 'id';
    protected $returnType    = 'array';

    protected $useSoftDeletes = true;

    protected $allowedFields = ['title', 'tags', 'archived_order'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = 'deleted';
}