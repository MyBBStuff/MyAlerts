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
		$num = $this->db->simple_select('alerts', 'COUNT(id) AS count', 'uid = '.(int) $this->mybb->user['uid']);
		return (int) $this->db->fetch_field($num, 'count');
	}

	/**
	 *	Get the number of unread alerts a user has
	 *
	 *	@return int
	 */
	public function getNumUnreadAlerts()
	{
		$num = $this->db->simple_select('alerts', 'COUNT(id) AS count', 'uid = '.(int) $this->mybb->user['uid'].' AND unread = 1');
		return (int) $this->db->fetch_field($num, 'count');
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
		if ((int) $this->mybb->user['uid'] > 0)	// check the user is a user and not a guest - no point wasting queries on guests afterall
		{
			$alerts = $this->db->write_query("SELECT a.*, u.uid, u.username, u.avatar FROM ".TABLE_PREFIX."alerts a INNER JOIN ".TABLE_PREFIX."users u ON (a.from = u.uid) WHERE a.uid = ".(int) $this->mybb->user['uid']." ORDER BY a.id DESC LIMIT ".(int) $start.", ".$this->mybb->settings['myalerts_perpage'].";");
			if ($this->db->num_rows($alerts) > 0)
			{
				$return = array();
				while ($alert = $this->db->fetch_array($alerts))
				{
					$alert['content'] = json_decode(base64_decode($alert['content']));
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
		if ((int) $this->mybb->user['uid'] > 0)	// check the user is a user and not a guest - no point wasting queries on guests afterall
		{
			$alerts = $this->db->write_query("SELECT a.*, u.uid, u.username, u.avatar FROM ".TABLE_PREFIX."alerts a INNER JOIN ".TABLE_PREFIX."users u ON (a.from = u.uid) WHERE a.uid = ".(int) $this->mybb->user['uid']." AND unread = '1' ORDER BY a.id DESC;");
			if ($this->db->num_rows($alerts) > 0)
			{
				$return = array();
				while ($alert = $this->db->fetch_array($alerts))
				{
					$alert['content'] = json_decode(base64_decode($alert['content']));
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
	 *	@param String/Array - either a string formatted for use in a MySQL IN() clause or an array to be parsed into said form
	 */
	public function markRead($alerts = '')
	{
		if (is_array($alerts))
		{
			$alerts = array_map('intval', $alerts);
			$alerts  = "'".my_strtolower(implode("','", $alerts))."'";
		}

		return $this->db->update_query('alerts', array('unread' => '0'), 'id IN('.$alerts.') AND uid = '.$this->mybb->user['uid']);
	}

	/**
	 *	Delete alerts
	 *
	 *	@param String/Array - either a string formatted for use in a MySQL IN() clause or an array to be parsed into said form
	 */
	public function deleteAlerts($alerts = '')
	{
		if (is_array($alerts))
		{
			$alerts = array_map('intval', $alerts);
			$alerts = "'".my_strtolower(implode("','", $alerts))."'";
		}

		return $this->db->delete_query('alerts', 'id IN('.$alerts.') AND uid = '.$this->mybb->user['uid']);
	}

	/**
	 *	Add an alert
	 *
	 *	@param int - UID
	 *	@param string - the type of alert
	 *	@param Array - content
	 *	@return boolean
	 */
	public function addAlert($uid, $type = '', $tid = 0, $from = 0, $content = array())
	{
		$content = base64_encode(json_encode($content));

		$insertArray = array(
			'uid'		=>	(int) $uid,
			'dateline'	=>	TIME_NOW,
			'type'		=>	$this->db->escape_string($type),
			'tid'		=>	(int) $tid,
			'from'		=>	(int) $from,
			'content'	=>	$this->db->escape_string($content),
			);

		$this->db->insert_query('alerts', $insertArray);
	}

	/**
	 *	Add an alert for multiple users
	 *
	 *	@param array - UIDs
	 *	@param string - the type of alert
	 *	@param Array - content
	 *	@return boolean
	 */
	public function addMassAlert($uids, $type = '', $tid = 0, $from = 0, $content = array())
	{
		$sqlString = '';
		$separator = '';
		$content = base64_encode(json_encode($content));

		foreach ($uids as $uid)
		{
			$sqlString .= $separator.'('.(int) $uid.','.(int) TIME_NOW.', \''.$this->db->escape_string($type).'\', '.(int) $tid.','.(int) $from.',\''.$this->db->escape_string($content).'\')';
			$separator = ",\n";
		}

		$this->db->write_query('INSERT INTO '.TABLE_PREFIX.'alerts (`uid`, `dateline`, `type`, `tid`, `from`, `content`) VALUES '.$sqlString.';');
	}
}