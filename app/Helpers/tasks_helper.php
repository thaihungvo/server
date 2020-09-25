<?php

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