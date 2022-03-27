<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\DocumentModel;
use App\Models\TaskModel;

class PermissionModel extends Model
{
    protected $table      = 'permissions';
    protected $primaryKey = 'resource';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['resource', 'type', 'user', 'permission'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = '';

    protected $validationRules = [
        'resource' => 'required|min_length[20]',
        'type' => 'required|in_list[DOCUMENT,TASK]',
        'permission' => 'required|in_list[FULL,EDIT,LIMITED,NONE]',
    ];

    public function getResource($id, $type)
    {
        $model = null;
        if ($type === "DOCUMENT") {
            $model = new DocumentModel();
        } elseif ($type == 'TASK') {
            $model = new TaskModel();
        } else {
            return null;
        }
        if (!$model) return null;
        return $model->find($id);
    }
}