<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$path = __DIR__ . "/../../..";
$path_local = __DIR__;

include_once $path . '/wp-config.php';
include_once $path . '/wp-load.php';
include_once $path . '/wp-includes/wp-db.php';
include_once $path . '/wp-includes/pluggable.php';

require_once('inc/sonnenstrasse-solo-database.php'); 
require_once('../sonnenstrasse-character/inc/rp-character-database.php');

$solo_user = aventurien_solo_user();

$module = $_POST['module'];
$archetype_id = $_POST['archetype'];

$hero_id = rp_character_create_hero_from_archetype($archetype_id, $solo_user->name, -1);

if (empty($hero_id))
{
	echo("failed. archetype $archetype_id unknown.");
}
else
{
	aventurien_solo_db_set_hero($module, $solo_user->name, $hero_id);
	echo("succeeded");
}

?>