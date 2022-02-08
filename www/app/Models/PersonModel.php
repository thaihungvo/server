<?php namespace App\Models;

use CodeIgniter\Model;

class PersonModel extends Model
{
    protected $table      = 'people';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = true;

    protected $allowedFields = ["id", "people", "firstName", "lastName", "email", "gender", "nickname", "birthday", "age", "jobTitle", "company", "officePhone", "cellPhone", "homePhone", "fax", "address", "county", "zip", "city", "country", "address2", "website", "notes", "socialTwitter", "socialFacebook", "socialLinkedin", "socialInstagram", "socialOther", "type", "avatar"];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = 'updated';
    protected $deletedField  = 'deleted';
}

