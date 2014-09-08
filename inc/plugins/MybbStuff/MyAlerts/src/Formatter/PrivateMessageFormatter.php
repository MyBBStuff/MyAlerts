<?php

/**
 * Alert formatter for private message (PM) alerts.
 */
class MybbStuff_MyAlerts_Formatter_PrivateMessageFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
    /**
     * @var postParser $parser
     */
    private $parser;

    /**
     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
     *
     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
     * @return string The formatted alert string.
     */
    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
    {
        $alertContent = $alert->getExtraDetails();

        $pmId = (int) $alertContent['pm_id'];
        $pmSubject = htmlspecialchars_uni($this->parser->parse_badwords($alertContent['pm_title']));

        $pmLink = <<<HTML
    <a href="{$this->mybb->settings['bburl']}/private.php?action=read&amp;pmid={$pmId}">{$pmSubject}</a>
HTML;

        return $this->lang->sprintf(
            $this->lang->myalerts_pm,
            $outputAlert['from_user_profilelink'],
            $pmLink,
            $outputAlert['dateline']
        );
    }

    /**
     * Init function called before running formatAlert(). Used to load language files and initialize other required resources.
     *
     * @return void
     */
    public function init()
    {
        if (!$this->lang->myalerts) {
            $this->lang->load('myalerts');
        }

        require_once  MYBB_ROOT.'inc/class_parser.php';
        $this->parser = new postParser;
    }
}
