<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BaseModel;
use App\Models\DocumentModel;
use App\Models\StackModel;
use App\Models\TaskModel;

class PermissionModel extends BaseModel
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
        'type' => 'required|in_list[DOCUMENT,STACK,TASK]',
        'permission' => 'required|in_list[FULL,EDIT,LIMITED,NONE]',
    ];

    public function getResource($id, $type)
    {
        $model = null;
        if ($type === "DOCUMENT") {
            $model = new DocumentModel($this->user);
        } elseif ($type == "STACK") {
            $model = new StackModel($this->user);
        } elseif ($type == "TASK") {
            $model = new TaskModel($this->user);
        } else {
            return null;
        }
        if (!$model) return null;
        return $model->find($id);
    }

    public function getPermission($resourceId, $owner)
    {
        $db = db_connect();
        $permissionBuilder = $this->builder();
        $permissionQuery = $permissionBuilder->select("permissions.*, userPermissions.permission AS userPermission")
            ->from("permissions AS permissions", true)
            ->join("permissions AS userPermissions", "permissions.resource = userPermissions.resource AND userPermissions.user = ".$db->escape($this->user->id), "left")
            ->where("permissions.resource", $resourceId)
            ->where("permissions.user", NULL)
            ->get();
        $permissions = $permissionQuery->getResult();

        $userPermission = $owner == $this->user->id ? "FULL" : "NONE";
        if (count($permissions) && $owner != $this->user->id) {
            $permission = $permissions[0];
            // if the document is the same as the permission's resource
            if (
                $resourceId === $permission->resource && 
                ($permission->userPermission || $permission->permission)
            ) {
                $userPermission = isset($permission->userPermission) ? $permission->userPermission : $permission->permission;
            }
        }

        return $userPermission;
    }

    public function getPermissions(&$resources, $appendToData = false)
    {
        $db = db_connect();
        $resourceIds = array_map(fn($resource) => $resource->id, $resources);

        $permissionBuilder = $this->builder();
        $permissionQuery = $permissionBuilder->select("permissions.*, userPermissions.permission AS userPermission")
            ->from("permissions AS permissions", true)
            ->join("permissions AS userPermissions", "permissions.resource = userPermissions.resource AND userPermissions.user = ".$db->escape($this->user->id), "left")
            ->whereIn("permissions.resource", $resourceIds)
            ->where("permissions.user", NULL)
            ->get();
        $permissions = $permissionQuery->getResult();

        foreach ($resources as &$resource) {
            $userPermission = "FULL";
            $isOwner = false;
            if (isset($resource->isOwner)) {
                $isOwner = $resource->isOwner;
            } else if (isset($resource->data->isOwner)) {
                $isOwner = $resource->data->isOwner;
            }
            
            // if the user is not the owner that we need to apply any available permissions
            if (!$isOwner) {
                foreach ($permissions as $permission) {
                    // if the document is the same as the permission's resource
                    if (
                        $resource->id === $permission->resource && 
                        ($permission->userPermission || $permission->permission)
                    ) {
                        $userPermission = isset($permission->userPermission) ? $permission->userPermission : $permission->permission;
                    }
                }
            }

            if ($appendToData) {
                $resource->data->permission = $userPermission;
            } else {
                $resource->permission = $userPermission;
            }
        }
    }
}