<?php
/**
 * MyAlerts alerts file - used to redirect to alerts, show alerts and more.
 */

define('IN_MYBB', true);
define('THIS_SCRIPT', 'alerts.php');

$templatelist = 'myalerts_alert_row_popup,myalerts_alert_row_popup_no_alerts,myalerts_modal_content';

require_once __DIR__ . '/global.php';

$action = $mybb->get_input('action', MyBB::INPUT_STRING);

if (!isset($lang->myalerts)) {
	$lang->load('myalerts');
}

if ((int) $mybb->user['uid'] < 1) {
	error_no_permission();
}

myalerts_create_instances();

switch ($action) {
	case 'view':
		myalerts_redirect_alert($mybb, $lang);
		break;
	case 'settings':
		myalerts_alert_settings(
			$mybb,
			$db,
			$lang,
			$plugins,
			$templates,
			$theme
		);
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
	case 'mark_all_read':
		// Will test true when backwards compatibility mode is on.
		if ($mybb->get_input('ajax') == '1') {
			$mybb->input['action'] = 'markAllRead';
			$mybb->input['modal'] = '1';
			myalerts_xmlhttp();
			exit;
		} else {
			myalerts_mark_all_alerts_read($mybb, $lang);
		}
		break;
	case 'get_latest_alerts':
		// Will test true when backwards compatibility mode is on.
		if ($mybb->get_input('ajax') == '1') {
			$mybb->input['action'] = 'getLatestAlerts';
			myalerts_xmlhttp();
			exit;
		}
	default:
		if ($mybb->get_input('modal') == '1') {
			$unreadOnly = !empty($mybb->cookies['myalerts_unread_only']) && $mybb->cookies['myalerts_unread_only'] != '0';
			myalerts_view_modal($mybb, $lang, $templates, $theme, $unreadOnly);
		} else {
			myalerts_view_alerts($mybb, $lang, $templates, $theme);
		}
		break;
}

/**
 * Handle a request to view a single alert by marking the alert read and
 * forwarding on to the correct location.
 *
 * @param MyBB       $mybb MyBB core object.
 * @param MyLanguage $lang Language object.
 */
function myalerts_redirect_alert($mybb, $lang)
{
	$alertId = $mybb->get_input('id', MyBB::INPUT_INT);

	$alertManager = MybbStuff_MyAlerts_AlertManager::getInstance();

	/** @var MybbStuff_MyAlerts_Entity_Alert $alert */
	$alert = $alertManager->getAlert($alertId);

	if ($alert === null) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
		error($lang->myalerts_error_alert_not_found);
		return;
	}

	/** @var MybbStuff_MyAlerts_Formatter_AbstractFormatter $alertTypeFormatter */
	$alertTypeFormatter = MybbStuff_MyAlerts_AlertFormatterManager::getInstance()
		->getFormatterForAlertType($alert->getType()->getCode());

	if (!$alert || !$alertTypeFormatter) {
		error($lang->myalerts_error_alert_not_found);
	}

	MybbStuff_MyAlerts_AlertManager::getInstance()->markRead(array($alertId));

	$redirectLink = unhtmlentities($alertTypeFormatter->buildShowLink($alert));

	if (empty($redirectLink)) {
		$redirectLink = $mybb->settings['bburl'] . '/alerts.php';
	}

	header('Location: ' . $redirectLink);
}

/**
 * Show a user their settings for MyAlerts.
 *
 * @param MyBB               $mybb      MyBB core object.
 * @param DB_Base $db        Database object.
 * @param MyLanguage         $lang      Language object.
 * @param pluginSystem       $plugins   MyBB plugin system.
 * @param templates          $templates Template manager.
 * @param array              $theme     Details about the current theme.
 */
function myalerts_alert_settings(
	$mybb,
	$db,
	$lang,
	$plugins,
	$templates,
	$theme
) {
	$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

	$alertTypes = $alertTypeManager->getAlertTypes();

	if (strtolower($mybb->request_method) == 'post') { // Saving alert type settings
		verify_post_check($mybb->get_input('my_post_key'));

		$disabledAlerts = array();

		foreach ($alertTypes as $alertCode => $alertType) {
			if (!isset($_POST[$alertCode]) && $alertType['can_be_user_disabled']) {
				$disabledAlerts[] = (int) $alertType['id'];
			}
		}

		if ($disabledAlerts != $mybb->user['myalerts_disabled_alert_types']) { // Different settings, so update
			$jsonEncodedDisabledAlerts = json_encode($disabledAlerts);

			$db->update_query(
				'users',
				array(
					'myalerts_disabled_alert_types' => $db->escape_string(
						$jsonEncodedDisabledAlerts
					)
				),
				'uid=' . (int) $mybb->user['uid']
			);
		}

		redirect(
			'alerts.php?action=settings',
			$lang->myalerts_settings_updated,
			$lang->myalerts_settings_updated_title
		);
	} else { // Displaying alert type settings form

		$content = $alertSettings = '';

		global $headerinclude, $header, $footer, $usercpnav;

		add_breadcrumb(
			$lang->myalerts_settings_page_title,
			'alerts.php?action=settings'
		);

		require_once __DIR__ . '/inc/functions_user.php';
		usercp_menu();

		foreach ($alertTypes as $key => $value) {
			if ($value['enabled'] && $value['can_be_user_disabled']) {
				$altbg = alt_trow();
				$tempKey = 'myalerts_setting_' . $key;

				$plugins->run_hooks('myalerts_load_lang');

				$langline = $lang->$tempKey;

				$checked = '';
				if (!in_array(
					$value['id'],
					$mybb->user['myalerts_disabled_alert_types']
				)) {
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
 * @param MyBB               $mybb MyBB core object.
 * @param DB_MySQL|DB_MySQLi $db   database object.
 * @param MyLanguage         $lang MyBB language system.
 */
function myalerts_delete_alert($mybb, $db, $lang)
{
	$id = $mybb->get_input('id', MyBB::INPUT_INT);
	$userId = (int) $mybb->user['uid'];

	if ($id > 0) {
		verify_post_check($mybb->get_input('my_post_key'));

		$db->delete_query('alerts', "id = {$id} AND uid = {$userId}");

		redirect(
			'alerts.php?action=alerts',
			$lang->myalerts_delete_deleted,
			$lang->myalerts_delete_deleted
		);
	} else {
		redirect(
			'alerts.php?action=alerts',
			$lang->myalerts_delete_error,
			$lang->myalerts_delete_error
		);
	}
}

/**
 * Mark all alerts as read.
 *
 * @param MyBB       $mybb MyBB core object.
 * @param MyLanguage $lang MyBB language system.
 */
function myalerts_mark_all_alerts_read($mybb, $lang)
{
	verify_post_check($mybb->get_input('my_post_key'));

	MybbStuff_MyAlerts_AlertManager::getInstance()->markAllRead();

	$retLink = $mybb->get_input('ret_link', MyBB::INPUT_STRING);

	if (!empty($retLink) && stripos($retLink, $mybb->settings['bburl']) === 0) {
		$retLink = htmlspecialchars_uni($retLink);
	} else {
		$retLink = 'alerts.php?action=alerts';
	}
	redirect(
		$retLink,
		$lang->myalerts_marked_all_read_desc,
		$lang->myalerts_marked_all_read_title
	);
}

/**
 * Delete all read alerts.
 *
 * @param MyBB               $mybb MyBB core object.
 * @param DB_MySQL|DB_MySQLi $db   database object.
 * @param MyLanguage         $lang MyBB language system.
 */
function myalerts_delete_read_alerts($mybb, $db, $lang)
{
	verify_post_check($mybb->get_input('my_post_key'));

	$userId = (int) $mybb->user['uid'];

	$db->delete_query('alerts', "uid = {$userId} AND unread = 0");

	$retLink = $mybb->get_input('ret_link', MyBB::INPUT_STRING);

	if (!empty($retLink) && stripos($retLink, $mybb->settings['bburl']) === 0) {
		$retLink = htmlspecialchars_uni($retLink);

		redirect(
			$retLink,
			$lang->myalerts_delete_read,
			$lang->myalerts_delete_read_deleted
		);
	} else {
		redirect(
			'alerts.php?action=alerts',
			$lang->myalerts_delete_read,
			$lang->myalerts_delete_read_deleted
		);
	}
}

/**
 * Delete all alerts.
 *
 * @param MyBB               $mybb MyBB core object.
 * @param DB_MySQL|DB_MySQLi $db   database object.
 * @param MyLanguage         $lang MyBB language system.
 */
function myalerts_delete_all_alerts($mybb, $db, $lang)
{
	verify_post_check($mybb->get_input('my_post_key'));

	$userId = (int) $mybb->user['uid'];

	$db->delete_query('alerts', "uid = {$userId}");

	$retLink = $mybb->get_input('ret_link', MyBB::INPUT_STRING);

	if (!empty($retLink) && stripos($retLink, $mybb->settings['bburl']) === 0) {
		$retLink = htmlspecialchars_uni($retLink);

		redirect(
			$retLink,
			$lang->myalerts_delete_all,
			$lang->myalerts_delete_mass_deleted
		);
	} else {
		redirect(
			'alerts.php?action=alerts',
			$lang->myalerts_delete_all,
			$lang->myalerts_delete_mass_deleted
		);
	}
}

/**
 * View the modal.
 *
 * @param MyBB       $mybb       MyBB core object.
 * @param MyLanguage $lang       Language object.
 * @param templates  $templates  Template manager.
 * @param array      $theme      Details about the current theme.
 * @param boolean    $unreadOnly Whether to show only unread alerts.
 */
function myalerts_view_modal($mybb, $lang, $templates, $theme, $unreadOnly = false)
{
	$userAlerts = MybbStuff_MyAlerts_AlertManager::getInstance()
		->getAlerts(
			0,
			$mybb->settings['myalerts_dropdown_limit'],
			$unreadOnly
		);

	$alerts = '';

	if (is_array($userAlerts) && !empty($userAlerts)) {
		foreach ($userAlerts as $alertObject) {
			$altbg = alt_trow();

			$alert = parse_alert($alertObject);

			if ($alertObject->getUnread()) {
				$markReadHiddenClass = '';
				$markUnreadHiddenClass = ' hidden';
			} else {
				$markReadHiddenClass = ' hidden';
				$markUnreadHiddenClass = '';
			}

			if ($alert['message']) {
				$alerts .= eval($templates->render('myalerts_alert_row_popup'));
			}

			$readAlerts[] = $alert['id'];
		}
	} else {
		$altbg = 'trow1';

		$alerts = eval($templates->render(
			'myalerts_alert_row_popup_no_alerts'
		));
	}

	$myalerts_return_link = $mybb->get_input('ret_link', MyBB::INPUT_STRING);

	if (!empty($myalerts_return_link)) {
		if (stripos($myalerts_return_link, $mybb->settings['bburl']) !== 0) {
			$myalerts_return_link = '';
		} else {
			$myalerts_return_link = htmlspecialchars_uni(urlencode($myalerts_return_link));
		}
	}

	$unreadOnly = !empty($mybb->cookies['myalerts_unread_only']) && $mybb->cookies['myalerts_unread_only'] != '0';
	$unreadOnlyChecked = $unreadOnly ? ' checked="checked"' : '';

	$myalerts_modal = eval($templates->render('myalerts_modal_content', 1, 0));

	echo $myalerts_modal;
	exit;
}

/**
 * View all alerts.
 *
 * @param MyBB       $mybb      MyBB core object.
 * @param MyLanguage $lang      Language object.
 * @param templates  $templates Template manager.
 * @param array      $theme     Details about the current theme.
 */
function myalerts_view_alerts($mybb, $lang, $templates, $theme)
{
	myalerts_create_instances();

	$alerts = MybbStuff_MyAlerts_AlertManager::getInstance()->getAlerts(0, 10);

	if (!isset($lang->myalerts)) {
		$lang->load('myalerts');
	}

	add_breadcrumb($lang->myalerts_page_title, 'alerts.php?action=alerts');

	require_once __DIR__ . '/inc/functions_user.php';
	usercp_menu();

	$numAlerts = MybbStuff_MyAlerts_AlertManager::getInstance()->getNumAlerts();
	$page = $mybb->get_input('page', MyBB::INPUT_INT);
	$pages = ceil($numAlerts / $mybb->settings['myalerts_perpage']);

	if ($page > $pages OR $page <= 0) {
		$page = 1;
	}

	if ($page) {
		$start = ($page - 1) * $mybb->settings['myalerts_perpage'];
	} else {
		$start = 0;
		$page = 1;
	}
	$multipage = multipage(
		$numAlerts,
		$mybb->settings['myalerts_perpage'],
		$page,
		"alerts.php"
	);

	$alertsList = MybbStuff_MyAlerts_AlertManager::getInstance()->getAlerts(
		$start
	);

	$readAlerts = array();
	$alertsListing = '';

	if (is_array($alertsList) && !empty($alertsList)) {
		foreach ($alertsList as $alertObject) {
			$altbg = alt_trow();

			$alert = parse_alert($alertObject);

			if ($alert['message']) {
				if ($alertObject->getUnread()) {
					$markReadHiddenClass = '';
					$markUnreadHiddenClass = ' hidden';
				} else {
					$markReadHiddenClass = ' hidden';
					$markUnreadHiddenClass = '';
				}
				eval("\$alertsListing .= \"" . $templates->get(
						'myalerts_alert_row'
					) . "\";");
			}

			$readAlerts[] = $alert['id'];
		}
	} else {
		$altbg = 'trow1';
		eval("\$alertsListing = \"" . $templates->get(
				'myalerts_alert_row_no_alerts'
			) . "\";");
	}

	global $headerinclude, $header, $footer, $usercpnav;

	$content = '';
	eval("\$content = \"" . $templates->get('myalerts_page') . "\";");
	output_page($content);
}
