<?php namespace App\Models;

use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table      = 'tasks';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = true;

    protected $allowedFields = ['id', 'title', 'content', 'tags', 'duedate', 'startdate', 'cover', 'done', 'altTags', 'estimate', 'spent', 'progress', 'user', 'assignee', 'stack', 'archived'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = 'deleted';

    protected $validationRules = [
        'id' => 'required|min_length[35]',
        'title' => 'required',
        'stack' => 'required|min_length[35]'
    ];

    protected $validationMessages = [
        'id' => [
            'required' => 'ERR_TASK_ID_REQUIRED',
            'min_length' => 'ERR_TASK_ID_INVALID',
        ],
        'title' => [
            'required' => 'ERR_TASK_TITLE_REQUIRED',
        ],
        'stack' => [
            'required' => 'ERR_TASK_STACK_REQUIRED',
            'min_length' => 'ERR_TASK_STACK_INVALID'
        ]
    ];
}