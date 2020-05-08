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
}