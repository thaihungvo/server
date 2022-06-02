<?php namespace App\Controllers;

use App\Models\NotepadModel;

class NotepadsController extends BaseController
{
    public function update_v1($notepadId)
    {
        $document = $this->getDocument($notepadId);
        $this->exists($document);

        $this->cat("update", $document);

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
        
        $this->addActivity(
            $document->id,
            $document->parent,
            $document->id,
            $this::ACTION_UPDATE,
            $this::SECTION_DOCUMENT
        );
        
        return $this->reply(true);
    }
}