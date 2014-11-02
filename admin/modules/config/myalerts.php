<?php
/**
 *  MyAlerts Core Plugin File
 *
 *  A simple notification/alert system for MyBB
 *
 * @package MyAlerts
 * @author  Euan T. <euan@euantor.com>
 * @license http://opensource.org/licenses/mit-license.php MIT license
 * @version 2.0.0
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

if (!$lang->myalerts) {
    $lang->load('myalerts');
}

$page->add_breadcrumb_item($lang->myalerts_alert_types, "index.php?module=config-myalerts_alert_types");

switch ($mybb->get_input('action')) {
    case 'alert_types':
    default:
        myalerts_acp_manage_alert_types();
        break;
}

function myalerts_acp_manage_alert_types()
{
    global $mybb, $lang, $page, $db, $cache;

    $alertTypeManager = new MybbStuff_MyAlerts_AlertTypeManager($db, $cache);

    $alertTypes = $alertTypeManager->getAlertTypes();

    if (strtolower($mybb->request_method) == 'post') {
        if(!verify_post_check($mybb->get_input('my_post_key'))) {
            flash_message($lang->invalid_post_verify_key2, 'error');
            admin_redirect("index.php?module=config-myalerts_alert_types");
        }

        $enabledAlertTypes = $mybb->get_input('alert_types', MyBB::INPUT_ARRAY);

        $enabledAlertTypes = array_map('intval', array_keys($enabledAlertTypes));

        $updateArray = array();

        foreach ($alertTypes as $alertType) {
            $type = MybbStuff_MyAlerts_Entity_AlertType::unserialize($alertType);
            $type->setEnabled(in_array($type->getId(), $enabledAlertTypes));
            $updateArray[] = $type;
        }

        $alertTypeManager->updateAlertTypes($updateArray);

        flash_message($lang->myalerts_alert_types_updated, 'success');
        admin_redirect("index.php?module=config-myalerts_alert_types");
    } else {
        $page->output_header($lang->myalerts_alert_types);

        $form = new Form('index.php?module=config-myalerts_alert_types','post');

        $table = new Table;
        $table->construct_header($lang->myalerts_alert_type_code);
        $table->construct_header($lang->myalerts_alert_type_enabled, array('width' => '5%', 'class' => 'align_center'));

        $noResults = false;

        if (!empty($alertTypes)) {
            foreach ($alertTypes as $type) {
                $alertCode = htmlspecialchars_uni($type['code']);
                $table->construct_cell($alertCode);
                $table->construct_cell($form->generate_check_box('alert_types[' . $type['id'] . ']', '', '', array('checked' => $type['enabled'])));
                $table->construct_row();
            }
        } else {
            $table->construct_cell($lang->myalerts_no_alert_types, array('colspan' => 2));
            $table->construct_row();

            $noResults = true;
        }

        $table->output($lang->myalerts_alert_types);

        if (!$noResults) {
            $buttons[] = $form->generate_submit_button($lang->myalerts_update_alert_types);
            $form->output_submit_wrapper($buttons);
        }

        $form->end();

        $page->output_footer();
    }
}
