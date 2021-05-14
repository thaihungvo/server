<?php 

use App\Models\DocumentModel;

if (!function_exists('documents_validate_type'))
{
    function documents_validate_type($type)
    {
        $types = array("folder", "project", "notepad", "people");
        return in_array($type, $types);
    }
}

if (!function_exists('documents_create'))
{
    function documents_create($documentData)
    {
        $documentModel = new DocumentModel();
        $documentData->order = 1;

        $documentModel
            ->where("type", $documentData->type)
            ->orderBy("order", "desc");
            
        
        if ($documentData->type !== "folder") {
            $documentModel->where("folder", $documentData->folder);
        }

        $lastOrder = $documentModel->first();

        if ($lastOrder) {
            $documentData->order = intval($lastOrder->order) + 1;
        }

        try {
            if ($documentModel->insert($documentData) === false) {
                return $documentModel->errors();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}

if (!function_exists('documents_update'))
{
    function documents_update($document)
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
        
        // just in case someone tries to change types
        unset($document->type);
        unset($document->created);

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

if (!function_exists('documents_load'))
{
    function documents_load($recordID, $user)
    {
        $documentModel = new DocumentModel();

        $recordBuilder = $documentModel->builder();
        $recordQuery = $recordBuilder->select("documents.*")
            ->join("documents_members", "documents_members.document = documents.id", "left")
            ->where("documents.deleted", NULL)
            ->where("documents.id", $recordID)
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

if (!function_exists('documents_delete'))
{
    function documents_delete($document)
    {
        $documentModel = new DocumentModel();

        try {
            if ($documentModel->delete([$document->id]) === false) {
                return $documentModel->errors();
            }    
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}