<?php

use App\Models\PersonModel;

if (!function_exists("people_expand")) {
    function people_expand(&$document) {

        // load people
        $personModel = new PersonModel();
        $document->people = $personModel->where("people", $document->id)->findAll();
    }
}

if (!function_exists("people_clean_up")) {
    function people_clean_up($document) {

        // delete all people for this document
        $personModel = new PersonModel();
        $personModel->where("people", $document->id)->delete();
    }
}

?>