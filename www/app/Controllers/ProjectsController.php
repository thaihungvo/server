<?php namespace App\Controllers;

class ProjectsController extends BaseController
{
    public function order_tasks_v1($projectId)
    {
        $this->lock($projectId);

        helper("documents");
        $user = $this->request->user;
        $document = documents_load($projectId, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-TASKS-ORDER");
        }
        
        $orderData = $this->request->getJSON();

        $db = db_connect();
        $query = array(
            "INSERT INTO ".$db->prefixTable("tasks")." (`id`, `stack`, `position`) VALUES"
        );

        $values = array();
        foreach ($orderData as $stack => $tasks) {
            foreach ($tasks as $index => $task) {
                $values[] = "(". $db->escape($task) .", ". $db->escape($stack) .", ". $db->escape($index + 1) .")";
            }
        }

        $query[] = implode(", ", $values);
        $query[] = "ON DUPLICATE KEY UPDATE id=VALUES(id), `stack`=VALUES(`stack`), `position`=VALUES(`position`);";
        $query = implode(" ", $query);

        if (!$db->query($query)) {
            return $this->reply("Unable to update tasks order", 500, "ERR-TASKS-ORDER");
        }

        $this->addActivity("", $document->id, $this::ACTION_UPDATE, $this::SECTION_DOCUMENT);

        return $this->reply(true);
    }
}
