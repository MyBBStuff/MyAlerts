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

	$read_days = 90; // 90 days for read alerts
	$unread_days = 120; // 120 days for unread alerts 
	// Read/unread alerts older than X seconds will be deleted to save some space in DB
	$read_time = $read_days*24*60*60; // Formula: days X hours X minutes X seconds
	$unread_time = $unread_days*24*60*60; //Formula: days X hours X minutes X seconds
	
	if (!isset($lang->myalerts)) {
		$lang->load('myalerts');
	}

	if ($db->delete_query('alerts', '(dateline <= \''.date('Y-m-d H:i:s', TIME_NOW-$unread_time).'\' AND unread = 1) OR (dateline <= \''.date('Y-m-d H:i:s', TIME_NOW-$read_time).'\' AND unread = 0)')) {
		add_task_log($task, $lang->sprintf($lang->myalerts_task_cleanup_ran, $read_days, $unread_days));
	} else {
		add_task_log($task, $lang->myalerts_task_cleanup_error);
	}
}
