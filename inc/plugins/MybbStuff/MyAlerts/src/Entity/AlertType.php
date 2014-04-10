<?php

namespace MybbStuff\MyAlerts\Entity;

/**
 * A single alert type object as it's represented in the database.
 *
 * @package MybbStuff\MyAlerts\Entity
 */
class AlertType
{
	/** @var int The ID of the alert type. */
	private $id = 0;

	/** @var string The short code identifying the alert type - eg: 'pm', 'rep'. */
	private $code = '';

	/** @var string The public facing title of the alert type. */
	private $title = '';

	/**
	 * @param string $code
	 */
	public function setCode($code)
	{
		$this->code = $code;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param boolean $enabled
	 */
	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
	}

	/**
	 * @return boolean
	 */
	public function getEnabled()
	{
		return $this->enabled;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/** @var bool Whether the alert type is enabled. */
	private $enabled = true;

	/**
	 * @param int $id
	 */
	public function setId($id = 0)
	{
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
} 
