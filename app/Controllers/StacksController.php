<?php namespace App\Controllers;

use App\Models\StackModel;
use App\Models\BoardModel;
use App\Models\StackOrderModel;
use App\Models\TaskModel;

class StacksController extends BaseController
{
    public function all_v1($id)
    {
        $board = $this->request->board;

        $stackModel = new StackModel();
        $stackBuilder = $stackModel->builder();
        $stackQuery = $stackBuilder->select("stacks.*")
            ->join('stacks_order', 'stacks_order.stack = stacks.id', 'left')
            ->where('stacks.board', $board->id)
            ->where('stacks.deleted', NULL)
            ->orderBy('stacks_order.`order`', 'ASC')
            ->get();
        $stacks = $stackQuery->getResult();

        return $this->reply($stacks);
    }

    public function add_v1($id)
    {
        $board = $this->request->board;

        $stackModel = new StackModel();
        $stackData = $this->request->getJSON();

        helper('uuid');

        $stackData->board = $board->id;

        if (!isset($stackData->id)) {
            $stackData->id = uuid();
        }

        try {
            if ($stackModel->insert($stackData) === false) {
                $errors = $stackModel->errors();
                return $this->reply($errors, 500, "ERR-STACK-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-CREATE");
        }

        // get the max order no. from all the stacks of the same board
        $stackOrderModel = new StackOrderModel();
        $builderStackOrderBuilder = $stackOrderModel->builder();
        $query = $builderStackOrderBuilder
            ->selectMax("order")
            ->where("board", $board->id)
            ->get();
        $maxStacks = $query->getResult();

        // created a new stack order object
        $order = new \stdClass();
        $order->board = $board->id;
        $order->stack = $stackData->id;
        $order->order = 1;

        if (count($maxStacks)) {
            // set the max order no. + 1
            $order->order = (int)$maxStacks[0]->order + 1; 
        }

        // insert the new stack order object
        try {
            if ($builderStackOrderBuilder->insert($order) === false) {
                $errors = $stackOrderModel->errors();
                return $this->reply($errors, 500, "ERR-STACK-ORDER");    
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-ORDER");
        }

        $stack = $stackModel->find($stackData->id);

        return $this->reply($stack, 200, "OK-STACK-CREATE-SUCCESS");
    }

    public function update_v1($idStack)
    {
        $board = $this->request->board;

        $stackData = $this->request->getJSON();

        if ($stackModel->update($board->stack, $stackData) === false) {
            return $this->reply($stackModel->errors(), 500, "ERR-STACK-UPDATE");
        }

        return $this->reply(null, 200, "OK-STACK-UPDATE-SUCCESS");
    }

    public function done_v1($idStack)
    {
        $board = $this->request->board;

        $taskModel = new TaskModel();
        $taskBuild = $taskModel->builder();
        $taskQuery = $taskBuild->select("*")
            ->join('tasks_order', 'tasks_order.task = tasks.id', 'left')
            ->where('tasks_order.stack', $board->stack)
            ->where('tasks.deleted', null)
            ->where('tasks.archived', null)
            ->get();

        $tasks = $taskQuery->getResult();

        $tasksIDs = array();
        foreach ($tasks as $task) {
            $tasksIDs[] = $task->id;
        }

        if (count($tasksIDs)) {
            $taskBuild = $taskModel->builder();
            $taskQuery = $taskBuild->set('done', 1)
                ->set('progress', 100)
                ->whereIn('id', $tasksIDs)
                ->update();

            if ($taskQuery === false) {
                return $this->reply($taskModel->errors(), 500, "ERR-STACK-DONE-ERROR");
            }
        }

        return $this->reply(null, 200, "OK-STACK-DONE-SUCCESS");
    }

    public function todo_v1($idStack)
    {
        $board = $this->request->board;

        $taskModel = new TaskModel();
        $taskBuild = $taskModel->builder();
        $taskQuery = $taskBuild->select("*")
            ->join('tasks_order', 'tasks_order.task = tasks.id', 'left')
            ->where('tasks_order.stack', $board->stack)
            ->where('tasks.deleted', null)
            ->where('tasks.archived', null)
            ->get();

        $tasks = $taskQuery->getResult();

        $tasksIDs = array();
        foreach ($tasks as $task) {
            $tasksIDs[] = $task->id;
        }

        if (count($tasksIDs)) {
            $taskBuild = $taskModel->builder();
            $taskQuery = $taskBuild->set('done', 0)
                ->set('progress', 0)
                ->whereIn('id', $tasksIDs)
                ->update();

            if ($taskQuery === false) {
                return $this->reply($taskModel->errors(), 500, "ERR-STACK-TODO-ERROR");
            }
        }

        return $this->reply(null, 200, "OK-STACK-TODO-SUCCESS");
    }

    public function archive_all_v1($idStack)
    {
        $board = $this->request->board;

        // get all tasks connected to this stack
        $taskModel = new TaskModel();
        $taskBuild = $taskModel->builder();
        $taskQuery = $taskBuild->select("*")
            ->join('tasks_order', 'tasks_order.task = tasks.id', 'left')
            ->where('tasks_order.stack', $board->stack)
            ->where('tasks.deleted', null)
            ->get();

        $tasks = $taskQuery->getResult();

        $tasksIDs = array();
        foreach ($tasks as $task) {
            $tasksIDs[] = $task->id;
        }

        if (count($tasksIDs)) {
            // update the archived date for the found tasks
            $taskBuild = $taskModel->builder();
            $taskQuery = $taskBuild->set('archived',  date('Y-m-d H:i:s'))
                ->whereIn('id', $tasksIDs)
                ->update();

            if ($taskQuery === false) {
                return $this->reply($taskModel->errors(), 500, "ERR-STACK-ARCHIVE-ALL-ERROR");
            }
        }

        return $this->reply(null, 200, "OK-STACK-ARCHIVE-ALL-SUCCESS");
    }

    public function archive_done_v1($idStack)
    {
        $board = $this->request->board;

        // get all completed tasks connected to this stack
        $taskModel = new TaskModel();
        $taskBuild = $taskModel->builder();
        $taskQuery = $taskBuild->select("*")
            ->join('tasks_order', 'tasks_order.task = tasks.id', 'left')
            ->where('tasks_order.stack', $board->stack)
            ->where('tasks.done', 1)
            ->where('tasks.deleted', null)
            ->get();

        $tasks = $taskQuery->getResult();

        $tasksIDs = array();
        foreach ($tasks as $task) {
            $tasksIDs[] = $task->id;
        }

        if (count($tasksIDs)) {
            // update the archived date for the found tasks
            $taskBuild = $taskModel->builder();
            $taskQuery = $taskBuild->set('archived',  date('Y-m-d H:i:s'))
                ->whereIn('id', $tasksIDs)
                ->update();

            if ($taskQuery === false) {
                return $this->reply($taskModel->errors(), 500, "ERR-STACK-ARCHIVE-DONE-ERROR");
            }
        }

        return $this->reply(null, 200, "OK-STACK-ARCHIVE-DONE-SUCCESS");
    }

    public function delete_v1($idStack)
    {
        $board = $this->request->board;

        // get all tasks connected to this stack
        $taskModel = new TaskModel();
        $taskBuild = $taskModel->builder();
        $taskQuery = $taskBuild->select("*")
            ->join('tasks_order', 'tasks_order.task = tasks.id', 'left')
            ->where('tasks_order.stack', $board->stack)
            ->where('tasks.deleted', null)
            ->get();

        $tasks = $taskQuery->getResult();

        $tasksIDs = array();
        foreach ($tasks as $task) {
            $tasksIDs[] = $task->id;
        }

        if (count($tasksIDs)) {
            try {
                if ($taskModel->delete($tasksIDs) === false) {
                    return $this->reply($taskModel->errors(), 500, "ERR-STACK-DELETE-TASKS-ERROR");
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-STACK-DELETE-TASKS-ERROR");
            }
        }

        // we don't need to remove the tasks and stacks order 
        // since we're not actually removing the items from the db
        // only marking them as deleted

        // delete selected stack
        $stackModel = new StackModel();
        try {
            if ($stackModel->delete([$board->stack]) === false) {
                return $this->reply($stackModel->errors(), 500, "ERR-STACK-DELETE-ERROR");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-DELETE-ERROR");
        }
        

        return $this->reply(null, 200, "OK-STACK-DELETE-SUCCESS");
    }
}