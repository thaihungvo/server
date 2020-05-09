<?php namespace App\Controllers;

use App\Models\TaskModel;

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
            return $this->reply(null, 404, "ERR_TASKS_NOT_FOUND_MSG");
        }

        return $this->reply($tasks[0]);
    }

    public function add_v1($id)
    {
        $taskModel = new TaskModel();
        $taskData = $this->request->getJSON();

        helper('uuid');
        
        if (!isset($taskData->id)) {
            $taskData->id = uuid();
        }

        try {
            if ($taskModel->insert($taskData) === false) {
                $errors = $taskModel->errors();
                return $this->reply($errors, 500, "ERR_TASK_CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR_TASK_CREATE");
        }

        $task = $taskModel->find($taskData->id);

        return $this->reply($task, 200, "OK_TASK_CREATE_SUCCESS");
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
            return $this->reply(null, 404, "ERR_TASK_NOT_FOUND_MSG");
        }

        $taskData = $this->request->getJSON();

        unset($taskData->id);

        if ($taskModel->update($taskID, $taskData) === false) {
            return $this->reply(null, 404, "ERR_TASK_UPDATE");
        }

        return $this->reply(null, 200, "OK_TASK_UPDATE_SUCCESS");
    }
}