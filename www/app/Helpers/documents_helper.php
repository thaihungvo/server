<?php 

use App\Models\FolderModel;
use App\Models\DocumentModel;

if (!function_exists('documents_validate_type'))
{
    function documents_validate_type($type)
    {
        $types = array("folder", "project");
        return in_array($type, $types);
    }
}

if (!function_exists('documents_create_folder'))
{
    function documents_create_folder($folder)
    {
        $folderModel = new FolderModel();
        $folder->order = 1;

        $lastOrder = $folderModel
            ->orderBy("order", "desc")
            ->first();

        if ($lastOrder) {
            $folder->order = intval($lastOrder->order) + 1;
        }

        try {
            if ($folderModel->insert($folder) === false) {
                return $folderModel->errors();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}

if (!function_exists('documents_update_folder'))
{
    function documents_update_folder($folder)
    {
        $folderModel = new FolderModel();

        $folderBuilder = $folderModel->builder();
        $folderQuery = $folderBuilder->select('folders.*')
            ->where('folders.deleted', NULL)
            ->where('folders.id', $folder->id)
            ->limit(1)
            ->get();

        $folders = $folderQuery->getResult();

        if (!count($folders)) {
            return "No folder found with the requested id `".$folder->id."`";
        }

        unset($folder->owner);
        // TODO: only owner might change the visibility
        unset($folder->everyone);
        unset($folder->type);

        try {
            if ($folderModel->update($folder->id, $folder) === false) {
                return $folderModel->errors();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}

if (!function_exists('documents_create_document'))
{
    function documents_create_document($document)
    {
        $documentModel = new DocumentModel();
        $document->order = 1;

        $lastOrder = $documentModel
            ->orderBy("order", "desc")
            ->first();

        if ($lastOrder) {
            $document->order = intval($lastOrder->order) + 1;
        }

        try {
            if ($documentModel->insert($document) === false) {
                return $documentModel->errors();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}

if (!function_exists('documents_update_document'))
{
    function documents_update_document($document)
    {
        $documentModel = new DocumentModel();

        $documentBuilder = $documentModel->builder();
        $documentQuery = $documentBuilder->select('documents.*')
            ->where('documents.deleted', NULL)
            ->where('documents.id', $document->id)
            ->limit(1)
            ->get();

        $documents = $documentQuery->getResult();

        if (!count($documents)) {
            return "No document found with the requested id `".$document->id."`";
        }

        try {
            if ($documentModel->update($document->id, $document) === false) {
                return $documentModel->errors();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}

if (!function_exists('documents_load_document'))
{
    function documents_load_document($recordID, $user)
    {
        $DocumentModel = new DocumentModel();

        $recordBuilder = $DocumentModel->builder();
        $recordQuery = $recordBuilder->select("records.*")
            ->join("documents_members", "documents_members.record = records.id", "left")
            ->where("records.deleted", NULL)
            ->where("records.id", $recordID)
            ->groupStart()
                ->where("records.owner", $user->id)
                ->orWhere("documents_members.user", $user->id)
                ->orWhere("records.everyone", 1)
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