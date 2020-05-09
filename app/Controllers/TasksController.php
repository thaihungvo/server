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
}