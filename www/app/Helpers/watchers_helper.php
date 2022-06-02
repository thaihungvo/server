<?php

use App\Models\UserModel;
use App\Models\TaskWatcherModel;


if (!function_exists('tasks_watchers_last'))
{
    function tasks_watchers_last($taskID, $user) 
	{
		$taskWatcherModel = new TaskWatcherModel();
        $taskWatcherBuilder = $taskWatcherModel->builder();

        $taskWatcherQuery = $taskWatcherBuilder->select("user")
            ->where('task', $taskID)
            ->where('user !=', $user->id)
            ->get();

        $watchers = $taskWatcherQuery->getResult();
        $currentWatchers = array();

        foreach ($watchers as $watcher) {
            $currentWatchers[] = $watcher->user;
        }

        return $currentWatchers;
    }
}