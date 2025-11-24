<?php

declare(strict_types=1);

/**
 * A single alert type object as it's represented in the database.
 *
 * @package MybbStuff\MyAlerts\Entity
 */
class MybbStuff_MyAlerts_Entity_AlertType
{
	/** @var int The ID of the alert type. */
	private int $id = 0;
	/** @var string The short code identifying the alert type - eg: 'pm', 'rep'. */
	private string $code = '';
	/** @var bool Whether the alert type is enabled. */
	private bool $enabled = true;
	/** @var bool Whether this alert type can be disabled by users. */
	private bool $canBeUserDisabled = true;
	/** @var bool Whether this alert type is enabled for users by default. */
	private bool $defaultUserEnabled = true;

	/**
	 * Unserialize an alert type from an array created using toArray().
	 *
	 * @param array $serialized The serialized alert type.
	 *
	 * @return MybbStuff_MyAlerts_Entity_AlertType The unserialised alert type.
	 */
	public static function unserialize(array $serialized): MybbStuff_MyAlerts_Entity_AlertType
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
	 * @return array The serialized alert type.
	 */
	public function toArray(): array
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
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 *
	 * @return MybbStuff_Myalerts_Entity_AlertType $this.
	 */
	public function setId(int $id = 0): MybbStuff_Myalerts_Entity_AlertType
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * @param string $code The code for the alet type.
	 *
	 * @return MybbStuff_Myalerts_Entity_AlertType $this.
	 */
	public function setCode(string $code): static
	{
		$this->code = $code;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getEnabled(): bool
	{
		return $this->enabled;
	}

	/**
	 * @param bool $enabled Whether the alert type is enabled.
	 *
	 * @return MybbStuff_Myalerts_Entity_AlertType $this.
	 */
	public function setEnabled(bool $enabled = true): static
	{
		$this->enabled = $enabled;

		return $this;
	}

	/**
	 * @return bool Whether this alert type can be disabled by users.
	 */
	public function getCanBeUserDisabled(): bool
	{
		return $this->canBeUserDisabled;
	}

	/**
	 * @return bool Whether this alert type is enabled for users by default.
	 */
	public function getDefaultUserEnabled(): bool
	{
		return $this->defaultUserEnabled;
	}

	/**
	 * @param bool $canBeUserDisabled Whether this alert type can be
	 *                                   disabled by users.
	 *
	 * @return $this
	 */
	public function setCanBeUserDisabled(bool $canBeUserDisabled = true): MybbStuff_MyAlerts_Entity_AlertType
	{
		$this->canBeUserDisabled = $canBeUserDisabled;

		return $this;
	}

	/**
	 * @param bool $defaultUserEnabled Whether this alert type is
	 *                                   enabled for users by default.
	 *
	 * @return $this
	 */
	public function setDefaultUserEnabled(bool $defaultUserEnabled = true): MybbStuff_MyAlerts_Entity_AlertType
	{
		$this->defaultUserEnabled = $defaultUserEnabled;

		return $this;
	}
}
