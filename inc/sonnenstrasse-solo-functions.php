<?php

function aventurien_solo_display_login($login_succeeded, $user, $module, $character_panel_html)
{
	$path_local = plugin_dir_path(__FILE__);
	$path_url = plugins_url() . "/sonnenstrasse-solo";

	$template = new Sonnenstrasse\Template($path_local . "../tpl/login.html");
	$template->set("PluginBaseUri", $path_url);
	$template->set("DisplayLogin", (($login_succeeded) ? "logout" : "login"));
	$template->set("DisplayWide", (($login_succeeded) ? "-wide" : ""));
	$template->set("Username", @$user->display_name);
	$template->set("Module", $module);
	$template->set("CharacterPanel", $character_panel_html);
	
	return $template->output();
}

function aventurien_solo_user()
{
	$cookie_username = @$_COOKIE["wp-sonnenstrasse-solo-username"];
	$user = new stdClass();
	
	if (is_user_logged_in())
	{
		$user->name = wp_get_current_user()->user_login;
		$user->display_name = wp_get_current_user()->user_login;
		$user->email = wp_get_current_user()->user_email;
	}
	else if (isset($cookie_username))
	{
		$userobj = aventurien_solo_db_get_user($cookie_username);
		$user->name = 'Anonymous-' . $userobj->username;
		$user->display_name = $userobj->username;
		$user->email = $userobj->email;
	}
	
	return $user;
}

?>