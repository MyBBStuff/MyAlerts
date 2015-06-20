<?php

/**
 * Base alert formatter. Alert type formatters should inherit from this base
 * class in order to have alerts displayed correctly.
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
	 * @var string
	 */
	protected $alertTypeName;

	/**
	 * Initialise a new alert formatter.
	 *
	 * @param MyBB       $mybb An instance of the MyBB core class to use when
	 *                         formatting.
	 * @param MyLanguage $lang An instance of the language class to use when
	 *                         formatting.
	 */
	public function __construct(
		MyBB &$mybb,
		MyLanguage &$lang,
		$alertTypeName = ''
	) {
		$this->mybb = $mybb;
		$this->lang = $lang;
		$this->alertTypeName = (string) $alertTypeName;
	}

	/**
	 * @return string
	 */
	public function getAlertTypeName()
	{
		return $this->alertTypeName;
	}

	/**
	 * @param string $alertTypeName
	 */
	public function setAlertTypeName($alertTypeName = '')
	{
		$this->alertTypeName = (string) $alertTypeName;
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
	public function setLang(MyLanguage $lang)
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
	public function setMybb(MyBB $mybb)
	{
		$this->mybb = $mybb;
	}

	/**
	 * Init function called before running formatAlert(). Used to load language
	 * files and initialize other required resources.
	 *
	 * @return void
	 */
	public abstract function init();

	/**
	 * Format an alert into it's output string to be used in both the main
	 * alerts listing page and the popup.
	 *
	 * @param MybbStuff_MyAlerts_Entity_Alert $alert       The alert to format.
	 * @param array                           $outputAlert The alert output
	 *                                                     details, including
	 *                                                     formated from user
	 *                                                     name, from user
	 *                                                     profile link and
	 *                                                     more.
	 *
	 * @return string The formatted alert string.
	 */
	public abstract function formatAlert(
		MybbStuff_MyAlerts_Entity_Alert $alert,
		array $outputAlert
	);

	/**
	 * Build a link to an alert's content so that the system can redirect to
	 * it.
	 *
	 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the
	 *                                               link for.
	 *
	 * @return string The built alert, preferably an absolute link.
	 */
	public abstract function buildShowLink(
		MybbStuff_MyAlerts_Entity_Alert $alert
	);
}
