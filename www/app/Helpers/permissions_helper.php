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
                $permission = $document->data->permission === "FULL" ? true : false;
            }
            if ($action === "update") {
                $permission = $document->data->permission === "FULL" || $document->data->permission === "EDIT" ? true : false;
            }
            if ($action === "options") {
                $permission = $document->data->permission === "FULL" ? true : false;
            }
        }

        // Stacks
        if ($section === "stacks") {
            if ($action === "add") {
                $permission = $document->data->permission === "FULL" || $document->data->permission === "EDIT" ? true : false;
            }
            if ($action === "read") {
                $permission = true;
            }
            if ($action === "update") {
                $permission = $document->data->permission === "FULL" || $document->data->permission === "EDIT" ? true : false;
            }
            if ($action === "delete") {
                $permission = $document->data->permission === "FULL" || $document->data->permission === "EDIT" ? true : false;
            }
        }

        // Tasks
        if ($section === "tasks") {
            if ($action === "add") {
                $permission = $document->data->permission === "FULL" || $document->data->permission === "EDIT" ? true : false;
            }
        }

        if ($permission && !$document->data->isOwner && !$document->data->public) {
            $permission = false;
        }

        return $permission;
    }
}