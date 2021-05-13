<?php namespace App\Controllers;

use CodeIgniter\Events\Events;
use App\Models\TagModel;
use App\Models\StackOrderModel;
use App\Models\TaskOrderModel;

class BoardsController extends BaseController
{
    public function order_stacks_v1($id)
    {
        $this->lock();

        $board = $this->request->board;
        $orderData = $this->request->getJSON();

        $stackOrderModel = new StackOrderModel();
        
        try {
            $stackOrderModel->where('board', $board->id)
                ->delete();
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-ORDER");
        }

        $stackOrderBuilder = $stackOrderModel->builder();

        $orders = [];
        foreach ($orderData as  $i => $stackID) {
            $orders[] = [
                'board' => $board->id,
                'stack' => $stackID,
                'order' => $i + 1
            ];
        }

        try {
            if ($stackOrderBuilder->insertBatch($orders) === false) {
                $errors = $stackOrderModel->errors();
                return $this->reply($errors, 500, "ERR-STACK-ORDER");    
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-ORDER");
        }

        Events::trigger("AFTER_stacks_ORDER", $board->id);

        return $this->reply(null, 200, "OK-STACK-ORDER");
    }

    public function order_tasks_v1($boardID, $stackID)
    {
        $this->lock();
        
        $board = $this->request->board;
        $orderData = $this->request->getJSON();

        $taskOrderModel = new TaskOrderModel();
        
        try {
            $taskOrderModel->where('board', $board->id)
                ->delete();
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-ORDER");
        }

        $taskOrderBuilder = $taskOrderModel->builder();

        $orders = [];
        foreach ($orderData as  $stackID => $stackOrder) {
            foreach ($stackOrder as $i => $taskID) {    
                $orders[] = [
                    'board' => $board->id,
                    'stack' => $stackID,
                    'task' => $taskID,
                    'order' => $i + 1
                ];
            }
        }

        // updating the tasks order
        try {
            if ($taskOrderBuilder->insertBatch($orders) === false) {
                $errors = $taskOrderModel->errors();
                return $this->reply($errors, 500, "ERR-TASK-ORDER");    
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TASK-ORDER");
        }

        Events::trigger("AFTER_tasks_ORDER", $board->id);

        return $this->reply(null, 200, "OK-TASK-ORDER");
    }

    private function set_tags($boardID, $tags) 
    {
        $tagModel = new TagModel();

        // delete previously saved tags
        try {
            if ($tagModel->where("board", $boardID)->delete() === false) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        foreach ($tags as &$tag) {
            $tag->board = $boardID;
        }

        // recreate them
        try {
            if ($tagModel->insertBatch($tags) === false) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
        
        return true;
    }

    private function get_tags($boardID) 
    {
        $tagModel = new TagModel();
        $tags = $tagModel->where('board', $boardID)->findAll();

        foreach ($tags as &$tag) {
            unset($tag->board);
        }

        return $tags;
    }
}
