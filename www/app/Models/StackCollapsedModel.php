<?php namespace App\Models;

use CodeIgniter\Model;

class StackCollapsedModel extends Model
{
    protected $table      = 'stacks_collapsed';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['stack', 'collapsed', 'user'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules = [
        'stack' => 'required|min_length[35]',
        'collapsed' => 'required|integer',
        'user' => 'required|integer'
    ];

    protected $validationMessages = [
        'stack' => [
            'required' => 'ERR-COLLAPSED-STACK-REQUIRED',
            'min_length' => 'ERR-COLLAPSED-STACK-INVALID',
        ],
        'collapsed' => [
            'required' => 'ERR-COLLAPSED-COLLAPSED-REQUIRED',
            'integer' => 'ERR-COLLAPSED-COLLAPSED-INVALID'
        ],
        'user' => [
            'required' => 'ERR-COLLAPSED-USER-REQUIRED',
            'integer' => 'ERR-COLLAPSED-USER-INVALID'
        ]
    ];
}