<?php 

use App\Models\DocumentModel;
use App\Models\NotepadModel;

if (!function_exists('notepads_load'))
{
    function notepads_load($documentID, $user)
    {
        $documentModel = new DocumentModel();

        $recordBuilder = $documentModel->builder();
        $recordQuery = $recordBuilder->select("documents.*, notepads.content")
            ->join("documents_members", "documents_members.document = documents.id", "left")
            ->join("notepads", "notepads.document = documents.id", "left")
            ->where("documents.deleted", NULL)
            ->where("documents.id", $documentID)
            ->groupStart()
            ->where("documents.owner", $user->id)
            ->orWhere("documents_members.user", $user->id)
            ->orWhere("documents.everyone", 1)
            ->groupEnd()
            ->limit(1)
            ->get();

        $records = $recordQuery->getResult();

        if (!count($records)) {
            return null;
        }

        return $records[0];
    }
}

if (!function_exists('notepads_create'))
{
    function notepads_create($documentID)
    {
        $notepadModel = new NotepadModel();
        $notepadData = ['document' => $documentID];

        try {
            if ($notepadModel->insert($notepadData) === false) {
                return $notepadModel->errors();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}