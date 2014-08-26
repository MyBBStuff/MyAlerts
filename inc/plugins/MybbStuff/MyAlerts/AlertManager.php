<?php

/**
 * Manages the creating, fetching and manipulating of alerts within the database.
 *
 * @package MybbStuff\MyAlerts
 */
class MybbStuff_MyAlerts_AlertManager
{
	/** @var string The version of the AlertManager. */
	const VERSION = '2.0.0';
	/** @var MyBB MyBB core object used to get settings and more. */
	private $mybb;
	/** @var DB_MySQLi Database connection to be used when manipulating alerts. */
	private $db;
	/** @var datacache Cache instance used to manipulate alerts. */
	private $cache;
	/**
	 * @var MybbStuff_MyAlerts_Entity_Alert[] A queue of alerts waiting to be committed to the database.
	 */
	private static $alertQueue;
	/** @var  MybbStuff_MyAlerts_Entity_AlertType[] A cache of the alert types currently available in the system. */
	private static $alertTypes;

	/**
	 * Initialise a new instance of the AlertManager.
	 *
	 * @param MyBB      $mybb  MyBB core object used to get settings and more.
	 * @param DB_MySQLi $db    Database connection to be used when manipulating alerts.
	 * @param datacache $cache Cache instance used to manipulate alerts and alert types.
	 */
	public function __construct($mybb, $db, $cache)
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
	 * @return MybbStuff_MyAlerts_Entity_AlertType[] The available alert types.
	 */
	public function getAlertTypes()
	{
		/** @var MybbStuff_MyAlerts_Entity_AlertType[] $alertTypes */
		$alertTypes = array();

		if (!empty(static::$alertTypes)) {
			$alertTypes = static::$alertTypes;
		} else {
			if ($this->cache != null) {
				$alertTypes = $this->cache->read('myalerts_alert_types');
			} else {
				$alertTypeQuery = $this->db->simple_select('alert_settings', '*');
				
				while ($alertType = $this->db->fetch_array($alertTypeQuery)) {
					$type = new MybbStuff_MyAlerts_Entity_AlertType();
					$type->setId((int) $alertType['id']);
					$type->setCode($alertType['code']);
					$alertTypes[] = $type;
				}
			}

			static::$alertTypes = $alertTypes;
		}

		return $alertTypes;
	}

	/**
	 * Add a new alert.
	 *
	 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to add.
	 *
	 * @return $this
	 */
	public function addAlert(MybbStuff_MyAlerts_Entity_Alert $alert)
	{
		// TODO: Check for duplicates...
		if (is_string($alert->getType())) {
			$alert->setType($this->getAlertTypeIdByCode($alert->getType()));
		}

		$alert->setFromUserId($this->mybb->user['uid']);

		static::$alertQueue[] = $alert;

		return $this;
	}
	
	/**
	 * Add a list of alerts.
	 * 
	 * @param MybbStuff_MyAlerts_Entity_Alert[] $alerts An array of alerts to add.
	 */
	public function addAlerts(array $alerts)
	{
		foreach ($alerts as $alert) {
			$this->addAlert($alert);
		}
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

		foreach ($this->getAlertTypes() as $alertType) {
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
	
	/**
     *  Get the number of alerts a user has
     *
     *  @return int The total number of alerts the user has
     */
    public function getNumAlerts()
    {
        static $numAlerts;
        
        if (!is_int($numAlerts)) {
            $numAlerts = 0;
            
            if (is_array($this->mybb->user['myalerts_settings'])) {
                $alertTypes = "'" . implode("','", array_keys(array_filter((array) $this->mybb->user['myalerts_settings']))) . "'";
                
                $num = $this->db->simple_select('alerts', 'COUNT(id) AS count', '(alert_type IN (' . $alertTypes . ') OR forced = 1) AND uid = ' . (int) $this->mybb->user['uid']);
                $numAlerts = (int) $this->db->fetch_field($num, 'count');
            }
        }
        
        return $numAlerts;
    }
    
    /**
     *  Get the number of unread alerts a user has
     *
     *  @return int The number of unread alerts
     */
    public function getNumUnreadAlerts()
    {
        static $numUnreadAlerts;
        
        if (!is_int($numUnreadAlerts)) {
            $numUnreadAlerts = 0;
            
            if (is_array($this->mybb->user['myalerts_settings'])) {
                $alertTypes = "'" . implode("','", array_keys(array_filter((array) $this->mybb->user['myalerts_settings']))) . "'";
                $num = $this->db->simple_select('alerts', 'COUNT(id) AS count', 'uid = ' . (int) $this->mybb->user['uid'] . ' AND unread = 1 AND (alert_type IN (' . $alertTypes . ') OR forced = 1)');
                $numUnreadAlerts = (int) $this->db->fetch_field($num, 'count');
            }
        }
        
        return $numUnreadAlerts;
    }
    
    /**
     *  Fetch all alerts for the currently logged in user
     *
     *  @param Integer The start point (used for multipaging alerts)
     *  @return Array
     *  @return boolean If the user has no new alerts
     */
    public function getAlerts($start = 0, $limit = 0)
    {
        if ((int) $this->mybb->user['uid'] > 0) { // check the user is a user and not a guest - no point wasting queries on guests afterall
            if ($limit == 0) {
                $limit = $this->mybb->settings['myalerts_perpage'];
            }
            
            $alertTypes = "'" . implode("','", array_keys(array_filter((array) $this->mybb->user['myalerts_settings']))) . "'";
            
            $alerts = $this->db->write_query("SELECT a.*, u.uid, u.username, u.avatar, u.usergroup, u.displaygroup FROM " . TABLE_PREFIX . "alerts a INNER JOIN " . TABLE_PREFIX . "users u ON (a.from_id = u.uid) WHERE a.uid = " . (int) $this->mybb->user['uid'] . " AND (alert_type IN ({$alertTypes}) OR a.forced = 1) ORDER BY a.id DESC LIMIT " . (int) $start . ", " . (int) $limit . ";");
            if ($this->db->num_rows($alerts) > 0) {
                $return = array();
                while ($alert = $this->db->fetch_array($alerts)) {
                    $alert['content'] = json_decode($alert['content'], true);
                    $return[] = $alert;
                }
                
                return $return;
            } else {
                return false;
            }
        } else {
            throw new Exception('Guests have not got access to the Alerts functionality');
        }
    }
    
    /**
     *  Fetch all unread alerts for the currently logged in user
     *
     *  @return Array When the user has unread alerts
     *  @return boolean If the user has no new alerts
     */
    public function getUnreadAlerts()
    {
        if ((int) $this->mybb->user['uid'] > 0) { // check the user is a user and not a guest - no point wasting queries on guests afterall
            $alertTypes = "'" . implode("','", array_keys(array_filter((array) $this->mybb->user['myalerts_settings']))) . "'";
            $alerts = $this->db->write_query("SELECT a.*, u.uid, u.username, u.avatar FROM " . TABLE_PREFIX . "alerts a INNER JOIN " . TABLE_PREFIX . "users u ON (a.from_id = u.uid) WHERE a.uid = " . (int) $this->mybb->user['uid'] . " AND unread = '1' AND (a.alert_type IN({$alertTypes}) OR a.forced = 1) ORDER BY a.id DESC;");
            
            if ($this->db->num_rows($alerts) > 0) {
                $return = array();
                while ($alert = $this->db->fetch_array($alerts)) {
                    $alert['content'] = json_decode($alert['content'], true);
                    $return[] = $alert;
                }
                
                return $return;
            } else {
                return false;
            }
        } else {
            throw new Exception('Guests have not got access to the Alerts functionality');
        }
    }
    
    /**
     *  Mark alerts as read
     *
     *  @param String/Array Either a string formatted for use in a MySQL IN() clause or an array to be parsed into said form
     */
    public function markRead($alerts = '')
    {
        $alerts = (array) $alerts;
        
        if (is_array($alerts)) {
            $alerts = array_map('intval', $alerts);
            $alerts = "'" . implode("','", $alerts) . "'";
        }
        
        return $this->db->update_query('alerts', array(
            'unread' => '0'
        ), 'id IN(' . $alerts . ') AND uid = ' . $this->mybb->user['uid']);
    }
    
    /**
     *  Delete alerts
     *
     *  @param String/Array Either a string formatted for use in a MySQL IN() clause or an array to be parsed into said form
     */
    public function deleteAlerts($alerts = '')
    {
        if (is_array($alerts) OR is_int($alerts)) {
            $alerts = (array) $alerts;
            $alerts = array_map('intval', $alerts);
            $alerts = "'" . implode("','", $alerts) . "'";
            
            return $this->db->delete_query('alerts', 'id IN(' . $alerts . ') AND uid = ' . (int) $this->mybb->user['uid']);
        } else {
            if ($alerts == 'allRead') {
                return $this->db->delete_query('alerts', 'unread = 0 AND uid = ' . (int) $this->mybb->user['uid']);
            } elseif ($alerts = 'allAlerts') {
                return $this->db->delete_query('alerts', 'uid = ' . (int) $this->mybb->user['uid']);
            }
        }
    }
} 
