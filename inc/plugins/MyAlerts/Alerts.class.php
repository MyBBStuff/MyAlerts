<?php
/**
 *	Main Alerts Class
 *
 *	@package MyAlerts
 *	@version 1.00
 *	@author Euan T. <euan@euantor.com>
 */

class Alerts
{
	private static $version = '1.00';
	private $mybb = null;
	private $db = null;

	/**
	 *	Constructor
	 *
	 *	@param MyBB Object
	 *	@param MyBB Database Object
	 */
	function __construct($mybbIn = null, $dbIn = null)
	{
		if (is_object($mybbIn) AND is_object($dbIn))
		{
			$this->mybb = $mybbIn;
			$this->db = $dbIn;
		}
		else
		{
			throw new Exception('You must pass $mybb and $db as parameters to the Alerts class');
			return false;
		}
	}

	/**
	 *	Get the current version number of the class - handy for upgrading etc
	 *
	 *	@return String
	 */
	public static function getVersion()
	{
		return $this->version;
	}

	/**
	 *	Fetch all alerts for the currently logged in user
	 *
	 *	@return Array
	 *	@return boolean - if the user has no new alerts
	 */
	public function getAlerts()
	{
		if (intval($this->mybb->user['uid']) > 0)	// check the user is a user and not a guest - no point wasting queries on guests afterall
		{
			$alerts = $this->db->simple_select('alerts', '*', 'uid = '.intval($this->mybb->user['uid']));
			if ($this->db->num_rows($alerts) > 0)
			{
				$return = array();
				while ($alert = $this->db->fetch_array($alerts))
				{
					$alert['content'] = unserialize($alert['content']);
					$return[] = $alert;
				}

				return $return;
			}
			else
			{
				return false;
			}
		}
		else
		{
			throw new Exception('Guests have not got access to the Alerts functionality');
		}
	}

	/**
	 *	Fetch all unread alerts for the currently logged in user
	 *
	 *	@return Array
	 *	@return boolean - if the user has no new alerts
	 */
	public function getUnreadAlerts()
	{
		if (intval($this->mybb->user['uid']) > 0)	// check the user is a user and not a guest - no point wasting queries on guests afterall
		{
			$alerts = $this->db->simple_select('alerts', '*', 'uid = '.intval($this->mybb->user['uid']).' AND unread = 1');
			if ($this->db->num_rows($alerts) > 0)
			{
				$return = array();
				while ($alert = $this->db->fetch_array($alerts))
				{
					$alert['content'] = unserialize($alert['content']);
					$return[] = $alert;
				}

				return $return;
			}
			else
			{
				return false;
			}
		}
		else
		{
			throw new Exception('Guests have not got access to the Alerts functionality');
		}
	}

	/**
	 *	Mark alerts as read
	 *
	 *	@param String - formatted for use in a MySQL IN statement
	 */
	public function markRead($alerts = '')
	{
		return $this->db->update_query('alerts', array('unread' => '0'), 'id IN('.$alerts.')');
	}

	/**
	 *	Delete alerts
	 *
	 *	@param String - formatted for use in a MySQL IN statement
	 */
	public function deleteAlerts($alerts = '')
	{
		return $this->db->delete_query('alerts', 'id IN('.$alerts.')');
	}
}