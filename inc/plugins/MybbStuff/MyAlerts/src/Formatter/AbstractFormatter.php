<?php

declare(strict_types=1);

/**
 * Base alert formatter. Alert type formatters should inherit from this base
 * class to have alerts displayed correctly.
 *
 * @package MybbStuff\MyAlerts
 */
abstract class MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
	/**
	 * @var MyBB
	 */
	protected MyBB $mybb;
	/**
	 * @var MyLanguage
	 */
	protected MyLanguage $lang;
	/**
	 * @var string
	 */
	protected string $alertTypeName;

	/**
	 * Initialize a new alert formatter.
	 *
	 * @param MyBB       $mybb An instance of the MyBB core class to use when
	 *                         formatting.
	 * @param MyLanguage $lang An instance of the language class to use when
	 *                         formatting.
	 */
	public function __construct(
		MyBB &$mybb,
		MyLanguage &$lang,
		string $alertTypeName = ''
	) {
		$this->mybb = $mybb;
		$this->lang = $lang;
		$this->alertTypeName = $alertTypeName;
	}

	/**
	 * @return string
	 */
	public function getAlertTypeName(): string
	{
		return $this->alertTypeName;
	}

	/**
	 * @param string $alertTypeName
	 */
	public function setAlertTypeName(string $alertTypeName = ''): void
	{
		$this->alertTypeName = $alertTypeName;
	}

	/**
	 * @return MyLanguage
	 */
	public function getLang(): MyLanguage
	{
		return $this->lang;
	}

	/**
	 * @param MyLanguage $lang
	 */
	public function setLang(MyLanguage $lang): void
	{
		$this->lang = $lang;
	}

	/**
	 * @return MyBB
	 */
	public function getMybb(): MyBB
	{
		return $this->mybb;
	}

	/**
	 * @param MyBB $mybb
	 */
	public function setMybb(MyBB $mybb): void
	{
		$this->mybb = $mybb;
	}

	/**
	 * Init function called before running formatAlert(). Used to load language
	 * files and initialize other required resources.
	 *
	 * @return void
	 */
	abstract public function init(): void;

	/**
	 * Format an alert into it's output string to be used in both the main
	 * alerts listing page and the popup.
	 *
	 * @param MybbStuff_MyAlerts_Entity_Alert $alert       The alert to format.
	 * @param array $outputAlert The alert output details, including formated from username, from user profile link and more.
	 *
	 * @return string The formatted alert string.
	 */
	abstract public function formatAlert(
		MybbStuff_MyAlerts_Entity_Alert $alert,
		array $outputAlert
	): string;

	/**
	 * Build a link to an alert's content so that the system can redirect to
	 * it.
	 *
	 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	 *
	 * @return string The built alert, preferably an absolute link.
	 */
	abstract public function buildShowLink(
		MybbStuff_MyAlerts_Entity_Alert $alert
	): string;
}
