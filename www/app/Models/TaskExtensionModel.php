<?php namespace App\Models;

use CodeIgniter\Model;

class TaskExtensionModel extends Model
{
    protected $table      = 'tasks_extensions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['id', 'task', 'title', 'type', 'content', 'options'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules = [
        'id' => 'required|min_length[35]',
        'task' => 'required|min_length[35]',
        'type' => 'required|string'
    ];

    protected $validationMessages = [
        'id' => [
            'required' => 'ERR-TASK-EXT-ID-REQUIRED',
            'min_length' => 'ERR-TASK-EXT-ID-INVALID',
        ],
        'task' => [
            'required' => 'ERR-TASK-ID-REQUIRED',
            'min_length' => 'ERR-TASK-ID-INVALID',
        ],
        'type' => [
            'required' => 'ERR-TASK-EXT-TYPE-REQUIRED',
            'string' => 'ERR-TASK-EXT-TYPE-INVALID'
        ]
    ];
}