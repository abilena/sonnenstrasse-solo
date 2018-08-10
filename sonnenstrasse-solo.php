<?php
/*
Plugin Name: Sonnenstrasse Solo Adventures
Plugin URI: https://wordpress.org/plugins/sonnenstrasse-solo/
Description: This plugin allows you to display twine text adventures (imported in twee format) in your posts using shortcodes.
Version: 1.00
Author: Klemens
Author URI: https://profiles.wordpress.org/Klemens#content-plugins
Text Domain: sonnenstrasse-solo
*/ 

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 'aventurien-solo' Installtion
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once('inc/sonnenstrasse-solo-database.php'); 
require_once('inc/sonnenstrasse-solo-functions.php');
require_once('inc/sonnenstrasse-solo-twee.php');
include_once(ABSPATH . '/wp-content/plugins/sonnenstrasse-character/inc/rp-character-functions.php'); 

register_activation_hook(__FILE__, 'aventurien_solo_activate');

function aventurien_solo_activate() {
	
	if( !class_exists( 'Sonnenstrasse\Template' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'Please install and activate Sonnenstrasse Base Shortcodes .', 'sonnenstrasse-solo' ), 'Plugin dependency check', array( 'back_link' => true ) );
    }
	if( !class_exists( 'Sonnenstrasse\Character' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'Please install and activate Sonnenstrasse Character .', 'sonnenstrasse-solo' ), 'Plugin dependency check', array( 'back_link' => true ) );
    }
		
	//sets up activation hook
	register_activation_hook(__FILE__, 'aventurien_solo_activate');

    aventurien_solo_create_tables();
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////

add_action('init', 'aventurien_solo_css_and_js');

function aventurien_solo_css_and_js() {
    wp_register_style('aventurien_solo_css', plugins_url('inc/css/index.css', __FILE__));
    wp_enqueue_style('aventurien_solo_css');
    wp_register_style('aventurien_solo_menu_css', plugins_url('inc/css/menu.css', __FILE__));
    wp_enqueue_style('aventurien_solo_menu_css');
    wp_register_script('aventurien_solo_js', plugins_url('inc/js/solo.js', __FILE__));
    wp_enqueue_script('aventurien_solo_js');
    wp_enqueue_style('dashicons');
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 'aventurien-solo' Shortcode
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

add_shortcode ('aventurien-solo', 'aventurien_solo_shortcode');

function aventurien_solo_shortcode($atts, $content) {

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

	if (!isset($content) || !$content) {
		$content = get_the_title();
	}

	extract(shortcode_atts(array(
		'module' => 'sample',
        'style' => 'default'
	), $atts));

	return aventurien_solo_html($module, $content);
}

function aventurien_solo_html($module, $title)
{
    $path_local = plugin_dir_path(__FILE__);
    $path_url = plugins_url() . "/sonnenstrasse-solo";
	
	$login_succeeded = false;
	if (is_user_logged_in())
	{
		$login_succeeded = true;
	}
	else
	{
		$cookie_username = @$_COOKIE['wp-sonnenstrasse-solo-username'];
		$cookie_password = @$_COOKIE['wp-sonnenstrasse-solo-password'];
		if (substr(aventurien_solo_db_login($cookie_username, $cookie_password), 0, 9) == "succeeded")
		{
			$login_succeeded = true;
		}
	}

	$solo_user = aventurien_solo_user();
	$last_pid = null;
	$passage = null;
	$debug = null;
	$last_module = null;
	if (array_key_exists('module', @$_POST)) {
		$last_module = @$_POST['module'];
	}
	if ($module == $last_module) {
		$last_pid = @$_POST['pid'];
		$passage = @$_POST['passage'];
		$debug = @$_POST['debug'];
	}

	$hero_id = aventurien_solo_db_get_hero($module, @$solo_user->name);
	$current_pid = aventurien_solo_db_get_pid($module, @$solo_user->name);
	$character_panel_html = rp_character_hero_html_by_id($hero_id, @$solo_user->name, "compact", $module, $current_pid <= 1);

	$output = "";
	$output .= aventurien_solo_display_login($login_succeeded, $solo_user, $module, $character_panel_html);
	$output .= '<div id="aventurien-solo-module-' . $module . '" class="aventurien-container-block-wide">';
	$output .= aventurien_solo_display(@$solo_user->name, $hero_id, $module, $title, $last_pid, $passage, $debug);
	$output .= '</div>';

	return $output;
}


?>