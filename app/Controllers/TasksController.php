<?php namespace App\Controllers;

use App\Models\TaskModel;
use App\Models\BoardModel;

class TasksController extends BaseController
{
    public function all_board_v1($id)
    {
        $user = $this->request->user;

        $boardModel = new BoardModel();
        $board = $boardModel
            ->where('owner', $user->id)
            ->find($id);

        if (!$board) {
            return $this->reply(null, 404, "ERR_BOARDS_NOT_FOUND_MSG");
        }

        /*
        SELECT stk_tasks.* FROM stk_tasks
        LEFT JOIN stk_stacks ON stk_stacks.id = stk_tasks.stack
        WHERE stk_stacks.board = "35436ec7-d32b-4f50-bba5-b5f276f289f9"
        */

        $stackModel = new StackModel();
        $stacks = $stackModel->where('board', $board->id)->findAll();

        return $this->reply($stacks);
    }