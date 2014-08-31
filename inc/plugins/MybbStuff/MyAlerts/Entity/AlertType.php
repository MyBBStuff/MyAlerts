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

	/** @var string The public facing title of the alert type. */
	private $title = '';
	/** @var bool Whether the alert type is enabled. */
	private $enabled = true;

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param string $code
	 */
	public function setCode($code)
	{
		$this->code = $code;
	}

	/**
	 * @return boolean
	 */
	public function getEnabled()
	{
		return $this->enabled;
	}

	/**
	 * @param boolean $enabled
	 */
	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
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
	 */
	public function setId($id = 0)
	{
		$this->id = $id;
	}
} 
