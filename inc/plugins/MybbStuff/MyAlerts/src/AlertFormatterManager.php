<?php

declare(strict_types=1);

/**
 * Manager class for alert formatters.
 *
 * All alert formatters should be registered with this class to be displayed.
 *
 * @package MybbStuff\MyAlerts
 */
class MybbStuff_MyAlerts_AlertFormatterManager
{
	/**
	 * @var MybbStuff_MyAlerts_AlertFormatterManager
	 */
	private static MybbStuff_MyAlerts_AlertFormatterManager $instance;
	/**
	 * @var MyBB
	 */
	private MyBB $mybb;
	/**
	 * @var MyLanguage
	 */
	private MyLanguage $lang;
	/**
	 * @var MybbStuff_MyAlerts_Formatter_AbstractFormatter[]
	 */
	private array $alertFormatters;
	/**
	 * @var bool
	 */
	private bool $registrationHookHasRun;

	/**
	 * Create a new formatter manager.
	 *
	 * @param MyBB       $mybb MyBB core object.
	 * @param MyLanguage $lang Language object.
	 */
	private function __construct(MyBB $mybb, MyLanguage $lang)
	{
		$this->mybb = $mybb;
		$this->lang = $lang;
		$this->alertFormatters = array();
		$this->registrationHookHasRun = false;
	}

	/**
	 * Create an instance of the alert formatter manager.
	 *
	 * @param MyBB       $mybb MyBB core object.
	 * @param MyLanguage $lang Language object.
	 *
	 * @return MybbStuff_MyAlerts_AlertFormatterManager The created instance.
	 */
	public static function createInstance(MyBB $mybb, MyLanguage $lang): self
	{
		if (!isset(static::$instance)) {
			static::$instance = new self($mybb, $lang);
		}

		return static::$instance;
	}

	/**
	 * Get an instance of the AlertFormatterManager if one has been created via
	 * @return MybbStuff_MyAlerts_AlertFormatterManager The existing instance, or false if not already instantiated.
	 * @throws Exception
	 * @see createInstance().
	 */
	public static function getInstance(): self
	{
		if (!isset(static::$instance)) {
			throw new Exception('AlertFormatterManager has not been instantiated.');
		}

		return static::$instance;
	}

	/**
	 * Register a new alert type formatter.
	 *
	 * @param MybbStuff_MyAlerts_Formatter_AbstractFormatter $formatterClass The formatter to use. Either the name or instance of a class extending MybbStuff_MyAlerts_Formatter_AbstractFormatter.
	 *
	 * @return $this
	 */
	public function registerFormatter(\MybbStuff_MyAlerts_Formatter_AbstractFormatter $formatterClass): MybbStuff_MyAlerts_AlertFormatterManager
	{
		$this->alertFormatters[$formatterClass->getAlertTypeName()] = $formatterClass;

		return $this;
	}

	/**
	 * Get the registered formatter for an alert type.
	 *
	 * @param string $alertTypeName The name of the alert type to retrieve the formatter for.
	 *
	 * @return MybbStuff_MyAlerts_Formatter_AbstractFormatter The located formatter if a registered formatter is not found.
	 */
	public function getFormatterForAlertType(string $alertTypeName = ''): \MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
		if (!$this->registrationHookHasRun) {
			global $plugins;

			$plugins->run_hooks('myalerts_register_client_alert_formatters', $this);
			$this->registrationHookHasRun = true;
		}

		if (!isset($this->alertFormatters[$alertTypeName])) {
			throw new InvalidArgumentException("No formatter registered for alert type '$alertTypeName'.");
		}

		return $this->alertFormatters[$alertTypeName];
	}
}
