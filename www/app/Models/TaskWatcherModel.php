<?php namespace App\Models;

use CodeIgniter\Model;

class TaskWatcherModel extends Model
{
    protected $table      = 'tasks_watchers';
    protected $primaryKey = 'task';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['board', 'task', 'user'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules = [
        'task' => 'required|min_length[20]',
        'user' => 'required',
    ];

    protected $validationMessages = [
        'task' => [
            'required' => 'ERR-TASK-ID-REQUIRED',
            'min_length' => 'ERR-TASK-ID-INVALID',
        ],
        'user' => [
            'required' => 'ERR-USER-ID-REQUIRED'
        ]
    ];
}