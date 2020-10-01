<?php

use App\Models\ActivityModel;

if (!function_exists('get_activities'))
{
    function get_activities($date, $user, $board) 
	{
		$activityModel = new ActivityModel();
        $activityBuilder = $activityModel->builder();
        $activityQuery = $activityBuilder->select("*")
            ->groupStart()
                ->where("board", $board)
                ->orGroupStart()    
                    ->where("section", "BOARDS")
                ->groupEnd()
            ->groupEnd()
            ->where('user !=', $user)
            ->where('created >', $date)
            ->orderBy('created', 'DESC')
            ->get();
        return $activityQuery->getResult();
    }
}