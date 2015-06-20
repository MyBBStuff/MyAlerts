<?php

/**
 * Alert formatter for reputation alerts.
 */
class MybbStuff_MyAlerts_Formatter_RepFormatter
	extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
	/**
	 * Format an alert into it's output string to be used in both the main
	 * alerts listing page and the popup.
	 *
	 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	 *
	 * @return string The formatted alert string.
	 */
	public function formatAlert(
		MybbStuff_MyAlerts_Entity_Alert $alert,
		array $outputAlert
	) {
		return $this->lang->sprintf(
			$this->lang->myalerts_rep,
			$outputAlert['from_user']
		);
	}

	/**
	 * Init function called before running formatAlert(). Used to load language
	 * files and initialize other required resources.
	 *
	 * @return void
	 */
	public function init()
	{
		if (!$this->lang->myalerts) {
			$this->lang->load('myalerts');
		}
	}

	/**
	 * Build a link to an alert's content so that the system can redirect to
	 * it.
	 *
	 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the
	 *                                               link for.
	 *
	 * @return string The built alert, preferably an absolute link.
	 */
	public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	{
		return "{$this->mybb->settings['bburl']}/reputation.php?uid={$this->mybb->user['uid']}";

	}
}
