<?php 

use App\Models\DocumentModel;


if (!function_exists('documents_get_default_options'))
{
    function documents_get_default_options($type)
    {
        if ($type === "project") {
            $projectOptions = new \stdClass();
            $projectOptions->feeCurrency = "USD";
            $projectOptions->archivedOrder = "archived-asc";

            return $projectOptions;
        }

        return new \stdClass();
    }
}

if (!function_exists('documents_load'))
{
    function documents_load($documentID, $user)
    {
        $documentModel = new DocumentModel();

        $documentBuilder = $documentModel->builder();
        $documentQuery = $documentBuilder->select("documents.*")
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

        $documents = $documentQuery->getResult();
        
        if (!count($documents)) return null;

        $document = $documents[0];
        if ($document->options) {
            $document->options = json_decode($document->options);
        }

        documents_expand_document($document, $user);

        // removing unncessary prop
        unset($document->deleted);
        unset($document->position);

        return $document;
    }
}

if (!function_exists('documents_expand_document'))
{
    function documents_expand_document(&$document, $user)
    {
        switch ($document->type) {
            case "project":
                helper("projects");
                projects_expand($document, $user);
                break;
            case "people":
                helper("people");
                people_expand($document);
                break;
            case "notepad":
                helper("notepads");
                notepads_expand($document);
                break;
            default:
                break;
        }
    }
}

if (!function_exists('documents_clean_up'))
{
    function documents_clean_up($documents)
    {
        foreach ($documents as $document) {        
            switch ($document->type) {
                case "project":
                    helper("projects");
                    projects_clean_up($document);
                    break;
                case "people":
                    helper("people");
                    people_clean_up($document);
                    break;
                case "notepad":
                    helper("notepads");
                    notepads_clean_up($document);
                    break;
                default:
                    break;
            }
        }
    }
}

if (!function_exists('documents_get_tree'))
{
    function documents_get_tree($documents, $folderId)
    {
        $tree = array();

        foreach ($documents as $document) {
            if ($document->parent !== $folderId) continue;
            if ($document->type === "folder") {
                $tree[] = $document;
                $tree = array_merge($tree, documents_get_tree($documents, $document->id));
            } else {
                $tree[] = $document;
            }
        }

        return $tree;
    }
}