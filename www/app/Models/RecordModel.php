<?php namespace App\Models;

use CodeIgniter\Model;

class RecordModel extends Model
{
    protected $table      = 'records';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = true;

    protected $allowedFields = ['id', 'title', 'owner', 'everyone', 'type'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = 'deleted';

    protected $validationRules = [
        'id' => 'required|min_length[35]',
        'title' => 'required|alpha_numeric_punct',
        'owner' => 'required|integer',
        'type' => 'required'
    ];

    protected $validationMessages = [
        'id' => [
            'required' => 'ERR_RECORD_ID_REQUIRED',
            'min_length' => 'ERR_RECORD_ID_INVALID',
        ],
        'title' => [
            'required' => 'ERR_RECORD_TITLE_REQUIRED',
        ],
        'owner' => [
            'required' => 'ERR_RECORD_OWNER_REQUIRED',
        ],
        'type' => [
            'required' => 'ERR_RECORD_TYPE_REQUIRED',
        ],
    ];
}