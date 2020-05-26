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

        foreach ($tasks as &$task) {
            $task->cover = (bool)$task->done;
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
            $task->cover = (bool)$task->done;
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

        $task->cover = (bool)$task->done;
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

    public function add_v1($id)
    {
        $taskModel = new TaskModel();
        $taskData = $this->request->getJSON();

        helper('uuid');
        
        // enforce an id in case there's none
        if (!isset($taskData->id)) {
            $taskData->id = uuid();
        }

        $taskData->archived = null;

        try {
            if ($taskModel->insert($taskData) === false) {
                $errors = $taskModel->errors();
                return $this->reply($errors, 500, "ERR-TASK-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-CREATE");
        }

        $task = $taskModel->find($taskData->id);
        $task->cover = (bool)$task->done;
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