<?php namespace App\Controllers;

class ProjectsController extends BaseController
{
	public function one_v1($id)
	{
        helper("documents");
        helper("projects");

        $user = $this->request->user;
        $document = documents_load($id, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-PROJECTS-GET");
        }
        
        $data = projects_load($document);

        unset($data->order);
        unset($data->deleted);

        return $this->reply($data);
	}

	public function update_v1($id)
	{
        $this->lock($id);

        helper("documents");
        helper("projects");

        $user = $this->request->user;
        $document = documents_load($id, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-PROJECTS-UPDATE");
        }

        $projectData = $this->request->getJSON();

        $document->updated = $projectData->updated;
        $document->title = $projectData->title;

        $result = documents_update($document);
        if ($result !== true) {
            return $this->reply($result, 500, "ERR-PROJECTS-UPDATE");
        }

        $result = projects_add_tags($document->id, $projectData->tags);
        if ($result !== true) {
            return $this->reply($result, 500, "ERR-PROJECTS-UPDATE");
        }

        $this->addActivity("", $id, $this::ACTION_UPDATE, $this::SECTION_PROJECT);
        $this->addActivity("", $id, $this::ACTION_UPDATE, $this::SECTION_DOCUMENT);
        $this->addActivity("", $id, $this::ACTION_UPDATE, $this::SECTION_DOCUMENTS);

        return $this->reply(true);
	}

    public function order_tasks_v1($projectId)
    {
        $this->lock($projectId);

        helper("documents");
        $user = $this->request->user;
        $document = documents_load($id, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-TASKS-ORDER");
        }
        
        $orderData = $this->request->getJSON();

        $db = db_connect();
        $query = array(
            "INSERT INTO ".$db->prefixTable("tasks")." (`id`, `stack`, `position`) VALUES"
        );

        $values = array();
        foreach ($orderData as $stack => $tasks) {
            foreach ($tasks as $index => $task) {
                $values[] = "(". $db->escape($task) .", ". $db->escape($stack) .", ". $db->escape($index + 1) .")";
            }
        }

        $query[] = implode(", ", $values);
        $query[] = "ON DUPLICATE KEY UPDATE id=VALUES(id), `stack`=VALUES(`stack`), `position`=VALUES(`position`);";
        $query = implode(" ", $query);

        if (!$db->query($query)) {
            return $this->reply("Unable to update tasks order", 500, "ERR-TASKS-ORDER");
        }

        $this->addActivity("", $document->id, $this::ACTION_UPDATE, $this::SECTION_PROJECT);

        return $this->reply(true);
    }
}
