<?php

/**
 * Manages settings for alert types for a user. Allows users to enable/disable complete types of alerts and handles the fetching of these settings.
 *
 * @package MybbStuff\MyAlerts
 */
class MybbStuff_MyAlerts_UserAlertTypeSettingManager
{
	/** @var DB_MySQLi Database connection instance to use. */
	private $db;

	/**
	 * Create a new instance of the UserAlertTypeSettingManager.
	 *
	 * @param DB_MySQLi $db A database instance to use with the manager.
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}
} 
