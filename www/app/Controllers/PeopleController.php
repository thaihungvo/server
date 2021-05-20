<?php namespace App\Controllers;



use App\Models\PeopleModel;

class PeopleController extends BaseController
{
    public function one_v1($peopleID)
    {
        helper("people");
        $people = people_load($peopleID);

        if (!$people) {
            return $this->reply(null, 404, "ERR-PEOPLE-NOT-FOUND");
        }

        return $this->reply($people);
    }

    public function add_v1()
    {
        $peopleData = $this->request->getJSON();

        if (!isset($peopleData->id)) {
            helper('uuid');
            $peopleData->id = uuid();
        }

        helper("people");

        $result = people_create($peopleData);

        if ($result !== true) {
            return $this->reply($result, 500, "ERR-PEOPLE-CREATE");
        }

        //$this->addActivity($", $peopleData->id, $this::ACTION_CREATE, $peopleData->type);

        return $this->reply(people_load($peopleData->id));
    }

    public function update_v1($peopleID)
    {
        $this->lock($peopleID);

        helper("people");
        $people = people_load($peopleID);

        if (!$people) {
            return $this->reply(null, 404, "ERR-PEOPLE-NOT-FOUND");
        }

        $peopleData = $this->request->getJSON();
        unset($peopleData->id);

        $result = people_update($peopleData);

        if ($result !== true) {
            return $this->reply($result, 500, "ERR-PEOPLE-UPDATE");
        }

        return $this->reply(true);
    }

    public function delete_v1($peopleID)
    {
        $this->lock($peopleID);

        helper("people");
        $people = people_load($peopleID);

        if (!$people) {
            return $this->reply(null, 404, "ERR-PEOPLE-DELETE");
        }

        $result = people_delete($people);

        if ($result !== true) {
            return $this->reply($result, 500, "ERR-PEOPLE-DELETE");
        }

        //$this->addActivity("", $people->id, $this::ACTION_DELETE, $this::SECTION_PEOPLE);

        return $this->reply(true);
    }
}