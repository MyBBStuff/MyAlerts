<?php

/**
 * A single alert type object as it's represented in the database.
 *
 * @package MybbStuff\MyAlerts\Entity
 */
class MybbStuff_MyAlerts_Entity_AlertType
{
	/** @var int The ID of the alert type. */
	private $id = 0;
	/** @var string The short code identifying the alert type - eg: 'pm', 'rep'. */
	private $code = '';
	/** @var bool Whether the alert type is enabled. */
	private $enabled = true;
	/** @var bool Whether this alert type can be disabled by users. */
	private $canBeUserDisabled = true;
	/** @var bool Whether this alert type is enabled for users by default. */
	private $defaultUserEnabled = true;

	/**
	 * Unserialize an alert type from an array created using toArray().
	 *
	 * @param array $serialized The serialized alert type.
	 *
	 * @return MybbStuff_MyAlerts_Entity_AlertType The unserialized alert type.
	 */
	public static function unserialize(array $serialized)
	{
		$serialized = array_merge(
			array(
				'id'                   => 0,
				'code'                 => '',
				'enabled'              => false,
				'can_be_user_disabled' => false,
				'default_user_enabled' => false,
			),
			$serialized
		);

		$alertType = new static();
		$alertType->setEnabled($serialized['enabled']);
		$alertType->setId($serialized['id']);
		$alertType->setCode($serialized['code']);
		$alertType->setCanBeUserDisabled($serialized['can_be_user_disabled']);
		$alertType->setDefaultUserEnabled($serialized['default_user_enabled']);

		return $alertType;
	}

	/**
	 * Serialize the alert type to an array.
	 *
	 * @return array The seralized alert type.
	 */
	public function toArray()
	{
		return array(
			'id'                   => $this->getId(),
			'code'                 => $this->getCode(),
			'enabled'              => (int) $this->getEnabled(),
			'can_be_user_disabled' => (int) $this->getCanBeUserDisabled(),
			'default_user_enabled' => (int) $this->getDefaultUserEnabled(),
		);
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 *
	 * @return MybbStuff_Myalerts_Entity_AlertType $this.
	 */
	public function setId($id = 0)
	{
		$this->id = (int) $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param string $code The code for the alet type.
	 *
	 * @return MybbStuff_Myalerts_Entity_AlertType $this.
	 */
	public function setCode($code)
	{
		$this->code = (string) $code;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getEnabled()
	{
		return $this->enabled;
	}

	/**
	 * @param boolean $enabled Whether the alert type is enabled.
	 *
	 * @return MybbStuff_Myalerts_Entity_AlertType $this.
	 */
	public function setEnabled($enabled = true)
	{
		$this->enabled = (bool) $enabled;

		return $this;
	}

	/**
	 * @return boolean Whether this alert type can be disabled by users.
	 */
	public function getCanBeUserDisabled()
	{
		return $this->canBeUserDisabled;
	}

	/**
	 * @return boolean Whether this alert type is enabled for users by default.
	 */
	public function getDefaultUserEnabled()
	{
		return $this->defaultUserEnabled;
	}

	/**
	 * @param boolean $canBeUserDisabled Whether this alert type can be
	 *                                   disabled by users.
	 *
	 * @return $this
	 */
	public function setCanBeUserDisabled($canBeUserDisabled = true)
	{
		$this->canBeUserDisabled = (bool) $canBeUserDisabled;

		return $this;
	}

	/**
	 * @param boolean $defaultUserEnabled Whether this alert type is
	 *                                   enabled for users by default.
	 *
	 * @return $this
	 */
	public function setDefaultUserEnabled($defaultUserEnabled = true)
	{
		$this->defaultUserEnabled = (bool) $defaultUserEnabled;

		return $this;
	}
}
