<?php 

use App\Models\DocumentModel;

if (!function_exists('permissions_can'))
{
    function permissions_can($action, $document, $section)
    {
        $permission = false;

        // Documents
        if ($section === "documents") {
            if ($action === "delete") {
                $permission = $document->permission === "FULL" ? true : false;
            }
            if ($action === "update") {
                $permission = $document->permission === "FULL" || $document->permission === "EDIT" ? true : false;
            }
            if ($action === "options") {
                $permission = $document->permission === "FULL" ? true : false;
            }
        }

        // Stacks
        if ($section === "stacks") {
            if ($action === "add") {
                $permission = $document->permission === "FULL" || $document->permission === "EDIT" ? true : false;
            }
            if ($action === "read") {
                $permission = true;
            }
            if ($action === "update") {
                $permission = $document->permission === "FULL" || $document->permission === "EDIT" ? true : false;
            }
            if ($action === "delete") {
                $permission = $document->permission === "FULL" || $document->permission === "EDIT" ? true : false;
            }
        }

        if ($permission && !$document->isOwner && !$document->public) {
            $permission = false;
        }

        return $permission;
    }
}