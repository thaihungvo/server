<?php 

use App\Models\DocumentModel;

if (!function_exists('permissions_task'))
{
    function permissions_task($project, $user, $permission)
    {
        // if the project is not public and the current user is not the owner
        if (!$project->public && $project->owner != $user->id) return false;
    }
}