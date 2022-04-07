<?php namespace App\Controllers;

use App\Models\StackModel;
use App\Models\TaskModel;
class ProjectsController extends BaseController
{
    public function set_order_stacks_v1($projectId)
    {
        $this->lock($projectId);
        $document = $this->getDocument($projectId);
        $this->exists($document);

        $data = $this->request->getJSON();

        $this->exists($data->stack);

        if (!isset($data->position)) {
            $data->position = 0;
        }

        $stackModel = new StackModel($this->request->user);
        $movedStack = $stackModel->getStack($data->stack);
        $this->exists($movedStack);
        
        $this->db->transBegin();

        // reset ordering
        if (!$this->db->query("SET @counter = 0;")) {
            return $this->reply("Unable to update stack order", 500, "ERR-STACK-ORDER");
        }
        $query = array();
        $query[] = "UPDATE ".$this->db->prefixTable("stacks");
        $query[] = "SET `position` = @counter := @counter + 1";
        $query[] = "WHERE project = ". $this->db->escape($movedStack->project);
        $query[] = "AND id <> ". $this->db->escape($movedStack->id);
        $query[] = "ORDER BY `position`";
        if (!$this->db->query(implode(" ", $query))) {
            return $this->reply("Unable to update stack order", 500, "ERR-STACk-ORDER");
        }

        // increase the ordering
        $query = array();
        $query[] = "UPDATE ".$this->db->prefixTable("stacks");
        $query[] = "SET `position` = `position` + 1";
        $query[] = "WHERE project = ". $this->db->escape($movedStack->project) ." AND `position` >= ". $this->db->escape($data->position + 1);
        $query[] = "ORDER BY `position`";
        
        if (!$this->db->query(implode(" ", $query))) {
            return $this->reply("Unable to update stack order", 500, "ERR-STACKS-ORDER");
        }

        // update the moved task
        $query = array();
        $query[] = "UPDATE ".$this->db->prefixTable("stacks");
        $query[] = "SET `position` = ".$this->db->escape($data->position + 1);
        $query[] = "WHERE id = ". $this->db->escape($movedStack->id);
        
        if (!$this->db->query(implode(" ", $query))) {
            return $this->reply("Unable to update stack order", 500, "ERR-STACKS-ORDER");
        }

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            return $this->reply("Transaction error", 500, "ERR-STACKS-ORDER");
        } else {
            $this->db->transCommit();
        }

        $this->addActivity(
            $document->id,
            $movedStack->project, 
            $movedStack->id, 
            $this::ACTION_UPDATE,
            $this::SECTION_ORDER
        );

        return $this->reply(true);
    }

    public function set_order_tasks_v1($projectId)
    {
        $this->lock($projectId);
        $document = $this->getDocument($projectId);
        $this->exists($document);
        
        $orderData = $this->request->getJSON();

        if (!isset($orderData->task) || !isset($orderData->stack)) {
            return $this->reply("Task not found", 404, "ERR-TASKS-ORDER");
        }
        if (!isset($orderData->position)) {
            $orderData->position = 0;
        }

        $taskModel = new TaskModel($this->request->user);
        $movedTask = $taskModel->getTask($orderData->task);
        $this->exists($movedTask);
        
        $this->db->transBegin();

        // reset ordering
        if (!$this->db->query("SET @counter = 0;")) {
            return $this->reply("Unable to update task order", 500, "ERR-TASKS-ORDER");
        }
        $query = array();
        $query[] = "UPDATE ".$this->db->prefixTable("tasks");
        $query[] = "SET `position` = @counter := @counter + 1";
        $query[] = "WHERE stack = ". $this->db->escape($orderData->stack);
        $query[] = "AND id <> ". $this->db->escape($movedTask->id);
        $query[] = "ORDER BY `position`";
        if (!$this->db->query(implode(" ", $query))) {
            return $this->reply("Unable to update task order", 500, "ERR-TASKS-ORDER");
        }

        // increase the ordering
        $query = array();
        $query[] = "UPDATE ".$this->db->prefixTable("tasks");
        $query[] = "SET `position` = `position` + 1";
        $query[] = "WHERE stack = ". $this->db->escape($orderData->stack) ." AND `position` >= ". $this->db->escape($orderData->position + 1);
        $query[] = "ORDER BY `position`";
        
        if (!$this->db->query(implode(" ", $query))) {
            return $this->reply("Unable to update task order", 500, "ERR-TASKS-ORDER");
        }

        // update the moved task
        $query = array();
        $query[] = "UPDATE ".$this->db->prefixTable("tasks");
        $query[] = "SET `position` = ".$this->db->escape($orderData->position + 1). ", stack = ".$this->db->escape($orderData->stack);
        $query[] = "WHERE id = ". $this->db->escape($movedTask->id);
        
        if (!$this->db->query(implode(" ", $query))) {
            return $this->reply("Unable to update task order", 500, "ERR-TASKS-ORDER");
        }

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            return $this->reply("Transaction error", 500, "ERR-TASKS-ORDER");
        } else {
            $this->db->transCommit();
        }

        $this->addActivity(
            $document->id,
            $movedTask->stack, 
            $movedTask->id, 
            $this::ACTION_UPDATE,
            $this::SECTION_ORDER
        );

        return $this->reply(true);
    }

    public function get_order_v1($projectId)
    {
        $this->lock($projectId);
        $document = $this->getDocument($projectId);
        $this->exists($document);
        
        $taskModel = new TaskModel($this->request->user);
        $tasks = $taskModel->where("project", $document->id)
            ->orderBy("position", "asc")
            ->find();

        $order = new \stdClass();
        if (count($tasks)) {
            foreach ($tasks as $task) {
                $stackId = $task->stack;
                if (!property_exists($order, $stackId)) {
                    $order->$stackId = array();
                }
                $order->$stackId[] = $task->id;
            }
        }

        return $this->reply($order);
    }
}
