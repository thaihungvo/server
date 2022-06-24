<?php

use App\Models\UserModel;

if (!function_exists("people_expand")) {
    function people_expand(&$document) {

        // load people
        $userModel = new UserModel();
        $people = $userModel->where("people", $document->id)->findAll();
        $document->people = [];

        foreach ($people as &$person) {
            unset($person->password);
            $document->people[] = $person;
        }
    }
}

if (!function_exists("people_clean_up")) {
    function people_clean_up($document) {

        // delete all people for this document
        $userModel = new UserModel();
        try {
            if ($userModel->where("people", $document->id)->delete() === false) return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}

?>