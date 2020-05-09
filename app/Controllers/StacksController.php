<?php namespace App\Controllers;

use App\Models\StackModel;
use App\Models\BoardModel;

class StacksController extends BaseController
{
    public function all_v1($id)
    {
        $board = $this->request->board;

        $stackModel = new StackModel();
        $stacks = $stackModel->where('board', $board->id)->findAll();

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
                return $this->reply($errors, 500, "ERR_STACK_CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR_STACK_CREATE");
        }

        $stack = $stackModel->find($data['id']);

        return $this->reply($stack, 200, "OK_STACK_CREATE_SUCCESS");
    }

    public function update_v1($idBoard, $idStack)
    {
        $board = $this->request->board;

        $stackModel = new StackModel();
        $stack = $stackModel
            ->where('board', $board->id)
            ->find($idStack);

        if (!$stack) {
            return $this->reply(null, 404, "ERR_STACK_NOT_FOUND_MSG");
        }

        $stackData = $this->request->getJSON();

        $stack->title = $stackData->title;

        if ($stackModel->update($stack->id, $stack) === false) {
            return $this->reply(null, 404, "ERR_STACK_UPDATE");
        }

        return $this->reply(null, 200, "OK_STACK_UPDATE_SUCCESS");
    }
}