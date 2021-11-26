<?php namespace App\Controllers;

use App\Models\StackModel;
use App\Models\TaskModel;
class ProjectsController extends BaseController
{
    public function set_order_stacks_v1($projectId)
    {
        $this->lock($projectId);

        helper("documents");
        $user = $this->request->user;
        $document = documents_load_document($projectId, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-STACK-ORDER");
        }

        $orderData = $this->request->getJSON();

        if (!isset($orderData->stack)) {
            return $this->reply("Stack not found", 404, "ERR-STACK-ORDER");
        }

        if (!isset($orderData->position)) {
            $orderData->position = 0;
        }

        $stackModel = new StackModel();
        $movedStack = $stackModel->find($orderData->stack);

        if (!$movedStack) {
            return $this->reply("Stack not found", 404, "ERR-STACK-ORDER");
        }

        $db = db_connect();
        
        $db->transBegin();

        // reset ordering
        if (!$db->query("SET @counter = 0;")) {
            return $this->reply("Unable to update stack order", 500, "ERR-STACK-ORDER");
        }
        $query = array();
        $query[] = "UPDATE ".$db->prefixTable("stacks");
        $query[] = "SET `position` = @counter := @counter + 1";
        $query[] = "WHERE project = ". $db->escape($movedStack->project);
        $query[] = "AND id <> ". $db->escape($movedStack->id);
        $query[] = "ORDER BY `position`";
        if (!$db->query(implode(" ", $query))) {
            return $this->reply("Unable to update stack order", 500, "ERR-STACk-ORDER");
        }

        // increase the ordering
        $query = array();
        $query[] = "UPDATE ".$db->prefixTable("stacks");
        $query[] = "SET `position` = `position` + 1";
        $query[] = "WHERE project = ". $db->escape($movedStack->project) ." AND `position` >= ". $db->escape($orderData->position + 1);
        $query[] = "ORDER BY `position`";
        
        if (!$db->query(implode(" ", $query))) {
            return $this->reply("Unable to update stack order", 500, "ERR-STACKS-ORDER");
        }

        // update the moved task
        $query = array();
        $query[] = "UPDATE ".$db->prefixTable("stacks");
        $query[] = "SET `position` = ".$db->escape($orderData->position + 1);
        $query[] = "WHERE id = ". $db->escape($movedStack->id);
        
        if (!$db->query(implode(" ", $query))) {
            return $this->reply("Unable to update stack order", 500, "ERR-STACKS-ORDER");
        }

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->reply("Transaction error", 500, "ERR-STACKS-ORDER");
        } else {
            $db->transCommit();
        }

        $this->addActivity(
            $document->id,
            $movedStack->project, 
            $movedStack->id, 
            $this::ACTION_UPDATE,
            $this::SECTION_STACKORDER
        );

        return $this->reply(true);
    }

    public function get_order_stacks_v1($projectId)
    {
        $this->lock($projectId);

        helper("documents");
        $user = $this->request->user;
        $document = documents_load_document($projectId, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-STACK-ORDER");
        }
        
        $stackModel = new StackModel();
        $stacks = $stackModel->where("project", $document->id)
            ->orderBy("position", "asc")
            ->find();

        $stackIds = array();
        if (count($stacks)) {
            foreach ($stacks as $stack) {
                $stackIds[] = $stack->id;
            }
        }

        return $this->reply($stackIds);
    }

    public function set_order_tasks_v1($projectId)
    {
        $this->lock($projectId);

        helper("documents");
        $user = $this->request->user;
        $document = documents_load_document($projectId, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-TASKS-ORDER");
        }
        
        $orderData = $this->request->getJSON();

        if (!isset($orderData->task) || !isset($orderData->stack)) {
            return $this->reply("Task not found", 404, "ERR-TASKS-ORDER");
        }
        if (!isset($orderData->position)) {
            $orderData->position = 0;
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
            return $this->reply("Unable to update task order", 500, "ERR-TASKS-ORDER");
        }
        $query = array();
        $query[] = "UPDATE ".$db->prefixTable("tasks");
        $query[] = "SET `position` = @counter := @counter + 1";
        $query[] = "WHERE stack = ". $db->escape($orderData->stack);
        $query[] = "AND id <> ". $db->escape($movedTask->id);
        $query[] = "ORDER BY `position`";
        if (!$db->query(implode(" ", $query))) {
            return $this->reply("Unable to update task order", 500, "ERR-TASKS-ORDER");
        }

        // increase the ordering
        $query = array();
        $query[] = "UPDATE ".$db->prefixTable("tasks");
        $query[] = "SET `position` = `position` + 1";
        $query[] = "WHERE stack = ". $db->escape($orderData->stack) ." AND `position` >= ". $db->escape($orderData->position + 1);
        $query[] = "ORDER BY `position`";
        
        if (!$db->query(implode(" ", $query))) {
            return $this->reply("Unable to update task order", 500, "ERR-TASKS-ORDER");
        }

        // update the moved task
        $query = array();
        $query[] = "UPDATE ".$db->prefixTable("tasks");
        $query[] = "SET `position` = ".$db->escape($orderData->position + 1). ", stack = ".$db->escape($orderData->stack);
        $query[] = "WHERE id = ". $db->escape($movedTask->id);
        
        if (!$db->query(implode(" ", $query))) {
            return $this->reply("Unable to update task order", 500, "ERR-TASKS-ORDER");
        }

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->reply("Transaction error", 500, "ERR-TASKS-ORDER");
        } else {
            $db->transCommit();
        }

        $this->addActivity(
            $document->id,
            $movedTask->stack, 
            $movedTask->id, 
            $this::ACTION_UPDATE,
            $this::SECTION_TASKORDER
        );

        return $this->reply(true);
    }

    public function get_order_tasks_v1($projectId)
    {
        $this->lock($projectId);

        helper("documents");
        $user = $this->request->user;
        $document = documents_load_document($projectId, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-TASK-ORDER");
        }
        
        $taskModel = new TaskModel();
        $tasks = $taskModel->where("project", $document->id)
            ->orderBy("position", "asc")
            ->find();

        $taskIds = new \stdClass();
        if (count($tasks)) {
            foreach ($tasks as $task) {
                $stackId = $task->stack;
                if (!property_exists($taskIds, $stackId)) {
                    $taskIds->$stackId = array();
                }
                $taskIds->$stackId[] = $task->id;
            }
        }

        return $this->reply($taskIds);
    }
}
