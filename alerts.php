<?php
/**
 * MyAlerts alerts file - used to redirect to alerts, show alerts and more.
 */

define('IN_MYBB', true);
define('THIS_SCRIPT', 'alerts.php');

require_once __DIR__ . '/global.php';

$action = $mybb->get_input('action', MyBB::INPUT_STRING);

if (!isset($lang->myalerts)) {
    $lang->load('myalerts');
}

switch ($action) {
    case 'view':
        myalerts_redirect_alert($mybb, $lang);
        break;
	case 'settings':
		myalerts_alert_settings($mybb, $db, $lang, $plugins, $templates);
    default:
        myalerts_view_alerts($mybb);
        break;
}

/**
 * Handle a request to view a single alert by marking the alert read and forwarding on to the correct location.
 *
 * @param MyBB $mybb MyBB core object.
 * @param MyLanguage $lang Language object.
 */
function myalerts_redirect_alert($mybb, $lang)
{
    $alertId = $mybb->get_input('alert_id', MyBB::INPUT_INT);

    /** @var MybbStuff_MyAlerts_Entity_Alert $alert */
    $alert = $GLOBALS['mybbstuff_myalerts_alert_manager']->getAlert($alertId);
    /** @var MybbStuff_MyAlerts_Formatter_AbstractFormatter $alertTypeFormatter */
    $alertTypeFormatter = $GLOBALS['mybbstuff_myalerts_alert_formatter_manager']->getFormatterForAlertType($alert->getType()->getCode());

    if (!$alert || !$alertTypeFormatter) {
        error($lang->myalerts_error_alert_not_found);
    }

    $GLOBALS['mybbstuff_myalerts_alert_manager']->markRead(array($alertId));

    header('Location: ' . $alertTypeFormatter->buildShowLink($alert));
}

/**
 * Show a user their settings for MyAlerts.
 *
 * @param MyBB $mybb MyBB core object.
 * @param DB_MySQLi|DB_MySQL $db Database object.
 * @param MyLanguage $lang Language object.
 * @param pluginSystem $plugins MyBB plugin system.
 * @param templates $templates Template manager.
 */
function myalerts_alert_settings($mybb, $db, $lang, $plugins, $templates)
{
	$alertTypes = $GLOBALS['mybbstuff_myalerts_alert_type_manager']->getAlertTypes();

	if (strtolower($mybb->request_method) == 'post') { // Saving alert type settings
		$disabledAlerts = array();

		foreach ($alertTypes as $alertCode => $alertType) {
			if (!isset($_POST[$alertCode])) {
				$disabledAlerts[] = (int) $alertType['id'];
			}
		}

		if ($disabledAlerts != $mybb->user['myalerts_disabled_alert_types']) { // Different settings, so update
			$jsonEncodedDisabledAlerts = json_encode($disabledAlerts);

			$db->update_query('users', array(
					'myalerts_disabled_alert_types' => $db->escape_string($jsonEncodedDisabledAlerts)
				), 'uid=' . (int) $mybb->user['uid']);
		}

		redirect(
			'alerts.php?action=settings',
			$lang->myalerts_settings_updated,
			$lang->myalerts_settings_updated_title
		);
	} else { // Displaying alert type settings form

		$content = '';

		global $headerinclude, $header, $footer, $usercpnav;

		add_breadcrumb($lang->myalerts_settings_page_title, 'alerts.php?action=settings');

		require_once __DIR__ . '/inc/functions_user.php';
		usercp_menu();

		foreach ($alertTypes as $key => $value) {
			if ($value['enabled']) {
				$altbg = alt_trow();
				$tempKey = 'myalerts_setting_' . $key;

				$baseSettings = array('rep', 'pm', 'buddylist', 'quoted', 'post_threadauthor');

				$plugins->run_hooks('myalerts_load_lang');

				$langline = $lang->$tempKey;

				$checked = '';
				if (!in_array($value['id'], $mybb->user['myalerts_disabled_alert_types'])) {
					$checked = ' checked="checked"';
				}

				eval("\$alertSettings .= \"" . $templates->get('myalerts_setting_row') . "\";");
			}
		}

		eval("\$content = \"" . $templates->get('myalerts_settings_page') . "\";");
		output_page($content);
	}
}

/**
 * View all alerts.
 *
 * @param MyBB $mybb MyBB core object.
 */
function myalerts_view_alerts($mybb)
{

}
