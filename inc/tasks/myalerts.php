<?php
/**
 *    MyAlerts alert cleanup task
 *
 *    A simple notification/alert system for MyBB
 *
 * @author  Euan T. <euan@euantor.com>
 * @package MyAlerts
 */

if (!defined('IN_MYBB')) {
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

function task_myalerts($task)
{
	global $db, $lang;
	
	// Read/unread alerts older than X seconds will be deleted to save some space in DB
	$read_time = 90*24*60*60; // 90 days for read alerts (formula: days X hours X minutes X seconds)
	$unread_time = 120*24*60*60; // 120 days for unread alerts (formula: days X hours X minutes X seconds)
	
	if (!isset($lang->myalerts)) {
		$lang->load('myalerts');
	}

	if ($db->delete_query('alerts', '(dateline <= \''.date('Y-m-d H:i:s', TIME_NOW-$unread_time).'\' AND unread = 1) OR (dateline <= \''.date('Y-m-d H:i:s', TIME_NOW-$read_time).'\' AND unread = 0)')) {
		add_task_log($task, $lang->myalerts_task_cleanup_ran);
	} else {
		add_task_log($task, $lang->myalerts_task_cleanup_error);
	}
}
