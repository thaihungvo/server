<?php
use Hidehalo\Nanoid\Client;
use Hidehalo\Nanoid\GeneratorInterface;

if (!function_exists('uuid'))
{
    function uuid() 
	{
        $client = new Client();
        return $client->generateId($size = 20);
    }
}