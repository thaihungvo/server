<?php

use App\Models\AttachmentModel;

if (!function_exists("files_expand")) {
    function files_expand(&$document) {
        // load files content
        $attachmentModel = new AttachmentModel();
        $attachment = $attachmentModel->where("resource", $document->id)->first();
        if ($attachment) {
            $document->name = $attachment->content;
            $document->extension = $attachment->extension;
            $document->path = "";
            $document->size = $attachment->size;
            $document->hash = $attachment->hash;
        }
    }
}

if (!function_exists("files_clean_up")) {
    function files_clean_up($document) {
        // delete all attachments
        $attachmentModel = new AttachmentModel();
        $attachment = $attachmentModel->where("resource", $document->id)->first();
        $attachmentModel->where("resource", $document->id)->delete();

        try {
            unlink(WRITEPATH . "uploads/attachments/". $attachment->hash); 
        } catch(Exception $e) { 
            return false;
        } 

        return true;
    }
}

?>