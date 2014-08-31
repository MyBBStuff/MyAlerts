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

defined('MYBBSTUFF_CORE_PATH') or define('MYBBSTUFF_CORE_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/Core/');
define('MYALERTS_PLUGIN_PATH', MYBB_ROOT.'inc/plugins/MybbStuff/MyAlerts');

defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

require_once MYBBSTUFF_CORE_PATH . 'ClassLoader.php';

$classLoader = new MybbStuff_Core_ClassLoader();
$classLoader->registerNamespace('MybbStuff_MyAlerts', array(MYALERTS_PLUGIN_PATH));
$classLoader->register();

function myalerts_info()
{
    global $mybb;
    return array(
        'name'          =>  'MyAlerts',
        'description'   =>  'A simple notifications/alerts system for MyBB<br/><br/>To enable all alerts for existing users, please click here: <a href="'.$mybb->settings['bburl'].'/inc/plugins/MyAlerts/force_enable_alerts.php">Run Script</a>. <strong>This script may take a while for large boards. If you have many users, it\'s advised to run this via the PHP CLI.</strong>',
        'website'       =>  'http://euantor.com/myalerts',
        'author'        =>  'euantor',
        'authorsite'    =>  'http://euantor.com',
        'version'       =>  '2.0.0',
        'guid'          =>  '',
        'compatibility' =>  '1*',
    );
}

function myalerts_install()
{
    global $db, $cache;

    $plugin_info = myalerts_info();
    $euantor_plugins = $cache->read('euantor_plugins');
    $euantor_plugins['myalerts'] = array(
        'title'     =>  'MyAlerts',
        'version'   =>  $plugin_info['version'],
    );
    $cache->update('euantor_plugins', $euantor_plugins);

    if (!$db->table_exists('alerts')) {
        $collation = $db->build_create_table_collation();
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."alerts(
            id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            uid INT(10) NOT NULL,
            unread TINYINT(4) NOT NULL DEFAULT '1',
            dateline BIGINT(30) NOT NULL,
            alert_type VARCHAR(25) NOT NULL,
            oject_id INT(10) unsigned NOT NULL DEFAULT '0',
            from_id INT(10),
            forced INT(1) NOT NULL DEFAULT '0',
            extra_details TEXT
            ) ENGINE=MyISAM{$collation};");
    }

    if (!$db->table_exists('alert_settings')) {
        $collation = $db->build_create_table_collation();
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."alert_settings(
            id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(75) NOT NULL,
            is_core INT(1) NOT NULL DEFAULT '0'
            ) ENGINE=MyISAM{$collation};");
    }

    if (!$db->table_exists('alert_setting_values')) {
        $collation = $db->build_create_table_collation();
        $db->write_query("CREATE TABLE ".TABLE_PREFIX."alert_setting_values(
            id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(10) NOT NULL,
            setting_id INT(10) NOT NULL,
            value INT(1) NOT NULL DEFAULT '1'
            ) ENGINE=MyISAM{$collation};");
    }

    // Settings
    $insertArray = array(
        0 => array(
            'code' => 'rep',
        ),
        1 => array(
            'code' => 'pm',
        ),
        2 => array(
            'code' => 'buddylist',
        ),
        3 => array(
            'code' => 'quoted',
        ),
        4 => array(
            'code' => 'post_threadauthor',
        ),
    );

    $db->insert_query_multiple('alert_settings', $insertArray);
}

function myalerts_is_installed()
{
    global $db;

    return $db->table_exists('alerts');
}

function myalerts_uninstall()
{
    global $db, $lang, $PL;

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->myalerts_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $PL or require_once PLUGINLIBRARY;

    if ($db->table_exists('alerts')) {
        $db->drop_table('alerts');
    }

    if ($db->table_exists('alert_settings')) {
        $db->drop_table('alert_settings');
    }

    if ($db->table_exists('alert_setting_values')) {
        $db->drop_table('alert_setting_values');
    }

    $PL->settings_delete('myalerts', true);
    $PL->templates_delete('myalerts');
    $PL->stylesheet_delete('alerts.css');

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    $sid = (int) $db->fetch_field($db->simple_select('helpsections', 'sid', 'name = \''.$db->escape_string($lang->myalerts_helpsection_name).'\''), 'sid');
    $db->delete_query('helpsections', 'sid = '.$sid);
    $db->delete_query('helpdocs', 'sid = '.$sid);
    $db->delete_query('tasks', 'file = \'myalerts\'');
}

function myalerts_activate()
{
    global $mybb, $db, $lang, $PL, $plugins, $cache;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->myalerts_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $PL or require_once PLUGINLIBRARY;

    if ($PL->version < 9) {
        flash_message('This plugin requires PluginLibrary 9 or newer', 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $plugin_info = myalerts_info();
    $this_version = $plugin_info['version'];

    $euantor_plugins = $cache->read('euantor_plugins');
//    if ($euantor_plugins['myalerts']['version'] != $plugin_info['version']) {
//        require MYALERTS_PLUGIN_PATH . '/upgrader.php';
//        myalerts_upgrader_run($plugin_info['version'], $euantor_plugins['myalerts']['version']);
//    }
    $euantor_plugins['myalerts'] = array(
        'title'     =>  'MyAlerts',
        'version'   =>  $plugin_info['version'],
    );
    $cache->update('euantor_plugins', $euantor_plugins);

    $PL->settings('myalerts',
        $lang->setting_group_myalerts,
        $lang->setting_group_myalerts_desc,
        array(
            'enabled'   =>  array(
                'title'         =>  $lang->setting_myalerts_enabled,
                'description'   =>  $lang->setting_myalerts_enabled_desc,
                'value'         =>  '1',
            ),
            'perpage'   =>  array(
                'title'         =>  $lang->setting_myalerts_perpage,
                'description'   =>  $lang->setting_myalerts_perpage_desc,
                'value'         =>  '10',
                'optionscode'   =>  'text',
            ),
            'dropdown_limit'  =>  array(
                'title'         =>  $lang->setting_myalerts_dropdown_limit,
                'description'   =>  $lang->setting_myalerts_dropdown_limit_desc,
                'value'         =>  '5',
                'optionscode'   =>  'text',
            ),
            'autorefresh'   =>  array(
                'title'         =>  $lang->setting_myalerts_autorefresh,
                'description'   =>  $lang->setting_myalerts_autorefresh_desc,
                'value'         =>  '0',
                'optionscode'   =>  'text',
            ),
            'alert_rep' =>  array(
                'title'         =>  $lang->setting_myalerts_alert_rep,
                'description'   =>  $lang->setting_myalerts_alert_rep_desc,
                'value'         =>  '1',
            ),
            'alert_pm'  =>  array(
                'title'         =>  $lang->setting_myalerts_alert_pm,
                'description'   =>  $lang->setting_myalerts_alert_pm_desc,
                'value'         =>  '1',
            ),
            'alert_buddylist'  =>  array(
                'title'         =>  $lang->setting_myalerts_alert_buddylist,
                'description'   =>  $lang->setting_myalerts_alert_buddylist_desc,
                'value'         =>  '1',
            ),
            'alert_quoted'  =>  array(
                'title'         =>  $lang->setting_myalerts_alert_quoted,
                'description'   =>  $lang->setting_myalerts_alert_quoted_desc,
                'value'         =>  '1',
            ),
            'alert_post_threadauthor'  =>  array(
                'title'         =>  $lang->setting_myalerts_alert_post_threadauthor,
                'description'   =>  $lang->setting_myalerts_alert_post_threadauthor_desc,
                'value'         =>  '1',
            ),
            'default_avatar' => array(
                'title'         => $lang->setting_myalerts_default_avatar,
                'description'   => $lang->setting_myalerts_default_avatar_desc,
                'optionscode'   => 'text',
                'value'         => './images/alerts/default-avatar.png',
            ),
        )
    );

    $dir = new DirectoryIterator(MYALERTS_PLUGIN_PATH . '/templates');
    $templates = array();
    foreach ($dir as $file) {
        if (!$file->isDot() AND !$file->isDir() AND pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'html') {
            $templates[$file->getBasename('.html')] = file_get_contents($file->getPathName());
        }
    }

    $PL->templates(
        'myalerts',
        'MyAlerts',
        $templates
    );

    $stylesheet = file_get_contents(MYALERTS_PLUGIN_PATH . '/stylesheets/alerts.css');

    $PL->stylesheet('alerts.css', $stylesheet);

    require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	
	$myalertsJs = <<<JAVASCRIPT
			
<script type="text/javascript">
    var unreadAlerts = '{\$mybb->user['unreadAlerts']}';
</script>
<script type="text/javascript" src="{\$mybb->asset_url}/jscripts/myalerts.js"></script>
{\$stylesheets}
JAVASCRIPT;
	
    // Add our JS. We need jQuery and myalerts.js. For jQuery, we check it hasn't already been loaded then load 1.7.2 from google's CDN
    find_replace_templatesets('headerinclude', "#".preg_quote('{$stylesheets}')."#i", $myalertsJs);
    find_replace_templatesets('header_welcomeblock_member', "#".preg_quote('{$modcplink}')."#i", '<myalerts_headericon>{$modcplink}');

    // Helpdocs
    $query = $db->simple_select('helpsections', 'sid', "name = '".$lang->myalerts_helpsection_name."'");
    if (!$db->num_rows($query)) {
        $helpsection = $db->insert_query('helpsections', array(
            'name'              =>  $lang->myalerts_helpsection_name,
            'description'       =>  $lang->myalerts_helpsection_desc,
            'usetranslation'    =>  1,
            'enabled'           =>  1,
            'disporder'         =>  3,
            )
        );
    } else {
        $sid = (int) $db->fetch_field($query, 'sid');
        $helpsection = $db->update_query('helpsections', array(
            'name'              =>  $lang->myalerts_helpsection_name,
            'description'       =>  $lang->myalerts_helpsection_desc,
            'usetranslation'    =>  1,
            'enabled'           =>  1,
            'disporder'         =>  3,
            ),
        "sid = {$sid}"
        );
    }

    unset($query);

    $helpDocuments = array(
        0   =>  array(
            'sid'               =>  (int) $helpsection,
            'name'              =>  $db->escape_string($lang->myalerts_help_info),
            'description'       =>  $db->escape_string($lang->myalerts_help_info_desc),
            'document'          =>  $db->escape_string($lang->myalerts_help_info_document),
            'usetranslation'    =>  1,
            'enabled'           =>  1,
            'disporder'         =>  1,
        ),
        1   =>  array(
            'sid'               =>  (int) $helpsection,
            'name'              =>  $db->escape_string($lang->myalerts_help_alert_types),
            'description'       =>  $db->escape_string($lang->myalerts_help_alert_types_desc),
            'document'          =>  $db->escape_string($lang->myalerts_help_alert_types_document),
            'usetranslation'    =>  1,
            'enabled'           =>  1,
            'disporder'         =>  2,
        ),
    );

    foreach ($helpDocuments as $document) {
        $query = $db->simple_select('helpdocs', 'hid', "name = '{$document['name']}'");
        if (!$db->num_rows($query)) {
            $db->insert_query('helpdocs', $document);
        } else {
            $db->update_query('helpdocs', $document, "name = '{$document['name']}'", 1);
        }
        unset($query);
    }

    $taskExists = $db->simple_select('tasks', 'tid', 'file = \'myalerts\'', array('limit' => '1'));
    if ($db->num_rows($taskExists) == 0) {
        require_once MYBB_ROOT.'/inc/functions_task.php';

        $myTask = array(
            'title'       => $lang->myalerts_task_title,
            'file'        => 'myalerts',
            'description' => $lang->myalerts_task_description,
            'minute'      => 0,
            'hour'        => 1,
            'day'         => '*',
            'weekday'     => 1,
            'month'       => '*',
            'nextrun'     => TIME_NOW + 3600,
            'lastrun'     => 0,
            'enabled'     => 1,
            'logging'     => 1,
            'locked'      => 0,
        );

        $task_id = $db->insert_query('tasks', $myTask);
        $theTask = $db->fetch_array($db->simple_select('tasks', '*', 'tid = '.(int) $task_id, 1));
        $nextrun = fetch_next_run($theTask);
        $db->update_query('tasks', 'nextrun = '.$nextrun, 'tid = '.(int) $task_id);
        $plugins->run_hooks('admin_tools_tasks_add_commit');
        $cache->update_tasks();
    } else {
        require_once MYBB_ROOT.'/inc/functions_task.php';
        $theTask = $db->fetch_array($db->simple_select('tasks', '*', 'file = \'myalerts\'', 1));
        $db->update_query('tasks', array('enabled' => 1, 'nextrun' => fetch_next_run($theTask)), 'file = \'myalerts\'');
        $cache->update_tasks();
    }
}

function myalerts_deactivate()
{
    global $Pl, $db;

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->myalerts_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $PL or require_once PLUGINLIBRARY;

    $PL->stylesheet_deactivate('alerts.css');

    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	$myalertsJs = <<<JAVASCRIPT
<script type="text/javascript">
    var unreadAlerts = '{\$mybb->user['unreadAlerts']}';
</script>
<script type="text/javascript" src="{\$mybb->asset_url}/jscripts/myalerts.js"></script>
JAVASCRIPT;
	
    find_replace_templatesets('headerinclude', "#".preg_quote($myalertsJs)."#i", '');
    find_replace_templatesets('header_welcomeblock_member', "#".preg_quote('<myalerts_headericon>')."#i", '');

    $db->update_query('tasks', array('enabled' => 0), 'file = \'myalerts\'');
}

global $settings;

if ($settings['myalerts_enabled']) {
	global $mybb, $db, $cache;
	
	$GLOBALS['myAlertsAlertManager'] = new MybbStuff_MyAlerts_AlertManager($mybb, $db, $cache);
	
	register_shutdown_function(array($GLOBALS['myAlertsAlertManager'], 'commit'));
}

function parse_alert($alert)
{
    global $mybb, $lang, $plugins;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    require_once  MYBB_ROOT.'inc/class_parser.php';
    $parser = new postParser;

    if (empty($alert['avatar'])) {
        $alert['avatar'] = htmlspecialchars_uni($mybb->settings['myalerts_default_avatar']);
    } else {
		$alert['avatar'] = htmlspecialchars_uni($alert['avatar']);
	}
    $alert['userLink'] = get_profile_link($alert['uid']);
    $alert['user'] = format_name($alert['username'], $alert['usergroup'], $alert['displaygroup']);
    $alert['user'] = build_profile_link($alert['user'], $alert['uid']);
    $alert['dateline'] = my_date($mybb->settings['dateformat'], $alert['dateline']).", ".my_date($mybb->settings['timeformat'], $alert['dateline']);

	$alert['alert_status'] = ' alert--read';
    if ($alert['unread'] == 1) {
        $alert['alert_status'] = ' alert--unread';
    }

    $plugins->run_hooks('myalerts_alerts_output_start', $alert);

	$alertFormatManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance($mybb, $lang);

	$formatter = $alertFormatManager->getFormatterForAlertType($alert['alert_type']);

	if ($formatter != null) {
		$alert['message'] = $formatter->formatAlert(null);
	}

    if ($alert['alert_type'] == 'rep' AND $mybb->settings['myalerts_alert_rep']) {
        $alert['message'] = $lang->sprintf($lang->myalerts_rep, $alert['user'], $mybb->user['uid'], $alert['dateline']);
    } elseif ($alert['alert_type'] == 'pm' AND $mybb->settings['myalerts_alert_pm']) {
        $alert['message'] = $lang->sprintf($lang->myalerts_pm, $alert['user'], "<a href=\"{$mybb->settings['bburl']}/private.php?action=read&amp;pmid=".(int) $alert['content']['pm_id']."\">".htmlspecialchars_uni($parser->parse_badwords($alert['content']['pm_title']))."</a>", $alert['dateline']);
    } elseif ($alert['alert_type'] == 'buddylist' AND $mybb->settings['myalerts_alert_buddylist']) {
        $alert['message'] = $lang->sprintf($lang->myalerts_buddylist, $alert['user'], $alert['dateline']);
    } elseif ($alert['alert_type'] == 'quoted' AND $mybb->settings['myalerts_alert_quoted']) {
        $alert['postLink'] = $mybb->settings['bburl'].'/'.get_post_link($alert['content']['pid'], $alert['content']['tid']).'#pid'.$alert['content']['pid'];
        $alert['message'] = $lang->sprintf($lang->myalerts_quoted, $alert['user'], $alert['postLink'], htmlspecialchars_uni($parser->parse_badwords($alert['content']['subject'])), $alert['dateline']);
    } elseif ($alert['alert_type'] == 'post_threadauthor' AND $mybb->settings['myalerts_alert_post_threadauthor']) {
        $alert['threadLink'] = $mybb->settings['bburl'].'/'.get_thread_link($alert['content']['tid'], 0, 'newpost');
        $alert['message'] = $lang->sprintf($lang->myalerts_post_threadauthor, $alert['user'], $alert['threadLink'], htmlspecialchars_uni($parser->parse_badwords($alert['content']['t_subject'])), $alert['dateline']);
    }

    $plugins->run_hooks('myalerts_alerts_output_end', $alert);

    return $alert;
}

if ($settings['myalerts_enabled']) {
    $plugins->add_hook('member_do_register_end', 'myalerts_register_do_end');
}
function myalerts_register_do_end()
{
    global $user_info, $db;

    $query = $db->simple_select('alert_settings', '*');
    $userSettings = array();
    while ($setting = $db->fetch_array($query)) {
        $userSettings[] = array(
            'user_id'    => (int) $user_info['uid'],
            'setting_id' => (int) $setting['id'],
            'value'      => 1,
        );
    }
    $db->insert_query_multiple('alert_setting_values', $userSettings);
}

if ($settings['myalerts_enabled']) {
    $plugins->add_hook('admin_user_users_delete_commit', 'myalerts_user_delete');
}
function myalerts_user_delete()
{
    global $db, $user;
    $user['uid'] = (int) $user['uid'];
    $db->delete_query('alert_setting_values', "user_id='{$user['uid']}'");
    $db->delete_query('alerts', "uid='{$user['uid']}'");
}

if ($settings['myalerts_enabled']) {
    $plugins->add_hook('pre_output_page', 'myalerts_pre_output_page');
}
function myalerts_pre_output_page(&$contents)
{
    global $templates, $mybb, $lang, $myalerts_headericon, $Alerts, $plugins;

    if ($mybb->user['uid']) {
        if (!$lang->myalerts) {
            $lang->load('myalerts');
        }

        try {
            $userAlerts = $GLOBALS['myAlertsAlertManager']->getAlerts(0, $mybb->settings['myalerts_dropdown_limit']);
        } catch (Exception $e) {
        }

        $alerts = '';

        if ($mybb->user['unreadAlerts']) {
            $newAlertsIndicator = ' newAlerts';
        }

        if (is_array($userAlerts) AND count($userAlerts) > 0) {
            foreach ($userAlerts as $alert) {
                $alert = array_merge($alert, parse_alert($alert));

                if ($alert['message']) {
                    eval("\$alerts .= \"".$templates->get('myalerts_alert_row_popup')."\";");
                }

                $readAlerts[] = $alert['id'];
            }
        } else {
            eval("\$alerts = \"".$templates->get('myalerts_alert_row_popup_no_alerts')."\";");
        }

        eval("\$myalerts_headericon = \"".$templates->get('myalerts_headericon')."\";");

        $contents = str_replace('<myalerts_headericon>', $myalerts_headericon, $contents);

        return $contents;
    }
}

if ($settings['myalerts_enabled']) {
    $plugins->add_hook('global_start', 'myalerts_global');
}
function myalerts_global()
{
    global $mybb, $db, $lang, $templatelist;

	myalerts_register_core_formatters($mybb, $db, $lang);

    if (isset($templatelist)) {
        $templatelist .= ',';
    }

    $templatelist .= 'myalerts_headericon,myalerts_popup_row,myalerts_alert_row_no_alerts,myalerts_alert_row_popup_no_alerts';

    if (THIS_SCRIPT == 'usercp.php') {
        $templatelist .= ',myalerts_usercp_nav';
    }

    if (THIS_SCRIPT == 'usercp.php' AND $mybb->input['action'] == 'alerts') {
        $templatelist .= ',myalerts_page,myalerts_alert_row,multipage_page_current,multipage_page,multipage_nextpage,multipage';
    }

    if (THIS_SCRIPT == 'usercp.php' AND $mybb->input['action'] == 'alert_settings') {
        $templatelist .= ',myalerts_setting_row,myalerts_settings_page';
    }
    
    $mybb->user['unreadAlerts'] = 0;

    if ($mybb->user['uid']) {
        global $db, $lang;

        if (!$lang->myalerts) {
            $lang->load('myalerts');
        }

        $userSettings = array();
        $queryString = "SELECT * FROM %salert_settings s LEFT JOIN %salert_setting_values v ON (s.id = v.setting_id) WHERE v.user_id = ".(int) $mybb->user['uid'];
        $query = $db->write_query(sprintf($queryString, TABLE_PREFIX, TABLE_PREFIX));
        while ($row = $db->fetch_array($query)) {
            $mybb->user['myalerts_settings'][$row['code']] = (int) $row['value'];
        }

        $mybb->user['unreadAlerts'] = my_number_format((int) $GLOBALS['myAlertsAlertManager']->getNumUnreadAlerts());
    }
}

if ($settings['myalerts_enabled']) {
    $plugins->add_hook('build_friendly_wol_location_end', 'myalerts_online_location');
}
function myalerts_online_location(&$plugin_array)
{
    global $mybb, $lang;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    if ($plugin_array['user_activity']['activity'] == 'usercp' AND my_strpos($plugin_array['user_activity']['location'], 'alerts')) {
        $plugin_array['location_name'] = $lang->myalerts_online_location_listing;
    }
}

if ($settings['myalerts_enabled']) {
    $plugins->add_hook('misc_help_helpdoc_start', 'myalerts_helpdoc');
}
function myalerts_helpdoc()
{
    global $helpdoc, $lang, $mybb;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    if ($helpdoc['name'] == $lang->myalerts_help_alert_types) {
        if ($mybb->settings['myalerts_alert_rep']) {
            $helpdoc['document'] .= $lang->myalerts_help_alert_types_rep;
        }

        if ($mybb->settings['myalerts_alert_pm']) {
            $helpdoc['document'] .= $lang->myalerts_help_alert_types_pm;
        }

        if ($mybb->settings['myalerts_alert_buddylist']) {
            $helpdoc['document'] .= $lang->myalerts_help_alert_types_buddylist;
        }

        if ($mybb->settings['myalerts_alert_quoted']) {
            $helpdoc['document'] .= $lang->myalerts_help_alert_types_quoted;
        }

        if ($mybb->settings['myalerts_alert_post_threadauthor']) {
            $helpdoc['document'] .= $lang->myalerts_help_alert_types_post_threadauthor;
        }
    }
}

if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_rep']) {
    $plugins->add_hook('reputation_do_add_process', 'myalerts_addAlert_rep');
}
function myalerts_addAlert_rep()
{
    global $mybb, $db, $reputation;

    $queryString = "SELECT s.*, v.*, u.uid FROM %salert_settings s LEFT JOIN %salert_setting_values v ON (v.setting_id = s.id) LEFT JOIN %susers u ON (v.user_id = u.uid) WHERE u.uid = ". (int) $reputation['uid'] ." AND s.code = 'rep' LIMIT 1";
    $query = $db->write_query(sprintf($queryString, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX));

    $userSetting = $db->fetch_array($query);

    if ((int) $userSetting['value'] == 1) {
		$alert = new MybbStuff_MyAlerts_Entity_Alert($reputation['uid'], 'rep', 0);
		
        $GLOBALS['myAlertsAlertManager']->addAlert($alert);
    }
}

if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_pm']) {
    $plugins->add_hook('private_do_send_end', 'myalerts_addAlert_pm');
}
function myalerts_addAlert_pm()
{
    global $mybb, $db, $pm, $pmhandler;

    if ($pm['saveasdraft'] != 1) {
        if (is_array($pm['bcc'])) {
            $toUsers = array_merge($pm['to'], $pm['bcc']);
        } else {
            $toUsers = $pm['to'];
        }

        $pmUsers = array_map("trim", $toUsers);
        $pmUsers = array_unique($pmUsers);

        $users = array();
        $userArray = array();

        foreach ($pmUsers as $user) {
            $users[] = $db->escape_string($user);
        }

        $queryString = "SELECT s.*, v.*, u.uid FROM %salert_settings s LEFT JOIN %salert_setting_values v ON (v.setting_id = s.id) LEFT JOIN %susers u ON (v.user_id = u.uid) WHERE LOWER(u.username) IN ('".my_strtolower(implode("','", $users))."') AND s.code = 'pm'";
        $query = $db->write_query(sprintf($queryString, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX));

        $alerts = array();
        while ($user = $db->fetch_array($query)) {
            if ((int) $user['value'] == 1) {
                $alert = new MybbStuff_MyAlerts_Entity_Alert((int) $user['uid'], 'pm', 0);
				$alert->setExtraDetails(array(
					'pm_title'  =>  $pm['subject'],
					'pm_id'     =>  (int) $pmhandler->pmid,
                ));
				$alerts[] = $alert;
            }
        }

        if (!empty($alerts)) {
			$GLOBALS['myAlertsAlertManager']->addAlerts($alerts);
        }
    }
}

if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_buddylist']) {
    $plugins->add_hook('usercp_do_editlists_end', 'myalerts_alert_buddylist');
}
function myalerts_alert_buddylist()
{
    global $mybb;

    if ($mybb->input['manage'] != 'ignore' AND !isset($mybb->input['delete'])) {
        global $Alerts, $db;


        $addUsers = explode(",", $mybb->input['add_username']);
        $addUsers = array_map("trim", $addUsers);
        $addUsers = array_unique($addUsers);

        $users = array();

        foreach ($addUsers as $user) {
            $users[] = $db->escape_string($user);
        }

        if (count($users) > 0) {
            $queryString = "SELECT s.*, v.*, u.uid FROM %salert_settings s LEFT JOIN %salert_setting_values v ON (v.setting_id = s.id) LEFT JOIN %susers u ON (v.user_id = u.uid) WHERE LOWER(u.username) IN ('".my_strtolower(implode("','", $users))."') AND s.code = 'buddylist'";
            $query = $db->write_query(sprintf($queryString, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX));

			$alerts = array();
            while ($user = $db->fetch_array($query)) {
                if ((int) $user['value'] == 1) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int) $user['uid'], 'buddylist', 0);
					$alerts[] = $alert;
                }
            }

            if (!empty($alerts)) {
                $GLOBALS['myAlertsAlertManager']->addAlerts($alerts);
            }
        }
    }
}

if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_quoted']) {
    $plugins->add_hook('newreply_do_newreply_end', 'myalerts_alert_quoted');
}
function myalerts_alert_quoted()
{
    global $mybb, $Alerts, $db, $pid, $post, $cache;

    $forumPerms = $cache->read('forumpermissions');

    $message = $post['message'];

    $pattern = "#\[quote=([\"']|&quot;|)(.*?)(?:\\1)(.*?)(?:[\"']|&quot;)?\](.*?)\[/quote\](\r\n?|\n?)#esi";

    preg_match_all($pattern, $message, $match);

    if (!array_key_exists('2', $match)) {
        return;
    }

    $matches = array_merge($match[2], $match[3]);

    foreach ($matches as $key => $value) {
        if (empty($value)) {
            unset($matches[$key]);
        }
    }

    $users = array_values($matches);

    if (!empty($users) AND is_array($users)) {
        foreach ($users as $value) {
            $queryArray[] = $db->escape_string($value);
        }

        $queryString = "SELECT s.*, v.*, u.uid, u.usergroup FROM %salert_settings s LEFT JOIN %salert_setting_values v ON (v.setting_id = s.id) LEFT JOIN %susers u ON (v.user_id = u.uid) WHERE LOWER(u.username) IN ('".my_strtolower(implode("','", $queryArray))."') AND u.uid != ". (int) $mybb->user['uid'] ." AND s.code = 'quoted'";
        $uids = $db->write_query(sprintf($queryString, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX));

        $alerts = array();
        while ($uid = $db->fetch_array($uids)) {
            if ((int) $uid['value'] == 1) {
                if (!isset($forumPerms[$post['fid']][$uid['usergroup']]['canviewthreads']) OR (int) $forumPerms[$post['fid']][$uid['usergroup']]['canviewthreads'] != 0) {
                    $userList[] = (int) $uid['uid'];
					$alert = new MybbStuff_MyAlerts_Entity_Alert((int) $uid['uid'], 'quoted', (int) $post['tid']);
					$alert->setExtraDetails(array(
						'tid'       =>  $post['tid'],
						'pid'       =>  $pid,
						'subject'   =>  $post['subject'],
						'fid'       =>  (int) $post['fid'],
					));
					$alerts[] = $alert;
                }
            }
        }

        if (!empty($alerts)) {
            $GLOBALS['myAlertsAlertManager']->addAlerts($alerts);
        }
    }
}

if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_post_threadauthor']) {
    $plugins->add_hook('datahandler_post_insert_post', 'myalerts_alert_post_threadauthor');
}
function myalerts_alert_post_threadauthor(&$post)
{
    global $mybb, $Alerts, $db, $cache;

    if (!$post->data['savedraft']) {
        $forumPerms = $cache->read('forumpermissions');

        if ($post->post_insert_data['tid'] == 0) {
            $query = $db->simple_select('threads', 'uid,subject,fid', 'tid = '.$post->data['tid'], array('limit' => '1'));
            $thread = $db->fetch_array($query);
        } else {
            $query = $db->simple_select('threads', 'uid,subject,fid', 'tid = '.$post->post_insert_data['tid'], array('limit' => '1'));
            $thread = $db->fetch_array($query);
        }

        if ($thread['uid'] != $mybb->user['uid']) {
            // Check if recipient wants this type of alert
            $queryString = "SELECT s.*, v.*, u.uid, u.usergroup FROM %salert_settings s LEFT JOIN %salert_setting_values v ON (v.setting_id = s.id) LEFT JOIN %susers u ON (v.user_id = u.uid) WHERE u.uid = ". (int) $thread['uid'] ." AND s.code = 'post_threadauthor' LIMIT 1";
            $wantsAlert = $db->fetch_array($db->write_query(sprintf($queryString, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX)));

            if ((int) $wantsAlert['value'] == 1) {
                // Check forum permissions
                if (!isset($forumPerms[$thread['fid']][$wantsAlert['usergroup']]['canviewthreads']) OR (int) $forumPerms[$thread['fid']][$wantsAlert['usergroup']]['canviewthreads'] != 0) {
                    //check if alerted for this thread already
                    $query = $db->simple_select('alerts', 'id', 'tid = '.(int) $post->post_insert_data['tid'].' AND unread = 1 AND alert_type = \'post_threadauthor\'');

                    if ($db->num_rows($query) < 1) {
                        $Alerts->addAlert($thread['uid'], 'post_threadauthor', (int) $post->post_insert_data['tid'], $mybb->user['uid'], array(
                            'tid'       =>  $post->post_insert_data['tid'],
                            't_subject' =>  $thread['subject'],
                            'fid'       => (int) $thread['fid'],
                            ));
                    }
                }
            }
        }
    }
}

if ($settings['myalerts_enabled']) {
    $plugins->add_hook('usercp_menu', 'myalerts_usercp_menu', 20);
}
function myalerts_usercp_menu()
{
    global $mybb, $templates, $theme, $usercpmenu, $lang, $collapsed, $collapsedimg;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    if ($mybb->user['unreadAlerts'] > 0) {
        $lang->myalerts_usercp_nav_alerts = '<strong>'.$lang->myalerts_usercp_nav_alerts.' ('.my_number_format((int) $mybb->user['unreadAlerts']).')</strong>';
    }

    eval("\$usercpmenu .= \"".$templates->get('myalerts_usercp_nav')."\";");
}

if ($settings['myalerts_enabled']) {
    $plugins->add_hook('usercp_start', 'myalerts_page');
}
function myalerts_page()
{
    global $mybb;

    if ($mybb->input['action'] == 'alerts') {
        global $db, $lang, $theme, $templates, $headerinclude, $header, $footer, $plugins, $usercpnav;

        if (!$lang->myalerts) {
            $lang->load('myalerts');
        }

        add_breadcrumb($lang->nav_usercp, 'usercp.php');
        add_breadcrumb($lang->myalerts_page_title, 'usercp.php?action=alerts');

        $numAlerts = $GLOBALS['myAlertsAlertManager']->getNumAlerts();
        $page = (int) $mybb->input['page'];
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
        $multipage = multipage($numAlerts, $mybb->settings['myalerts_perpage'], $page, "usercp.php?action=alerts");

        try {
            $alertsList = $GLOBALS['myAlertsAlertManager']->getAlerts($start);
        } catch (Exception $e) {
            die($e->getMessage());
        }

        $readAlerts = array();

        if ($numAlerts > 0 AND !empty($alertsList) AND is_array($alertsList)) {
            foreach ($alertsList as $alert) {
                $altbg = alt_trow();

                $alert = array_merge($alert, parse_alert($alert));

                if ($alert['message']) {
                    eval("\$alertsListing .= \"".$templates->get('myalerts_alert_row')."\";");
                }

                $readAlerts[] = $alert['id'];
            }
        } else {
            $altbg = 'trow1';
            eval("\$alertsListing = \"".$templates->get('myalerts_alert_row_no_alerts')."\";");
        }

		$GLOBALS['myAlertsAlertManager']->markRead($readAlerts);

        eval("\$content = \"".$templates->get('myalerts_page')."\";");
        output_page($content);
    }

    if ($mybb->input['action'] == 'alert_settings') {
        global $db, $lang, $theme, $templates, $headerinclude, $header, $footer, $plugins, $usercpnav;

        if (!$lang->myalerts) {
            $lang->load('myalerts');
        }

        add_breadcrumb($lang->nav_usercp, 'usercp.php');
        add_breadcrumb($lang->myalerts_settings_page_title, 'usercp.php?action=alert_settings');


        $possible_settings = array();
        $query = $db->write_query('SELECT * FROM '.TABLE_PREFIX.'alert_settings s');
        while ($setting = $db->fetch_array($query)) {
            $possible_settings[$setting['code']] = 0;
        }
        unset($query);

        if ($mybb->request_method == 'post') {
            verify_post_check($mybb->input['my_post_key']);

            $settings = array_intersect_key($mybb->input, $possible_settings);

            //  Seeing as unchecked checkboxes just aren't sent, we need an array of all the possible settings, defaulted to 0 (or off) to merge
            $settings = array_merge($possible_settings, $settings);

            $insertSettings = array();
            if (!empty($settings) AND is_array($settings)) {
                $db->delete_query('alert_setting_values', 'user_id = '. (int) $mybb->user['uid']);
                foreach ($settings as $key => $val) {
                    if (strtolower($val) == 'on') {
                        $val = 1;
                    }
                    $id = $db->simple_select('alert_settings', 'id', "code = '". $db->escape_string($key) ."'", array('limit' => 1));
                    if ($db->num_rows($id) == 1) {
                        $insertSettings[] = array(
                            'user_id'    => (int) $mybb->user['uid'],
                            'setting_id' => (int) $db->fetch_field($id, 'id'),
                            'value'      => (int) $val,
                        );
                    }
                }
            }

            $db->insert_query_multiple('alert_setting_values', $insertSettings);
            redirect('usercp.php?action=alert_settings', $lang->myalerts_settings_updated, $lang->myalerts_settings_updated_title);
        } else {
            $settings = array_merge($possible_settings, (array) $mybb->user['myalerts_settings']);
            $settings = array_intersect_key($settings, $possible_settings);
            foreach ($settings as $key => $value) {
                $temparraykey = 'myalerts_alert_'.$key;

                if ($mybb->settings[$temparraykey]) {
                    $altbg = alt_trow();
                    //  variable variables. What fun! http://php.net/manual/en/language.variables.variable.php
                    $tempKey = 'myalerts_setting_'.$key;

                    $baseSettings = array('rep', 'pm', 'buddylist', 'quoted', 'post_threadauthor');

                    $plugins->run_hooks('myalerts_load_lang');

                    if (!isset($lang->$tempKey) AND !in_array($key, $baseSettings)) {
                        @$lang->load($tempKey);
                    }

                    $langline = $lang->$tempKey;

                    $checked = '';
                    if ($value) {
                        $checked = ' checked="checked"';
                    }

                    eval("\$alertSettings .= \"".$templates->get('myalerts_setting_row')."\";");
                }
            }

            eval("\$content = \"".$templates->get('myalerts_settings_page')."\";");
            output_page($content);
        }
    }

    if ($mybb->input['action'] == 'deleteAlert' AND $mybb->input['id']) {
        global $Alerts, $lang;

        verify_post_check($mybb->input['my_post_key']);

        if (!$lang->myalerts) {
            $lang->load('myalerts');
        }

        if ($Alerts->deleteAlerts(array($mybb->input['id']))) {
            if ($mybb->input['accessMethod'] == 'js') {
                $resp = array(
                    'success'   =>  $lang->myalerts_delete_deleted,
                    );
                $numAlerts = $Alerts->getNumAlerts();
                if ($numAlerts < 1) {
                    global $templates;

                    $altbg = 'trow1';
                    eval("\$resp['template'] = \"".$templates->get('myalerts_alert_row_no_alerts')."\";");
                }

                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Content-type: application/json');
                echo json_encode($resp);
            } else {
                redirect('usercp.php?action=alerts', $lang->myalerts_delete_deleted, $lang->myalerts_delete_deleted);
            }
        } else {
            if ($mybb->input['accessMethod'] == 'js') {
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Content-type: application/json');
                echo json_encode(array('error' =>   $lang->myalerts_delete_error));
            } else {
                redirect('usercp.php?action=alerts', $lang->myalerts_delete_error, $lang->myalerts_delete_error);
            }
        }
    }

    if ($mybb->input['action'] == 'deleteReadAlerts') {
        global $Alerts, $lang;

        verify_post_check($mybb->input['my_post_key']);

        if (!$lang->myalerts) {
            $lang->load('myalerts');
        }

        if ($Alerts->deleteAlerts('allRead')) {
            redirect('usercp.php?action=alerts', $lang->myalerts_delete_all_read, $lang->myalerts_delete_mass_deleted);
        } else {
            redirect('usercp.php?action=alerts', $lang->myalerts_delete_mass_error_more, $lang->myalerts_delete_mass_error);
        }
    }

    if ($mybb->input['action'] == 'deleteAllAlerts') {
        global $Alerts, $lang;

        verify_post_check($mybb->input['my_post_key']);

        if (!$lang->myalerts) {
            $lang->load('myalerts');
        }

        if ($Alerts->deleteAlerts('allAlerts')) {
            redirect('usercp.php?action=alerts', $lang->myalerts_delete_all, $lang->myalerts_delete_mass_deleted);
        } else {
            redirect('usercp.php?action=alerts', $lang->myalerts_delete_mass_error_more, $lang->myalerts_delete_mass_error);
        }
    }
}

if ($settings['myalerts_enabled']) {
    $plugins->add_hook('xmlhttp', 'myalerts_xmlhttp', -1);
}
function myalerts_xmlhttp()
{
    global $Alerts, $mybb, $db, $lang, $templates, $plugins;

    require_once MYALERTS_PLUGIN_PATH.'Alerts.class.php';
    try {
        $Alerts = new Alerts($mybb, $db);
    } catch (Exception $e) {
        die($e->getMessage());
    }

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    if ($mybb->input['action'] == 'getNewAlerts') {
        try {
            $newAlerts = $Alerts->getUnreadAlerts();
        } catch (Exception $e) {
            die($e->getMessage());
        }

        if (!empty($newAlerts) AND is_array($newAlerts)) {
            $alertsListing = '';
            $markRead = array();

            foreach ($newAlerts as $alert) {
                $altbg = alt_trow();

                $alert = array_merge($alert, parse_alert($alert));

                if (isset($mybb->input['from']) AND $mybb->input['from'] == 'header') {
                    if ($alert['message']) {
                        eval("\$alertsListing .= \"".$templates->get('myalerts_alert_row_popup')."\";");
                    }
                } else {
                    if ($alert['message']) {
                        eval("\$alertsListing .= \"".$templates->get('myalerts_alert_row')."\";");
                    }
                }

                $markRead[] = $alert['id'];
            }

            $Alerts->markRead($markRead);
        } else {
            if ($mybb->input['from'] == 'header') {
                $alertinfo = $lang->myalerts_no_new_alerts;

                eval("\$alertsListing = \"".$templates->get('myalerts_alert_row_popup')."\";");
            }
        }

        echo $alertsListing;
    }

    if ($mybb->input['action'] == 'getNumUnreadAlerts') {
        echo $Alerts->getNumUnreadAlerts();
    }

    if ($mybb->input['action'] == 'markRead') {
        if ($mybb->user['uid'] == 0) {
            return false;
        }

        if (!verify_post_check($mybb->input['my_post_key'], true)) {
            xmlhttp_error($lang->invalid_post_code);
        }

        $toMarkRead = $mybb->input['toMarkRead'];

        if (isset($mybb->input['js_type']) AND $mybb->input['js_type'] == 'prototype') {
            $toMarkRead = json_decode($toMarkRead);
        }

        $Alerts->markRead($toMarkRead);
    }
}

function myalerts_register_core_formatters($mybb, $db, $lang)
{
	$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance($mybb, $db);

	$formatterManager->registerFormatter(new MybbStuff_MyAlerts_Formatter_RepFormatter($mybb, $lang, 'rep'));
}
