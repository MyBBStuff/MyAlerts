<?php

/**
 * A user's setting for a single alert type as represented int eh database.
 *
 * @package MybbStuff\MyAlerts\Entity
 */
class MybbStuff_MyAlerts_Entity_UserAlertTypeSetting
{
	/** @var int The ID of the user/alert type mapping in the database. */
	private $id;

	/** @var int The alert type this mapping applies to. */
	private $alertType;

	/** @var int The ID of the user this mapping applies to. */
	private $userId;

	/** @var bool Whether the alert type is enabled. */
	private $enabled = true;

	/**
	 * @param int|MybbStuff_MyAlerts_Entity_AlertType $alertType
	 */
	public function setAlertType($alertType)
	{
		if ($alertType instanceof AlertType) {
			$this->alertType = $alertType->getId();
		} else {
			$this->alertType = (int) $alertType;
		}
	}

	/**
	 * @return int
	 */
	public function getAlertType()
	{
		return $this->alertType;
	}

	/**
	 * @param boolean $enabled
	 */
	public function setEnabled($enabled)
	{
		$this->enabled = (boolean) $enabled;
	}

	/**
	 * @return boolean
	 */
	public function getEnabled()
	{
		return $this->enabled;
	}

	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = (int) $id;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int|array $userId
	 */
	public function setUserId($userId)
	{
		if (is_array($userId)) {
			$this->userId = (int) $userId['uid'];
		} else {
			$this->userId = (int) $userId;
		}
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}
} 
