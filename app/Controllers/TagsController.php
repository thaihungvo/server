<?php namespace App\Controllers;

use App\Models\TagModel;
use App\Models\BoardModel;

class TagsController extends BaseController
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

        $tagModel = new TagModel();
        $tags = $tagModel->where('board', $board->id)->findAll();

        return $this->reply($tags);
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

        $tagModel = new TagModel();
        $tagData = $this->request->getJSON();

        $data = [
            'id' => $tagData->id,
            'title' => $tagData->title,
            'color' => $tagData->color,
            'board' => $board->id
        ];

        try {
            if ($tagModel->insert($data) === false) {
                $errors = $tagModel->errors();
                return $this->reply($errors, 500, "ERR_BOARD_TAGS_CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR_BOARD_TAGS_CREATE");
        }

        $tag = $tagModel->find($tagData->id);

        return $this->reply($tag, 200, "OK_BOARD_TAGS_CREATE_SUCCESS");
    }

    public function update_v1($idBoard, $idTag)
	{
        $user = $this->request->user;

        $boardModel = new BoardModel();
        $board = $boardModel
            ->where('owner', $user->id)
            ->find($idBoard);

        if (!$board) {
            return $this->reply(null, 404, "ERR_BOARDS_NOT_FOUND_MSG");
        }

        $tagModel = new TagModel();
        $tag = $tagModel
            ->where('board', $board->id)
            ->find($idTag);

        if (!$tag) {
            return $this->reply(null, 404, "ERR_BOARDS_TAG_NOT_FOUND_MSG");
        }

        $tagData = $this->request->getJSON();

        $tag->title = $tagData->title;
        $tag->color = $tagData->color;

        if ($tagModel->update($tag->id, $tag) === false) {
            return $this->reply(null, 404, "ERR_BOARDS_TAGS_UPDATE");
        }

        return $this->reply($tag);
    }
}