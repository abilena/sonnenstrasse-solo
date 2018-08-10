<?php

require_once('../../../wp-load.php');
require_once('inc/sonnenstrasse-solo-database.php'); 
require_once('inc/sonnenstrasse-solo-functions.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$solo_user = aventurien_solo_user();
$module = @$_GET['module'];
$title = @$_GET['title'];
$last_pid = @$_POST['pid'];
$passage = @$_POST['passage'];
$debug = @$_POST['debug'];

$hero_id = aventurien_solo_db_get_hero($module, @$solo_user->name);

echo(aventurien_solo_display(@$solo_user->name, $hero_id, $module, $title, $last_pid, $passage, $debug));

?>