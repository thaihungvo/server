<?php namespace App\Controllers;

use App\Models\BoardModel;

class BoardsController extends BaseController
{
	public function all()
	{
        $boardModel = new BoardModel();
        // $boards = $boardModel->where('owner', 1)->findAll();

        $builder = $boardModel->builder();

        $query = $builder->join('boards_members', 'boards_members.board = boards.id')
            ->where('boards.deleted', NULL)
            ->where('boards.owner', 1)
            ->orWhere('boards_members.user', 1)
            ->get();

        $boards = $query->getResult();

        /*
        SELECT stk_boards.* FROM stk_boards
        LEFT JOIN stk_boards_members ON stk_boards_members.board = stk_boards.id
        WHERE stk_boards.owner = 1 OR stk_boards_members.user = 1 AND stk_boards.deleted IS NULL
        */

        return $this->reply($boards);
	}

	public function one($id)
	{
        $boardModel = new BoardModel();
        $board = $boardModel
            ->where('owner', 1)
            ->find($id);

        if (!$board) {
            return $this->reply(null, 404, "ERR_BOARD_NOT_FOUND_MSG");
        }

        return $this->reply($board);
    }
}
