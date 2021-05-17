<?php namespace App\Controllers;

use App\Models\StackModel;
use App\Models\TaskModel;
use App\Models\StackCollapsedModel;

class StacksController extends BaseController
{
    public function add_v1($idStack)
    {
        helper("documents");

        $user = $this->request->user;
        $document = documents_load($idStack, $user);
        
        if (!$document) {
            $this->reply("Project not found", 404, "ERR-STACK-CREATE");
        }

        $stackModel = new StackModel();
        $stackData = $this->request->getJSON();
        $stackData->project = $document->id;

        if (isset($stackData->tag)) {
            $stackData->tag = json_encode($stackData->tag);
        }

        if (!isset($stackData->position)) {
            $lastPosition = $stackModel
                ->where("project", $document->id)
                ->orderBy("position", "desc")
                ->first();

            $stackData->position = intval($lastPosition->position) + 1;
        }

        if (!isset($stackData->id)) {
            helper('uuid');
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

        $stackCollapsedModel = new StackCollapsedModel();
        
        // create a default collapsed state
        $collapsed = [
            "stack" => $stackData->id,
            "collapsed" => 0,
            "user" => $user->id
        ];

        try {
            if ($stackCollapsedModel->insert($collapsed) === false) {
                $errors = $stackCollapsedModel->errors();
                return $this->reply($errors, 500, "ERR-STACK-CREATE");    
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-CREATE");
        }

        $this->addActivity($document->id, $idStack, $this::ACTION_CREATE, $this::SECTION_PROJECT);

        $stack = $stackModel->find($stackData->id);
        return $this->reply($stack);
    }

    public function update_v1($idProject, $idStack)
    {
        $this->lock($idStack);

        helper("documents");

        $user = $this->request->user;
        $document = documents_load($idProject, $user);

        $stackData = $this->request->getJSON();
        unset($stackData->created);

        // forcing the stack project id
        $stackData->project = $document->id;

        // saving stack tag/tint color
        if (isset($stackData->tag)) {
            $stackData->tag = json_encode($stackData->tag);
        } else {
            $stackData->tag = "";
        }

        // saving collapsed state of the stack for the current user
        if (isset($stackData->collapsed)) {
            $stackCollapsedModel = new StackCollapsedModel();
            try {
                if (
                    $stackCollapsedModel
                        ->where("user", $user->id)
                        ->where("stack", $stackData->id)
                        ->delete() === false
                ) {
                    $errors = $stackCollapsedModel->errors();
                    return $this->reply($errors, 500, "ERR-STACK-UPDATE");
                }
                    
                if ($stackCollapsedModel->insert([
                    "stack" => $stackData->id,
                    "collapsed" => intval($stackData->collapsed),
                    "user" => $user->id
                ]) === false) {
                    $errors = $stackCollapsedModel->errors();
                    return $this->reply($errors, 500, "ERR-STACK-UPDATE");
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-STACK-UPDATE");
            }
        }

        // update the stack data
        $stackModel = new StackModel();
        if ($stackModel->update($idStack, $stackData) === false) {
            return $this->reply($stackModel->errors(), 500, "ERR-STACK-UPDATE");
        }

        $this->addActivity($idProject, $idStack, $this::ACTION_UPDATE, $this::SECTION_STACK);

        return $this->reply(true);
    }

    public function done_v1($idProject, $idStack)
    {
        $this->lock($idStack);

        helper("documents");

        $user = $this->request->user;
        $document = documents_load($idProject, $user);

        $taskModel = new TaskModel();
        $taskBuild = $taskModel->builder();
        $taskQuery = $taskBuild->select("*")
            ->join('tasks_order', 'tasks_order.task = tasks.id', 'left')
            ->where('tasks_order.stack', "CHANGE ME")
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

        return $this->reply(true);
    }

    public function todo_v1($idStack)
    {
        $this->lock();

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
        $this->lock();

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
        $this->lock();

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
        $this->lock($idStack);

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