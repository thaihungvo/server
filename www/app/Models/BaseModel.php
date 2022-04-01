<?php namespace App\Models;

use CodeIgniter\Model;

class BaseModel extends Model
{
    protected $user = null;

    public function __construct($user = null, ConnectionInterface &$db = null, ValidationInterface $validation = null)
	{
		parent::__construct($db, $validation);
        $this->user = $user;
    }
}