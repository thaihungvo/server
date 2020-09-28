<?php namespace App\Controllers;

use App\Models\TaskModel;
use App\Models\TaskOrderModel;
use App\Models\StackModel;
use App\Models\TaskAssigneeModel;
use App\Models\TaskWatcherModel;

class TasksController extends BaseController
{
    public function all_board_v1($id)
    {
        $board = $this->request->board;

        $taskModel = new TaskModel();
        $taskBuilder = $taskModel->builder();
        $taskQuery = $taskBuilder->select('tasks.*')
            ->join('tasks_order', 'tasks_order.task = tasks.id')
            ->where('tasks.deleted', NULL)
            ->where('tasks_order.board', $board->id)
            ->get();
        $tasks = $taskQuery->getResult();

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

    public function all_stack_v1($stackID)
    {
        $board = $this->request->board;   

        $taskModel = new TaskModel();

        $builder = $taskModel->builder();
        $query = $builder->select('tasks.*')
            ->join('tasks_order', 'tasks_order.task = tasks.id')
            ->where('tasks.deleted', NULL)
            ->where('tasks_order.stack', $stackID)
            ->where('tasks_order.board', $board->id)
            ->orderBy('tasks_order.`order`', 'ASC')
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

    public function one_v1($taskID)
    {
        $board = $this->request->board;   

        $taskModel = new TaskModel();
        $taskBuilder = $taskModel->builder();
        $taskQuery = $taskBuilder->select('tasks.*')
            ->join('tasks_order', 'tasks_order.task = tasks.id')
            ->where('tasks.deleted', NULL)
            ->where('tasks.id', $taskID)
            ->where('tasks_order.board', $board->id)
            ->limit(1)
            ->get();

        $tasks = $taskQuery->getResult();

        if (!count($tasks)) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND-MSG");
        }

        $task = $tasks[0];
        
        helper('tasks');
        $task = task_format($task);

        return $this->reply($task);
    }

    public function add_v1($id, $position)
    {
        $user = $this->request->user;
        $board = $this->request->board;

        $taskModel = new TaskModel();
        $taskData = $this->request->getJSON();

        helper('uuid');
        
        // enforce an id in case there's none
        if (!isset($taskData->id)) {
            $taskData->id = uuid();
        }

        $taskData->archived = null;
        $taskData->owner = $user->id;

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

        helper('tasks');
        $task = task_format($task);

        return $this->reply($task, 200, "OK-TASK-CREATE-SUCCESS");
    }

    public function update_v1($taskID)
    {
        $board = $this->request->board;

        $taskModel = new TaskModel();

        $builder = $taskModel->builder();
        $query = $builder->select('tasks.*')
            ->join('tasks_order', 'tasks_order.task = tasks.id')
            ->where('tasks.deleted', NULL)
            ->where('tasks.id', $taskID)
            ->where('tasks_order.board', $board->id)
            ->limit(1)
            ->get();

        $tasks = $query->getResult();

        if (!count($tasks)) {
            return $this->reply(null, 404, "ERR-TASK-NOT-FOUND-MSG");
        }

        $taskData = $this->request->getJSON();

        // delete all assigned task users
        $taskAssigneeModel = new TaskAssigneeModel();
        try {
            if ($taskAssigneeModel->where('task', $taskData->id)->delete() === false) {
                return $this->reply($taskAssigneeModel->errors(), 500, "ERR-TASK-DELETE-ASSIGNEES-ERROR");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-DELETE-ASSIGNEES-ERROR");
        }

        // generate a list of new assignees
        $assignees = array();
        if (isset($taskData->assignees)) {
            foreach ($taskData->assignees as $userID) {
                $assignee = new \stdClass();
                $assignee->task = $taskData->id;
                $assignee->user = $userID;
                $assignees[] = $assignee;
            }
        }
        
        // insert the assignees if any
        if (count($assignees)) {
            try {
                if ($taskAssigneeModel->insertBatch($assignees) === false) {
                    return $this->reply($taskOrderModel->errors(), 500, "ERR-TASK-ASSIGNEES");    
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-TASK-ASSIGNEES");
            }
        }

        unset($taskData->id);
        unset($taskData->order);
        unset($taskData->stack);
        unset($taskData->assignees);
        $taskData->archived = null;

        if ($taskModel->update($taskID, $taskData) === false) {
            return $this->reply(null, 404, "ERR-TASK-UPDATE");
        }

        return $this->reply(null, 200, "OK-TASK-UPDATE-SUCCESS");
    }

    public function delete_v1($taskID)
    {
        $board = $this->request->board;   

        $taskModel = new TaskModel();
        
        $builder = $taskModel->builder();
        $query = $builder->select('tasks.*')
            ->join('tasks_order', 'tasks_order.task = tasks.id')
            ->where('tasks.deleted', NULL)
            ->where('tasks.id', $taskID)
            ->where('tasks_order.board', $board->id)
            ->limit(1)
            ->get();

        $tasks = $query->getResult();

        if (!count($tasks)) {
            return $this->reply(null, 404, "ERR-TASKS-NOT-FOUND-MSG");
        }

        $task = $tasks[0];

        // delete selected task
        try {
            if ($taskModel->delete([$task->id]) === false) {
                return $this->reply($taskModel->errors(), 500, "ERR-TASK-DELETE-ERROR");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-DELETE-ERROR");
        }

        // // delete task order
        // $taskOrderModel = new TaskOrderModel();
        // try {
        //     $taskOrderModel->where('task', $task->id)->delete();
        // } catch (\Exception $e) {
        //     return $this->reply($e->getMessage(), 500, "ERR-TASK-ORDER-DELETE-ERROR");
        // }

        return $this->reply(null, 200, "OK-TASK-DELETE-SUCCESS");
    }

    public function get_watchers_v1($taskID)
    {
        $user = $this->request->user;
        $board = $this->request->board;

        // remove all stuck watchers
        $taskWatcherModel = new TaskWatcherModel();
        
        try {
            if ($taskWatcherModel->where('created <= DATE_SUB(NOW(), INTERVAL 2 HOUR)', NULL, false)->delete() === false) {
                return $this->reply($taskModel->errors(), 500, "ERR-TASK-CLEAR-WATCHERS-ERROR");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-CLEAR-WATCHERS-ERROR");
        }

        helper('assignees');
        $watchers = tasks_watchers($board->task, $user);

        return $this->reply($watchers, 200, "OK-TASK-WATCHERS-SUCCESS");
    }

    public function add_watcher_v1($taskID)
    {
        $user = $this->request->user;

        $taskWatcherModel = new TaskWatcherModel();

        $watchers = $taskWatcherModel->where('task', $taskID)
            ->where('user', $user->id)
            ->findAll();

        if (!count($watchers)) {
            $watcher = array(
                'task' => $taskID,
                'user' => $user->id
            );

            try {
                if ($taskWatcherModel->insert($watcher) === false) {
                    return $this->reply($taskWatcherModel->errors(), 500, "ERR-TASK-ADD-WATCHER-ERROR");
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-TASK-ADD-WATCHER-ERROR");
            }
        }

        return $this->reply(null, 200, "OK-TASK-ADD-WATCHERS-SUCCESS");
    }

    public function remove_watcher_v1($taskID)
    {
        $user = $this->request->user;

        $taskWatcherModel = new TaskWatcherModel();

        try {
            if ($taskWatcherModel->where('user', $user->id)->delete() === false) {
                return $this->reply($taskAssigneeModel->errors(), 500, "ERR-TASK-DELETE-WATCHER-ERROR");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-DELETE-WATCHER-ERROR");
        }

        return $this->reply(null, 200, "OK-TASK-DELETE-WATCHERS-SUCCESS");
    }
}