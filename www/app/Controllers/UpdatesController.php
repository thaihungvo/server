<?php namespace App\Controllers;

use CodeIgniter\Events\Events;
use App\Models\ActivityModel;

class UpdatesController extends BaseController
{
    public function updates_v1()
    {
        $user = $this->request->user;
        $date = date('Y-m-d H:i:s', strtotime('-3 seconds'));

        $activityModel = new ActivityModel();
        $activities = $activityModel->where("instance !=", $user->instance)
            ->where("created >", $date)
            ->orderBy("created", "desc")
            ->findAll();

        return $this->reply($activities);
    }

    public function updates()
    {
        $session = session();
        $session->destroy();
        
        // @set_time_limit(10);
        header("Cache-Control: no-cache");
        header("Content-Type: text/event-stream");
        header("Connection: keep-alive");
        if(function_exists('apache_setenv')){
            @apache_setenv('no-gzip',1);
        }
        @ini_set("zlib.output_compression", 0);
        @ini_set("implicit_flush", 1);
        for($i = 0; $i < ob_get_level(); $i++){
            ob_end_flush();
        }
        ob_implicit_flush(1);

        $user = $this->request->user;
        $board = $this->request->getGet("board");

        helper('activities');
        $lastCachedTimestamp = cache("last-update");
        $lastTimestamp = null;
        $lastDate = date("Y-m-d H:i:s");
        $notificationsList = array();

        ob_end_clean();

        do {
            //  && (!$lastCachedTimestamp || $lastCachedTimestamp > $lastTimestamp)
            if ($lastDate) {
                $activities = get_activities($lastDate, $user, $board);

                if (count($activities)) {
                    $activitiesGrouped = array();

                    foreach ($activities as $activity) {
                        $notificationID = $activity->section.$activity->item.$activity->action;

                        if (in_array($notificationID, $notificationsList)) {
                            continue;
                        }
                        $notificationsList[] = $notificationID;

                        if (!isset($activitiesGrouped[$activity->section][$activity->item])) {
                            $activitiesGrouped[$activity->section][$activity->item] = array();
                        }

                        $activitiesGrouped[$activity->section][$activity->item]["type"] = strtolower($activity->section);
                        $activitiesGrouped[$activity->section][$activity->item][strtolower($activity->action)] = $activity->created;
                        $activitiesGrouped[$activity->section][$activity->item]["id"] = $activity->item;
                        $activitiesGrouped[$activity->section][$activity->item]["action"] = strtolower($activity->action);
                        $activitiesGrouped[$activity->section][$activity->item]["user"] = array(
                            "id" => $activity->uid,
                            "email" => $activity->email, 
                            "nickname" => $activity->nickname, 
                            "firstName" => $activity->firstName, 
                            "lastName" => $activity->lastName
                        );
                    }

                    foreach ($activitiesGrouped as $section => $activity) {
                        foreach (array_reverse($activity) as $item => $changes) {
                            echo "id: ". strtolower($item) . PHP_EOL;
                            echo "event: update".PHP_EOL;
                            echo "data: ". json_encode($changes) . PHP_EOL;
                            echo PHP_EOL;

                            @ob_end_flush();
                            @flush();
                        }
                    }

                    $lastDate = date("Y-m-d H:i:s");
                }

                $lastTimestamp = strtotime("now");
            }            

            $notificationsList = [];

            // Break the loop if the client aborted the connection (closed the page)
            if (connection_aborted()) break;
            sleep(2);
        } while(true);
    }
}