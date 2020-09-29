<?php namespace App\Controllers;

class UpdatesController extends BaseController
{
    public function boards_v1()
    {

    }

    public function board_v1()
    {

    }

    public function task_v1()
    {
        header("Cache-Control: no-cache");
        header("Content-Type: text/event-stream");

        $task = $this->request->getGet("id");
        $sleep = $this->request->getGet("sleep");

        if (!$task) {
            return;
        }

        if (!$sleep || intval($sleep) < 2) {
            $sleep = 5;
        }

        helper('tasks');
        $lastDate = null;

        while (true) {
            $currentDate = task_last_updated($task);

            if ($lastDate == null) {
                $lastDate = $currentDate;
            }

            $shouldUpdate = $currentDate != $lastDate;
            echo "data: ". ($shouldUpdate ? 1 : 0) ."\n\n";

            ob_end_flush();
            flush();

            $lastDate = $currentDate;

            // Break the loop if the client aborted the connection (closed the page)
            if (connection_aborted()) break;
            sleep(intval($sleep));
        }
    }

    public function task_watchers_v1()
    {
        header("Cache-Control: no-cache");
        header("Content-Type: text/event-stream");

        $user = $this->request->user;
        $task = $this->request->getGet("id");
        $sleep = $this->request->getGet("sleep");

        if (!$task) {
            return;
        }

        if (!$sleep || intval($sleep) < 2) {
            $sleep = 5;
        }

        helper('watchers');
        $lastWatchers = null;

        while (true) {
            // Break the loop if the client aborted the connection (closed the page)
            if (connection_aborted()) break;
            sleep(intval($sleep));
            
            $currentWatchers = serialize(tasks_watchers_last($task, $user));

            if ($lastWatchers == null) {
                $lastWatchers = $currentWatchers;
            }

            $shouldUpdate = $currentWatchers != $lastWatchers;

            // echo "event: ping\n";
            echo "data: ". ($shouldUpdate ? 1 : 0) ."\n\n";

            $lastWatchers = $currentWatchers;
            
            ob_end_flush();
            flush();
        }
    }
}