<?php namespace App\Entities;

use CodeIgniter\Entity;

class User extends Entity
{
    public $id;
    public $username;
    public $email;
    public $password;


    // public function __get(string $key)
    // {
    //     if (property_exists($this, $key))
    //     {
    //         return $this->$key;
    //     }
    // }
}