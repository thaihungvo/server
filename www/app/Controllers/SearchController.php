<?php namespace App\Controllers;

class SearchController extends BaseController
{
    public function query_v1()
	{
        $db = db_connect();
        $searchQuery = $this->request->getGet("q");

        if (!\strlen($searchQuery)) {
            return $this->reply($searchResults);
        }

        $queryTasks = array(
            "SELECT tasks.*, project.title as projectTitle, stacks.title as stackTitle, folder.title as folderTitle FROM ".$db->prefixTable("tasks")." as tasks",
            "LEFT JOIN ".$db->prefixTable("documents")." as project ON project.id = tasks.project",
            "LEFT JOIN ".$db->prefixTable("stacks")." as stacks ON stacks.id = tasks.stack",
            "LEFT JOIN ".$db->prefixTable("documents")." as folder ON folder.id = project.folder",
            "WHERE tasks.title LIKE '%".$db->escapeString(urldecode($searchQuery))."%'"
        );

        $query = $db->query(implode(" ", $queryTasks));
        $searchResults = array();

        foreach ($query->getResult() as $task){
            $search = new \stdClass();
            $search->title = $task->title;
            $search->type = "project";
            $search->itemId = $task->id;
            $search->recordId = $task->project;
            $search->parents = array(
                $task->folderTitle,
                $task->projectTitle,
                $task->stackTitle
            );

            $searchResults[] = $search;
        }

        $db->close();

        return $this->reply($searchResults);
    }
}