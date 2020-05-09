<?php namespace App\Controllers;

use App\Models\TaskModel;

class TasksController extends BaseController
{
    public function all_board_v1($id)
    {
        $user = $this->request->user;
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
}