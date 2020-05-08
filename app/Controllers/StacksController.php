<?php namespace App\Controllers;

use App\Models\StackModel;
use App\Models\BoardModel;

class StacksController extends BaseController
{
    public function all_v1($id)
    {
        $user = $this->request->user;

        $boardModel = new BoardModel();
        $board = $boardModel
            ->where('owner', $user->id)
            ->find($id);

        if (!$board) {
            return $this->reply(null, 404, "ERR_BOARDS_NOT_FOUND_MSG");
        }

        $stackModel = new StackModel();
        $stacks = $stackModel->where('board', $board->id)->findAll();

        return $this->reply($stacks);
    }

    public function add_v1($id)
    {
        $user = $this->request->user;

        $boardModel = new BoardModel();
        $board = $boardModel
            ->where('owner', $user->id)
            ->find($id);

        if (!$board) {
            return $this->reply(null, 404, "ERR_BOARDS_NOT_FOUND_MSG");
        }

        $stackModel = new StackModel();
        $stackData = $this->request->getJSON();

        helper('uuid');

        $data = [
            'id' => uuid(),
            'title' => $stackData->title,
            'board' => $board->id
        ];

        if (isset($stackData->id)) {
            $data['id'] = $stackData->id;
        }

        try {
            if ($stackModel->insert($data) === false) {
                $errors = $stackModel->errors();
                return $this->reply($errors, 500, "ERR_BOARD_STACKS_CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR_BOARD_STACKS_CREATE");
        }

        $stack = $stackModel->find($data['id']);

        return $this->reply($stack, 200, "OK_BOARD_STACKS_CREATE_SUCCESS");
    }

    public function update_v1($idBoard, $idStack)
    {
        $user = $this->request->user;

        $boardModel = new BoardModel();
        $board = $boardModel
            ->where('owner', $user->id)
            ->find($idBoard);

        if (!$board) {
            return $this->reply(null, 404, "ERR_BOARDS_NOT_FOUND_MSG");
        }

        $stackModel = new StackModel();
        $stack = $stackModel
            ->where('board', $board->id)
            ->find($idStack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR_BOARDS_STACK_NOT_FOUND_MSG");
        }

        $stackData = $this->request->getJSON();

        $stack->title = $stackData->title;

        if ($stackModel->update($stack->id, $stack) === false) {
            return $this->reply(null, 404, "ERR_BOARDS_STACKS_UPDATE");
        }

        return $this->reply($stack);
    }
}