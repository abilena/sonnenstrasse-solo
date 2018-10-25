<?php

$pid = 1;
$vars = array();

function aventurien_solo_display($user, $hero_id, $module, $title, $last_pid, $passage, $debug)
{
    global $vars;
    $path_local = plugin_dir_path(__FILE__);
	
    $module_file = $path_local . "../modules/" . $module . "/module.twee";
    if (!file_exists($module_file))
    {
        $template = new Sonnenstrasse\Template($path_local . "../tpl/page.html");
        $template->set("Name", "Fehler");
        $template->set("Content", "Das Abenteuer <i>$title</i> konnte nicht gefunden werden.");
        $template->set("Menu", "");
        return $template->output();
    }
	
    $xml = aventurien_solo_load_twee($module_file);

	if (empty($user))
	{
        // load cover passage
		$passage = "Cover";
	}
	else if (empty($hero_id))
	{
		// load cover passage
		$passage = "Cover";
	}

    if ($passage != "")
    {
        // load requested passage and use it's pid
		$passage_xml_results = $xml->xpath("/tw/tw-storydata/tw-passagedata[@name=\"$passage\"]");
		if (empty($passage_xml_results) || (count($passage_xml_results) == 0))
		{
			$template = new Sonnenstrasse\Template($path_local . "../tpl/page.html");
			$template->set("Name", "Fehler");
			$template->set("Content", "Der Abschnitt <i>$passage</i> konnte nicht gefunden werden.");
			$template->set("Menu", "");
			return $template->output();			
		}

        $passage_xml = $passage_xml_results[0];
        $pid = (int)$passage_xml->attributes()['pid'];
    }
    else
    {
        // query the last seen pid and load that passage
        $pid = aventurien_solo_db_get_pid($module, $user);
        $passage_xml = $xml->xpath("/tw/tw-storydata/tw-passagedata[@pid=$pid]")[0];
    }

    if (($passage == "Start") || ($passage == "Cover"))
    {
        $vars = array();
    }
    else
    {
        $vars = aventurien_solo_db_get_vars($module, $user);
    }

    $passage_name = $passage_xml->attributes()['name'];
    $passage_text = $passage_xml[0];

    $passage_name = aventurien_solo_get_title($passage_text, $passage_name);
    $passage_text = aventurien_solo_do_replacements($passage_text, $module, $pid);
    $passage_text = aventurien_solo_command_text($passage_text);
	$passage_text = aventurien_solo_command_unset($passage_text, ((!$last_pid) || ($last_pid == $pid)));
    $passage_text = aventurien_solo_command_set($passage_text, ((!$last_pid) || ($last_pid == $pid)));
    $passage_text = aventurien_solo_command_if($passage_text);
    $passage_text = aventurien_solo_var_replace($passage_text, "");
	$passage_text = trim($passage_text, " \r\n\t");
    $passage_text = nl2br($passage_text);

	if (!empty($user))
	{
		aventurien_solo_db_set_pid($module, $user, $pid);
		aventurien_solo_db_set_vars($module, $user, $vars);
	}

    $debug_html = "";
    foreach ($vars as $var_name => $var_value)
    {
        $template_debug = new Sonnenstrasse\Template($path_local . "../tpl/debug.html");
        $template_debug->set("Name", $var_name);
        $template_debug->set("Value", $var_value);
        $debug_html .= $template_debug->output();
    }

    $template = new Sonnenstrasse\Template($path_local . "../tpl/page.html");
    $template->set("Module", $module);
    $template->set("Name", $passage_name);
    $template->set("Content", $passage_text);
    $template->set("Debug", $debug_html);
    $template->set("DebugDisplay", ($debug == 'true') ? "block" : "none");
    $output = $template->output();

    return $output;
}

function aventurien_solo_get_title($passage_text, $passage_name)
{
	if (preg_match('/<title>(.*?)<\/title>/', $passage_text, $matches))
	{
		return $matches[1];
	}
	
	return $passage_name;
}

function aventurien_solo_command_text($passage_text)
{
    $passage_text = preg_replace('/\(text\:\s*(.*?)\s*\)/', '"$1"', $passage_text);

    return $passage_text;
}

function aventurien_solo_get_var($variable)
{
    global $vars;

    if (array_key_exists($variable, $vars))
    {
        return $vars[$variable];
    }
    else
    {
        return NULL;
    }
}

function aventurien_solo_var_replace($expression, $quotes)
{
    preg_match_all('/\$(\w*)/', $expression, $matches);

    $count = count($matches[0]);
    for ($i = 0; $i < $count; $i++) 
    {
        $variable = $matches[1][$i];
        $value = aventurien_solo_get_var($variable);

        if (!is_numeric($value))
        {
            $value = $quotes . $value . $quotes;
        }

        $expression = str_replace("\$" . $variable, $value, $expression);
    }

    return $expression;
}

function aventurien_solo_command_unset($passage_text, $reload)
{
    if (!$reload)
    {
        preg_match_all('/\(unset\:\s*\$(\w*)\s*\)/', $passage_text, $matches);

        global $vars;
        $count = count($matches[0]);

        for ($i = 0; $i < $count; $i++) 
        {
            $variable = $matches[1][$i];
			unset($vars["$variable"]);
        }
    }

    // remove all set statements from the text
    $passage_text = preg_replace('/\(unset\:(.*?)\)/', '', $passage_text);

    return $passage_text;
}

function aventurien_solo_command_set($passage_text, $reload)
{
    if (!$reload)
    {
        preg_match_all('/\(set\:\s*\$(\w*)\s* to \s*(.*?)\s*\)/', $passage_text, $matches);

        global $vars;
        $count = count($matches[0]);

        for ($i = 0; $i < $count; $i++) 
        {
            $variable = $matches[1][$i];
            $expression = $matches[2][$i];
			
            // replace all known variables in expression by their values so they can be evaled
            $expression = "\$vars['$variable'] = " . aventurien_solo_var_replace($expression, "'") . ";";
            // evaluate the set statement
            eval($expression);
        }
    }

    // remove all set statements from the text
    $passage_text = preg_replace('/\(set\:(.*?)\)/', '', $passage_text);

    return $passage_text;
}

function aventurien_solo_command_if($passage_text)
{
    preg_match_all('/\(if\:\s*(.*?)\s*\)(.*?)\(endif\:\)/', $passage_text, $matches);

    global $vars;

    $count = count($matches[0]);
    for ($i = 0; $i < $count; $i++) 
    {
        $text = $matches[0][$i];
        $condition = $matches[1][$i];
        $content = $matches[2][$i];
        $if_text = $content;
        $else_text = "";
        $result = "";

        if (preg_match('/(.*?)\(else\:\)(.*)/', $content, $content_matches))
        {
            $if_text = $content_matches[1];
            $else_text = $content_matches[2];
        }

        // replace all known variables in expression by their values so they can be evaled
        $condition = "\$result = " . aventurien_solo_var_replace($condition, "'") . ";";
        // evaluate the set statement
        eval($condition);

        if ($result)
        {
            $passage_text = str_replace($text, $if_text, $passage_text);
        }
        else
        {
            $passage_text = str_replace($text, $else_text, $passage_text);
        }
    }

    return $passage_text;
}

function aventurien_solo_do_replacements($passage_text, $module, $pid)
{
	$baseUri = plugins_url() . "/sonnenstrasse-solo";
	$passage_text = preg_replace('/\[\@ModuleUrl\]/', plugins_url() . '/sonnenstrasse-solo/modules/' . $module, $passage_text);
	$passage_text = preg_replace('/<title>(.*?)<\/title>/', '', $passage_text);
    $passage_text = preg_replace('/\'\'(.*?)\'\'/', '<strong>$1</strong>', $passage_text);
    $passage_text = preg_replace('/\/\/(.*?)\/\//', '<i>$1</i>', $passage_text);
    $passage_text = preg_replace('/\^\^(.*?)\^\^/', '<sup>$1</sup>', $passage_text);
    $passage_text = preg_replace('/\~\~(.*?)\~\~/', '<del>$1</del>', $passage_text);
    $passage_text = preg_replace('/\(\[\[([^\]]*?)\-\>(.*?)\]\]\)/', '<span class="sonnenstrasse-solo-link">(<a href="#" onclick="javascript:return select(\'' . $baseUri . '\', \'' . $module . '\', ' . $pid . ', \'$2\')">$1</a>)</span>', $passage_text);
    $passage_text = preg_replace('/\(\[\[([^\]]*?)\<\-(.*?)\]\]\)/', '<span class="sonnenstrasse-solo-link">(<a href="#" onclick="javascript:return select(\'' . $baseUri . '\', \'' . $module . '\', ' . $pid . ', \'$1\')">$2</a>)</span>', $passage_text);
    $passage_text = preg_replace('/\(\[\[(.*?)\]\]\)/', '<span class="sonnenstrasse-solo-link">(<a href="#" onclick="javascript:return select(\'' . $baseUri . '\', \'' . $module . '\', ' . $pid . ', \'$1\')">$1</a>)</span>', $passage_text);
    $passage_text = preg_replace('/\(\[\[(.*?)\]\]\)/', '<span class="sonnenstrasse-solo-link">(<a href="#" onclick="javascript:return select(\'' . $baseUri . '\', \'' . $module . '\', ' . $pid . ', \'$1\')">$1</a>)</span>', $passage_text);
    $passage_text = preg_replace('/\[\[([^\]]*?)\-\>(.*?)\]\]/', '<span class="sonnenstrasse-solo-link"><a href="#" onclick="javascript:return select(\'' . $baseUri . '\', \'' . $module . '\', ' . $pid . ', \'$2\')">$1</a></span>', $passage_text);
    $passage_text = preg_replace('/\[\[([^\]]*?)\<\-(.*?)\]\]/', '<span class="sonnenstrasse-solo-link"><a href="#" onclick="javascript:return select(\'' . $baseUri . '\', \'' . $module . '\', ' . $pid . ', \'$1\')">$2</a></span>', $passage_text);
    $passage_text = preg_replace('/\[\[(.*?)\]\]/', '<span class="sonnenstrasse-solo-link"><a href="#" onclick="javascript:return select(\'' . $baseUri . '\', \'' . $module . '\', ' . $pid . ', \'$1\')">$1</a></span>', $passage_text);
    $passage_text = preg_replace('/\[\[(.*?)\]\]/', '<span class="sonnenstrasse-solo-link"><a href="#" onclick="javascript:return select(\'' . $baseUri . '\', \'' . $module . '\', ' . $pid . ', \'$1\')">$1</a></span>', $passage_text);

    return $passage_text;
}

function aventurien_solo_load_twee($file)
{
	global $count;
	$count = 0;

    $twee = file_get_contents($file);
	$twee = htmlspecialchars(html_entity_decode($twee));
	$passages = preg_replace_callback('/\:\:(\S*)/', 'aventurien_solo_match_passage', $twee);
	$passages .= "</tw-passagedata>\r\n";

    $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\r\n";
    $xml .= '<tw><tw-storydata>' . "\r\n";
    $xml .= $passages;
    $xml .= "</tw-storydata></tw>";

	return simplexml_load_string($xml);
}

function aventurien_solo_match_passage($matches) {
	global $count;
	$count++;
	if ($count == 1)
		return '<tw-passagedata name="' . $matches[1] . '" pid="' . $count . '">';
	else
		return '</tw-passagedata>' . "\r\n" . '<tw-passagedata name="' . $matches[1] . '" pid="' . $count . '">';
}

?>