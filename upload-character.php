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
require_once('../sonnenstrasse-character/inc/rp-character-xml-import.php');
require_once('../sonnenstrasse-character/inc/rp-character-database.php');

$solo_user = aventurien_solo_user();

$module = $_POST['module'];
$uploadedXml = $_FILES['xmlFile'];
$uploadedImage = $_FILES['imgFile'];

$storedImageName = $solo_user->name . '.' . pathinfo($uploadedXml['name'], PATHINFO_FILENAME) . '.' . pathinfo($uploadedImage['name'], PATHINFO_EXTENSION);
$storedImagePath = $path_local . "/../sonnenstrasse-character/portraits/" . $storedImageName;

move_uploaded_file($uploadedImage['tmp_name'], $storedImagePath);
$uploaded_content = file_get_contents($_FILES['xmlFile']["tmp_name"]);

echo(rp_character_upload_hero($uploaded_content, $solo_user, $storedImageName, $module));

?>