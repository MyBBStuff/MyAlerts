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

	if ($mybb->settings['myalerts_cleanup_time'] != 0)
	{
		$dateline = time() - (int) $mybb->settings['myalerts_cleanup_time'];

		if ($db->delete_query('alerts', 'unread = 0 AND dateline <= '.(int) $dateline))
		{
			add_task_log($task, $lang->myalerts_task_cleanup_ran);
		}
		else
		{
			add_task_log($task, $lang->myalerts_task_cleanup_error);
		}
	}
}
?>
