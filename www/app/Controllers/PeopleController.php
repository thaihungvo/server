<?php namespace App\Controllers;

use App\Models\PersonModel;

class PeopleController extends BaseController
{
	public function get_v1($peopleId)
	{
        helper("documents");

        $user = $this->request->user;
        $document = documents_load($peopleId, $user);

        if (!$document) {
            return $this->reply("People list not found", 404, "ERR-PEOPLE-GET");
        }
        
        $personModel = new PersonModel();
        $document->people = $personModel->where("people", $peopleId)->findAll();

        return $this->reply($document);
	}
}