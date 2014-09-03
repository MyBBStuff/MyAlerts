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
    /**
     * @var MybbStuff_MyAlerts_Entity_Alert[] A queue of alerts waiting to be committed to the database.
     */
    private static $alertQueue;
    /** @var  MybbStuff_MyAlerts_Entity_AlertType[] A cache of the alert types currently available in the system. */
    private static $alertTypes;
    /** @var MyBB MyBB core object used to get settings and more. */
    private $mybb;
    /** @var DB_MySQLi Database connection to be used when manipulating alerts. */
    private $db;
    /** @var datacache Cache instance used to manipulate alerts. */
    private $cache;
    /** @var array An array of the currently enabled alert types for the user. */
    private $currentUserEnabledAlerts = array();

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

        $this->currentUserEnabledAlerts = $this->filterEnabledAlerts($mybb->user['myalerts_settings']);

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
     * Add a list of alerts.
     *
     * @param MybbStuff_MyAlerts_Entity_Alert[] $alerts An array of alerts to add.
     */
    public function addAlerts(array $alerts)
    {
        foreach($alerts as $alert)
        {
            $this->addAlert($alert);
        }
    }

    /**
     * Add a new alert.
     *
     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to add.
     * @return $this
     */
    public function addAlert(MybbStuff_MyAlerts_Entity_Alert $alert)
    {
        // TODO: Check for duplicates...
        if(is_string($alert->getType()))
        {
            $alert->setType($this->getAlertTypeIdByCode($alert->getType()));
        }

        $alert->setFromUser($this->mybb->user);

        static::$alertQueue[] = $alert;

        return $this;
    }

    /**
     * Get the ID of an alert type by its short code.
     *
     * @param string $code The short code name of the alert type.
     * @return int The ID of the alert type.
     */
    private function getAlertTypeIdByCode($code = '')
    {
        $typeId = 0;

        foreach($this->getAlertTypes() as $alertType)
        {
            if($alertType->getCode() == $code)
            {
                $typeId = $alertType->getId();
                break;
            }
        }

        return $typeId;
    }

    /**
     * Get all of the available alert types in the system.
     *
     * @param bool $useCache Whether to use the alert type cache.
     * @return MybbStuff_MyAlerts_Entity_AlertType[] The available alert types.
     */
    public function getAlertTypes($useCache = true)
    {
        $useCache = (bool)$useCache;

        /** @var MybbStuff_MyAlerts_Entity_AlertType[] $alertTypes */
        $alertTypes = array();

        if(!empty(static::$alertTypes))
        {
            $alertTypes = static::$alertTypes;
        }
        else
        {
//          if ($this->cache != null && $useCache) {
//              $alertTypes = $this->cache->read('myalerts_alert_types');
//          } else {
            $alertTypeQuery = $this->db->simple_select('alert_settings', '*');

            while($alertType = $this->db->fetch_array($alertTypeQuery))
            {
                $type = new MybbStuff_MyAlerts_Entity_AlertType();
                $type->setId((int)$alertType['id']);
                $type->setCode($alertType['code']);
                $alertTypes[] = $type;
            }
//          }

            static::$alertTypes = $alertTypes;
        }

        return $alertTypes;
    }

    /**
     * Commit the currently queued alerts to the database.
     *
     * @return bool Whether the alerts were added successfully.
     */
    public function commit()
    {
        if(empty(static::$alertQueue))
        {
            $success = true;
        }
        else
        {
            $toCommit = array();

            foreach(static::$alertQueue as $alert)
            {
                $toCommit[] = $alert->toArray();
            }

            $success = (boolean)$this->db->insert_query_multiple('alerts', $toCommit);
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

        if(!is_int($numAlerts))
        {
            $numAlerts = 0;

            if(!empty($this->currentUserEnabledAlerts))
            {
                $alertTypes = $this->getAlertTypesForIn();

                $this->mybb->user['uid'] = (int)$this->mybb->user['uid'];
                $prefix = TABLE_PREFIX;

                $queryString = <<<SQL
                SELECT COUNT(*) AS count FROM {$prefix}alerts a
                INNER JOIN {$prefix}alert_settings s ON (a.alert_type = s.id)
                WHERE (s.code IN ({$alertTypes}) OR a.forced = 1) AND a.uid = {$this->mybb->user['uid']};
SQL;

                $query = $this->db->write_query($queryString);

                $numAlerts = (int)$this->db->fetch_field($query, 'count');
            }
        }

        return $numAlerts;
    }

    /**
     *  Get the number of unread alerts a user has
     *
     * @return int The number of unread alerts
     */
    public function getNumUnreadAlerts()
    {
        static $numUnreadAlerts;

        if(!is_int($numUnreadAlerts))
        {
            $numAlerts = 0;

            if(!empty($this->currentUserEnabledAlerts))
            {
                $alertTypes = $this->getAlertTypesForIn();

                $this->mybb->user['uid'] = (int)$this->mybb->user['uid'];

                $prefix = TABLE_PREFIX;
                $queryString = <<<SQL
                SELECT COUNT(*) AS count FROM {$prefix}alerts a
                INNER JOIN {$prefix}alert_settings s ON (a.alert_type = s.id)
                WHERE (s.code IN ({$alertTypes}) OR a.forced = 1) AND a.uid = {$this->mybb->user['uid']} AND a.unread = 1;
SQL;

                $query = $this->db->write_query($queryString);;

                $numUnreadAlerts = (int)$this->db->fetch_field($query, 'count');
            }
        }

        return $numUnreadAlerts;
    }

    /**
     *  Fetch all alerts for the currently logged in user
     *
     * @param int $start The start point (used for multipaging alerts)
     * @param int $limit The maximum number of alerts to retreive.
     * @return array The alerts for the user.
     * @return boolean If the user has no new alerts.
     * @throws Exception Thrown if the use cannot access the alerts system.
     */
    public function getAlerts($start = 0, $limit = 0)
    {
        $alerts = array();

        $start = (int) $start;
        $limit = (int) $limit;

        if((int)$this->mybb->user['uid'] > 0)
        { // check the user is a user and not a guest - no point wasting queries on guests afterall
            if (!empty($this->currentUserEnabledAlerts)) {
                if ($limit == 0) {
                    $limit = $this->mybb->settings['myalerts_perpage'];
                }

                $alertTypes = $this->getAlertTypesForIn();

                $this->mybb->user['uid'] = (int) $this->mybb->user['uid'];
                $prefix                  = TABLE_PREFIX;
                $alertsQuery             = <<<SQL
    SELECT a.*, s.code, u.uid, u.username, u.avatar, u.usergroup, u.displaygroup FROM {$prefix}alerts a
    INNER JOIN {$prefix}users u ON (a.from_user_id = u.uid)
    INNER JOIN {$prefix}alert_settings s ON (a.alert_type = s.id)
    WHERE a.uid = {$this->mybb->user['uid']}
    AND (s.code IN ({$alertTypes}) OR a.forced = 1) ORDER BY a.id DESC LIMIT {$start}, {$limit};
SQL;

                $query = $this->db->write_query($alertsQuery);

                if($this->db->num_rows($query) > 0) {
                    $return = array();
                    while($alertRow = $this->db->fetch_array($query))
                    {
                        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
                        $alertType->setCode($alertRow['code']);
                        $alertType->setId($alertRow['alert_type']);
                        $alert = new MybbStuff_MyAlerts_Entity_Alert($alertRow['uid'], $alertType, $alertRow['object_id']);
                        $alert->setId($alertRow['id']);
                        $alert->setCreatedAt(new DateTime($alertRow['dateline']));
                        $alert->setUnread((bool) $alertRow['unread']);
                        $alert->setExtraDetails(json_decode($alertRow['extra_details'], true));

                        $user = array(
                            'uid' => (int) $alertRow['uid'],
                            'username' => $alertRow['username'],
                            'avatar' => $alertRow['avatar'],
                            'usergroup' => $alertRow['usergroup'],
                            'displaygroup' => $alertRow['displaygroup'],
                        );

                        $alert->setFromUser($user);

                        $alerts[]         = $alert;
                    }
                }
            }
        }
        else
        {
            throw new Exception('Guests have not got access to the Alerts functionality');
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
        if((int)$this->mybb->user['uid'] > 0)
        { // check the user is a user and not a guest - no point wasting queries on guests afterall
            if (!empty($this->currentUserEnabledAlerts)) {
                $alertTypes = $this->getAlertTypesForIn();

                $this->mybb->user['uid'] = (int)$this->mybb->user['uid'];
                $prefix                  = TABLE_PREFIX;
                $alertsQuery             = <<<SQL
    SELECT a.*, u.uid, u.username, u.avatar, u.usergroup, u.displaygroup FROM {$prefix}alerts a
    INNER JOIN {$prefix}users u ON (a.from_user_id = u.uid)
    INNER JOIN {$prefix}alert_settings s ON (a.alert_type = s.id)
    WHERE a.uid = {$this->mybb->user['uid']} AND a.unread = 1
    AND (s.code IN ({$alertTypes}) OR a.forced = 1) ORDER BY a.id DESC;
SQL;

                $query = $this->db->write_query($alertsQuery);

                if($this->db->num_rows($query) > 0)
                {
                    while($alertRow = $this->db->fetch_array($query))
                    {
                        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
                        $alertType->setCode($alertRow['code']);
                        $alertType->setId($alertRow['alert_type']);
                        $alert = new MybbStuff_MyAlerts_Entity_Alert($alertRow['uid'], $alertType, $alertRow['object_id']);
                        $alert->setId($alertRow['id']);
                        $alert->setCreatedAt(new DateTime($alertRow['dateline']));
                        $alert->setUnread((bool) $alertRow['unread']);
                        $alert->setExtraDetails(json_decode($alertRow['extra_details'], true));

                        $user = array(
                            'uid' => (int) $alertRow['uid'],
                            'username' => $alertRow['username'],
                            'avatar' => $alertRow['avatar'],
                            'usergroup' => $alertRow['usergroup'],
                            'displaygroup' => $alertRow['displaygroup'],
                        );

                        $alert->setFromUser($user);

                        $alerts[]         = $alert;
                    }
                }
            }
        }
        else
        {
            throw new Exception('Guests have not got access to the Alerts functionality');
        }

        return $alerts;
    }

    /**
     *  Mark alerts as read.
     *
     * @param array Either a string formatted for use in a MySQL IN() clause or an array to be parsed into said form.
     * @return bool Whether the alerts were marked read successfully.
     */
    public function markRead(array $alerts = array())
    {
        $alerts = (array) $alerts;

        $success = true;

        if(is_array($alerts) && !empty($alerts))
        {
            $alerts = array_map('intval', $alerts);
            $alerts = "'".implode("','", $alerts)."'";

            $success = (bool) $this->db->update_query(
                'alerts', array(
                    'unread' => '0'
                ), 'id IN('.$alerts.') AND uid = '.$this->mybb->user['uid']
            );
        }

        return $success;
    }

    /**
     *  Delete alerts.
     *
     * @param array Either a string formatted for use in a MySQL IN() clause or an array to be parsed into said form.
     * @return bool Whether the alerts were deleted successfully.
     */
    public function deleteAlerts(array $alerts = array())
    {
        $success = true;

        if(is_array($alerts) OR is_int($alerts))
        {
            $alerts = (array) $alerts;

            if (!empty($alerts)) {
                $alerts = array_map('intval', $alerts);
                $alerts = "'".implode("','", $alerts)."'";

                $success = (bool) $this->db->delete_query('alerts', 'id IN('.$alerts.') AND uid = '.(int)$this->mybb->user['uid']);
            }
        }
        else
        {
            if($alerts == 'allRead')
            {
                $success = (bool) $this->db->delete_query('alerts', 'unread = 0 AND uid = '.(int)$this->mybb->user['uid']);
            }
            elseif($alerts = 'allAlerts')
            {
                $success = (bool) $this->db->delete_query('alerts', 'uid = '.(int)$this->mybb->user['uid']);
            }
        }

        return $success;
    }

    /**
     * Filter the current user's enabled alerts array and format it so that it is an array of just alert type codes that are enabled.
     *
     * @return array The filtered array.
     */
    private function filterEnabledAlerts(array $alertSettings = null)
    {
        return array_keys(array_filter($alertSettings));
    }

    /**
     * Gets the enabled alert types for the current user ready to be used in a MySQL IN() call.
     *
     * @return string The formatted string of alert types enabled for the user.
     */
    private function getAlertTypesForIn()
    {
        $alertTypes = array_map(array($this->db, 'escape_string'), $this->currentUserEnabledAlerts);
        $alertTypes = "'".implode("','", $alertTypes)."'";
        return $alertTypes;
    }
}
