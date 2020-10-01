<?php namespace App\Models;

use CodeIgniter\Model;

class BoardMemberModel extends Model
{
    protected $table      = 'boards_members';
    protected $primaryKey = 'board';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['board', 'user'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules = [
        'board' => 'required|min_length[35]',
        'user' => 'required'
    ];

    protected $validationMessages = [
        'board' => [
            'required' => 'ERR_BOARD_ID_REQUIRED',
            'min_length' => 'ERR_BOARD_ID_INVALID',
        ],
        'user' => [
            'required' => 'ERR_USER_ID_REQUIRED',
        ]
    ];
}