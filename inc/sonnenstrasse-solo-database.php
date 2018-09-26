<?php

function aventurien_solo_create_tables()
{
   	global $wpdb;

    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_states';
	// create the ECPT metabox database table
	if($wpdb->get_var("show tables like '$db_table_name'") != $db_table_name) 
	{
		$sql = "CREATE TABLE " . $db_table_name . " (
		`id` mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `module` tinytext NOT NULL,
        `user` tinytext NOT NULL,
		`hero_id` mediumint(9),
        `pid` smallint NOT NULL,
        `vars` text NOT NULL,
		UNIQUE KEY id (id)
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
	$db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_users';
	// create the ECPT metabox database table
	if($wpdb->get_var("show tables like '$db_table_name'") != $db_table_name) 
	{
		$sql = "CREATE TABLE " . $db_table_name . " (
		`username` VARCHAR(255) NOT NULL PRIMARY KEY,
        `password` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `activation_token` tinytext,
		UNIQUE KEY username (username)
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}

function aventurien_solo_db_get_id($module, $user)
{
   	global $wpdb;
    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_states';

    $id = $wpdb->get_var("SELECT id FROM $db_table_name WHERE module='$module' AND user='$user'");
    
    return $id;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// States
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aventurien_solo_db_get_pid($module, $user)
{
   	global $wpdb;
    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_states';
    
    $pid = $wpdb->get_var("SELECT pid FROM $db_table_name WHERE module='$module' AND user='$user'");

    if (is_null($pid))
        return 1;

    return $pid;
}

function aventurien_solo_db_set_pid($module, $user, $pid)
{
   	global $wpdb;
    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_states';

    $wpdb->query('START TRANSACTION');

    $id = aventurien_solo_db_get_id($module, $user);

    if (!isset($id) || !$id)
    {
        $wpdb->insert($db_table_name, array('module' => $module, 'user' => $user, 'pid' => $pid));
    }
    else
    {
        $wpdb->update($db_table_name, array('pid' => $pid), array('id' => $id));
    }

    $wpdb->query('COMMIT');
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Variables
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aventurien_solo_db_get_vars($module, $user)
{
   	global $wpdb;
    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_states';

    $json = $wpdb->get_var("SELECT vars FROM $db_table_name WHERE module='$module' AND user='$user'");
    
    return is_null($json) ? array() : json_decode($json, true);
}

function aventurien_solo_db_set_vars($module, $user, $vars)
{
   	global $wpdb;
    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_states';

    $wpdb->query('START TRANSACTION');

    $id = aventurien_solo_db_get_id($module, $user);
    $json = json_encode($vars);
    
    if ($id)
    {
        $wpdb->update($db_table_name, array('vars' => $json), array('id' => $id));
    }

    $wpdb->query('COMMIT');

}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Hero
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aventurien_solo_db_get_hero($module, $user)
{
   	global $wpdb;
    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_states';
    $hero_id = $wpdb->get_var("SELECT hero_id FROM $db_table_name WHERE module='$module' AND user='$user'");

    if (!is_null($hero_id))
	{
		// make sure the character exists
		
		$db_table_name = $wpdb->prefix . 'sonnenstrasse_heroes';
		$hero_id = $wpdb->get_var("SELECT hero_id FROM $db_table_name WHERE hero_id='$hero_id'");
	}
	
	if (is_null($hero_id))
	{
		// try to switch to an existing character, if any
		
		$db_table_name = $wpdb->prefix . 'sonnenstrasse_heroes';
		$results = $wpdb->get_results("SELECT hero_id FROM $db_table_name WHERE creator='$user' LIMIT 1");
		if (count($results) < 1)
		{
			return 0;
		}
		
		$hero_id = $results[0]->hero_id;
		aventurien_solo_db_set_hero($module, $user, $hero_id);
	}

    return $hero_id;
}

function aventurien_solo_db_set_hero($module, $user, $hero_id)
{
	global $wpdb;
    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_states';

    $wpdb->query('START TRANSACTION');

    $id = aventurien_solo_db_get_id($module, $user);

	if (!isset($id) || !$id)
    {
        $wpdb->insert($db_table_name, array('module' => $module, 'user' => $user, 'pid' => 1, 'hero_id' => $hero_id));
    }
    else
    {
        $wpdb->update($db_table_name, array('hero_id' => $hero_id), array('id' => $id));
    }

    $wpdb->query('COMMIT');
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Login
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function aventurien_solo_db_get_user($username)
{
   	global $wpdb;
    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_users';
    
    $results = $wpdb->get_results("SELECT * FROM $db_table_name WHERE username='$username'");
	
	if (count($results) == 1)
	{
		return $results[0];
	}
	
	return null;
}

function aventurien_solo_db_login($username, $password)
{
	$user = aventurien_solo_db_get_user($username);
	if (empty($user)) {
		return "Du hast einen falschen Benutzer-Namen angegeben. Bitte prüfe deinen Benutzer-Namen und versuche es erneut. Wenn du weiterhin auf Probleme stößt, wende dich bitte an die Administration.";
	}
	
	if (!empty($user->activation_token)) {
		return "Du musst deinen Benutzer-Konto zuerst aktivieren bevor du dich anmelden kannst. Du solltest eine E-Mail zur Aktivierung erhalten haben. Bitte prüfe deine E-Mails.";
	}
	
	$succeeded = ($password == $user->password) || password_verify($password, $user->password);
	
	if (!$succeeded) {
		return "Du hast einen falschen Benutzer-Namen angegeben. Bitte prüfe deinen Benutzer-Namen und versuche es erneut. Wenn du weiterhin auf Probleme stößt, wende dich bitte an die Administration.";
	}
	
	return "succeeded;" . $user->password;
}

function aventurien_solo_db_register($username, $password, $email)
{
	global $wpdb;
    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_users';
	
	if (empty($username) || (strlen($username) < 3)) {
		return "Der Benutzer-Name muss mindestens 3 Zeichen lang sein.";
	}
	if (strlen($password) < 7) {
		return "Das Passwort muss mindestens 7 Zeichen lang sein.";
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return "Die angegebene E-Mail Adresse ist nicht gültig.";
	}
	
	$results = $wpdb->get_results("SELECT * FROM $db_table_name WHERE username='$username'");
	if (count($results) > 0)
		return "Dieser Benutzer-Name ist bereits vergeben. Bitte suche dir einen anderen Benutzer-Namen aus und versuche es erneut.";

	$results = $wpdb->get_results("SELECT * FROM $db_table_name WHERE email='$email'");
	if (count($results) > 0)
		return "Diese E-Mail Adresse ist bereits in Verwendung. Bitte gib eine andere E-Mail Adresse an und versuche es erneut.";
	
	$token = sha1(mt_rand(10000,99999).time().$email);
	
	$subject = "Willkommen in der Solo-Abenteuer Rubrik der DSA Gruppe Sonnenstraße";
	$message .= "Hallo $username,\n";
	$message .= "\n";
	$message .= "vielen Dank für deine Registrierung und willkommen in der Solo-Abenteuer Rubrik der DSA Gruppe Sonnenstraße. Bitte aktiviere dein Benutzer-Konto vor der ersten Anmeldung.\n";
	$message .= "\n";
	$message .= "Besuche folgende Seite um dein Benutzer-Konto zu aktivieren:\n";
	$message .= "\n";
	$message .= plugins_url() . "/sonnenstrasse-solo/activate-user.php?username=$username&token=$token\n";
	$message .= "\n";
	$message .= "Du hast dich mit folgenden Daten registriert\n";
	$message .= "E-Mail: $email\n";
	$message .= "Benutzername: $username\n";
	$message .= "\n";
	$message .= "Solltest du Probleme haben, kannst du dich gerne an uns wenden:\n";
	$message .= "\n";
	$message .= "E-Mail: dsa.sonnenstrasse@gmail.com\n";
	$message .= "\n";
	$message .= "\n";
	$message .= "Bis bald,\n";
	$message .= "DSA Gruppe Sonnenstraße\n";

	// use wordwrap() if lines are longer than 70 characters
	$message = wordwrap($message, 70);

	// send email
	mail($email, $subject, $message);

    $wpdb->query('START TRANSACTION');
	$wpdb->insert($db_table_name, array('username' => $username, 'password' => password_hash($password, PASSWORD_DEFAULT), 'email' => $email, 'activation_token' => $token));
	$wpdb->query('COMMIT');

	return "succeeded";
}

function aventurien_solo_db_activate($username, $token)
{
	$user = aventurien_solo_db_get_user($username);

	if (empty($user)) {
		return "Du hast einen falschen Benutzer-Namen angegeben. Bitte prüfe deinen Benutzer-Namen und versuche es erneut. Wenn du weiterhin auf Probleme stößt, wende dich bitte an die Administration.";
	}

	if (empty($user->activation_token)) {
		return "Dieses Benutzer-Konto ist bereits aktiviert.";
	}
	
	if ($user->activation_token !== $token) {
		return "Dieses Registrierungs-Token ist bereits abgelaufen.";
	}
	
	global $wpdb;
    $db_table_name = $wpdb->prefix . 'sonnenstrasse_solo_users';
    $wpdb->query('START TRANSACTION');
	$wpdb->update($db_table_name, array('activation_token' => null), array('username' => $username, 'activation_token' => $token));
	$wpdb->query('COMMIT');
	return "succeeded";
}

?>