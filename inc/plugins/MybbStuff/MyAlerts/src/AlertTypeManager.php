<?php

declare(strict_types=1);

/**
 * Manager class for alert types.
 */
class MybbStuff_MyAlerts_AlertTypeManager
{
	public const CACHE_NAME = 'mybbstuff_myalerts_alert_types';

	/** @var MybbStuff_MyAlerts_AlertTypeManager */
	private static MybbStuff_MyAlerts_AlertTypeManager $instance;

	/** @var array */
	private array $alertTypes = array();

	/** @var DB_Base */
	private DB_Base $db;

	/** @var datacache */
	private datacache $cache;

	private function __construct(DB_Base $db, datacache $cache)
	{
		$this->db = $db;
		$this->cache = $cache;

		$this->getAlertTypes();
	}

	/**
	 * Get all the alert types in the system.
	 *
	 * Alert types are both stored in the private $alertTypes variable and are
	 * also returned for usage.
	 *
	 * @param bool $forceDatabase Whether to force the reading of alert types
	 *                            from the database.
	 *
	 * @return array All the alert types currently in the system.
	 */
	public function getAlertTypes(bool $forceDatabase = false): array
	{
		$this->alertTypes = array();

		if (($cachedAlertTypes = $this->cache->read(self::CACHE_NAME)) === false || $forceDatabase) {
			$this->alertTypes = $this->loadAlertTypes();
			$this->cache->update(self::CACHE_NAME, $this->alertTypes);
		} else {
			$this->alertTypes = $cachedAlertTypes;
		}

		return $this->alertTypes;
	}

	/**
	 * Load all the alert types currently in the system from the database.
	 * Should only be used to refresh the cache.
	 *
	 * @return MybbStuff_MyAlerts_Entity_AlertType[] All the alert types currently in the database.
	 */
	private function loadAlertTypes(): array
	{
		$query = $this->db->simple_select('alert_types', '*');

		$alertTypes = array();

		while ($row = $this->db->fetch_array($query)) {
			$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
			$alertType->setId($row['id']);
			$alertType->setCode($row['code']);
			$alertType->setEnabled((int) $row['enabled'] == 1);
			$alertType->setCanBeUserDisabled(
				(int) $row['can_be_user_disabled'] == 1
			);
			$alertType->setDefaultUserEnabled(
				(int) $row['default_user_enabled'] == 1
			);

			$alertTypes[$row['code']] = $alertType->toArray();
		}

		return $alertTypes;
	}

	/**
	 * Create an instance of the alert type manager.
	 *
	 * @param DB_Base   $db    MyBB database object.
	 * @param datacache $cache MyBB cache object.
	 *
	 * @return MybbStuff_MyAlerts_AlertTypeManager The created instance.
	 */
	public static function createInstance(DB_Base $db, datacache $cache): self
	{
		if (!isset(static::$instance)) {
			static::$instance = new self($db, $cache);
		}

		return static::$instance;
	}

	/**
	 * Get a prior created instance of the alert type manager. @see createInstance().
	 *
	 * @return self The prior created instance, or false if not already instantiated.
	 */
	public static function getInstance(): self
	{
		if(!isset(static::$instance)) {
			throw new RuntimeException('Alert type manager has not been created yet.');
		}

		return static::$instance;
	}

	/**
	 * @param MybbStuff_MyAlerts_Entity_AlertType $alertType
	 *
	 * @return bool Whether the alert type was added successfully.
	 */
	public function add(MybbStuff_MyAlerts_Entity_AlertType $alertType): bool
	{
		$success = true;

		if (!isset($this->alertTypes[$alertType->getCode()])) {
			$insertArray = $alertType->toArray();

			if (isset($insertArray['id'])) {
				unset($insertArray['id']);
			}

			$success = (bool) $this->db->insert_query(
				'alert_types',
				$insertArray
			);

			$this->getAlertTypes(true);
		}

		return $success;
	}

	/**
	 * Add multiple alert types.
	 *
	 * @param MybbStuff_MyAlerts_Entity_AlertType[] $alertTypes AN array of
	 *                                                          alert types to
	 *                                                          add.
	 *
	 * @return bool Whether the alert types were added successfully.
	 */
	public function addTypes(array $alertTypes): bool
	{
		$toInsert = array();
		$success = true;

		foreach ($alertTypes as $alertType) {
			if ($alertType instanceof MybbStuff_MyAlerts_Entity_AlertType) {
				if (!isset($this->alertTypes[$alertType->getCode()])) {
					$insertArray = $alertType->toArray();

					if (isset($insertArray['id'])) {
						unset($insertArray['id']);
					}

					$toInsert[] = $insertArray;
				}
			}
		}

		if (!empty($toInsert)) {
			try{
				$this->db->insert_query_multiple(
					'alert_types',
					$toInsert
				);
			} catch (Exception $e) {
				$success = false;
			}
		}

		$this->getAlertTypes(true);

		return $success;
	}

	/**
	 * Update a set of alert types to change their enabled/disabled status.
	 *
	 * @param MybbStuff_MyAlerts_Entity_AlertType[] $alertTypes An array of
	 *                                                          alert types to
	 *                                                          update.
	 */
	public function updateAlertTypes(array $alertTypes): void
	{
		foreach ($alertTypes as $alertType) {
			if (!($alertType instanceof MybbStuff_MyAlerts_Entity_AlertType)) {
				continue;
			}

			$updateArray = array(
				'enabled'              => (int) $alertType->getEnabled(),
				'can_be_user_disabled' => (int) $alertType->getCanBeUserDisabled(),
				'default_user_enabled' => (int) $alertType->getDefaultUserEnabled(),
			);

			$id = $alertType->getId();

			$this->db->update_query(
				'alert_types',
				$updateArray,
				"id = {$id}"
			);
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
	 * @throws Exception
	 */
	public function deleteByCode(string $code = ''): bool
	{
		$alertType = $this->getByCode($code);

		return $this->deleteById($alertType->getId());
	}

	/**
	 * Get an alert type by its code.
	 *
	 * @param string $code The code of the alert type to fetch.
	 *
	 * @return MybbStuff_MyAlerts_Entity_AlertType The found alert type or null if it doesn't exist (hasn't yet been registered).
	 * @throws Exception
	 */
	public function getByCode(string $code = ''): \MybbStuff_MyAlerts_Entity_AlertType
	{
		if (!isset($this->alertTypes[$code])) {
			throw new Exception("Alert type with code '{$code}' does not exist.");
		}

		return MybbStuff_MyAlerts_Entity_AlertType::unserialize(
			$this->alertTypes[$code]
		);
	}

	/**
	 * Delete an alert type by ID.
	 *
	 * @param int $id The ID of the alert type.
	 *
	 * @return bool Whether the alert type was deleted.
	 */
	public function deleteById(int $id = 0): bool
	{
		$queryResult = (bool) $this->db->delete_query(
			'alert_types',
			"id = {$id}"
		);

		$this->getAlertTypes(true);

		return $queryResult;
	}
}
