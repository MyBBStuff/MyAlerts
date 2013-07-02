<?php

define('IN_MYBB', 1);
require_once '../../init.php';

//global $db;

// start over
$db->query("DELETE FROM " . TABLE_PREFIX . "alert_setting_values");

$alert_type_query = $db->simple_select('alert_settings');

$alert_type_count = 0;

if ($db->num_rows($alert_type_query) > 0) {
	$alert_type_set = array();
	
	while ($alert_id = $db->fetch_field($alert_type_query, 'id')) {
		$alert_type_set[] = $alert_id;
		++$alert_type_count;
	}
} else {
	echo "MyAlerts not installed! Exiting...";
	die;
}

$query = $db->simple_select('users', 'uid');

$user_count = 0;

if ($db->num_rows($query) > 0) {
	$settings = array();
	while ($user = $db->fetch_field($query, 'uid')) {
		++$user_count;
		$alert_type_user_set = array();
		
		foreach ($alert_type_set as $id) {
			$settings[] = array(
				"user_id" => $user,
				"setting_id" => $id,
				"value" => 1
			);
		}
	}
	
	$db->insert_query_multiple('alert_setting_values', $settings);
	
	echo "Done.<br /><br />Alert Types affected: {$alert_type_count}<br />Total Users affected: {$user_count}";
}
