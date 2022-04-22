<?php namespace App\Controllers;

use App\Models\PermissionModel;

class PermissionsController extends BaseController
{
    public function get_v1($resourceId)
    {
        $permissionModel = new PermissionModel($this->request->user);
        $permission = $permissionModel
            ->where("user", NULL)
            ->find($resourceId);

        $this->exists($permission);
        
        $resource = $permissionModel->getResource($resourceId, $permission->type);

        $msg = "You do not have permission to perform this action.";
        if (!$resource) return $this->reply(null, 403, $msg);

        $permissions = new \stdClass();
        $permissions->$resourceId = isset($resource->data) ? $resource->data->permissions : $resource->permissions;

        return $this->reply($permissions);
    }

    public function get_users_v1($resourceId)
	{
        $permissionModel = new PermissionModel($this->request->user);

        $permissions = $permissionModel
            ->select("permissions.permission, permissions.type, users.id, users.firstName, users.lastName, users.nickname, users.email")
            ->join("users", "users.id = permissions.user", "left")
            ->where("permissions.resource", $resourceId)
            ->where($this->db->protectIdentifiers("permissions.user") . " IS NOT NULL", NULL, false)
            ->findAll();

        if (count($permissions)) {
            $resource = $permissionModel->getResource($resourceId, $permissions[0]->type);
            $msg = "You do not have permission to perform this action.";

            if (!$resource) return $this->reply(null, 403, $msg);
            if (isset($resource->data)) {
                if ($this->request->user->id != $resource->data->owner) {
                    return $this->reply(null, 403, $msg);
                }
            } else if ($this->request->user->id != $resource->owner) {
                return $this->reply(null, 403, $msg);
            }
        }

        // removing unneeded type field
        foreach ($permissions as &$permission) {
            unset($permission->type);
            $permission->id = intval($permission->id);
            $permission->avatar = false;
            $permission->name = $permission->firstName . " " . $permission->lastName;
            unset($permission->firstName);
            unset($permission->lastName);
        }

        return $this->reply($permissions);
    }

    public function add_v1()
    {
        $permissionModel = new PermissionModel($this->request->user);
        
        $data = $this->request->getJSON();
        if (!isset($data->permission)) {
            return $this->reply("Permission missing or not valid", 500, "ERR-PERMISSION-CREATE");
        }

        $resource = $permissionModel->getResource($data->resource, $data->type);
        $msg = "You do not have permission to perform this action.";

        if (!$resource) return $this->reply(null, 403, $msg);
        if (isset($resource->data)) {
            if ($this->request->user->id != $resource->data->owner) {
                return $this->reply(null, 403, $msg);
            }
        } else if ($this->request->user->id != $resource->owner) {
            return $this->reply(null, 403, $msg);
        }

        // check if there's already a permission with these settings
        $permissions = $permissionModel
            ->where("user", $data->user)
            ->where("resource", $data->resource)
            ->where("type", $data->type)
            ->findAll();

        if (count($permissions) > 0) {
            return $this->reply("A permission with these params already exist", 409, "ERR-PERMISSION-ADD");
        }

        try {
            if ($permissionModel->insert($data) === false) {
                return $this->reply($permissionModel->errors(), 500, "ERR-PERMISSIONS-ADD");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-PERMISSIONS-ADD");
        }

        $this->addActivity(
            "",
            "",
            $resource->id,
            $this::ACTION_CREATE,
            $this::SECTION_PERMISSION
        );

        return $this->reply(true);
    }

    public function delete_user_v1($resourceId, $userId)
    {
        $permissionModel = new PermissionModel($this->request->user);
        $permission = $permissionModel
            ->where("user", $userId)
            ->find($resourceId);
        $this->exists($permission);

        $resource = $permissionModel->getResource($resourceId, $permission->type);
        if (!$resource) return $this->reply(null, 403, $msg);
        if (isset($resource->data)) {
            if ($this->request->user->id != $resource->data->owner) {
                return $this->reply(null, 403, $msg);
            }
        } else if ($this->request->user->id != $resource->owner) {
            return $this->reply(null, 403, $msg);
        }

        $deleted = $permissionModel
            ->where("user", $userId)
            ->where("resource", $resourceId)
            ->delete();

        try {
            if ($deleted === false) {
                return $this->reply($permissionModel->errors(), 500, "ERR-PERMISSIONS-DELETE");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-PERMISSIONS-DELETE");
        }

        $this->addActivity(
            "",
            "",
            $resource->id,
            $this::ACTION_DELETE,
            $this::SECTION_PERMISSION
        );

        return $this->reply(true);
    }

    public function update_user_v1($resourceId)
    {
        $data = $this->request->getJSON();
        $userId = isset($data->user) ? $data->user : NULL;

        $permissionModel = new PermissionModel($this->request->user);
        $permission = $permissionModel
            ->where("user", $userId)
            ->find($resourceId);
        $this->exists($permission);

        $resource = $permissionModel->getResource($resourceId, $permission->type);
        $msg = "You do not have permission to perform this action.";

        $this->exists($resource);

        if (isset($resource->data)) {
            if ($this->request->user->id != $resource->data->owner) {
                return $this->reply(null, 403, $msg);
            }
        } else if ($this->request->user->id != $resource->owner) {
            return $this->reply(null, 403, $msg);
        }
        
        $updated = $permissionModel
            ->where("user", $userId)
            ->where("resource", $resourceId)
            ->set(["permission" => $data->permission])
            ->update();

        try {
            if ($updated === false) {
                return $this->reply($permissionModel->errors(), 500, "ERR-PERMISSIONS-UPDATE");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-PERMISSIONS-UPDATE");
        }

        $this->addActivity(
            "",
            "",
            $resource->id,
            $this::ACTION_UPDATE,
            $this::SECTION_PERMISSION
        );

        return $this->reply(true);
    }
}