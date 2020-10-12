<?php namespace App\Controllers;

use App\Models\TagModel;
use App\Models\BoardModel;

class TagsController extends BaseController
{
    public function all_v1($id)
    {
        $user = $this->request->user;
        $board = $this->request->board;

        $tagModel = new TagModel();
        $tags = $tagModel->where('board', $board->id)->findAll();

        return $this->reply($tags);
    }

    public function add_v1($id)
    {
        $this->lock();
        
        $user = $this->request->user;
        $board = $this->request->board;

        $tagModel = new TagModel();
        $tagData = $this->request->getJSON();

        helper('uuid');

        $tagData->board = $board->id;

        if (!isset($tagData->id)) {
            $tagData->id = uuid();
        }

        try {
            if ($tagModel->insert($tagData) === false) {
                $errors = $tagModel->errors();
                return $this->reply($errors, 500, "ERR-BOARD-TAGS-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-BOARD-TAGS-CREATE");
        }

        $tag = $tagModel->find($tagData->id);

        return $this->reply($tag, 200, "OK-BOARD-TAGS-CREATE-SUCCESS");
    }

    public function update_v1($idBoard, $idTag)
	{
        $user = $this->request->user;
        $board = $this->request->board;

        $tagModel = new TagModel();
        $tag = $tagModel
            ->where('board', $board->id)
            ->find($idTag);

        if (!$tag) {
            return $this->reply(null, 404, "ERR-BOARDS-TAG-NOT-FOUND-MSG");
        }

        $tagData = $this->request->getJSON();

        unset($tagData->id);

        if ($tagModel->update($tag->id, $tagData) === false) {
            return $this->reply(null, 404, "ERR-BOARDS-TAG-UPDATE");
        }

        return $this->reply(null, 200, "OK-BOARDS-TAG-UPDATE-SUCCESS");
    }
}