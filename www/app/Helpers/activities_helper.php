<?php

use App\Models\ActivityModel;

if (!function_exists('get_activities'))
{
    function get_activities($date, $user, $board) 
	{
		$activityModel = new ActivityModel();
        $activityBuilder = $activityModel->builder();
        $activityQuery = $activityBuilder->select("activities.*, users.id AS uid, users.email, users.nickname, users.firstName, users.lastName")
            ->join('users', 'users.id = activities.user', 'left')
            // ->groupStart()
            //     ->where("activities.board", $board)
            //     ->orGroupStart()    
            //         ->where("activities.section", "BOARDS")
            //     ->groupEnd()
            // ->groupEnd()
            // ->where('activities.user !=', $user->id)
            ->where('activities.instance !=', $user->instance)
            ->where('activities.created >', $date)
            ->orderBy('activities.created', 'DESC')
            ->get();
        return $activityQuery->getResult();
    }
}