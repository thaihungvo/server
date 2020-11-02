<?php namespace App\Models;

use CodeIgniter\Model;

class TagModel extends Model
{
    protected $table      = 'boards_tags';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = ['id', 'title', 'color', 'board'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';

    protected $validationRules = [
        'id' => 'required|min_length[35]',
        'title' => 'required|alpha_numeric_punct',
        'color' => 'required|alpha_numeric_punct',
        'board' => 'required|min_length[35]'
    ];

    protected $validationMessages = [
        'id' => [
            'required' => 'ERR_BOARD_TAGS_ID_REQUIRED',
            'min_length' => 'ERR_BOARD_TAGS_ID_INVALID',
        ],
        'title' => [
            'required' => 'ERR_BOARD_TAGS_TITLE_REQUIRED',
        ],
        'color' => [
            'required' => 'ERR_BOARD_TAGS_COLOR_REQUIRED',
        ],
        'board' => [
            'required' => 'ERR_BOARD_TAGS_BOARD_REQUIRED',
        ],
    ];
}