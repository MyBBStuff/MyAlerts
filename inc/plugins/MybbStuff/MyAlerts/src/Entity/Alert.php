<?php

declare(strict_types=1);

/**
 * A single alert object as it's represented in the database.
 *
 * @package MybbStuff\MyAlerts\Entity
 */
class MybbStuff_MyAlerts_Entity_Alert
{
	/** @var int The ID of the alert. */
	private int $id = 0;
	/** @var array The details of the user that sent the alert. */
	private array $fromUser = array();
	/** @var int The ID of the user this alert is from. */
	private int $fromUserId;
	/** @var int The ID of the user this alert is for. */
	private int $userId;
	/** @var int The ID of the type of alert this is. */
	private int $typeId;
	/** @var MybbSTuff_MyAlerts_Entity_AlertType The type of the alert. */
	private MybbSTuff_MyAlerts_Entity_AlertType $type;
	/** @var int The ID of the object this alert is linked to. */
	private int $objectId;
	/** @var DateTime The date/time this alert was created at. */
	private DateTime $createdAt;
	/** @var bool Whether the alert is unread. */
	private bool $unread = true;
	/** @var array Any extra details for the alert. */
	private array $extraDetails = array();

	/**
	 * Initialize a new Alert instance.
	 *
	 * @param int                                      $user     The ID
	 *                                                                 of the
	 *                                                                 user
	 *                                                                 this
	 *                                                                 alert is
	 *                                                                 for.
	 * @param MybbSTuff_MyAlerts_Entity_AlertType $type     The ID
	 *                                                                 of the
	 *                                                                 object
	 *                                                                 this
	 *                                                                 alert is
	 *                                                                 linked
	 *                                                                 to.
	 *                                                                 Optionally
	 *                                                                 pass in
	 *                                                                 an
	 *                                                                 AlertType object or the short code name of the alert type.
	 * @param int                                            $objectId The ID
	 *                                                                 of the
	 *                                                                 object
	 *                                                                 this
	 *                                                                 alert is
	 *                                                                 linked
	 *                                                                 to.
	 */
	public function __construct(int $user, \MybbStuff_MyAlerts_Entity_AlertType $type, int $objectId = 0)
	{
		$this->userId =  $user;

		$this->setType($type);

		if ($objectId) {
			$this->objectId = $objectId;
		}

		$this->createdAt = new DateTime();
	}

	/**
	 * Create an alert object with the given details.
	 *
	 * @param int $userID         The ID of the user this alert is for.
	 * @param MybbStuff_MyAlerts_Entity_AlertType $type         The ID of the object this alert is linked to.
	 * @param int $objectId     The ID of the object this alert is linked to.
	 * @param array $extraDetails An array of optional extra details to be stored with the alert.
	 *
	 * @return MybbStuff_MyAlerts_Entity_Alert The created alert object.
	 */
	public static function make(
		int $userID,
		MybbStuff_MyAlerts_Entity_AlertType $type,
		int $objectId = 0,
		array $extraDetails = array()
	): MybbStuff_MyAlerts_Entity_Alert {
		$alert = new static($userID, $type, $objectId);

		$alert->setExtraDetails($extraDetails);

		return $alert;
	}

	/**
	 * @return int
	 */
	public function getId():int
	{
		return $this->id;
	}

	/**
	 * @param int $id The ID to set.
	 *
	 * @return MybbStuff_MyAlerts_Entity_Alert $this
	 */
	public function setId(int $id): MybbStuff_MyAlerts_Entity_Alert
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Convert an alert object into an array ready to be inserted into the
	 * database.
	 *
	 * @return array Array representation of the Alert.
	 */
	public function toArray(): array
	{
		return array(
			'uid'           => $this->getUserId(),
			'from_user_id'  => $this->getFromUserId(),
			'alert_type_id' => $this->getTypeId(),
			'object_id'     => $this->getObjectId(),
			'dateline'      => $this->getCreatedAt()->format('Y-m-d H:i:s'),
			'extra_details' => json_encode($this->getExtraDetails()),
			'unread'        => (int) $this->getUnread(),
		);
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		return $this->userId;
	}

	/**
	 * @param int $userId The user ID to set.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setUserId(int $userId): MybbStuff_MyAlerts_Entity_Alert
	{
		$this->userId = $userId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getFromUserId(): int
	{
		return $this->fromUserId;
	}

	/**
	 * @param int $fromUserId The form user ID to set.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setFromUserId(int $fromUserId): MybbStuff_MyAlerts_Entity_Alert
	{
		$this->fromUserId = $fromUserId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTypeId(): int
	{
		return $this->typeId;
	}

	/**
	 * @param int $typeId The ID of the alert type.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setTypeId(int $typeId): MybbStuff_MyAlerts_Entity_Alert
	{
		$this->typeId = $typeId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getObjectId(): int
	{
		return $this->objectId;
	}

	/**
	 * @param int $objectId The ID of the object this alert relates to.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setObjectId(int $objectId = 0): MybbStuff_MyAlerts_Entity_Alert
	{
		$this->objectId = $objectId;

		return $this;
	}

	/**
	 * @return DateTime
	 */
	public function getCreatedAt(): DateTime
	{
		return $this->createdAt;
	}

	/**
	 * @param DateTime $createdAt The date the alert was created at.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setCreatedAt(DateTime $createdAt): MybbStuff_MyAlerts_Entity_Alert
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getExtraDetails(): array
	{
		return $this->extraDetails;
	}

	/**
	 * @param array $extraDetails Extra details about the alert.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setExtraDetails(array $extraDetails = array()): MybbStuff_MyAlerts_Entity_Alert
	{
		$this->extraDetails = $extraDetails;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getUnread(): bool
	{
		return $this->unread;
	}

	/**
	 * @param bool $unread Whether the alert is unread.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setUnread(bool $unread = true): MybbStuff_MyAlerts_Entity_Alert
	{
		$this->unread = $unread;

		return $this;
	}

	/**
	 * @return MybbSTuff_MyAlerts_Entity_AlertType The type of alert this is.
	 */
	public function getType(): \MybbSTuff_MyAlerts_Entity_AlertType
	{
		return $this->type;
	}

	/**
	 * @param MybbStuff_MyAlerts_Entity_AlertType $type The alert type to set.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setType(MybbStuff_MyAlerts_Entity_AlertType $type): MybbStuff_MyAlerts_Entity_Alert
	{
		$this->type = $type;
		$this->setTypeId($type->getId());

		return $this;
	}

	/**
	 * Get the user who sent the alert's details.
	 */
	public function getFromUser(): array
	{
		return $this->fromUser;
	}

	/**
	 * @param array $user The user array of the user sending the alert.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setFromUser(array $user = array()): MybbStuff_MyAlerts_Entity_Alert
	{
		$this->fromUser = $user;
		$this->setFromUserId($user['uid']);

		return $this;
	}
}
