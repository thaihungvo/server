<?php namespace App\Controllers;

use App\Models\TaskModel;
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

        if (!isset($orderData->task) || !isset($orderData->stack)) {
            return $this->reply("Task not found", 404, "ERR-TASKS-ORDER");
        }

        $taskModel = new TaskModel();
        $movedTask = $taskModel->find($orderData->task);

        if (!$movedTask) {
            return $this->reply("Task not found", 404, "ERR-TASKS-ORDER");
        }

        $db = db_connect();
        
        $db->transBegin();

        // reset ordering
        if (!$db->query("SET @counter = 0;")) {
            return $this->reply("Unable to update tasks order", 500, "ERR-TASKS-ORDER");
        }
        $query = array();
        $query[] = "UPDATE ".$db->prefixTable("tasks");
        $query[] = "SET `position` = @counter := @counter + 1";
        $query[] = "WHERE stack = ". $db->escape($orderData->stack);
        $query[] = "AND id <> ". $db->escape($movedTask->id);
        $query[] = "ORDER BY `position`";
        if (!$db->query(implode(" ", $query))) {
            return $this->reply("Unable to update tasks order", 500, "ERR-TASKS-ORDER");
        }

        // increase the ordering
        $query = array();
        $query[] = "UPDATE ".$db->prefixTable("tasks");
        $query[] = "SET `position` = `position` + 1";
        $query[] = "WHERE stack = ". $db->escape($orderData->stack) ." AND `position` >= ". $db->escape($orderData->position + 1);
        $query[] = "ORDER BY `position`";
        
        if (!$db->query(implode(" ", $query))) {
            return $this->reply("Unable to update tasks order", 500, "ERR-TASKS-ORDER");
        }

        // update the moved task
        $query = array();
        $query[] = "UPDATE ".$db->prefixTable("tasks");
        $query[] = "SET `position` = ".$db->escape($orderData->position + 1). ", stack = ".$db->escape($orderData->stack);
        $query[] = "WHERE id = ". $db->escape($movedTask->id);
        
        if (!$db->query(implode(" ", $query))) {
            return $this->reply("Unable to update tasks order", 500, "ERR-TASKS-ORDER");
        }

        if ($db->transStatus() === false) {
            $db->transRollback();
            error_log("ERROR TRANSACTION\n\n");
            return $this->reply("Transaction erro", 500, "ERR-TASKS-ORDER");
        } else {
            $db->transCommit();
        }

        $this->addActivity($movedTask->stack, $movedTask->id, $this::ACTION_UPDATE, $this::SECTION_DOCUMENT);

        return $this->reply(true);
    }
}
