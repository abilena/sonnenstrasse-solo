<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT'];

include_once $path . '/wp-config.php';
include_once $path . '/wp-load.php';
include_once $path . '/wp-includes/wp-db.php';
include_once $path . '/wp-includes/pluggable.php';

require_once('inc/sonnenstrasse-solo-database.php'); 

$username = $_POST['username'];
$password = $_POST['password'];
$remember = $_POST['remember'];

$output = aventurien_solo_db_login($username, $password);

if (substr($output, 0, 9) == "succeeded")
{
	$password_hash = substr($output, 10);
	setcookie("wp-sonnenstrasse-solo-username", $username, 0, "/");
	setcookie("wp-sonnenstrasse-solo-password", $password_hash, 0, "/");
	setcookie("wp-sonnenstrasse-solo-remember", $remember, 0, "/");
}

echo($output);

?>