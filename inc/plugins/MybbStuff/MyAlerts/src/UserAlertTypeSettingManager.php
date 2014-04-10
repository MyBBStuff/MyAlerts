<?php

namespace MybbStuff\MyAlerts;

/**
 * Manages settings for alert types for a user. Allows users to enable/disable complete types of alerts and handles the fetching of these settings.
 *
 * @package MybbStuff\MyAlerts
 */
class UserAlertTypeSettingManager
{
	/** @var \DB_MySQLi Database connection instance to use. */
	private $db;

	public function __construct(\DB_MySQLi $db)
	{
		$this->db = $db;
	}
} 
