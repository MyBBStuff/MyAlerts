<?php

/**
 * Manager class for alert types.
 */
class MybbStuff_MyAlerts_AlertTypeManager
{
	/** @var MybbStuff_MyAlerts_Entity_AlertType[] */
	private $alertTypes = array();
	/** @var DB_MySQLi */
	private $db;
	/** @var datacache */
	private $cache;

	public function __construct(DB_MySQLi $db, datacache $cache)
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
	 * @return MybbStuff_MyAlerts_Entity_AlertType[] All of the alert types currently in the system.
	 */
	public function getAlertTypes($forceDatabase = false)
	{
        $forceDatabase = (bool) $forceDatabase;

		$this->alertTypes = array();

		if (!($cachedAlertTypes = $this->cache->read('mybbstuff_myalerts_alert_types')) || $forceDatabase) {
            $this->alertTypes = $this->loadAlertTypes();
            $this->cache->update('mybbstuff_myalerts_alert_types', $this->alertTypes);
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
		$queryString = <<<SQL
SELECT * FROM {$tablePrefix}alert_types;
SQL;

		$query = $this->db->write_query($queryString);

        $alertTypes = array();

		if ($this->db->num_rows($query) > 0) {
			while ($row = $this->db->fetch_array($query)) {
				$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
				$alertType->setId($row['id']);
				$alertType->setCode($row['code']);
				$alertType->setEnabled((int) $row['enabled'] == 1);

                $alertTypes[$row['code']] = $alertType->toArray();
			}
		}

        return $alertTypes;
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
     * Get an alert type by it's code.
     *
     * @param string $code The code of the alert type to fetch.
     *
     * @return MybbStuff_MyAlerts_Entity_AlertType|null The found alert type or null if it doesn't exist (hasn't yet been registered).
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
} 
