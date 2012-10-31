<?php
/**
 *	MyAlerts alert cleanup task
 *
 *	A simple notification/alert system for MyBB
 *
 *	@author Euan T. <euan@euantor.com>
 *	@version 1.00
 *	@package MyAlerts
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

function task_myalerts($task)
{
    global $mybb, $db, $lang;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    if ($db->delete_query('alerts', 'unread = 0')) {
        add_task_log($task, $lang->myalerts_task_cleanup_ran);
    } else {
        add_task_log($task, $lang->myalerts_task_cleanup_error);
    }
}
