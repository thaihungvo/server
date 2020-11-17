<?php namespace App\Models;

use CodeIgniter\Model;

class FolderModel extends Model
{
    protected $table      = 'folders';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = true;

    protected $allowedFields = ['id', 'title', 'owner'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = 'deleted';

    protected $validationRules = [
        'id' => 'required|min_length[35]',
        'title' => 'required|alpha_numeric_punct',
        'owner' => 'required'
    ];

    protected $validationMessages = [
        'id' => [
            'required' => 'ERR_FOLDER_ID_REQUIRED',
            'min_length' => 'ERR_FOLDER_ID_INVALID',
        ],
        'title' => [
            'required' => 'ERR_FOLDER_TITLE_REQUIRED',
        ],
        'owner' => [
            'required' => 'ERR_FOLDER_OWNER_REQUIRED'
        ],
    ];
}