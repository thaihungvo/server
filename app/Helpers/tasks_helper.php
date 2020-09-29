<?php

use App\Models\TaskModel;

if (!function_exists('task_format'))
{
    function task_format($task) 
	{
		$task->cover = (bool)$task->cover;
        $task->done = (bool)$task->done;
        $task->altTags = (bool)$task->altTags;
        $task->progress = (int)$task->progress;
        if (is_string($task->tags)) {
            $task->tags = json_decode($task->tags);
        }
        if (is_string($task->info)) {
            $task->info = json_decode($task->info);
        }

        return $task;
    }
}

if (!function_exists('task_last_updated'))
{
    function task_last_updated($taskID) 
	{
		$taskModel = new TaskModel();
        $taskBuilder = $taskModel->builder();

        $taskQuery = $taskBuilder->select("updated")
            ->where("id", $taskID)
            ->limit(1)
            ->get();
        
        $tasks = $taskQuery->getResult();

        if (!count($tasks)) {
            return null;
        }

        return $tasks[0]->updated;
    }
}