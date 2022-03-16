<?php namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table      = 'permissions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['resource', 'user', 'permission'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = '';

    protected $validationRules = [
        'resource' => 'required|min_length[20]',
        'permission' => 'required|in_list[FULL,EDIT,LIMITED,NONE]',
    ];
}