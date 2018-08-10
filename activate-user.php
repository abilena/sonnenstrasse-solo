<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT'];

include_once $path . '/wp-config.php';
include_once $path . '/wp-load.php';
include_once $path . '/wp-includes/wp-db.php';
include_once $path . '/wp-includes/pluggable.php';

require_once('inc/sonnenstrasse-solo-database.php'); 

$username = $_GET['username'];
$token = $_GET['token'];

get_header();

echo('<div class="articles">');
echo('<div class="post-caption"><h1>Benutzer-Konto Aktivierung:</h1></div>');
echo('<div class="post-body">');
echo('<p>');

echo(aventurien_solo_db_activate($username, $token));

echo('</p>');
echo('</div>');
echo('</div>');

get_footer();

?>