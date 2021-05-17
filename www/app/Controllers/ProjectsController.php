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

        documents_update($document);

        
        
        // if (isset($projectData->tags)) {
        //     if (!$this->set_tags($recordData->id, $recordData->tags)) {
        //         return $this->reply(null, 500, "ERR-BOARD-TAGS");   
        //     }
        // }
	}
}
