<?php namespace App\Controllers;

class PingController extends BaseController
{
	public function index()
	{
        return $this->reply("pong", 200);
	}
}
