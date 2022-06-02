<?php 

use App\Models\DocumentModel;
use App\Models\PermissionModel;

/*
    FULL - edit, create, delete, options
    EDIT - edit, create, delete
    LIMIT - edit only owner (or assignee)
    NONE - read only
*/

if (!function_exists('permissions_can'))
{
    function permissions_can($action, $section, $permission, $isPublic, $isOwner)
    {
        $can = false;

        // Documents
        if ($section === "documents") {
            if ($action === "delete") {
                $can = $permission === "FULL" ? true : false;
            }
            if ($action === "update") {
                $can = $permission === "FULL" || $permission === "EDIT" ? true : false;
            }
            if ($action === "options") {
                $can = $permission === "FULL" ? true : false;
            }
        }

        // Stacks
        if ($section === "stacks") {
            if ($action === "add") {
                $can = $permission === "FULL" || $permission === "EDIT" ? true : false;
            }
            if ($action === "read") {
                $can = true;
            }
            if ($action === "update") {
                $can = $permission === "FULL" || $permission === "EDIT" ? true : false;
            }
            if ($action === "delete") {
                $can = $permission === "FULL" || $permission === "EDIT" ? true : false;
            }
        }

        // Tasks
        if ($section === "tasks") {
            if ($action === "add") {
                $can = $permission === "FULL" || $permission === "EDIT" ? true : false;
            }
            if ($action === "read") {
                $can = $permission !== "NONE" ? true : false;
            }
            if ($action === "update") {
                $can = $permission === "FULL" || $permission === "EDIT" || ($permission === "LIMITED" && $isOwner) ? true : false;
            }
            if ($action === "delete") {
                $can = $permission === "FULL" || $permission === "EDIT" ? true : false;
            }
        }

        // if ($can && !$resource->data->public && !$resource->data->isOwner) {
        //     $can = false;
        // }

        return $can;
    }
}

if (!function_exists('permissions_load_permissions'))
{
    function permissions_load_permissions(&$resources, $userId, $appendToData = false)
    {
        $db = db_connect();
        $resourceIds = array();
        foreach ($resources as $resource) {
            $resourceIds[] = $resource->id;
        }

        $permissionModel = new PermissionModel();
        $permissionBuilder = $permissionModel->builder();
        $permissionQuery = $permissionBuilder->select("permissions.*, userPermissions.permission AS userPermission")
            ->from("permissions AS permissions", true)
            ->join("permissions AS userPermissions", "permissions.resource = userPermissions.resource AND userPermissions.user = ".$db->escape($userId), "left")
            ->whereIn("permissions.resource", $resourceIds)
            ->where("permissions.user", NULL)
            ->get();
        $permissions = $permissionQuery->getResult();

        foreach ($resources as &$resource) {
            if ($appendToData) {
                $resource->data->permission = "FULL";
            } else {
                $resource->permission = "FULL";
            }
            
            // if the user is not the owner that we need to apply any available permissions
            if ($resource->owner != $userId) {
                foreach ($permissions as $permission) {
                    // if the document is the same as the permission's resource
                    if (
                        $resource->id === $permission->resource && 
                        ($permission->userPermission || $permission->permission)
                    ) {
                        $resource->data->permission = isset($permission->userPermission) ? $permission->userPermission : $permission->permission;
                    }
                }
            }
        }
    }
}