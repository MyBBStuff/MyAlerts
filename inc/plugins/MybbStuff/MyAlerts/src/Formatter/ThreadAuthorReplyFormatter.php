<?php

/**
 * Alert formatter for private thread author reply alerts.
 */
class MybbStuff_MyAlerts_Formatter_ThreadAuthorReplyFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
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

        $threadLink = $this->mybb->settings['bburl'] . '/' . get_thread_link(
            (int) $alertContent['tid'],
            0,
            'newpost'
        );

        return $this->lang->sprintf(
            $this->lang->myalerts_post_threadauthor,
            $outputAlert['from_user_profilelink'],
            $threadLink,
            htmlspecialchars_uni($this->parser->parse_badwords($alertContent['t_subject'])),
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
