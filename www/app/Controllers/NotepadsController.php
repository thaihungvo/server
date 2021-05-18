<?php namespace App\Controllers;

use App\Models\NotepadModel;
use App\Models\StackModel;

class NotepadsController extends BaseController
{
    public function one_v1($notepadID)
    {
        $user = $this->request->user;

        $notepadModel = new NotepadModel();
        $notepad = $notepadModel->find($notepadID);

        helper("documents");
        $document = documents_load($notepad->document, $user->id);

        if (!$document || !$notepad) {
            return $this->reply(null, 404, "ERR-NOTEPAD-NOT-FOUND");
        }

        //helper("notepad");
        //$notepad = notepad_format($notepad);

        return $this->reply($notepad);
    }

    public function add_v1($documentId)
    {
        $user = $this->request->user;
        $notepadData = $this->request->getJSON();

        helper("documents");
        $document = documents_load($documentId, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-NOTEPAD-CREATE");
        }

        // enforce an id in case there"s none
        if (!isset($notepadData->document)) {
            helper("uuid");
            $notepadData->document = uuid();
        }

        // check if the stack exists
        $stackModel = new StackModel();
        $stack = $stackModel->where("project", $document->id)
            ->where("id", $notepadData->stack)
            ->first();

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-NOTEPAD-CREATE");
        }

        $notepadModel = new NotepadModel();

        $notepadData->updated = null;
        $notepadData->document = $document->id;
        
        try {
            if ($notepadModel->insert($notepadData) === false) {
                $errors = $notepadModel->errors();
                return $this->reply($errors, 500, "ERR-NOTEPAD-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-NOTEPAD-CREATE");
        }

        $notepad = $notepadModel->find($notepadData->id);

        //helper("notepad");
        //$notepad = notepad_format($notepad);

        $this->addActivity("", $notepad->document, $this::ACTION_CREATE, $this::SECTION_NOTEPAD);
        $this->addActivity("", $document->id, $this::ACTION_UPDATE, $this::SECTION_PROJECT);

        return $this->reply($notepad);
    }

    public function update_v1($notepadID)
    {
        $this->lock($notepadID);

        $notepadModel = new NotepadModel();
        $notepad = $notepadModel->find($notepadID);
        $user = $this->request->user;

        helper("documents");
        $document = documents_load($notepad->document, $user->id);

        if (!$document || !$notepad) {
            return $this->reply(null, 404, "ERR-NOTEPADS-NOT-FOUND");
        }

        $notepadData = $this->request->getJSON();
        helper("uuid");

        unset($notepadData->document);

        if ($notepadModel->update($notepadID, $notepadData) === false) {
            return $this->reply(null, 500, "ERR-NOTEPAD-UPDATE");
        }

        // Events::trigger("AFTER_notepad_UPDATE", $notepadID);
        // Events::trigger("update_board", $board->id);

        return $this->reply(true);
    }

    public function delete_v1($notepadID)
    {
        $this->lock($notepadID);

        $notepadModel = new NotepadModel();
        $notepad = $notepadModel->find($notepadID);

        if (!$notepad) {
            return $this->reply(null, 404, "ERR-NOTEPADS-DELETE");
        }

        // delete selected notepad
        try {
            if ($notepadModel->delete([$notepad->document]) === false) {
                return $this->reply($notepadModel->errors(), 500, "ERR-NOTEPADS-DELETE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-NOTEPADS-DELETE");
        }

        $this->addActivity("", $notepad->document, $this::ACTION_DELETE, $this::SECTION_NOTEPAD);

        return $this->reply(true);
    }
}