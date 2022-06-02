<?php namespace App\Controllers;

use App\Models\PersonModel;

class PeopleController extends BaseController
{
    public function add_v1($peopleId)
    {
        $document = $this->getDocument($peopleId);
        $this->exists($document);

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
        
        $this->addActivity(
            $document->id,
            $document->parent, 
            $document->id, 
            $this::ACTION_CREATE, 
            $this::SECTION_DOCUMENT
        );
        
        return $this->reply(true);
    }

    public function update_v1($personId)
    {
        $personModel = new PersonModel();
        $person = $personModel->find($personId);
        $this->exists($person);

        helper("documents");

        $user = $this->request->user;
        $document = documents_load_document($person->people, $user);

        if (!$document) {
            return $this->reply("People list not found", 404, "ERR-PEOPLE-UPDATE");
        }

        $personData = $this->request->getJSON();
        $personData->people = $document->id;
        
        try {
            if ($personModel->update($person->id, $personData) === false) {
                return $this->reply($personModel->errors(), 500, "ERR-PEOPLE-UPDATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-PEOPLE-UPDATE");
        }
        
        $this->addActivity(
            $document->id,
            $document->parent, 
            $document->id, 
            $this::ACTION_UPDATE, 
            $this::SECTION_DOCUMENT
        );
        
        return $this->reply(true);
    }

    public function delete_v1($personId)
    {
        $personModel = new PersonModel();
        $person = $personModel->find($personId);

        if (!$person) {
            return $this->reply("Person not found", 404, "ERR-PEOPLE-DELETE");
        }

        helper("documents");

        $user = $this->request->user;
        $document = documents_load_document($person->people, $user);

        if (!$document) {
            return $this->reply("People list not found", 404, "ERR-PEOPLE-DELETE");
        }
        
        try {
            if ($personModel->delete([$person->id]) === false) {
                return $this->reply($personModel->errors(), 500, "ERR-PEOPLE-DELETE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-PEOPLE-DELETE");
        }
        
        $this->addActivity(
            $document->id,
            $document->parent, 
            $document->id, 
            $this::ACTION_DELETE, 
            $this::SECTION_DOCUMENT
        );
        
        return $this->reply(true);
    }
}