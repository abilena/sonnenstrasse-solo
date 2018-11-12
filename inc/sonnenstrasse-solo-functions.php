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

function aventurien_solo_display_overview()
{
	$path_local = plugin_dir_path(__FILE__);
	$path_url = plugins_url() . "/sonnenstrasse-solo";
	
	$overview_html = "";
	$modules = aventurien_solo_db_get_all_modules();
	foreach ($modules as $module)
	{
		$module_players_html = "";
		foreach ($module->states as $module_player)
		{
			$vars = json_decode($module_player->vars);
			$module_player->ap = (empty(@$vars->AP) ? "" : $vars->AP . " AP");
			$module_player->status = (($module_player->pid <= 1) ? "" : (($module_player->ap == 0) ? "Spielt Abenteuer" : "Abgeschlossen"));
			$template_module_player = new Sonnenstrasse\Template($path_local . "../tpl/overview-module-player.html");
			$template_module_player->setObject($module_player);
			$template_module_player->set("User", ucwords(str_replace("Anonymous-", "", $module_player->user)));
			$module_players_html .= $template_module_player->output();
		}
		
	    $module_file = $path_local . "../modules/" . $module->module . "/module.twee";
		$twee = (file_exists($module_file) ? file_get_contents($module_file) : "");
		if (preg_match('/^::Cover\r?\n<title\>([^\<]*)\<\/title\>/mi', $twee, $matches))
		{
			$module->name = $matches[1];
		}
		else
		{
			$module->name = $module->module;
		}

		$template_module = new Sonnenstrasse\Template($path_local . "../tpl/overview-module.html");
		$template_module->set("Module", $module->name);
		$template_module->set("Players", $module_players_html);
		$overview_html .= $template_module->output();
	}
	
	$template = new Sonnenstrasse\Template($path_local . "../tpl/overview.html");
	$template->set("Overview", $overview_html);
	return $template->output();
}

?>