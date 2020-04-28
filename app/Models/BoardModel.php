<?php namespace App\Models;

use CodeIgniter\Model;

class BoardModel extends Model
{
    protected $table      = 'boards';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useSoftDeletes = true;

    protected $allowedFields = ['id', 'title', 'owner', 'archived_order'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = 'deleted';

    protected $validationRules = [
        'id' => 'required|min_length[35]',
        'title' => 'required|alpha_numeric_punct',
        'owner' => 'required|integer',
        'archived_order' => 'required|string'
    ];

    protected $validationMessages = [
        'id' => [
            'required' => 'ERR_BOARD_ID_REQUIRED',
            'min_length' => 'ERR_BOARD_ID_INVALID',
        ],
        'title' => [
            'required' => 'ERR_BOARD_TITLE_REQUIRED',
        ],
        'owner' => [
            'required' => 'ERR_BOARD_OWNER_REQUIRED',
        ],
        'archived_order' => [
            'required' => 'ERR_BOARD_ORDER_REQUIRED',
        ],
    ];
}