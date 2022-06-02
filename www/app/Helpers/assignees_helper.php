<?php

use App\Models\TaskAssigneeModel;
use App\Models\UserModel;

if (!function_exists('tasks_assignees'))
{
    function tasks_assignees($tasksIDs) 
	{
        if (!count($tasksIDs)) {
            return array();
        }

		$taskAssigneeModel = new TaskAssigneeModel();
        $taskAssigneeBuilder = $taskAssigneeModel->builder();
        $taskAssigneeQuery = $taskAssigneeBuilder->select("users.id, users.firstName, users.lastName, tasks_assignees.task")
            ->join('users', 'users.id = tasks_assignees.person', 'left')
            ->whereIn('tasks_assignees.task', $tasksIDs)
            ->get();
        $assignees = $taskAssigneeQuery->getResult();
        return $assignees;
    }
}