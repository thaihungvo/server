<?php namespace App\Controllers;

use App\Models\TaskModel;
use App\Models\TaskOrderModel;
use App\Models\StackModel;

class TasksController extends BaseController
{
    public function all_board_v1($id)
    {
        $board = $this->request->board;

        $taskModel = new TaskModel();

        $builder = $taskModel->builder();
        $query = $builder->select('tasks.*')
            ->join('stacks', 'stacks.id = tasks.stack')
            ->where('tasks.deleted', NULL)
            ->where('stacks.board', $board->id)
            ->get();

        $tasks = $query->getResult();

        foreach ($tasks as &$task) {
            $task->cover = (bool)$task->cover;
            $task->done = (bool)$task->done;
            $task->altTags = (bool)$task->altTags;
            $task->progress = (int)$task->progress;
            if (is_string($task->tags)) {
                $task->tags = json_decode($task->tags);
            }
            if (is_string($task->info)) {
                $task->info = json_decode($task->info);
            }
        }

        return $this->reply($tasks);
    }

    public function all_stack_v1($boardID, $stackID)
    {
        $board = $this->request->board;   

        $taskModel = new TaskModel();

        $builder = $taskModel->builder();
        $query = $builder->select('tasks.*')
            ->join('stacks', 'stacks.id = tasks.stack')
            ->where('tasks.deleted', NULL)
            ->where('tasks.stack', $stackID)
            ->where('stacks.board', $board->id)
            ->get();

        $tasks = $query->getResult();

        foreach ($tasks as &$task) {
            $task->cover = (bool)$task->cover;
            $task->done = (bool)$task->done;
            $task->altTags = (bool)$task->altTags;
            $task->progress = (int)$task->progress;
            if (is_string($task->tags)) {
                $task->tags = json_decode($task->tags);
            }
            if (is_string($task->info)) {
                $task->info = json_decode($task->info);
            }
        }

        return $this->reply($tasks);
    }

    public function one_v1($boardID, $taskID)
    {
        $board = $this->request->board;   

        $taskModel = new TaskModel();
        
        $builder = $taskModel->builder();
        $query = $builder->select('tasks.*')
            ->join('stacks', 'stacks.id = tasks.stack')
            ->where('tasks.deleted', NULL)
            ->where('tasks.id', $taskID)
            ->where('stacks.board', $board->id)
            ->limit(1)
            ->get();

        $tasks = $query->getResult();

        if (!count($tasks)) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND-MSG");
        }

        $task = $tasks[0];

        $task->cover = (bool)$task->cover;
        $task->done = (bool)$task->done;
        $task->altTags = (bool)$task->altTags;
        $task->progress = (int)$task->progress;
        if (is_string($task->tags)) {
            $task->tags = json_decode($task->tags);
        }
        if (is_string($task->info)) {
            $task->info = json_decode($task->info);
        }

        return $this->reply($tasks);
    }

    public function add_v1($id, $position)
    {
        $board = $this->request->board;
        $taskModel = new TaskModel();
        $taskData = $this->request->getJSON();

        helper('uuid');
        
        // enforce an id in case there's none
        if (!isset($taskData->id)) {
            $taskData->id = uuid();
        }

        $taskData->archived = null;

        // TODO: check if the stack connected to this task is one of the users
        // check if the stack exists
        $stackModel = new StackModel();
        $stacks = $stackModel->where('board', $board->id)
            ->where('id', $taskData->stack)
            ->findAll();

        if (!count($stacks)) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-CREATE");
        }

        try {
            if ($taskModel->insert($taskData) === false) {
                $errors = $taskModel->errors();
                return $this->reply($errors, 500, "ERR-TASK-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-CREATE");
        }

        $taskOrderModel = new TaskOrderModel();
        $builderTaskOrderBuilder = $taskOrderModel->builder();
        
        $order = new \stdClass();
        $order->board = $board->id;
        $order->stack = $taskData->stack;
        $order->task = $taskData->id;
        $order->order = 1;

        // get the max order no. from all the stacks of the same board
        if ($position === "bottom") {
            $query = $builderTaskOrderBuilder
                ->selectMax("order")
                ->where("board", $board->id)
                ->where("stack", $taskData->stack)
                ->get();
            $maxTasks = $query->getResult();

            if (count($maxStacks)) {
                // set the max order no. + 1
                $order->order = (int)$maxStacks[0]->order + 1; 
            }

            try {
                if ($builderTaskOrderBuilder->insert($order) === false) {
                    $errors = $taskOrderModel->errors();
                    return $this->reply($errors, 500, "ERR-TASK-ORDER");    
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-TASK-ORDER");
            }
        } else {
            $taskOrderQuery = $builderTaskOrderBuilder->select('task')
                ->where('stack', $taskData->stack)
                ->orderBy('`order`', 'ASC')
                ->get();
            $currentOrder = $taskOrderQuery->getResult();

            $orders = [$order];

            foreach ($currentOrder as $i => $task) {
                $order = new \stdClass();
                $order->board = $board->id;
                $order->stack = $taskData->stack;
                $order->task = $task->task;
                $order->order = $i + 2;
                $orders[] = $order;
            }

            try {
                $taskOrderModel->where('stack', $taskData->stack)
                    ->delete();
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-TASK-ORDER");
            }

            try {
                if ($builderTaskOrderBuilder->insertBatch($orders) === false) {
                    return $this->reply($taskOrderModel->errors(), 500, "ERR-TASK-ORDER");    
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-TASK-ORDER");
            }
        }

        $task = $taskModel->find($taskData->id);
        $task->cover = (bool)$task->cover;
        $task->done = (bool)$task->done;
        $task->altTags = (bool)$task->altTags;
        $task->progress = (int)$task->progress;
        if (is_string($task->tags)) {
            $task->tags = json_decode($task->tags);
        }
        if (is_string($task->info)) {
            $task->info = json_decode($task->info);
        }

        return $this->reply($task, 200, "OK-TASK-CREATE-SUCCESS");
    }

    public function update_v1($boardID, $taskID)
    {
        $board = $this->request->board;

        $taskModel = new TaskModel();

        $builder = $taskModel->builder();
        $query = $builder->select('tasks.*')
            ->join('stacks', 'stacks.id = tasks.stack')
            ->where('tasks.deleted', NULL)
            ->where('tasks.id', $taskID)
            ->where('stacks.board', $board->id)
            ->limit(1)
            ->get();

        $tasks = $query->getResult();

        if (!count($tasks)) {
            return $this->reply(null, 404, "ERR-TASK-NOT-FOUND-MSG");
        }

        $taskData = $this->request->getJSON();

        unset($taskData->id);
        $taskData->archived = null;

        if ($taskModel->update($taskID, $taskData) === false) {
            return $this->reply(null, 404, "ERR-TASK-UPDATE");
        }

        return $this->reply(null, 200, "OK-TASK-UPDATE-SUCCESS");
    }
}