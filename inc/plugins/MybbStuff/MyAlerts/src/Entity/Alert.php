<?php

/**
 * A single alert object as it's represented in the database.
 *
 * @package MybbStuff\MyAlerts\Entity
 */
class MybbStuff_MyAlerts_Entity_Alert
{
	/** @var int The ID of the alert. */
	private $id = 0;
	/** @var array The details of the user that sent the alert. */
	private $fromUser = array();
	/** @var int The ID of the user this alert is from. */
	private $fromUserId;
	/** @var int The ID of the user this alert is for. */
	private $userId;
	/** @var int|string The ID of the type of alert this is. */
	private $typeId;
	/** @var MybbSTuff_MyAlerts_Entity_AlertType The type of the alert. */
	private $type = null;
	/** @var int The ID of the object this alert is linked to. */
	private $objectId = null;
	/** @var \DateTime The date/time this alert was created at. */
	private $createdAt;
	/** @var bool Whether the alert is unread. */
	private $unread = true;
	/** @var array Any extra details for the alert. */
	private $extraDetails = array();

	/**
	 * Initialise a new Alert instance.
	 *
	 * @param int|array                                      $user     The ID
	 *                                                                 of the
	 *                                                                 user
	 *                                                                 this
	 *                                                                 alert is
	 *                                                                 for.
	 * @param int|MybbSTuff_MyAlerts_Entity_AlertType|string $type     The ID
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
	public function __construct($user = 0, $type = 0, $objectId = null)
	{
		if (is_array($user)) {
			$this->userId = (int) $user['uid'];
		} else {
			$this->userId = (int) $user;
		}

		if ($type instanceof MybbStuff_MyAlerts_Entity_AlertType) {
			$this->setType($type);
		} else {
			$this->setTypeId($type);
		}

		if (isset($objectId)) {
			$this->objectId = (int) $objectId;
		}

		$this->createdAt = new \DateTime();
	}

	/**
	 * Create an alert object with the given details.
	 *
	 * @param int|array                               $user         The ID of
	 *                                                              the user
	 *                                                              this alert
	 *                                                              is for.
	 * @param int|MybbStuff_MyAlerts_Entity_AlertType $type         The ID of
	 *                                                              the object
	 *                                                              this alert
	 *                                                              is linked
	 *                                                              to.
	 * @param int                                     $objectId     The ID of
	 *                                                              the object
	 *                                                              this alert
	 *                                                              is linked
	 *                                                              to.
	 * @param array                                   $extraDetails An array of
	 *                                                              optional
	 *                                                              extra
	 *                                                              details to
	 *                                                              be stored
	 *                                                              with the
	 *                                                              alert.
	 *
	 * @return MybbStuff_MyAlerts_Entity_Alert The created alert object.
	 */
	public static function make(
		$user = 0,
		$type = 0,
		$objectId = null,
		array $extraDetails = array()
	) {
		/** @var MybbStuff_MyAlerts_Entity_Alert $alert */
		$alert = new static($user, $type, $objectId);

		$alert->setExtraDetails($extraDetails);

		return $alert;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $id The ID to set.
	 *
	 * @return MybbStuff_MyAlerts_Entity_Alert $this
	 */
	public function setId($id)
	{
		$this->id = (int) $id;

		return $this;
	}

	/**
	 * Convert an alert object into an array ready to be inserted into the
	 * database.
	 *
	 * @return array Array representation of the Alert.
	 */
	public function toArray()
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
	public function getUserId()
	{
		return (int) $this->userId;
	}

	/**
	 * @param int $userId The user ID to set.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setUserId($userId)
	{
		$this->userId = (int) $userId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getFromUserId()
	{
		return (int) $this->fromUserId;
	}

	/**
	 * @param int $fromUserId The from user ID to set.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setFromUserId($fromUserId)
	{
		$this->fromUserId = (int) $fromUserId;

		return $this;
	}

	/**
	 * @return int|string
	 */
	public function getTypeId()
	{
		return $this->typeId;
	}

	/**
	 * @param int $typeId The ID of the alert type.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setTypeId($typeId)
	{
		$this->typeId = (int) $typeId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getObjectId()
	{
		return (int) $this->objectId;
	}

	/**
	 * @param int $objectId The ID of the object this alert relates to.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setObjectId($objectId = 0)
	{
		$this->objectId = (int) $objectId;

		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreatedAt()
	{
		return $this->createdAt;
	}

	/**
	 * @param \DateTime $createdAt The date the alert was created at.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setCreatedAt(DateTime $createdAt)
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getExtraDetails()
	{
		return $this->extraDetails;
	}

	/**
	 * @param array $extraDetails Extra details about the alert.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setExtraDetails(array $extraDetails = array())
	{
		$this->extraDetails = $extraDetails;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getUnread()
	{
		return (boolean) $this->unread;
	}

	/**
	 * @param boolean $unread Whether the alert is unread.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setUnread($unread = true)
	{
		$this->unread = (boolean) $unread;

		return $this;
	}

	/**
	 * @return MybbSTuff_MyAlerts_Entity_AlertType The type of alert this is.
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param MybbStuff_MyAlerts_Entity_AlertType $type The alert type to set.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setType(MybbStuff_MyAlerts_Entity_AlertType $type)
	{
		$this->type = $type;
		$this->setTypeId($type->getId());

		return $this;
	}

	/**
	 * Get the user who sent the alert's details.
	 */
	public function getFromUser()
	{
		return $this->fromUser;
	}

	/**
	 * @param array $user The user array of the user sending the alert.
	 *
	 * @return MybbStuff_Myalerts_Entity_Alert $this.
	 */
	public function setFromUser(array $user = array())
	{
		$this->fromUser = $user;
		$this->setFromUserId($user['uid']);

		return $this;
	}
}
