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
	private static $version = '0.01';
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
		return self::$version;
	}

	/**
	 *	Get the number of alerts a user has
	 *
	 *	@return int
	 */
	public function getNumAlerts()
	{
		$num = $this->db->simple_select('alerts', 'COUNT(id) AS count', 'uid = '.intval($this->mybb->user['uid']));
		return intval($this->db->fetch_field($num, 'count'));
	}

	/**
	 *	Fetch all alerts for the currently logged in user
	 *
	 *	@param Integer - the start point (used for multipaging alerts)
	 *	@return Array
	 *	@return boolean - if the user has no new alerts
	 */
	public function getAlerts($start = 0)
	{
		if (intval($this->mybb->user['uid']) > 0)	// check the user is a user and not a guest - no point wasting queries on guests afterall
		{
			$alerts = $this->db->simple_select('alerts', '*', 'uid = '.intval($this->mybb->user['uid']), array('limit' => $start.', '.$this->mybb->settings['myalerts_perpage'], 'order_by' => 'id', 'order_dir' => 'DESC'));
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
			$alerts = $this->db->simple_select('alerts', '*', 'uid = '.intval($this->mybb->user['uid']).' AND unread = 1', array('order_by' => 'id', 'order_dir' => 'DESC'));
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

	/**
	 *	Add an alert
	 *
	 *	@param int - UID
	 *	@param Array - content
	 *	@return boolean
	 */
	public function addAlert($uid, $content = array())
	{
		$content = serialize($content);

		$insertArray = array(
			'uid'		=> intval($uid),
			'content'	=>	$this->db->escape_string($content)
			);

		$this->db->insert_query('alerts', $insertArray);
	}
}