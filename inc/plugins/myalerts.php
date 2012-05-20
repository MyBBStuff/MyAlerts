<?php
/**
 *	MyAlerts Core Plugin File
 *
 *	A simple notification/alert system for MyBB
 *
 *	@author Euan T. <euan@euantor.com>
 *	@version 0.01
 *	@package MyAlerts
 */

if (!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

define('MYALERTS_PLUGIN_PATH', MYBB_ROOT.'inc/plugins/MyAlerts/');

if(!defined("PLUGINLIBRARY"))
{
    define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}

function myalerts_info()
{
    return array(
        'name'          =>  'MyAlerts',
        'description'   =>  'A simple notifications/alerts system for MyBB',
        'website'       =>  'http://euantor.com',
        'author'        =>  'euantor',
        'authorsite'    =>  '',
        'version'       =>  '0.01',
        'guid'          =>  '',
        'compatibility' =>  '16*',
        );
}

function myalerts_install()
{
    global $db, $cache;

    $plugin_info = myalerts_info();
    $euantor_plugins = $cache->read('euantor_plugins');
    $euantor_plugins[] = array(
        'title'     =>  'MyAlerts',
        'version'   =>  $plugin_info['version'],
        );
    $cache->update('euantor_plugins', $euantor_plugins);

    if (!$db->table_exists('alerts'))
    {
        $db->write_query('CREATE TABLE `'.TABLE_PREFIX.'alerts` (
                `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `uid` INT(10) NOT NULL,
                `unread` TINYINT(4) NOT NULL DEFAULT \'1\',
                `content` TEXT NOT NULL
            ) ENGINE=MyISAM '.$db->build_create_table_collation().';');
    }
}

function myalerts_is_installed()
{
    global $db;
    return $db->table_exists('alerts');
}

function myalerts_uninstall()
{
    global $db;

    if ($db->table_exists('alerts'))
    {
        $db->write_query('DROP TABLE '.TABLE_PREFIX.'alerts');
    }
}

function myalerts_activate()
{
    if(!file_exists(PLUGINLIBRARY))
    {
        flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
        admin_redirect("index.php?module=config-plugins");
    }

    global $PL;
    $PL or require_once PLUGINLIBRARY;

    $PL->settings('myalerts',
    	'MyAlerts Settings',
    	'Settings for the MyAlerts plugin',
    	array(
    		'enabled'	=>	array(
    			'title'			=>	'Enable MyAlerts?',
    			'description'	=>	'This switch can be used to globally disable all MyAlerts features',
    			'value'			=>	'1',
    			),
    		)
    	);
}

function myalerts_deactivate()
{
	if(!file_exists(PLUGINLIBRARY))
    {
        flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
        admin_redirect("index.php?module=config-plugins");
    }

    global $PL;
    $PL or require_once PLUGINLIBRARY;

    $PL->settings_delete('myalerts');
}

$plugins->add_hook('global_start', 'myalerts_global');
function myalerts_global()
{
	global $Alerts, $db, $mybb;

	require_once MYALERTS_PLUGIN_PATH.'Alerts.class.php';
	$Alerts = new Alerts($mybb, $db);
}

$plugins->add_hook('xmlhttp', 'myalerts_xmlhttp');
function myalerts_xmlhttp()
{
	global $mybb, $db, $Alerts;

	if ($mybb->input['action'] == 'getAlerts')
	{
		$newAlerts = $Alerts->getAlerts();
		header('Content-Type: text/javascript');
		echo json_encode($newAlerts);
	}

	if ($mybb->input['action'] == 'getNewAlerts')
	{
		$newAlerts = $Alerts->getUnreadAlerts();
		header('Content-Type: text/javascript');
		echo json_encode($newAlerts);
	}
}