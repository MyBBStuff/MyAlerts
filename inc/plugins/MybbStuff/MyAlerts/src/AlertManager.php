<?php

namespace MybbStuff\MyAlerts;

use MybbStuff\MyAlerts\Entity\Alert;
use MybbStuff\MyAlerts\Entity\AlertType;

/**
 * Manages the creating, fetching and manipulating of alerts within the database.
 *
 * @package MybbStuff\MyAlerts
 */
class AlertManager
{
	/** @var \MyBB MyBB core object used to get settings and more. */
	private $mybb;

	/** @var \DB_MySQLi Database connection to be used when manipulating alerts. */
	private $db;

	/** @var \datacache Cache instance used to manipulate alerts. */
	private $cache;

	/**
	 * @var Alert[] A queue of alerts waiting to be committed to the database.
	 */
	private static $alertQueue;

	/** @var  AlertType[] A cache of the alert types currently available in the system. */
	private static $alertTypes;

	/**
	 * Initialise a new instance of the AlertManager.
	 *
	 * @param \MyBB      $mybb  MyBB core object used to get settings and more.
	 * @param \DB_MySQLi $db    Database connection to be used when manipulating alerts.
	 * @param \datacache $cache Cache instance used to manipulate alerts and alert types.
	 */
	public function __construct(\MyBB $mybb, \DB_MySQLi $db, \datacache $cache)
	{
		$this->mybb  = $mybb;
		$this->db    = $db;
		$this->cache = $cache;

		static::$alertQueue = array();
		static::$alertTypes = array();
	}

	/**
	 * Shortcut to get MyBB settings.
	 *
	 * @return array An array of settings and values.
	 */
	public function settings()
	{
		return $this->mybb->settings;
	}

	/**
	 * Get all of the available alert types in the system.
	 *
	 * @return Entity\AlertType[] The available alert types.
	 */
	public function getAlertTypes()
	{
		/** @var AlertType[] $alertTypes */
		$alertTypes = array();

		if (!empty(static::$alertTypes)) {
			$alertTypes = static::$alertTypes;
		} else {
			if ($this->cache != null) {
				$alertTypes = $this->cache->read('myalerts_alert_types');
			} else {
				// TODO: Load alert types straight out of the database
			}

			static::$alertTypes = $alertTypes;
		}

		return $alertTypes;
	}

	/**
	 * Add a new alert.
	 *
	 * @param Alert $alert The alert to add.
	 *
	 * @return $this
	 */
	public function addAlert(Alert $alert)
	{
		// TODO: Check for duplicates...
		if (is_string($alert->getTypeId())) {
			$alert->setTypeId($this->getAlertTypeIdByCode($alert->getTypeId()));
		}

		$alert->setFromUserId($this->mybb->user['uid']);

		static::$alertQueue[] = $alert;

		return $this;
	}

	/**
	 * Get the ID of an alert type by its short code.
	 *
	 * @param string $code The short code name of the alert type.
	 *
	 * @return int The ID of the alert type.
	 */
	private function getAlertTypeIdByCode($code = '')
	{
		$typeId = 0;

		if (empty(static::$alertTypes)) {
			$this->getAlertTypes();
		}

		foreach (static::$alertTypes as $alertType) {
			if ($alertType->getCode() == $code) {
				$typeId = $alertType->getId();
				break;
			}
		}

		return $typeId;
	}

	/**
	 * Commit the currently queued alerts to the database.
	 *
	 * @return bool Whether the alerts were added successfully.
	 */
	public function commit()
	{
		if (empty(static::$alertQueue)) {
			$success = true;
		} else {
			$toCommit = array();

			foreach (static::$alertQueue as $alert) {
				$toCommit[] = $alert->toArray();
			}

			$success = (boolean) $this->db->insert_query_multiple('alerts', $toCommit);
		}

		return $success;
	}
} 
