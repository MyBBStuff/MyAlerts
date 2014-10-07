<?php
/**
 * MyAlerts alerts file - used to redirect to alerts, show alerts and more.
 */

define('IN_MYBB', true);

$action = isset($mybb->input['action']) ? $mybb->input['action'] : '';

if (!isset($lang->myalerts)) {
    $lang->load('myalerts');
}

switch ($action) {
    case 'view':
        myalerts_redirect_alert($mybb, $lang);
        break;
    default:
        myalerts_view_alerts($mybb);
        break;
}

/**
 * Handle a request to view a single alert by marking the alert read and forwarding on to the correct location.
 */
function myalerts_redirect_alert($mybb, $lang)
{
    $alertId = (int) $mybb->get_input('alert_id', 1);

    /** @var MybbStuff_MyAlerts_Entity_Alert $alert */
    $alert = $GLOBALS['mybbstuff_myalerts_alert_manager']->getAlert($alertId);
    /** @var MybbSTuff_MyAlerts_Formatter_AbstractFormatter $alertTypeFormatter */
    $alertTypeFormatter = $GLOBALS['mybbstuff_myalerts_alert_formatter_manager']->getFormatterForAlertType($alert->getType()->getCode());

    if (!$alert || !$alertTypeFormatter) {
        error($lang->myalerts_error_alert_not_found);
    }

    $GLOBALS['mybbstuff_myalerts_alert_manager']->markRead(array($alertId));

    header('Location: ' . $alertTypeFormatter->buildShowLink($alert));
}
