<?php

/**
 * Manages the creating, fetching and manipulating of alerts within the
 * database.
 *
 * @package MybbStuff\MyAlerts
 */
class MybbStuff_MyAlerts_AlertManager
{
	/** @var string The version of the AlertManager. */
	const VERSION                = '2.1.0-beta';
	const FIND_USERS_BY_UID      = 0;
	const FIND_USERS_BY_USERNAME = 1;
	/**
	 * @var MybbStuff_MyAlerts_Entity_Alert[] A queue of alerts waiting to be
	 *      committed to the database.
	 */
	private static $alertQueue;
	/** @var  MybbStuff_MyAlerts_Entity_AlertType[] A cache of the alert types currently available in the system. */
	private static $alertTypes;
	/** @var MybbStuff_MyAlerts_AlertManager */
	private static $instance = null;
	/** @var MyBB MyBB core object used to get settings and more. */
	private $mybb;
	/** @var DB_Base Database connection to be used when manipulating alerts. */
	private $db;
	/** @var datacache Cache instance used to manipulate alerts. */
	private $cache;
	/** @var pluginSystem $plugins MyBB plugin system. */
	private $plugins;
	/** @var MybbStuff_MyAlerts_AlertTypeManager $alertTypeManager */
	private $alertTypeManager;
	/** @var array An array of the currently enabled alert types for the user. */
	private $currentUserEnabledAlerts = array();
	/**
	 * Whether the commit() function is registered.
	 */
	public static $isCommitRegistered = false;

	/**
	 * Initialise a new instance of the AlertManager.
	 *
	 * @param MyBB                                $mybb             MyBB core
	 *                                                              object used
	 *                                                              to get
	 *                                                              settings
	 *                                                              and more.
	 * @param DB_Base                             $db               Database
	 *                                                              connection
	 *                                                              to be used
	 *                                                              when
	 *                                                              manipulating
	 *                                                              alerts.
	 * @param datacache                           $cache            Cache
	 *                                                              instance
	 *                                                              used to
	 *                                                              manipulate
	 *                                                              alerts and
	 *                                                              alert
	 *                                                              types.
	 * @param pluginSystem                        $plugins          MyBB plugin
	 *                                                              system.
	 * @param MybbSTuff_MyAlerts_AlertTypeManager $alertTypeManager Alert type
	 *                                                              manager
	 *                                                              instance.
	 */
	private function __construct(
		$mybb,
		DB_Base $db,
		datacache $cache,
		pluginSystem $plugins,
		MybbStuff_MyAlerts_AlertTypeManager $alertTypeManager
	) {
		$this->mybb = $mybb;
		$this->db = $db;
		$this->cache = $cache;
		$this->plugins = $plugins;
		$this->alertTypeManager = $alertTypeManager;

		$this->currentUserEnabledAlerts = $this->filterEnabledAlerts(
			$mybb->user['myalerts_disabled_alert_types']
		);

		static::$alertQueue = array();
		static::$alertTypes = array();
	}

	/**
	 * Filter the current user's enabled alerts array and format it so that it
	 * is an array of just alert type codes that are enabled.
	 *
	 * @param array $userDisabledAlertIds The user's disabled alert types.
	 *
	 * @return array The filtered array.
	 */
	private function filterEnabledAlerts($userDisabledAlertIds = array())
	{
		$userDisabledAlertIds = (array) $userDisabledAlertIds;
		$alertTypes = $this->alertTypeManager->getAlertTypes();
		$enabledAlertTypes = array();

		foreach ($alertTypes as $alertType) {
			if (!in_array($alertType['id'], $userDisabledAlertIds) || !$alertType['can_be_user_disabled']) {
				$enabledAlertTypes[] = (int) $alertType['id'];
			}
		}

		return $enabledAlertTypes;
	}

	/**
	 * @return MybbStuff_MyAlerts_Entity_Alert[]
	 */
	public static function getAlertQueue()
	{
		return self::$alertQueue;
	}

	/**
	 * @return MybbStuff_MyAlerts_Entity_AlertType[]
	 */
	public static function getAlertTypes()
	{
		return self::$alertTypes;
	}

	/** Create an instance of the alert manager.
	 *
	 * @param MyBB                                $mybb             MyBB core
	 *                                                              object.
	 * @param DB_Base                             $db               MyBB
	 *                                                              database
	 *                                                              object.
	 * @param datacache                           $cache            MyBB cache
	 *                                                              object.
	 * @param pluginSystem                        $plugins          MyBB plugin
	 *                                                              system.
	 * @param MybbStuff_MyAlerts_AlertTypeManager $alertTypeManager Alert type
	 *                                                              manager
	 *                                                              instance.
	 *
	 * @return MybbStuff_MyAlerts_AlertManager The created instance.
	 */
	public static function createInstance(
		MyBB $mybb,
		DB_Base $db,
		datacache $cache,
		pluginSystem $plugins,
		MybbStuff_MyAlerts_AlertTypeManager $alertTypeManager
	) {
		if (static::$instance === null) {
			static::$instance = new self(
				$mybb,
				$db,
				$cache,
				$plugins,
				$alertTypeManager
			);
		}

		return static::$instance;
	}

	/**
	 * Get a prior created instance of the alert manager. @see
	 * createInstance().
	 *
	 * @return bool|MybbStuff_MyAlerts_AlertManager The existing instance, or
	 *                                              false if not already
	 *                                              instantiated.
	 */
	public static function getInstance()
	{
		if (static::$instance === null) {
			return false;
		}

		return static::$instance;
	}

	/**
	 * @return MyBB
	 */
	public function getMybb()
	{
		return $this->mybb;
	}

	/**
	 * @return DB_Base
	 */
	public function getDb()
	{
		return $this->db;
	}

	/**
	 * @return datacache
	 */
	public function getCache()
	{
		return $this->cache;
	}

	/**
	 * @return pluginSystem
	 */
	public function getPlugins()
	{
		return $this->plugins;
	}

	/**
	 * @return MybbStuff_MyAlerts_AlertTypeManager
	 */
	public function getAlertTypeManager()
	{
		return $this->alertTypeManager;
	}

	/**
	 * @return array
	 */
	public function getCurrentUserEnabledAlerts()
	{
		return $this->currentUserEnabledAlerts;
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
	 * Add a list of alerts.
	 *
	 * @param MybbStuff_MyAlerts_Entity_Alert[] $alerts An array of alerts to
	 *                                                  add.
	 */
	public function addAlerts(array $alerts)
	{
		foreach ($alerts as $alert) {
			$this->addAlert($alert);
		}
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
		$fromUser = $alert->getFromUser();

		if (!isset($fromUser['uid'])) {
			$alert->setFromUser($this->mybb->user);
		}

		$alertType = $alert->getType();

		$usersWhoWantAlert = $this->doUsersWantAlert(
			$alert->getType(),
			array($alert->getUserId())
		);
		if ($alertType->getEnabled() && (!empty($usersWhoWantAlert) || !$alertType->getCanBeUserDisabled())) {
			if ($alertType->getCode() === 'quoted') {
				// If there is already an alert queued to the user of the type
				// 'post_threadauthor', don't add the alert.
				$extraDetails = $alert->getExtraDetails();
				$tid = $extraDetails['tid'];

				if (isset(static::$alertQueue['post_threadauthor_' . $alert->getUserId(
					) . '_' . $tid])) {
					return $this;
				}
			}

			$passToHook = array(
				'alertManager' => &$this,
				'alert'        => &$alert,
			);

			$this->plugins->run_hooks(
				'myalerts_alert_manager_add_alert',
				$passToHook
			);

			// Basic duplicate checking by overwrite - only one alert for each alert type/object id combination
			static::$alertQueue[$alert->getType()->getCode() . '_' . $alert->getUserId() . '_' . $alert->getObjectId()] = $alert;
		}

		return $this;
	}

	/**
	 * Get the users who want to receive a certain alert type.
	 *
	 * @param MybbStuff_MyAlerts_Entity_AlertType $alertType   The alert type
	 *                                                         to check.
	 * @param array                               $users       An array of User
	 *                                                         IDs to check.
	 * @param int                                 $findUsersBy The column to
	 *                                                         find users by.
	 *                                                         Should be one of
	 *                                                         FIND_USERS_BY_UID
	 *                                                         or
	 *                                                         FIND_USERS_BY_USERNAME
	 *
	 * @return array
	 */
	public function doUsersWantAlert(
		MybbStuff_MyAlerts_Entity_AlertType $alertType,
		array $users = array(),
		$findUsersBy = self::FIND_USERS_BY_UID
	) {
		$usersWhoWantAlert = array();

		switch ($findUsersBy) {
			case self::FIND_USERS_BY_USERNAME:
				$users = array_map(array($this->db, 'escape_string'), $users);

				$usernames = "'" . implode("','", $users) . "'";
				$query = $this->db->simple_select(
					'users',
					'uid, myalerts_disabled_alert_types, usergroup',
					"username IN({$usernames})"
				);
				break;
			case self::FIND_USERS_BY_UID:
			default:
				$users = array_map('intval', $users);

				$uids = "'" . implode("','", $users) . "'";
				$query = $this->db->simple_select(
					'users',
					'uid, myalerts_disabled_alert_types',
					"uid IN({$uids})"
				);
				break;
		}

		while ($user = $this->db->fetch_array($query)) {
			$disabledAlertTypes = @json_decode(
				$user['myalerts_disabled_alert_types']
			);

			if (empty($disabledAlertTypes) || !in_array(
					$alertType->getId(),
					$disabledAlertTypes
				) || !$alertType->getCanBeUserDisabled()
			) {
				$usersWhoWantAlert[] = $user;
			}
		}

		return $usersWhoWantAlert;
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
				$alertArray = $alert->toArray();

				$alertArray['extra_details'] = $this->db->escape_string(
					$alertArray['extra_details']
				);

				$toCommit[] = $alertArray;
			}
			
			// Empty the alert queue.
			static::$alertQueue = array();

			$success = (boolean) $this->db->insert_query_multiple(
				'alerts',
				$toCommit
			);
		}

		return $success;
	}

	/**
	 *  Get the number of alerts a user has
	 *
	 * @return int The total number of alerts the user has
	 */
	public function getNumAlerts()
	{
		static $numAlerts;

		if (!is_int($numAlerts)) {
			$numAlerts = 0;

			if (!empty($this->currentUserEnabledAlerts)) {
				$alertTypes = $this->getAlertTypesForIn();

				$this->mybb->user['uid'] = (int) $this->mybb->user['uid'];
				$prefix = TABLE_PREFIX;

				$queryString = <<<SQL
SELECT COUNT(*) AS count FROM {$prefix}alerts a
INNER JOIN {$prefix}alert_types t ON (a.alert_type_id = t.id)
WHERE (a.alert_type_id IN ({$alertTypes}) OR a.forced = 1 OR t.can_be_user_disabled = 0) AND t.enabled = 1 AND a.uid = {$this->mybb->user['uid']};
SQL;

				$query = $this->db->write_query($queryString);

				$numAlerts = (int) $this->db->fetch_field($query, 'count');
			}
		}

		return $numAlerts;
	}

	/**
	 * Gets the enabled alert types for the current user ready to be used in a
	 * MySQL IN() call.
	 *
	 * @return string The formatted string of alert types enabled for the user.
	 */
	private function getAlertTypesForIn()
	{
		$alertTypes = implode(',', $this->currentUserEnabledAlerts);

		return $alertTypes;
	}

	/**
	 *  Get the number of unread alerts a user has
	 *
	 * @return int The number of unread alerts
	 */
	public function getNumUnreadAlerts($force_recount = false)
	{
		static $numUnreadAlerts;

		if (!is_int($numUnreadAlerts) || $force_recount) {
			$numAlerts = 0;

			if (!empty($this->currentUserEnabledAlerts)) {
				$alertTypes = $this->getAlertTypesForIn();

				$this->mybb->user['uid'] = (int) $this->mybb->user['uid'];

				$prefix = TABLE_PREFIX;
				$queryString = <<<SQL
SELECT COUNT(*) AS count FROM {$prefix}alerts a
INNER JOIN {$prefix}alert_types t ON (a.alert_type_id = t.id)
WHERE (a.alert_type_id IN ({$alertTypes}) OR a.forced = 1 OR t.can_be_user_disabled = 0) AND t.enabled = 1 AND a.uid = {$this->mybb->user['uid']} AND a.unread = 1;
SQL;

				$query = $this->db->write_query($queryString);;

				$numUnreadAlerts = (int) $this->db->fetch_field(
					$query,
					'count'
				);
			}
		}

		return $numUnreadAlerts;
	}

	/**
	 *  Fetch all alerts for the currently logged in user
	 *
	 * @param int $start The start point (used for multipaging alerts)
	 * @param int $limit The maximum number of alerts to retrieve.
	 * @param boolean $unreadOnly Whether to show only unread alerts.
	 *
	 * @return array The alerts for the user.
	 * @return boolean If the user has no new alerts.
	 * @throws Exception Thrown if the use cannot access the alerts system.
	 */
	public function getAlerts($start = 0, $limit = 0, $unreadOnly = false)
	{
		$alerts = array();

		$start = (int) $start;
		$limit = (int) $limit;

		if (!empty($this->currentUserEnabledAlerts)) {
			if ($limit == 0) {
				$limit = $this->mybb->settings['myalerts_perpage'];
			}

			$alertTypes = $this->getAlertTypesForIn();

			$this->mybb->user['uid'] = (int) $this->mybb->user['uid'];
			$unreadCondition = $unreadOnly ? " AND a.unread = 1" : '';
			$prefix = TABLE_PREFIX;
			$alertsQuery = <<<SQL
SELECT a.*, u.uid, u.username, u.avatar, u.usergroup, u.displaygroup, t.code FROM {$prefix}alerts a
LEFT JOIN {$prefix}users u ON (a.from_user_id = u.uid)
INNER JOIN {$prefix}alert_types t ON (a.alert_type_id = t.id)
WHERE a.uid = {$this->mybb->user['uid']}
AND (a.alert_type_id IN ({$alertTypes}) OR a.forced = 1 OR t.can_be_user_disabled = 0) AND t.enabled = 1{$unreadCondition} ORDER BY a.id DESC LIMIT {$limit} OFFSET {$start};
SQL;

			$query = $this->db->write_query($alertsQuery);

			if ($this->db->num_rows($query) > 0) {
				$return = array();
				while ($alertRow = $this->db->fetch_array($query)) {
					$alertType = $this->alertTypeManager->getByCode(
						$alertRow['code']
					);
					if ($alertType != null) {
						$alert = new MybbStuff_MyAlerts_Entity_Alert(
							$alertRow['uid'],
							$alertType,
							$alertRow['object_id']
						);
						$alert->setId($alertRow['id']);
						$alert->setCreatedAt(
							new DateTime($alertRow['dateline'])
						);
						$alert->setUnread((bool) $alertRow['unread']);
						$alert->setExtraDetails(
							json_decode($alertRow['extra_details'], true)
						);

						$user = array(
							'uid'          => (int) $alertRow['uid'],
							'username'     => $alertRow['username'],
							'avatar'       => $alertRow['avatar'],
							'usergroup'    => $alertRow['usergroup'],
							'displaygroup' => $alertRow['displaygroup'],
						);

						$alert->setFromUser($user);

						$alerts[] = $alert;
					}
				}
			}
		}

		return $alerts;
	}

	/**
	 *  Fetch all unread alerts for the currently logged in user.
	 *
	 * @return Array When the user has unread alerts.
	 * @return boolean If the user has no new alerts.
	 * @throws Exception Thrown if the use cannot access the alerts system.
	 */
	public function getUnreadAlerts()
	{
		$alerts = array();

		if (!empty($this->currentUserEnabledAlerts)) {
			$alertTypes = $this->getAlertTypesForIn();

			$this->mybb->user['uid'] = (int) $this->mybb->user['uid'];
			$prefix = TABLE_PREFIX;
			$alertsQuery = <<<SQL
SELECT a.*, u.uid, u.username, u.avatar, u.usergroup, u.displaygroup, t.code FROM {$prefix}alerts a
LEFT JOIN {$prefix}users u ON (a.from_user_id = u.uid)
INNER JOIN {$prefix}alert_types t ON (a.alert_type_id = t.id)
WHERE a.uid = {$this->mybb->user['uid']} AND a.unread = 1
AND (a.alert_type_id IN ({$alertTypes}) OR a.forced = 1 OR t.can_be_user_disabled = 0) AND t.enabled = 1 ORDER BY a.id DESC;
SQL;

			$query = $this->db->write_query($alertsQuery);

			if ($this->db->num_rows($query) > 0) {
				while ($alertRow = $this->db->fetch_array($query)) {
					$alertType = $this->alertTypeManager->getByCode(
						$alertRow['code']
					);

					if ($alertType != null) {
						$alert = new MybbStuff_MyAlerts_Entity_Alert(
							$alertRow['uid'],
							$alertType,
							$alertRow['object_id']
						);
						$alert->setId($alertRow['id']);
						$alert->setCreatedAt(
							new DateTime($alertRow['dateline'])
						);
						$alert->setUnread((bool) $alertRow['unread']);
						$alert->setExtraDetails(
							json_decode($alertRow['extra_details'], true)
						);

						$user = array(
							'uid'          => (int) $alertRow['uid'],
							'username'     => $alertRow['username'],
							'avatar'       => $alertRow['avatar'],
							'usergroup'    => $alertRow['usergroup'],
							'displaygroup' => $alertRow['displaygroup'],
						);

						$alert->setFromUser($user);

						$alerts[] = $alert;
					}
				}
			}
		}

		return $alerts;
	}

	/**
	 * Get a single alert by ID.
	 *
	 * @param int $id The ID of the alert to fetch.
	 *
	 * @return MybbSTuff_MyAlerts_Entity_Alert
	 */
	public function getAlert($id = 0)
	{
		$id = (int) $id;

		$alert = null;

		$this->mybb->user['uid'] = (int) $this->mybb->user['uid'];
		$prefix = TABLE_PREFIX;
		$alertsQuery = <<<SQL
SELECT a.*, u.uid, u.username, u.avatar, u.usergroup, u.displaygroup, t.code FROM {$prefix}alerts a
LEFT JOIN {$prefix}users u ON (a.from_user_id = u.uid)
INNER JOIN {$prefix}alert_types t ON (a.alert_type_id = t.id)
WHERE a.uid = {$this->mybb->user['uid']} AND a.id = {$id};
SQL;

		$query = $this->db->write_query($alertsQuery);

		if ($this->db->num_rows($query) > 0) {
			while ($alertRow = $this->db->fetch_array($query)) {
				$alertType = $this->alertTypeManager->getByCode(
					$alertRow['code']
				);

				if ($alertType != null) {
					$alert = new MybbStuff_MyAlerts_Entity_Alert(
						$alertRow['uid'],
						$alertType,
						$alertRow['object_id']
					);
					$alert->setId($alertRow['id']);
					$alert->setCreatedAt(new DateTime($alertRow['dateline']));
					$alert->setUnread((bool) $alertRow['unread']);
					$alert->setExtraDetails(
						json_decode($alertRow['extra_details'], true)
					);

					$user = array(
						'uid'          => (int) $alertRow['uid'],
						'username'     => $alertRow['username'],
						'avatar'       => $alertRow['avatar'],
						'usergroup'    => $alertRow['usergroup'],
						'displaygroup' => $alertRow['displaygroup'],
					);

					$alert->setFromUser($user);
				}
			}
		}

		return $alert;
	}

	/**
	 * Mark all alerts for the currently logged in user as read.
	 *
	 * @return bool Whether all alerts were marked read successfully.
	 */
	public function markAllRead() {
		$success = (bool) $this->db->update_query(
			'alerts',
			array(
				'unread' => '0'
			),
			'uid = ' . $this->mybb->user['uid']
		);

		if ($success) {
			$affectedRows = $this->db->affected_rows();
		} else {
			$affectedRows = 0;
		}

		$passToHook = array(
			'alertManager' => &$this,
			'affectedRows' => $affectedRows,
		);

		$this->plugins->run_hooks(
			'myalerts_alert_manager_mark_all_read',
			$passToHook
		);

		return $success;
	}

	/**
	 *  Mark alerts as read.
	 *
	 * @param array $alerts An array of alert IDs to be marked read.
	 *
	 * @return bool Whether the alerts were marked read successfully.
	 */
	public function markRead(array $alerts = array())
	{
		return $this->markReadOrUnread($alerts, true);
	}

	/**
	 *  Mark alerts as unread.
	 *
	 * @param array $alerts An array of alert IDs to be marked unread.
	 *
	 * @return bool Whether the alerts were marked unread successfully.
	 */
	public function markUnread(array $alerts = array())
	{
		return $this->markReadOrUnread($alerts, false);
	}

	/**
	 *  Mark alerts as either read or unread.
	 *
	 * @param array $alerts An array of alert IDs to be marked read or unread.
	 * @param bool $markRead Mark read if true; mark unread if false.
	 *
	 * @return bool Whether the alerts were marked read or unread successfully.
	 */
	public function markReadOrUnread(array $alerts = array(), $markRead = true)
	{
		$alerts = (array) $alerts;

		$success = true;

		if (is_array($alerts) && !empty($alerts)) {
			$alerts = array_map('intval', $alerts);
			$alerts = "'" . implode("','", $alerts) . "'";

			$success = (bool) $this->db->update_query(
				'alerts',
				array(
					'unread' => $markRead ? '0' : '1'
				),
				'id IN(' . $alerts . ') AND uid = ' . $this->mybb->user['uid']
			);

			if ($success) {
				$affectedRows = $this->db->affected_rows();
			} else {
				$affectedRows = 0;
			}

			$passToHook = array(
				'alertManager' => &$this,
				'alertIds'     => $alerts,
				'affectedRows' => $affectedRows,
			);

			$this->plugins->run_hooks(
				$markRead ? 'myalerts_alert_manager_mark_read' : 'myalerts_alert_manager_mark_unread',
				$passToHook
			);
		}

		return $success;
	}

	/**
	 *  Delete alerts.
	 *
	 * @param array $alerts An array of alert IDs to be deleted.
	 *
	 * @return bool Whether the alerts were deleted successfully.
	 */
	public function deleteAlerts(array $alerts = array())
	{
		$success = false;

		if (is_array($alerts) || is_int($alerts)) {
			$alerts = (array) $alerts;

			if (!empty($alerts)) {
				$alerts = array_map('intval', $alerts);
				$alerts = "'" . implode("','", $alerts) . "'";

				$success = (bool) $this->db->delete_query(
					'alerts',
					'id IN(' . $alerts . ') AND uid = ' . (int) $this->mybb->user['uid']
				);
			}
		}

		return $success;
	}
}
