<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$path = __DIR__ . "/../../..";

include_once $path . '/wp-config.php';
include_once $path . '/wp-load.php';
include_once $path . '/wp-includes/wp-db.php';
include_once $path . '/wp-includes/pluggable.php';

require_once('inc/sonnenstrasse-solo-database.php'); 

$username = $_POST['username'];
$password = $_POST['password'];
$email = $_POST['email'];

echo(aventurien_solo_db_register($username, $password, $email));

?>