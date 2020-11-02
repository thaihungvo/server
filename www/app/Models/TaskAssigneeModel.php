<?php namespace App\Models;

use CodeIgniter\Model;

class TaskAssigneeModel extends Model
{
    protected $table      = 'tasks_assignees';
    protected $primaryKey = 'task';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['task', 'user'];

    protected $useTimestamps = false;
    protected $createdField  = '';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules = [
        'task' => 'required|min_length[35]',
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