<?php namespace App\Controllers;

use App\Models\DocumentModel;
use App\Models\NotepadModel;

class NotepadsController extends BaseController
{
    public function one_v1($notepadID)
    {
        $user = $this->request->user;

        helper("notepads");
        $notepad = notepads_load($notepadID, $user->id);

        if (!$notepad) {
            return $this->reply(null, 404, "ERR-NOTEPAD-NOT-FOUND");
        }

        return $this->reply($notepad);
    }

    public function update_v1($notepadID)
    {
        $this->lock($notepadID);

        $user = $this->request->user;

        $notepadModel = new NotepadModel();
        helper("notepads");
        $notepad = notepads_load($notepadID, $user->id);

        if (!$notepad) {
            return $this->reply(null, 404, "ERR-NOTEPADS-NOT-FOUND");
        }

        $notepadData = $this->request->getJSON();

        unset($notepadData->document);

        if ($notepadModel->update($notepadID, $notepadData) === false) {
            return $this->reply(null, 500, "ERR-NOTEPAD-UPDATE");
        }

        return $this->reply(true);
    }

    public function delete_v1($notepadID)
    {
        $this->lock($notepadID);

        $user = $this->request->user;
        
        $documentModel = new DocumentModel();
        helper("notepads");
        $notepad = notepads_load($notepadID, $user->id);

        if (!$notepad) {
            return $this->reply(null, 404, "ERR-NOTEPADS-DELETE");
        }

        // delete selected notepad
        try {
            if ($documentModel->delete([$notepad->id]) === false) {
                return $this->reply($documentModel->errors(), 500, "ERR-NOTEPADS-DELETE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-NOTEPADS-DELETE");
        }

        //$this->addActivity("", $notepad->document, $this::ACTION_DELETE, $this::SECTION_NOTEPAD);

        return $this->reply(true);
    }
}