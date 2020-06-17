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
        $stacks = $stackModel->where('board', $board->id)
            ->orderBy('order', 'asc')
            ->findAll();

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

        $order = new \stdClass();
        $order->board = $board->id;
        $order->stack = $stackData->id;
        $order->order = 1;

        if (count($maxStacks)) {
            // set the max order no. + 1
            $order->order = (int)$maxStacks[0]->order + 1; 
        }

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

    public function update_v1($idBoard, $idStack)
    {
        $board = $this->request->board;

        $stackModel = new StackModel();
        $stack = $stackModel
            ->where('board', $board->id)
            ->find($idStack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR-STACK-NOT-FOUND-MSG");
        }

        $stackData = $this->request->getJSON();

        if ($stackModel->update($stack->id, $stackData) === false) {
            return $this->reply($stackModel->errors(), 500, "ERR-STACK-UPDATE");
        }

        return $this->reply(null, 200, "OK-STACK-UPDATE-SUCCESS");
    }

    public function done_v1($idBoard, $idStack)
    {
        $board = $this->request->board;

        $stackModel = new StackModel();
        $stack = $stackModel
            ->where('board', $board->id)
            ->find($idStack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR-STACK-NOT-FOUND-MSG");
        }

        $data = [
            'done' => 1,
            'progress' => 100
        ];

        $taskModel = new TaskModel();
        if ($taskModel->where('stack', $stack->id)->set($data)->update() === false) {
            return $this->reply($taskModel->errors(), 500, "ERR-STACK-DONE-ERROR");
        }

        return $this->reply(null, 200, "OK-STACK-DONE-SUCCESS");
    }

    public function todo_v1($idBoard, $idStack)
    {
        $board = $this->request->board;

        $stackModel = new StackModel();
        $stack = $stackModel
            ->where('board', $board->id)
            ->find($idStack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR-STACK-NOT-FOUND-MSG");
        }

        $data = [
            'done' => 0,
            'progress' => 0
        ];

        $taskModel = new TaskModel();
        if ($taskModel->where('stack', $stack->id)->set($data)->update() === false) {
            return $this->reply($taskModel->errors(), 500, "ERR-STACK-TODO-ERROR");
        }

        return $this->reply(null, 200, "OK-STACK-TODO-SUCCESS");
    }

    public function archive_v1($idBoard, $idStack)
    {
        $board = $this->request->board;

        $stackModel = new StackModel();
        $stack = $stackModel
            ->where('board', $board->id)
            ->find($idStack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR-STACK-NOT-FOUND-MSG");
        }

        $data = [
            'archived' => date('Y-m-d H:i:s')
        ];

        $taskModel = new TaskModel();
        if ($taskModel->where('stack', $stack->id)->set($data)->update() === false) {
            return $this->reply($taskModel->errors(), 500, "ERR-STACK-ARCHIVE-ALL-ERROR");
        }

        return $this->reply(null, 200, "OK-STACK-ARCHIVE-ALL-SUCCESS");
    }

    public function archive_done_v1($idBoard, $idStack)
    {
        $board = $this->request->board;

        $stackModel = new StackModel();
        $stack = $stackModel
            ->where('board', $board->id)
            ->find($idStack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR-STACK-NOT-FOUND-MSG");
        }

        $data = [
            'archived' => date('Y-m-d H:i:s')
        ];

        $taskModel = new TaskModel();
        if ($taskModel->where('stack', $stack->id)->where('done', 1)->set($data)->update() === false) {
            return $this->reply($taskModel->errors(), 500, "ERR-STACK-ARCHIVE-DONE-ERROR");
        }

        return $this->reply(null, 200, "OK-STACK-ARCHIVE-DONE-SUCCESS");
    }

    public function delete_v1($idBoard, $idStack)
    {
        $board = $this->request->board;

        $stackModel = new StackModel();
        $stack = $stackModel
            ->where('board', $board->id)
            ->find($idStack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR-STACK-NOT-FOUND-MSG");
        }

        // delete all tasks attachments
        // TODO: delete all tasks attachments

        // delete all tasks
        $taskModel = new TaskModel();
        try {
            if ($taskModel->where('stack', $stack->id)->delete() === false) {
                return $this->reply($taskModel->errors(), 500, "ERR-STACK-DELETE-TASKS-ERROR");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-DELETE-TASKS-ERROR");
        }

        // delete selected stack
        try {
            if ($stackModel->delete([$stack->id]) === false) {
                return $this->reply($stackModel->errors(), 500, "ERR-STACK-DELETE-ERROR");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-DELETE-ERROR");
        }
        

        return $this->reply(null, 200, "OK-STACK-DELETE-SUCCESS");
    }
}