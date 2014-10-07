<?php

/**
 * Manager class for alert formatters.
 *
 * All alert formatters should be registered with this class in order to be displayed.
 *
 * @package MybbStuff\MyAlerts
 */
class MybbStuff_MyAlerts_AlertFormatterManager
{
    /**
     * @var MyBB
     */
    private $mybb;
    /**
     * @var MyLanguage
     */
    private $lang;
    /**
     * @var array
     */
    private $alertFormatters;

    /**
     * Create a new formatter manager.
     */
    private function __construct(MyBB &$mybb, MyLanguage &$lang)
    {
        $this->mybb = $mybb;
        $this->lang = $lang;
        $this->alertFormatters = array();
    }

    /**
     * Register a new alert type formatter.
     *
     * @param string|MybbStuff_MyAlerts_Formatter_AbstractFormatter $formatterClass The formatter to use. Either the name or instance of a class extending MybbStuff_MyAlerts_Formatter_AbstractFormatter.
     *
     * @return $this
     */
    public function registerFormatter($formatterClass = '')
    {
        $formatter = null;

        if (is_string($formatterClass)) {
            /** @var MybbStuff_MyAlerts_Formatter_AbstractFormatter $formatter */
            $formatter = new $formatterClass($this->mybb, $this->lang);
            $formatter->init();
        } elseif (is_object(
                $formatterClass
            ) && $formatterClass instanceof MybbStuff_MyAlerts_Formatter_AbstractFormatter
        ) {
            $formatter = $formatterClass;
        } else {
            throw new InvalidArgumentException(
                '$formatterClass must either be the name or instance of a class extending MybbStuff_MyAlerts_Formatter_AbstractFormatter.'
            );
        }

        $this->alertFormatters[$formatter->getAlertTypeName()] = $formatter;

        return $this;
    }

    /**
     * Get the registered formatter for an alert type.
     *
     * @param string $alertTypeName The name of the alert type to retrieve the formatter for.
     *
     * @return MybbStuff_MyAlerts_Formatter_AbstractFormatter|null The located formatter or null if a registered formatter is not found.
     */
    public function getFormatterForAlertType($alertTypeName = '')
    {
        $alertTypeName = (string) $alertTypeName;
        $formatter     = null;

        if (isset($this->alertFormatters[$alertTypeName])) {
            $formatter = $this->alertFormatters[$alertTypeName];
        }

        return $formatter;
    }
} 
