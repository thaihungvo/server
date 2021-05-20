<?php

use App\Models\UserModel;
use App\Models\TaskWatcherModel;

if (!function_exists('watchers_load'))
{
    function watchers_load($taskID, $user) 
	{
		$userModel = new UserModel();
        $userBuilder = $userModel->builder();
        $usersQuery = $userBuilder->select("users.id, users.email, users.nickname, users.firstName, users.lastName, tasks_watchers.created")
            ->join('tasks_watchers', 'tasks_watchers.user = users.id', 'left')
            ->groupStart()
                ->where('tasks_watchers.task', $taskID)
                ->where('users.id !=', $user->id)
            ->groupEnd()
            ->orderBy('users.firstName', 'ASC')
            ->get();
        return $usersQuery->getResult();
    }
}

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