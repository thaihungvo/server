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

        /*
        SELECT stk_boards.* FROM stk_boards
        LEFT JOIN stk_boards_members ON stk_boards_members.board = stk_boards.id
        WHERE stk_boards.owner = 1 OR stk_boards_members.user = 1 AND stk_boards.deleted IS NULL
        */

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
            'title' => $boardData->title,
            'owner' => $user->id
        ];

        $boardModel = new BoardModel();

        if (!$boardModel->save($data)) {
            return $this->reply(null, 500, "ERR_BOARD_CREATE");
        }

        return $this->reply(null, 200, "OK_BOARD_CREATE_SUCCESS");
    }
}
