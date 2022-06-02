<?php namespace App\Models;

use CodeIgniter\Model;

class AttachmentModel extends Model
{
    protected $table      = 'attachments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = true;

    protected $allowedFields = ['owner', 'resource', 'title', 'content', 'extension', 'size', 'hash', 'type'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = 'deleted';

    protected $validationRules = [
        'owner' => 'required|integer',
        'resource' => 'required|min_length[20]',
        'title' => 'required|string',
        'content' => 'required|string',
        'extension' => 'required|string',
        'size' => 'required|integer',
        'type' => 'required|string'
    ];
}