<?php

use App\Models\PeopleModel;
use CodeIgniter\Model;

if (!function_exists('people_load'))
{
    function people_load($peopleID)
    {
        $peopleModel = new PeopleModel();

        $recordBuilder = $peopleModel->builder();
        $recordQuery = $recordBuilder->select("people.*")
            ->where("people.deleted", NULL)
            ->where("people.id", $peopleID)
            ->limit(1)
            ->get();

        $records = $recordQuery->getResult();

        if (!count($records)) {
            return null;
        }

        return $records[0];
    }
}

if (!function_exists('people_create'))
{
    function people_create($peopleData)
    {
        $peopleModel = new PeopleModel();

        try {
            if ($peopleModel->insert($peopleData) === false) {
                return $peopleModel->errors();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}

if (!function_exists('people_delete'))
{
    function people_delete($people)
    {
        $peopleModel = new PeopleModel();
        // delete selected notepad
        try {
            if ($peopleModel->delete([$people->id]) === false) {
                return $peopleModel->errors();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}