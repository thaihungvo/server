<?php namespace App\Controllers;

class ProjectsController extends BaseController
{
	public function one_v1($id)
	{
        helper("documents");
        helper("projects");

        $user = $this->request->user;
        $document = documents_load($id, $user);
        
        $data = projects_load($document);

        return $this->reply($data);
	}

	public function update_v1($id)
	{
        $this->lock($id);


	}
}
