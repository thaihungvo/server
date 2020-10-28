<?php namespace App\Controllers;

use CodeIgniter\Events\Events;
use App\Models\BoardModel;
use App\Models\BoardMemberModel;
use App\Models\TagModel;
use App\Models\StackModel;
use App\Models\TaskModel;
use App\Models\StackOrderModel;
use App\Models\TaskOrderModel;

class BoardsController extends BaseController
{
	public function all_v1()
	{
        $user = $this->request->user;

        $boardModel = new BoardModel();
        $boardBuilder = $boardModel->builder();

        $boardQuery = $boardBuilder->select('boards.id, boards.title, boards.updated, boards.created')
            ->join('boards_members', 'boards_members.board = boards.id', 'left')
            ->where('boards.deleted', NULL)
            ->groupStart()
                ->where('boards.owner', $user->id)
                ->orWhere('boards_members.user', $user->id)
                ->orWhere('boards.everyone', 1)
            ->groupEnd()
            ->groupBy('boards.id')
            ->get();

        $boards = $boardQuery->getResult();

        return $this->reply($boards);
	}

	public function one_v1($id)
	{
        $user = $this->request->user;
        $board = $this->request->board;

        // load board tags
        $tagModel = new TagModel();
        $board->tags = $tagModel->where('board', $board->id)->findAll();

        // load board stacks
        $stackModel = new StackModel();
        $stackBuilder = $stackModel->builder();
        $stackQuery = $stackBuilder->select("stacks.*")
            ->join('stacks_order', 'stacks_order.stack = stacks.id', 'left')
            ->where('stacks.board', $board->id)
            ->where('stacks.deleted', NULL)
            ->orderBy('stacks_order.`order`', 'ASC')
            ->get();
        $board->stacks = $stackQuery->getResult();

        if (count($board->stacks)) {
            $stacksIDs = [];
            foreach ($board->stacks as $stack) {
                $stacksIDs[] = $stack->id;
            }

            helper('tasks');

            // load all tasks
            $tasks = tasks_load($stacksIDs);

            // connect tasks to stacks
            foreach ($board->stacks as &$stack) {
                // remove the order property from the stack
                unset($stack->order);

                $stack->tasks = [];
                foreach ($tasks as &$task) {                    
                    if ($task->stack === $stack->id) {
                        $stack->tasks[] = task_format($task);
                    }
                }
            }
        }

        $board->archived = [];

        return $this->reply($board);
    }

    public function add_v1()
    {
        $user = $this->request->user;
        $boardData = $this->request->getJSON();

        helper('uuid');

        if (!isset($boardData->id)) {
            $boardData->id = uuid();
        }

        $boardData->owner = $user->id;
        $boardData->archived_order = 'title-asc';

        if (!isset($boardData->hourlyFee)) {
            $boardData->hourlyFee = 0;
        }

        if (!isset($boardData->feeCurrency)) {
            $boardData->feeCurrency = "USD";
        }

        if (!isset($boardData->everyone)) {
            $boardData->everyone = 1;
        } else {
            $boardData->everyone = intval($boardData->everyone);
        }

        $membersIDs = array();
        if (isset($boardData->members)) {
            $membersIDs = $boardData->members;
            $boardData->everyone = 0;
        }

        $boardModel = new BoardModel();

        try {
            if ($boardModel->insert($boardData) === false) {
                $errors = $boardModel->errors();
                return $this->reply($errors, 500, "ERR-BOARD-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-BOARD-CREATE");
        }

        // if members are defined then assigned them to the board
        if (count($membersIDs)) {
            $boardMemberModel = new BoardMemberModel();
            $boardMemberBuilder = $boardMemberModel->builder();

            $members = array();
            foreach ($membersIDs as $userID) {
                $members[] = [
                    'board' => $boardData->id,
                    'user' => $userID
                ];
            }

            try {
                if ($boardMemberBuilder->insertBatch($members) === false) {
                    $errors = $stackOrderModel->errors();
                    return $this->reply($errors, 500, "ERR-BOARD-MEMBERS");    
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-BOARD-MEMBERS");
            }
        }

        Events::trigger("AFTER_board_ADD", $boardData->id);

        return $this->reply($boardData, 200, "OK-BOARD-CREATE-SUCCESS");
    }

    public function update_v1($id)
	{
        $this->lock();

        $user = $this->request->user;
        $board = $this->request->board;
        
        $boardModel = new BoardModel();

        $boardData = $this->request->getJSON();

        unset($boardData->id); // we enforce this in another way
        unset($boardData->deleted); // this should pass via the designated route
        unset($boardData->owner); // this should pass via the designated route

        try {
            if ($boardModel->update($board->id, $boardData) === false) {
                return $this->reply(null, 404, "ERR-BOARDS-UPDATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-BOARD-UPDATE");
        }

        Events::trigger("AFTER_board_UPDATE", $board->id);

        return $this->reply();
    }

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
}
