<?php
$l['myalerts'] = "MyAlerts";
$l['myalerts_pluginlibrary_missing'] = "The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.";
$l['myalerts_class_outdated'] = "It seems the Alerts class is not up to date. Please ensure the /inc/plugins/MyAlerts/ folder is up to date. (MyAlerts version: {1}, MyAlerts Class version: {2})";

$l['setting_group_myalerts'] = "MyAlerts Settings";
$l['setting_group_myalerts_desc'] = "Settings for the MyAlerts plugin";
$l['setting_myalerts_enabled'] = "Enable MyAlerts?";
$l['setting_myalerts_enabled_desc'] = "This switch can be used to globally disable all MyAlerts features";
$l['setting_myalerts_perpage'] = "Alerts per page";
$l['setting_myalerts_perpage_desc'] = "How many alerts do you wish to display on the alerts listing page? (default is 10)";
$l['setting_myalerts_dropdown_limit'] = "Number of alerts to show in dropdown";
$l['setting_myalerts_dropdown_limit_desc'] = "How many alerts do you wish to display in the global alerts dropdown? (default is 5)";
$l['setting_myalerts_autorefresh'] = "MyAlerts page AJAX autorefresh";
$l['setting_myalerts_autorefresh_desc'] = "How often (in seconds) do you wish the MyAlerts page to refresh the alerts listing via AJAX? (0 for no autorefresh)";
$l['setting_myalerts_alert_rep'] = "Alert on reputation?";
$l['setting_myalerts_alert_rep_desc'] = "Do you wish for users to receive a new alert when somebody gives them a reputation?";
$l['setting_myalerts_alert_pm'] = "Alert on Private Message?";
$l['setting_myalerts_alert_pm_desc'] = "Do you wish for users to receive an alert when they are sent a new Private Message (PM)?";
$l['setting_myalerts_alert_buddylist'] = "Alert on Buddy List addition?";
$l['setting_myalerts_alert_buddylist_desc'] = "Do you wish for users to receive an alert when they are added to another user's buddy list?";
$l['setting_myalerts_alert_quoted'] = "Alert when quoted in a post?";
$l['setting_myalerts_alert_quoted_desc'] = "Do you wish for users to receive an alert when they are quoted in a post?";
$l['setting_myalerts_alert_post_threadauthor'] = "Alert thread authors on reply?";
$l['setting_myalerts_alert_post_threadauthor_desc'] = "Do you wish for thread authors to receive an alert when somebody responds to their thread?";

$l['myalerts_helpsection_name'] = 'User Alerts';
$l['myalerts_helpsection_desc'] = 'Basic information relating to the user alerts system in place on this site.';

$l['myalerts_help_info'] = 'Basic Information';
$l['myalerts_help_info_desc'] = 'Basic information about the user alerts system and how it works.';
$l['myalerts_help_info_document'] = 'The alerts system on this site provides you with a simple way to see what\'s been happening recently around the site by the way of a simple notification.
<p>
	There is a simple count of your oustanding unread alerts found within the header of the site. Clicking upon this count will open a dropdown list of your unread alerts from which you can then progress to the alerts listing page if you so desire.
</p>
<p>
	The alerts listing page can be <a href="usercp.php?action=alerts">found here</a> and contains a list of all the alerts you\'ve received, both unread and read. You can also delete old alerts that you don\'t wish to retain from this page.
</p>';

$l['myalerts_help_alert_types'] = 'Alert Types';
$l['myalerts_help_alert_types_desc'] = 'Information about the different types of alerts that can be received.';
$l['myalerts_help_alert_types_document'] = 'There are many types of alerts that you can receive based on different actions performed around this site. These are the current different actions that cause an alert to be received:
<br /><br />';

$l['myalerts_task_cleanup_ran'] = 'Read alerts over a week old were deleted successfully!';
$l['myalerts_task_cleanup_error'] = 'Something went wrong while cleaning up the alerts...';
$l['myalerts_task_cleanup_disabled'] = 'The alerts cleanup task has been disabled via the settings.';

$l['myalerts_task_title'] = 'MyAlerts Cleanup';
$l['myalerts_task_description'] = 'A task to cleanup old read alerts. THis is required as otherwise the alerts table could swell to massive sizes.';