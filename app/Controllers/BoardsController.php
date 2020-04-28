<?php namespace App\Controllers;

use App\Models\BoardModel;

class BoardsController extends BaseController
{
	public function all_v1()
	{
        $user = $this->request->user;

        $boardModel = new BoardModel();
        $builder = $boardModel->builder();

        $query = $builder->join('boards_members', 'boards_members.board = boards.id')
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

        $boardModel = new BoardModel();
        $board = $boardModel
            ->where('owner', $user->id)
            ->find($id);

        if (!$board) {
            return $this->reply(null, 404, "ERR_BOARDS_NOT_FOUND_MSG");
        }

        return $this->reply($board);
    }

    public function create_v1()
    {
        $user = $this->request->user;
        $boardData = $this->request->getJSON();

        $data = [
            'id' => $boardData->id,
            'title' => $boardData->title,
            'owner' => $user->id,
            'archived_order' => 'title-asc'
        ];

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

        $board->title = $boardData->title;

        if (isset($boardData->archived_order)) {
            $board->archived_order = $boardData->archived_order;
        }

        if ($boardModel->update($board->id, $board) === false) {
            return $this->reply(null, 404, "ERR_BOARDS_UPDATE");
        }

        return $this->reply($board);
    }
}
