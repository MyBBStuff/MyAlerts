<?php
/**
 *	MyAlerts alert cleanup task
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

function task_myalerts($task)
{
	global $mybb, $db, $lang;

	if (!$lang->myalerts)
	{
		$lang->load('myalerts');
	}

	require_once MYBB_ROOT.'inc/plugins/MyAlerts/Alerts.class.php';
	$Alerts = new Alerts($mybb, $db);
	else {
		add_task_log($task, $lang->myxbl_task_xml_not_loaded);
	}
}
?>
