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
		myalerts_alert_settings($mybb, $db, $lang, $plugins, $templates, $theme);
        break;
    case 'delete':
        myalerts_delete_alert($mybb, $db, $lang);
        break;
    case 'delete_read':
        myalerts_delete_read_alerts($mybb, $db, $lang);
        break;
    case 'delete_all':
        myalerts_delete_all_alerts($mybb, $db, $lang);
        break;
    default:
        myalerts_view_alerts($mybb, $lang, $templates, $theme);
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
    $alertId = $mybb->get_input('id', MyBB::INPUT_INT);

    /** @var MybbStuff_MyAlerts_Entity_Alert $alert */
    $alert = MybbStuff_MyAlerts_AlertManager::getInstance()->getAlert($alertId);
    /** @var MybbStuff_MyAlerts_Formatter_AbstractFormatter $alertTypeFormatter */
    $alertTypeFormatter = MybbStuff_MyAlerts_AlertFormatterManager::getInstance()->getFormatterForAlertType($alert->getType()->getCode());

    if (!$alert || !$alertTypeFormatter) {
        error($lang->myalerts_error_alert_not_found);
    }

    MybbStuff_MyAlerts_AlertManager::getInstance()->markRead(array($alertId));

    $redirectLink = unhtmlentities($alertTypeFormatter->buildShowLink($alert));

    header('Location: ' . $redirectLink);
}

/**
 * Show a user their settings for MyAlerts.
 *
 * @param MyBB $mybb MyBB core object.
 * @param DB_MySQLi|DB_MySQL $db Database object.
 * @param MyLanguage $lang Language object.
 * @param pluginSystem $plugins MyBB plugin system.
 * @param templates $templates Template manager.
 * @param array $theme Details about the current theme.
 */
function myalerts_alert_settings($mybb, $db, $lang, $plugins, $templates, $theme)
{
	$alertTypes = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getAlertTypes();

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
 * Delete a single alert.
 *
 * @param MyBB $mybb MyBB core object.
 * @param DB_MySQL|DB_MySQLi $db database object.
 * @param MyLanguage $lang MyBB language system.
 */
function myalerts_delete_alert($mybb, $db, $lang)
{
    $id = $mybb->get_input('id', MyBB::INPUT_INT);
    $userId = (int) $mybb->user['uid'];

    if ($id > 0) {
        verify_post_check($mybb->get_input('my_post_key'));

        $db->delete_query('alerts', "id = {$id} AND uid = {$userId}");

        redirect('alerts.php?action=alerts', $lang->myalerts_delete_deleted, $lang->myalerts_delete_deleted);
    } else {
        redirect('alerts.php?action=alerts', $lang->myalerts_delete_error, $lang->myalerts_delete_error);
    }
}

/**
 * Delete all read alerts.
 *
 * @param MyBB $mybb MyBB core object.
 * @param DB_MySQL|DB_MySQLi $db database object.
 * @param MyLanguage $lang MyBB language system.
 */
function myalerts_delete_read_alerts($mybb, $db, $lang)
{
    verify_post_check($mybb->get_input('my_post_key'));

    $userId = (int) $mybb->user['uid'];

    $db->delete_query('alerts', "uid = {$userId} AND unread = 0");

    redirect('alerts.php?action=alerts', $lang->myalerts_delete_all_read, $lang->myalerts_delete_mass_deleted);
}

/**
 * Delete all alerts.
 *
 * @param MyBB $mybb MyBB core object.
 * @param DB_MySQL|DB_MySQLi $db database object.
 * @param MyLanguage $lang MyBB language system.
 */
function myalerts_delete_all_alerts($mybb, $db, $lang)
{
    verify_post_check($mybb->get_input('my_post_key'));

    $userId = (int) $mybb->user['uid'];

    $db->delete_query('alerts', "uid = {$userId}");

    redirect('alerts.php?action=alerts', $lang->myalerts_delete_all, $lang->myalerts_delete_mass_deleted);
}

/**
 * View all alerts.
 *
 * @param MyBB $mybb MyBB core object.
 * @param MyLanguage $lang Language object.
 * @param templates $templates Template manager.
 * @param array $theme Details about the current theme.
 */
function myalerts_view_alerts($mybb, $lang, $templates, $theme)
{
    $alerts = MybbStuff_MyAlerts_AlertManager::getInstance()->getAlerts(0, 10);

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    add_breadcrumb($lang->myalerts_page_title, 'alerts.php?action=alerts');

    require_once __DIR__ . '/inc/functions_user.php';
    usercp_menu();

    $numAlerts = MybbStuff_MyAlerts_AlertManager::getInstance()->getNumAlerts();
    $page      = (int) $mybb->input['page'];
    $pages     = ceil($numAlerts / $mybb->settings['myalerts_perpage']);

    if ($page > $pages OR $page <= 0) {
        $page = 1;
    }

    if ($page) {
        $start = ($page - 1) * $mybb->settings['myalerts_perpage'];
    } else {
        $start = 0;
        $page  = 1;
    }
    $multipage = multipage($numAlerts, $mybb->settings['myalerts_perpage'], $page, "usercp.php?action=alerts");

    $alertsList = MybbStuff_MyAlerts_AlertManager::getInstance()->getAlerts($start);

    $readAlerts = array();

    if (is_array($alertsList) && !empty($alertsList)) {
        foreach ($alertsList as $alertObject) {
            $altbg = alt_trow();

            $alert = parse_alert($alertObject);

            if ($alert['message']) {
                eval("\$alertsListing .= \"" . $templates->get('myalerts_alert_row') . "\";");
            }

            $readAlerts[] = $alert['id'];
        }
    } else {
        $altbg = 'trow1';
        eval("\$alertsListing = \"" . $templates->get('myalerts_alert_row_no_alerts') . "\";");
    }

    MybbStuff_MyAlerts_AlertManager::getInstance()->markRead($readAlerts);

    global $headerinclude, $header, $footer, $usercpnav;

    $content = '';
    eval("\$content = \"" . $templates->get('myalerts_page') . "\";");
    output_page($content);
}
