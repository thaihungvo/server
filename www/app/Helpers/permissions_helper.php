<?php 

use App\Models\DocumentModel;

if (!function_exists('permissions_can'))
{
    function permissions_can($permission, $action, $section)
    {
        // Documents
        if ($section === "documents") {
            if ($action === "delete") {
                return $permission === "FULL" ? true : false;
            }

            if ($action === "update") {
                return $permission === "FULL" || $permission === "EDIT" ? true : false;
            }
        }

        return false;
    }
}