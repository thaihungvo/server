<?php

use App\Models\TaskAssigneeModel;
use App\Models\PersonModel;

if (!function_exists('tasks_assignees'))
{
    function tasks_assignees($tasksIDs) 
	{
        if (!count($tasksIDs)) {
            return array();
        }

		$taskAssigneeModel = new TaskAssigneeModel();
        $taskAssigneeBuilder = $taskAssigneeModel->builder();
        $taskAssigneeQuery = $taskAssigneeBuilder->select("people.id, people.firstName, people.lastName, tasks_assignees.task")
            ->join('people', 'people.id = tasks_assignees.person', 'left')
            ->whereIn('tasks_assignees.task', $tasksIDs)
            ->get();
        return $taskAssigneeQuery->getResult();
    }
}