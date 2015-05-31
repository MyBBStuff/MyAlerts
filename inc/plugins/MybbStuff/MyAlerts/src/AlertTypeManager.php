<?php

/**
 * Manager class for alert types.
 */
class MybbStuff_MyAlerts_AlertTypeManager
{
    const CACHE_NAME = 'mybbstuff_myalerts_alert_types';
    /** @var MybbStuff_MyAlerts_AlertTypeManager */
    private static $instance = null;
    /** @var array */
    private $alertTypes = array();
    /** @var DB_Base */
    private $db;
    /** @var datacache */
    private $cache;

    private function __construct(DB_Base $db, datacache $cache)
    {
        $this->db = $db;
        $this->cache = $cache;

        $this->getAlertTypes();
    }

    /**
     * Get all of the alert types in the system.
     *
     * Alert types are both stored in the private $alertTypes variable and are also returned for usage.
     *
     * @param bool $forceDatabase Whether to force the reading of alert types from the database.
     *
     * @return array All of the alert types currently in the system.
     */
    public function getAlertTypes($forceDatabase = false)
    {
        $forceDatabase = (bool) $forceDatabase;

        $this->alertTypes = array();

        if (!($cachedAlertTypes = $this->cache->read(self::CACHE_NAME)) || $forceDatabase) {
            $this->alertTypes = $this->loadAlertTypes();
            $this->cache->update(self::CACHE_NAME, $this->alertTypes);
        } else {
            $this->alertTypes = $cachedAlertTypes;
        }

        return $this->alertTypes;
    }

    /**
     * Load all of the alert types currently in the system from the database. Should only be used to refresh the cache.
     *
     * @return MybbStuff_MyAlerts_Entity_AlertType[] All of the alert types currently in the database.
     */
    private function loadAlertTypes()
    {
        $tablePrefix = TABLE_PREFIX;
        $queryString = "SELECT * FROM {$tablePrefix}alert_types;";

        $query = $this->db->write_query($queryString);

        $alertTypes = array();

        if ($this->db->num_rows($query) > 0) {
            while ($row = $this->db->fetch_array($query)) {
                $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
                $alertType->setId($row['id']);
                $alertType->setCode($row['code']);
                $alertType->setEnabled((int) $row['enabled'] == 1);
                $alertType->setCanBeUserDisabled((int) $row['can_be_user_disabled'] == 1);

                $alertTypes[$row['code']] = $alertType->toArray();
            }
        }

        return $alertTypes;
    }

    /**
     * Create an instance of the alert type manager.
     *
     * @param DB_Base $db    MyBB database object.
     * @param datacache          $cache MyBB cache object.
     *
     * @return MybbStuff_MyAlerts_AlertTypeManager The created instance.
     */
    public static function createInstance(DB_Base $db, datacache $cache)
    {
        if (static::$instance === null) {
            static::$instance = new self($db, $cache);
        }

        return static::$instance;
    }

    /**
     * Get a prior created instance of the alert type manager. @see createInstance().
     *
     * @return bool|MybbStuff_MyAlerts_AlertTypeManager The prior created instance, or false if not already instantiated.
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            return false;
        }

        return static::$instance;
    }

    /**
     * @param MybbStuff_MyAlerts_Entity_AlertType $alertType
     *
     * @return bool Whether the alert type was added successfully.
     */
    public function add(MybbStuff_MyAlerts_Entity_AlertType $alertType)
    {
        $success = (bool) $this->db->insert_query('alert_types', $alertType->toArray());

        $this->getAlertTypes(true);

        return $success;
    }

    /**
     * Add multiple alert types.
     *
     * @param MybbStuff_MyAlerts_Entity_AlertType[] $alertTypes AN array of alert types to add.
     *
     * @return bool Whether the alert types were added successfully.
     */
    public function addTypes(array $alertTypes)
    {
        $toInsert = array();

        foreach ($alertTypes as $alertType) {
            if ($alertType instanceof MybbStuff_MyAlerts_Entity_AlertType) {
                $toInsert[] = $alertType->toArray();
            }
        }

        $success = (bool) $this->db->insert_query_multiple('alert_types', $toInsert);

        $this->getAlertTypes(true);

        return $success;
    }

    /**
     * Update a set of alert types to change their enabled/disabled status.
     *
     * @param MybbStuff_MyAlerts_Entity_AlertType[] $alertTypes An array of alert types to update.
     */
    public function updateAlertTypes(array $alertTypes)
    {
        foreach ($alertTypes as $alertType) {
            $updateArray = array(
                'enabled' => (int) $alertType->getEnabled(),
                'can_be_user_disabled' => (int) $alertType->getCanBeUserDisabled(),
            );

            $this->db->update_query('alert_types', $updateArray, "id = {$alertType->getId()}");
        }

        // Flush the cache
        $this->getAlertTypes(true);
    }

    /**
     * Delete an alert type by the unique code assigned to it.
     *
     * @param string $code The unique code for the alert type.
     *
     * @return bool Whether the alert type was deleted.
     */
    public function deleteByCode($code = '')
    {
        $alertType = $this->getByCode($code);

        if ($alertType !== null) {
            return $this->deleteById($alertType->getId());
        }

        return false;
    }

    /**
     * Get an alert type by it's code.
     *
     * @param string $code The code of the alert type to fetch.
     *
     * @return MybbStuff_MyAlerts_Entity_AlertType|null The found alert type or null if it doesn't exist (hasn't yet
     *                                                  been registered).
     */
    public function getByCode($code = '')
    {
        $code = (string) $code;

        $alertType = null;

        if (isset($this->alertTypes[$code])) {
            $alertType = MybbStuff_MyAlerts_Entity_AlertType::unserialize($this->alertTypes[$code]);
        }

        return $alertType;
    }

    /**
     * Delete an alert type by ID.
     *
     * @param int $id The ID of the alert type.
     *
     * @return bool Whether the alert type was deleted.
     */
    public function deleteById($id = 0)
    {
        $id = (int) $id;

        $queryResult = (bool) $this->db->delete_query('alert_types', "id = {$id}");

        $this->getAlertTypes(true);

        return $queryResult;
    }
}
