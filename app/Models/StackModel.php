<?php namespace App\Models;

use CodeIgniter\Model;

class StackModel extends Model
{
    protected $table      = 'stacks';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = true;

    protected $allowedFields = ['id', 'title', 'board'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = 'deleted';

    protected $validationRules = [
        'id' => 'required|min_length[35]',
        'title' => 'required|alpha_numeric_punct',
        'board' => 'required|min_length[35]'
    ];

    protected $validationMessages = [
        'id' => [
            'required' => 'ERR_BOARD_STACKS_ID_REQUIRED',
            'min_length' => 'ERR_BOARD_STACKS_ID_INVALID',
        ],
        'title' => [
            'required' => 'ERR_BOARD_STACKS_TITLE_REQUIRED',
        ],
        'board' => [
            'required' => 'ERR_BOARD_STACKS_BOARD_REQUIRED',
            'min_length' => 'ERR_BOARD_STACKS_BOARD_INVALID',
        ],
    ];
}