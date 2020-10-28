<?php 
$servername = "stacksdb";
$username   = "stacks";
$password   = "stacks";
$db         = "stacks";
$port       =  3306;

// Create connection
$db = new mysqli($servername, $username, $password, $db, $port);

// Check connection
if ($db->connect_error)
{
    exit("Connection failed: " . $db->connect_error);
} 

echo "Connected successfully"; 

?>