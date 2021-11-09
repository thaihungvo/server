<?php namespace App\Controllers;

use App\Models\NotepadModel;

class NotepadsController extends BaseController
{
    public function update_v1($notepadId)
    {
        helper("documents");

        $user = $this->request->user;
        $document = documents_load_document($notepadId, $user);

        if (!$document) {
            return $this->reply("Notepad not found", 404, "ERR-NOTEPADS-UPDATE");
        }

        $notepadData = $this->request->getJSON();

        $notepadModel = new NotepadModel();
        $notepad = $notepadModel->find($document->id);

        $data = array(
            "notepad" => $document->id,
            "content" => $notepadData->content
        );
        
        try {
            if (!$notepad) {
                if ($notepadModel->insert($data) === false) {
                    return $this->reply($notepadModel->errors(), 500, "ERR-NOTEPADS-UPDATE");
                }
            } else {
                if ($notepadModel->update($document->id, $data) === false) {
                    return $this->reply($notepadModel->errors(), 500, "ERR-NOTEPADS-UPDATE");
                }
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-NOTEPADS-UPDATE");
        }
        
        $this->addActivity($document->parent, $document->id, $this::ACTION_UPDATE, $this::SECTION_DOCUMENT);
        
        return $this->reply(true);
    }
}