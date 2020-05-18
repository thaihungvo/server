<?php namespace App\Controllers;

use App\Models\BoardModel;
use App\Models\TagModel;
use App\Models\StackModel;
use App\Models\TaskModel;

class BoardsController extends BaseController
{
	public function all_v1()
	{
        $user = $this->request->user;

        $boardModel = new BoardModel();
        $builder = $boardModel->builder();

        $query = $builder->select('boards.id, boards.title, boards.updated, boards.created')
            ->join('boards_members', 'boards_members.board = boards.id', 'left')
            ->where('boards.deleted', NULL)
            ->groupStart()
                ->where('boards.owner', $user->id)
                ->orWhere('boards_members.user', $user->id)
            ->groupEnd()
            ->get();

        $boards = $query->getResult();

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
        $stacksBuilder = $stackModel->builder();
        $stacksQuery = $stacksBuilder->select("stacks.*")
            ->join('stacks_order', 'stacks_order.id = stacks.id', 'left')
            ->where('stacks.board', $board->id)
            ->where('stacks.deleted', NULL)
            ->orderBy('stacks_order.`order`', 'ASC')
            ->get();
        $board->stacks = $stacksQuery->getResult();

        if (count($board->stacks)) {
            $stacksIDs = [];
            foreach ($board->stacks as $stack) {
                $stacksIDs[] = $stack->id;
            }

            // load all tasks
            $taskModel = new TaskModel();
            $tasks = $taskModel->whereIn('stack', $stacksIDs)
                ->orderBy('order', 'asc')
                ->findAll();

            foreach ($board->stacks as &$stack) {
                // remove the order property from the stack
                unset($stack->order);

                $stack->tasks = [];
                foreach ($tasks as &$task) {
                    // remove the order property from the task
                    unset($task->order);
                    
                    if ($task->stack === $stack->id) {
                        $stack->tasks[] = $task;
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

        $boardModel = new BoardModel();

        try {
            if ($boardModel->insert($boardData) === false) {
                $errors = $boardModel->errors();
                return $this->reply($errors, 500, "ERR_BOARD_CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-BOARD-CREATE");
        }

        return $this->reply($boardData, 200, "OK-BOARD-CREATE-SUCCESS");
    }

    public function update_v1($id)
	{
        $user = $this->request->user;

        $boardModel = new BoardModel();
        $board = $boardModel
            ->where('owner', $user->id)
            ->find($id);

        if (!$board) {
            return $this->reply(null, 404, "ERR-BOARDS-NOT-FOUND-MSG");
        }

        $boardData = $this->request->getJSON();

        unset($boardData->id);

        if ($boardModel->update($board->id, $boardData) === false) {
            return $this->reply(null, 404, "ERR-BOARDS-UPDATE");
        }

        return $this->reply();
    }
}
