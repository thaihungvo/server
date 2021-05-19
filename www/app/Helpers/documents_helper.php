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
        $documentData->position = 1;

        $documentModel
            ->where("type", $documentData->type)
            ->orderBy("position", "desc");
            
        
        if ($documentData->type !== "folder") {
            $documentModel->where("folder", $documentData->folder);
        }

        $lastPosition = $documentModel->first();

        if ($lastPosition) {
            $documentData->position = intval($lastPosition->position) + 1;
        }

        $optionsResult = documents_create_options($documentData);
        if ($optionsResult !== true) {
            return $optionsResult;
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

if (!function_exists('documents_create_options'))
{
    function documents_create_options($documentData)
    {
        // PROJECT OPTIONS
        if ($documentData->type === "project") {
            helper("projects");

            return projects_add_options($documentData->id, $documentData);
        }

        if ($documentData->type === "notepad") {
            helper("notepads");
            return notepads_create($documentData->id);
        }

        return true;
    }
}

if (!function_exists('documents_update'))
{
    function documents_update($documentData)
    {
        $documentModel = new DocumentModel();

        $document = $documentModel->where("deleted", NULL)
            ->find($documentData->id);

        if (!$document) {
            return "No document found with the requested id `".$documentData->id."`";
        }
        
        // just in case someone tries to change types
        unset($documentData->type);
        unset($documentData->created);

        // in case everyone was not set will enforce it to 1
        if (!isset($documentData->everyone)) {
            $documentData->everyone = 1;
        } else {
            $documentData->everyone = intval($documentData->everyone);
        }

        try {
            if ($documentModel->update($documentData->id, $documentData) === false) {
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
    function documents_load($documentID, $user)
    {
        $documentModel = new DocumentModel();

        $recordBuilder = $documentModel->builder();
        $recordQuery = $recordBuilder->select("documents.*")
            ->join("documents_members", "documents_members.document = documents.id", "left")
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

if (!function_exists('documents_delete'))
{
    function documents_delete($document)
    {
        $documentModel = new DocumentModel();

        // delete the current document
        try {
            if ($documentModel->delete([$document->id]) === false) {
                return $documentModel->errors();
            }    
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        // in case the user deleted a folder
        // then also delete all sub documents
        if ($document->type === "folder") {
            try {
                if ($documentModel->where("folder", $document->id)->delete() === false) {
                    return $documentModel->errors();
                }    
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }

        return true;
    }
}