<?php

/**
 * Alert formatter for buddy list alerts.
 */
class MybbStuff_MyAlerts_Formatter_BuddylistFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
    /**
     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
     *
     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
     * @return string The formatted alert string.
     */
    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert)
    {
        $fromUser = $alert->getFromUser();
        return $this->lang->sprintf(
            $this->lang->myalerts_buddylist,
            htmlspecialchars_uni($fromUser['username']),
            $alert->getCreatedAt()->format('Y-m-d H:i')
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
    }
}
