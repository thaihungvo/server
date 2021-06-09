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

    public function add_v1($peopleId)
    {
        helper("documents");

        $user = $this->request->user;
        $document = documents_load($peopleId, $user);

        if (!$document) {
            return $this->reply("People list not found", 404, "ERR-PEOPLE-ADD");
        }

        $personData = $this->request->getJSON();
        $personData->people = $document->id;

        $personModel = new PersonModel();
        try {
            if ($personModel->insert($personData) === false) {
                return $this->reply($personModel->errors(), 500, "ERR-PEOPLE-ADD");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-PEOPLE-ADD");
        }
        
        $this->addActivity($document->folder || "", $document->id, $this::ACTION_CREATE, $this::SECTION_DOCUMENT);
        
        return $this->reply(documents_load($documentData->id, $user));
    }
}