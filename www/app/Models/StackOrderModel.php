<?php namespace App\Models;

use CodeIgniter\Model;

class StackOrderModel extends Model
{
    protected $table      = 'stacks_order';
    protected $primaryKey = 'stack';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['board', 'stack', 'order'];

    protected $useTimestamps = false;
    protected $createdField  = '';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules = [
        'board' => 'required|min_length[35]',
        'stack' => 'required|min_length[35]',
        'order' => 'required|integer'
    ];

    protected $validationMessages = [
        'board' => [
            'required' => 'ERR-BOARD-ID-REQUIRED',
            'min_length' => 'ERR-BOARD-ID-INVALID',
        ],
        'stack' => [
            'required' => 'ERR-STACK-ID-REQUIRED',
            'min_length' => 'ERR-STACK-ID-INVALID',
        ],
        'order' => [
            'required' => 'ERR-ORDER-REQUIRED',
            'integer' => 'ERR-ORDER-INVALID'
        ]
    ];
}