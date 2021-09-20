<?php namespace App\Controllers;

use App\Models\TagModel;

class TagsController extends BaseController
{
    public function all_v1()
    {
        $tagModel = new TagModel();
        return $this->reply($tagModel->findAll());
    }

    public function add_v1()
    {        
        $tagModel = new TagModel();
        $tagData = $this->request->getJSON();

        helper('uuid');

        if (!isset($tagData->id)) {
            $tagData->id = uuid();
        }

        try {
            if ($tagModel->insert($tagData) === false) {
                return $this->reply($tagModel->errors(), 500, "ERR-TAGS-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TAGS-CREATE");
        }

        return $this->reply($tagData);
    }

    public function update_v1($idTag)
	{
        $tagModel = new TagModel();
        $tag = $tagModel->find($idTag);

        if (!$tag) {
            return $this->reply(null, 404, "ERR-TAG-NOT-FOUND");
        }

        $tagData = $this->request->getJSON();

        unset($tagData->id);
        unset($tagData->create);
        if (!isset($tagData->updated)) {
            $tagData->updated = date('Y-m-d H:i:s');
        }

        try {
            if ($tagModel->update($tag->id, $tagData) === false) {
                return $this->reply($tagModel->errors(), 500, "ERR-TAGS-UPDATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TAGS-UPDATE");
        }

        return $this->reply(true);
    }

    public function delete_v1($idTag)
	{
        $tagModel = new TagModel();
        $tag = $tagModel->find($idTag);

        if (!$tag) {
            return $this->reply(null, 404, "ERR-TAG-NOT-FOUND");
        }

        try {
            if ($tagModel->delete([$idTag]) === false) {
                return $this->reply($tagModel->errors(), 500, "ERR-TAG-DELETE");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-TAG-DELETE");
        }

        return $this->reply(true);
    }
}