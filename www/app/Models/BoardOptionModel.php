<?php namespace App\Models;

use CodeIgniter\Model;

class RecordModel extends Model
{
    protected $table      = 'boards_options';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['board', 'hourlyFee', 'feeCurrency', 'archived_order'];

    protected $useTimestamps = false;
    protected $createdField  = '';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules = [
        'board' => 'required|min_length[35]',
        'archived_order' => 'required|string'
    ];

    protected $validationMessages = [
        'board' => [
            'required' => 'ERR_BOARD_ID_REQUIRED',
            'min_length' => 'ERR_BOARD_ID_INVALID',
        ],
        'archived_order' => [
            'required' => 'ERR_BOARD_ORDER_REQUIRED',
        ],
    ];
}