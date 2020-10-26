<?php namespace App\Models;

use CodeIgniter\Model;

class AttachmentModel extends Model
{
    protected $table      = 'attachments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = true;

    protected $allowedFields = ['owner', 'task', 'title', 'content', 'extension', 'size', 'hash', 'type'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = 'deleted';

    protected $validationRules = [
        'owner' => 'required|integer',
        'task' => 'required|min_length[35]',
        'title' => 'required|string',
        'content' => 'required|string',
        'extension' => 'required|string',
        'hash' => 'required|string',
        'size' => 'required|integer',
        'type' => 'required|string'
    ];

    protected $validationMessages = [
        'task' => [
            'required' => 'ERR-ATTACHMENT-TASK-REQUIRED',
            'min_length' => 'ERR-ATTACHMENT-TASK-INVALID',
        ],
        'title' => [
            'required' => 'ERR-ATTACHMENT-TITLE-REQUIRED',
            'string' => 'ERR-ATTACHMENT-TITLE-INVALID'
        ],
        'extension' => [
            'required' => 'ERR-ATTACHMENT-EXTENSION-REQUIRED',
            'string' => 'ERR-ATTACHMENT-EXTENSION-INVALID'
        ],
        'size' => [
            'required' => 'ERR-ATTACHMENT-SIZE-REQUIRED',
            'integer' => 'ERR-ATTACHMENT-SIZE-INVALID'
        ]
    ];
}