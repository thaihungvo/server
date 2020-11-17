<?php namespace App\Models;

use CodeIgniter\Model;

class RecordOrderModel extends Model
{
    protected $table      = 'records_order';
    protected $primaryKey = 'record';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['folder', 'record', 'order'];

    protected $useTimestamps = false;
    protected $createdField  = '';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules = [
        'folder' => 'required|min_length[35]',
        'record' => 'required|min_length[35]',
        'order' => 'required|integer'
    ];

    protected $validationMessages = [
        'folder' => [
            'required' => 'ERR-FOLDER-ID-REQUIRED',
            'min_length' => 'ERR-FOLDER-ID-INVALID',
        ],
        'record' => [
            'required' => 'ERR-RECORD-ID-REQUIRED',
            'min_length' => 'ERR-RECORD-ID-INVALID',
        ],
        'order' => [
            'required' => 'ERR-ORDER-REQUIRED',
            'integer' => 'ERR-ORDER-INVALID'
        ]
    ];
}