<?php

/**
 * Base alert formatter. Alert type formatters should inherit from this base class in order to have alerts displayed correctly.
 *
 * @package MybbStuff\MyAlerts
 */
abstract class MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
	/**
	 * @var MyBB
	 */
	protected $mybb;
	/**
	 * @var MyLanguage
	 */
	protected $lang;

	/**
	 * Initialise a new alert formatter.
	 *
	 * @param MyBB       $mybb An instance of the MyBB core class to use when formatting.
	 * @param MyLanguage $lang An instance of the language class to use when formatting.
	 */
	public function __construct(MyBB &$mybb, MyLanguage &$lang)
	{
		$this->mybb = &$mybb;
		$this->lang = &$lang;
	}

	/**
	 * @return MyLanguage
	 */
	public function getLang()
	{
		return $this->lang;
	}

	/**
	 * @param MyLanguage $lang
	 */
	public function setLang($lang)
	{
		$this->lang = $lang;
	}

	/**
	 * @return MyBB
	 */
	public function getMybb()
	{
		return $this->mybb;
	}

	/**
	 * @param MyBB $mybb
	 */
	public function setMybb($mybb)
	{
		$this->mybb = $mybb;
	}

	/**
	 * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	 *
	 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	 *
	 * @return string The formatted alert string.
	 */
	public abstract function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert);
} 
