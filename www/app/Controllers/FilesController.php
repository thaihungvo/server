<?php namespace App\Controllers;

use App\Models\UserModel;
use App\Models\AttachmentModel;

class FilesController extends BaseController
{
	public function upload_v1($taskID)
	{
        $task = $this->request->board->task;
        $user = $this->request->user;

        $chunk = $this->request->getBody();
        $localChunkHash = md5($chunk);

        $chunkHash = $this->request->getGet("hash");
        $fileSize = $this->request->getGet("filesize");
        $fileName = base64_decode($this->request->getGet("filename"));
        $fileHash = $this->request->getGet("filehash");
        $sent = $this->request->getGet("sent");
        $chunkSize = strlen($chunk);
        
        if ($localChunkHash != $chunkHash) {
            return $this->reply(null, 406, "ERR-CHUNK-MD5-MISSMATCH");
        }

        $uploadPath =  WRITEPATH . "uploads/attachments/". $fileHash;
        $uploadTmpPath = $uploadPath . "-tmp-".$user->id;

        if (file_exists($uploadPath)) {
            // the current user tries to upload a file that's already on the server
            $savedAttachment = $this->saveAttachment($task, $fileName, $fileSize, $fileHash);
            if ($savedAttachment === false) {
                return $this->reply(null, 500, "ERR-ATTACHMENT-CREATE");
            }
            return $this->reply($savedAttachment, 200);
        }

        if ($sent == 0 && file_exists($uploadTmpPath)) {
            if (!unlink($uploadTmpPath)) {
                return $this->reply(null, 417, "ERR-FILE-REMOVE-OLD");
            }
        }

        // save the chunk
        $bytesSaved = file_put_contents($uploadTmpPath, $chunk, FILE_APPEND);

        // if the size of data saved don't match the size of the sent chunk
        if($bytesSaved != $chunkSize){
            return $this->reply(null, 417, "ERR-FILE-DATA-SIZE-MISSMATCH");
        }

        // if where're not finished send back the required info
        if(filesize($uploadTmpPath) < $fileSize){
            return $this->reply(null, 206);
        }

        // if the upload is finished
        // check the consistency
        if(md5_file($uploadTmpPath) != strtolower($fileHash)){
            return $this->reply(null, 417, "ERR-FILE-CONSISTENCY-MISSMATCH");
        }

        if (!rename($uploadTmpPath, $uploadPath)) {
            return $this->reply(null, 500, "ERR-FILE-RENAME");
        }

        $savedAttachment = $this->saveAttachment($task, $fileName, $fileSize, $fileHash);
        if ($savedAttachment === false) {
            return $this->reply(null, 500, "ERR-ATTACHMENT-CREATE");
        }

        // add the new record to the db
        return $this->reply($savedAttachment, 200);
    }

    public function link_v1()
    {
        $task = $this->request->board->task;
        $user = $this->request->user;
        $data = $this->request->getJSON();

        $savedLink = $this->saveLink($task, $data->title, $data->url);
        if ($savedLink === false) {
            return $this->reply(null, 500, "ERR-LINK-CREATE");
        }

        // add the new record to the db
        return $this->reply($savedLink, 200);
    }

    public function delete_v1()
    {

    }

    public function download_v1()
    {

    }

    public function update_v1()
    {

    }

    private function saveAttachment($taskID, $fileName, $fileSize, $fileHash)
    {
        $user = $this->request->user;

        $attachmentModel = new AttachmentModel();

        $attachment = new \stdClass();
        $attachment->owner = $user->id;
        $attachment->task = $taskID;
        $attachment->title = $fileName;
        $attachment->content = $fileName;
        $attachment->hash = $fileHash;
        $attachment->extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $attachment->size = $fileSize;
        $attachment->type = "file";

        try {
            if ($attachmentModel->insert($attachment) === false) {
                // $errors = $attachmentModel->errors();
                // return $this->reply($errors, 500, "ERR-ATTACHMENT-CREATE");
                return false;
            }
        } catch (\Exception $e) {
            // return $this->reply($e->getMessage(), 500, "ERR-ATTACHMENT-CREATE");
            return false;
        }

        $attachment->id = $attachmentModel->insertID;

        return $attachment;
    }

    private function saveLink($taskID, $title, $url)
    {
        $user = $this->request->user;

        $attachmentModel = new AttachmentModel();

        $attachment = new \stdClass();
        $attachment->owner = $user->id;
        $attachment->task = $taskID;
        $attachment->title = $title;
        $attachment->content = $url;
        $attachment->extension = "lnk";
        $attachment->size = 0;
        $attachment->type = "link";

        try {
            if ($attachmentModel->insert($attachment) === false) {
                // $errors = $attachmentModel->errors();
                // return $this->reply($errors, 500, "ERR-ATTACHMENT-CREATE");
                return false;
            }
        } catch (\Exception $e) {
            // return $this->reply($e->getMessage(), 500, "ERR-ATTACHMENT-CREATE");
            return false;
        }

        $attachment->id = $attachmentModel->insertID;

        return $attachment;
    }
}