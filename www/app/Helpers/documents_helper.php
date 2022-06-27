<?php 

use App\Models\DocumentModel;

if (!function_exists('documents_load_counters'))
{
    function documents_load_counters(&$documents)
    {
        $today = date("Y-m-d");
        $db = db_connect();

        // get counters
        $documentModel = new DocumentModel();
        $documentBuilder = $documentModel->builder();
        $documentQuery = $documentBuilder->select("COUNT(". $db->protectIdentifiers("tasks", true) .".`id`) AS totalTasks, COUNT(". $db->protectIdentifiers("users", true) .".`id`) AS totalPeople, SUM(CASE WHEN ". $db->protectIdentifiers("tasks", true) .".`duedate` < '". $today ."' AND ". $db->protectIdentifiers("tasks", true) .".`done` = 0 THEN 1 ELSE 0 END) AS warning, documents.id")
            ->join("tasks", "tasks.project = documents.id", "left")
            ->join("users", "users.people = documents.id", "left")
            ->whereIn("documents.type", ["project", "people"])
            ->where("documents.deleted", NULL)
            ->where("tasks.deleted", NULL)
            ->where("users.deleted", NULL)
            ->groupBy("documents.id")
            ->get();
        $counters = $documentQuery->getResult();

        foreach ($documents as &$document) {
            foreach ($counters as $counter) {
                if ($document->id === $counter->id) {
                    $document->data->counter = new \stdClass();

                    if ($document->data->type === "project") {
                        $document->data->counter->total = (int)$counter->totalTasks;
                        if ($counter->warning && $counter->warning > 0) {
                            $document->data->counter->warning = (int)$counter->warning;
                        }
                    } else if ($document->data->type === "people") {
                        $document->data->counter->total = (int)$counter->totalPeople;
                    }
                }
            }
        }
    }
}

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
            case "file":
                helper("files");
                files_expand($document);
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
            switch ($document->data->type) {
                case "project":
                    helper("projects");
                    return projects_clean_up($document);
                    break;
                case "people":
                    helper("people");
                    return people_clean_up($document);
                    break;
                case "notepad":
                    helper("notepads");
                    return notepads_clean_up($document);
                    break;
                case "file":
                    helper("files");
                    return files_clean_up($document);
                    break;
                default:
                    return true;
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