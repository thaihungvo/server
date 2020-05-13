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

        $query = $builder->select('boards.id, boards.title, boards.updated, boards.created')->join('boards_members', 'boards_members.board = boards.id')
            ->where('boards.deleted', NULL)
            ->where('boards.owner', $user->id)
            ->orWhere('boards_members.user', $user->id)
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
        $board->stacks = $stackModel->where('board', $board->id)->findAll();

        $stacksIDs = [];
        foreach ($board->stacks as $stack) {
            $stacksIDs[] = $stack->id;
        }

        // load all tasks
        $taskModel = new TaskModel();
        $tasks = $taskModel->whereIn('stack', $stacksIDs)->findAll();

        foreach ($board->stacks as &$stack) {
            $stack->tasks = [];
            foreach ($tasks as $task) {
                if ($task->stack === $stack->id) {
                    $stack->tasks[] = $task;
                }
            }
        }

        return $this->reply($board);
    }

    public function add_v1()
    {
        $user = $this->request->user;
        $boardData = $this->request->getJSON();

        helper('uuid');

        $data = [
            'id' => uuid(),
            'title' => $boardData->title,
            'owner' => $user->id,
            'archived_order' => 'title-asc'
        ];

        if (isset($boardData->id)) {
            $data['id'] = $boardData->id;
        }

        $boardModel = new BoardModel();

        try {
            if ($boardModel->insert($data) === false) {
                $errors = $boardModel->errors();
                return $this->reply($errors, 500, "ERR_BOARD_CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR_BOARD_CREATE");
        }

        $board = $boardModel->find($boardData->id);

        return $this->reply($board, 200, "OK_BOARD_CREATE_SUCCESS");
    }

    public function update_v1($id)
	{
        $user = $this->request->user;

        $boardModel = new BoardModel();
        $board = $boardModel
            ->where('owner', $user->id)
            ->find($id);

        if (!$board) {
            return $this->reply(null, 404, "ERR_BOARDS_NOT_FOUND_MSG");
        }

        $boardData = $this->request->getJSON();

        unset($boardData->id);

        if ($boardModel->update($board->id, $boardData) === false) {
            return $this->reply(null, 404, "ERR_BOARDS_UPDATE");
        }

        return $this->reply();
    }
}
