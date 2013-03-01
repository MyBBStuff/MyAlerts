<?php

function myalerts_upgrader_run($currentVersion = '1.04', $oldVersion = '1.00')
{

	$currentVersion = (double) $currentVersion;
	$oldVersion     = (double) $oldVersion;

	if ($currentVersion == 1.04 AND $oldVersion < 1.04) {
		global $db;

		if (!$db->table_exists('alert_settings')) {
	        $collation = $db->build_create_table_collation();
	        $db->write_query("CREATE TABLE ".TABLE_PREFIX."alert_settings(
	            id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	            code VARCHAR(75) NOT NULL
	            ) ENGINE=MyISAM{$collation};");
	    }

	    if (!$db->table_exists('alert_setting_values')) {
	        $collation = $db->build_create_table_collation();
	        $db->write_query("CREATE TABLE ".TABLE_PREFIX."alert_setting_values(
	            id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	            user_id INT(10) NOT NULL,
	            setting_id INT(10) NOT NULL,
	            value INT(1) NOT NULL DEFAULT '1'
	            ) ENGINE=MyISAM{$collation};");
	    }

	    if ($db->field_exists('myalerts_settings', 'users')) {
	    	$db->drop_column('users', 'myalerts_settings');
	    }
	}
}
