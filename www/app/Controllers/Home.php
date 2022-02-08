<?php namespace App\Controllers;

class Home extends BaseController
{
	public function index()
	{
        $date = date('Y-m-d H:i:s', strtotime('-3 seconds'));

        return $this->reply($date);
	}
}
