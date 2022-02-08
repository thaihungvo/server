<?php

use App\Models\NotepadModel;

if (!function_exists("notepads_expand")) {
    function notepads_expand(&$document) {
        // load notepad content
        $notepadModel = new NotepadModel();
        $notepad = $notepadModel->find($document->id);
        $document->content = $notepad->content;
    }
}

if (!function_exists("notepads_clean_up")) {
    function notepads_clean_up($document) {

        // delete all contents for this document
        $notepadModel = new NotepadModel();
        try {
            if ($notepadModel->where("notepad", $document->id)->delete() === false) return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}

?>