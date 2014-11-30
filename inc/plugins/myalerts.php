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
define('MYALERTS_PLUGIN_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/MyAlerts');

defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

require_once MYBBSTUFF_CORE_PATH . 'ClassLoader.php';

$classLoader = new MybbStuff_Core_ClassLoader();
$classLoader->registerNamespace('MybbStuff_MyAlerts', array(MYALERTS_PLUGIN_PATH . '/src'));
$classLoader->register();

function myalerts_info()
{
    return array(
        'name' => 'MyAlerts',
        'description' => 'A simple notifications/alerts system for MyBB.',
        'website' => 'http://mybbstuff.com/myalerts',
        'author' => 'Euan T',
        'authorsite' => 'http://euantor.com',
        'version' => '2.0.0',
        'compatibility' => '18*',
    );
}

function myalerts_install()
{
    global $db, $cache, $plugins;

    $plugin_info = myalerts_info();
    $euantor_plugins = $cache->read('euantor_plugins');
    $euantor_plugins['myalerts'] = array(
        'title' => 'MyAlerts',
        'version' => $plugin_info['version'],
    );
    $cache->update('euantor_plugins', $euantor_plugins);

    $collation = $db->build_create_table_collation();

    if (!$db->table_exists('alerts')) {
        $db->write_query(
            "CREATE TABLE " . TABLE_PREFIX . "alerts(
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `uid` int(10) unsigned NOT NULL,
                `unread` tinyint(4) NOT NULL DEFAULT '1',
                `dateline` datetime NOT NULL,
                `alert_type_id` int(10) unsigned NOT NULL,
                `object_id` int(10) unsigned NOT NULL DEFAULT '0',
                `from_user_id` int(10) unsigned DEFAULT NULL,
                `forced` int(1) NOT NULL DEFAULT '0',
                `extra_details` text,
                PRIMARY KEY (`id`),
                KEY `uid_index` (`id`)
            ) ENGINE=MyISAM{$collation};"
        );
    }

    if (!$db->table_exists('alert_types')) {
        $db->write_query(
            "CREATE TABLE " . TABLE_PREFIX . "alert_types(
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `code` varchar(100) NOT NULL DEFAULT '',
                `enabled` tinyint(4) NOT NULL DEFAULT '1',
                `can_be_user_disabled` tinyint(4) NOT NULL DEFAULT '1',
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_code` (`code`)
            ) ENGINE=MyISAM{$collation};"
        );
    }

    if (!$db->field_exists('myalerts_disabled_alert_types', 'users')) {
        $db->add_column('users', 'myalerts_disabled_alert_types', 'TEXT NOT NULL');
    }

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);

    $insertArray = array('rep', 'pm', 'buddylist', 'quoted', 'post_threadauthor');
    $alertTypesToAdd = array();

    foreach ($insertArray as $type) {
        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode($type);
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);

        $alertTypesToAdd[] = $alertType;
    }

    $alertTypeManager->addTypes($alertTypesToAdd);

    $plugins->run_hooks('myalerts_install');
}

function myalerts_is_installed()
{
    global $db;

    return $db->table_exists('alerts') && $db->table_exists('alert_types');
}

function myalerts_uninstall()
{
    global $db, $lang, $PL, $plugins;

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->myalerts_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $PL or require_once PLUGINLIBRARY;

    $plugins->run_hooks('myalerts_uninstall');

    if ($db->table_exists('alerts')) {
        $db->drop_table('alerts');
    }

    if ($db->table_exists('alert_types')) {
        $db->drop_table('alert_types');
    }

    $db->drop_column('users', 'myalerts_disabled_alert_types');

    $PL->settings_delete('myalerts', true);
    $PL->templates_delete('myalerts');
    $PL->stylesheet_delete('alerts.css');

    $db->delete_query('tasks', 'file = \'myalerts\'');
}

function myalerts_activate()
{
    global $db, $lang, $PL, $plugins, $cache;

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

    $euantor_plugins = $cache->read('euantor_plugins');
//    if ($euantor_plugins['myalerts']['version'] != $plugin_info['version']) {
//        require MYALERTS_PLUGIN_PATH . '/upgrader.php';
//        myalerts_upgrader_run($plugin_info['version'], $euantor_plugins['myalerts']['version']);
//    }
    $euantor_plugins['myalerts'] = array(
        'title' => 'MyAlerts',
        'version' => $plugin_info['version'],
    );
    $cache->update('euantor_plugins', $euantor_plugins);

    $PL->settings(
        'myalerts',
        $lang->setting_group_myalerts,
        $lang->setting_group_myalerts_desc,
        array(
            'perpage' => array(
                'title' => $lang->setting_myalerts_perpage,
                'description' => $lang->setting_myalerts_perpage_desc,
                'value' => '10',
                'optionscode' => 'text',
            ),
            'dropdown_limit' => array(
                'title' => $lang->setting_myalerts_dropdown_limit,
                'description' => $lang->setting_myalerts_dropdown_limit_desc,
                'value' => '5',
                'optionscode' => 'text',
            ),
            'autorefresh' => array(
                'title' => $lang->setting_myalerts_autorefresh,
                'description' => $lang->setting_myalerts_autorefresh_desc,
                'value' => '0',
                'optionscode' => 'text',
            ),
            'avatar_size' => array(
                'title' => $lang->setting_myalerts_avatar_size,
                'description' => $lang->setting_myalerts_avatar_size_desc,
                'optionscode' => 'text',
                'value' => '64|64',
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

    // Attach usercp.css to alerts.php
    $query = $db->simple_select('themestylesheets', 'sid,attachedto,tid', "name = 'usercp.css'");

    while ($userCpStylesheet = $db->fetch_array($query)) {
        $sid = (int) $userCpStylesheet['sid'];

        $db->update_query('themestylesheets', array(
                'attachedto' => $db->escape_string($userCpStylesheet['attachedto'] . '|alerts.php'),
            ), "sid = {$sid}");

        update_theme_stylesheet_list((int) $userCpStylesheet['tid']);
    }

    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';


    find_replace_templatesets('headerinclude', '/$/', '{$myalerts_js}');
    find_replace_templatesets(
        'header_welcomeblock_member',
        "#" . preg_quote('{$modcplink}') . "#i",
        '{$myalerts_headericon}{$modcplink}'
    );

    $taskExists = $db->simple_select('tasks', 'tid', 'file = \'myalerts\'', array('limit' => '1'));
    if ($db->num_rows($taskExists) == 0) {
        require_once MYBB_ROOT . '/inc/functions_task.php';

        $myTask = array(
            'title' => $lang->myalerts_task_title,
            'file' => 'myalerts',
            'description' => $lang->myalerts_task_description,
            'minute' => 0,
            'hour' => 1,
            'day' => '*',
            'weekday' => 1,
            'month' => '*',
            'nextrun' => TIME_NOW + 3600,
            'lastrun' => 0,
            'enabled' => 1,
            'logging' => 1,
            'locked' => 0,
        );

        $task_id = $db->insert_query('tasks', $myTask);
        $theTask = $db->fetch_array($db->simple_select('tasks', '*', 'tid = ' . (int) $task_id, 1));
        $nextrun = fetch_next_run($theTask);
        $db->update_query('tasks', 'nextrun = ' . $nextrun, 'tid = ' . (int) $task_id);
        $plugins->run_hooks('admin_tools_tasks_add_commit');
        $cache->update_tasks();
    } else {
        require_once MYBB_ROOT . '/inc/functions_task.php';
        $theTask = $db->fetch_array($db->simple_select('tasks', '*', 'file = \'myalerts\'', 1));
        $db->update_query('tasks', array('enabled' => 1, 'nextrun' => fetch_next_run($theTask)), 'file = \'myalerts\'');
        $cache->update_tasks();
    }

    $plugins->run_hooks('myalerts_activate');
}

function myalerts_deactivate()
{
    global $PL, $db, $lang, $plugins;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->myalerts_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    isset($PL) or require_once PLUGINLIBRARY;

    $plugins->run_hooks('myalerts_deactivate');

    $PL->stylesheet_deactivate('alerts.css');

    // remove usercp.css from alerts.php
    $query = $db->simple_select('themestylesheets', 'sid,attachedto,tid', "name = 'usercp.css'");

    while ($userCpStylesheet = $db->fetch_array($query)) {
        $sid = (int) $userCpStylesheet['sid'];

        $attachedTo = str_replace('|alerts.php', '', $userCpStylesheet['attachedto']);

        $db->update_query('themestylesheets', array(
                'attachedto' => $db->escape_string($attachedTo),
            ), "sid = {$sid}");

        update_theme_stylesheet_list((int) $userCpStylesheet['tid']);
    }

    require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";

    find_replace_templatesets('headerinclude', "#" . preg_quote('{$myalerts_js}') . "#i", '');
    find_replace_templatesets('header_welcomeblock_member', "#" . preg_quote('{$myalerts_headericon}') . "#i", '');

    $db->update_query('tasks', array('enabled' => 0), 'file = \'myalerts\'');
}

/**
 * Check whether MyAlerts is activated. Useful for 3rd parties. Example usage:
 *
 * <pre>
 * if (function_exists('myalerts_is_activated') && myalerts_is_activated()) {
 *  // Do work with MyAlerts.
 * }
 * </pre>
 *
 * @return bool Whetehr MyAlerts is activated and installed.
 */
function myalerts_is_activated()
{
    global $cache;

    $plugins = $cache->read('plugins');
    $activePlugins = $plugins['active'];

    $isActive = false;

    if(in_array('myalerts', $activePlugins)) {
        $isActive = true;
    }

    return myalerts_is_installed() && $isActive;
}

function parse_alert(MybbStuff_MyAlerts_Entity_Alert $alertToParse)
{
    global $mybb, $lang, $plugins;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    /** @var MybbStuff_MyAlerts_Formatter_AbstractFormatter $formatter */
    $formatter = MybbStuff_MyAlerts_AlertFormatterManager::getInstance()
        ->getFormatterForAlertType($alertToParse->getType()->getCode());

    $outputAlert = array();

    if ($formatter != null) {
        $plugins->run_hooks('myalerts_alerts_output_start', $alert);

        $formatter->init();

        $fromUser = $alertToParse->getFromUser();

        $maxDimensions = str_replace('|', 'x', $mybb->settings['myalerts_avatar_size']);

        $outputAlert['avatar'] = format_avatar($fromUser['avatar'], $mybb->settings['myalerts_avatar_size'], $maxDimensions);
        $outputAlert['avatar']['image'] = htmlspecialchars_uni($outputAlert['avatar']['image']);

        $outputAlert['id'] = $alertToParse->getId();
        $outputAlert['from_user'] = format_name(
            htmlspecialchars_uni($fromUser['username']),
            $fromUser['usergroup'],
            $fromUser['displaygroup']
        );
        $outputAlert['from_user_raw_profilelink'] = get_profile_link((int) $fromUser['uid']); // htmlspecialchars_uni done by get_profile_link
        $outputAlert['from_user_profilelink'] = build_profile_link($outputAlert['from_user'], $fromUser['uid']);
        $outputAlert['dateline'] = $alertToParse->getCreatedAt()->format('Y-m-d H:i');

        $outputAlert['alert_status'] = ' alert--read';
        if ($alertToParse->getUnread()) {
            $outputAlert['alert_status'] = ' alert--unread';
        }

        $outputAlert['message'] = $formatter->formatAlert($alertToParse, $outputAlert);

        $outputAlert['alert_code'] = $alertToParse->getType()->getCode();

        $plugins->run_hooks('myalerts_alerts_output_end', $alert);
    }

    return $outputAlert;
}


$plugins->add_hook('admin_user_users_delete_commit', 'myalerts_user_delete');
function myalerts_user_delete()
{
    global $db, $user;
    $user['uid'] = (int) $user['uid'];
    $db->delete_query('alerts', "uid='{$user['uid']}'");
}

$plugins->add_hook('global_start', 'myalerts_global_start', -1);
function myalerts_global_start()
{
    global $mybb, $templatelist, $templates, $myalerts_js;

    if (isset($templatelist)) {
        $templatelist .= ',';
    }

    $templatelist .= 'myalerts_headericon,myalerts_popup_row,myalerts_alert_row_no_alerts,myalerts_alert_row_popup,myalerts_alert_row_popup_no_alerts,myalerts_scripts';

    if (THIS_SCRIPT == 'usercp.php' || THIS_SCRIPT == 'alerts.php') {
        $templatelist .= ',myalerts_usercp_nav';
    }

    if (THIS_SCRIPT == 'alerts.php') { // Hack to load User CP menu items in alerts.php without querying for templates
        $templatelist .= ',usercp_nav_messenger,usercp_nav_messenger_tracking,usercp_nav_messenger_compose,usercp_nav_messenger_folder,usercp_nav_changename,usercp_nav_editsignature,usercp_nav_profile,usercp_nav_attachments,usercp_nav_misc,usercp_nav';
    }

    if (THIS_SCRIPT == 'alerts.php') {
        $templatelist .= ',myalerts_page,myalerts_alert_row,multipage_page_current,multipage_page,multipage_nextpage,multipage';
    }

    if (THIS_SCRIPT == 'alerts.php' && $mybb->input['action'] == 'settings') {
        $templatelist .= ',myalerts_setting_row,myalerts_settings_page';
    }

    $mybb->user['unreadAlerts'] = 0;

    if ($mybb->user['uid'] > 0) {
        global $lang;

        if (!$lang->myalerts) {
            $lang->load('myalerts');
        }

        $mybb->user['myalerts_disabled_alert_types'] = json_decode($mybb->user['myalerts_disabled_alert_types']);
        if (!empty($mybb->user['myalerts_disabled_alert_types']) && is_array($mybb->user['myalerts_disabled_alert_types']
            )
        ) {
            $mybb->user['myalerts_disabled_alert_types'] = array_map('intval',
                                                                     $mybb->user['myalerts_disabled_alert_types']
            );
        } else {
            $mybb->user['myalerts_disabled_alert_types'] = array();
        }

        myalerts_create_instances();

        $mybb->user['unreadAlerts'] = my_number_format((int) MybbStuff_MyAlerts_AlertManager::getInstance()->getNumUnreadAlerts(
            )
        );
    }

    $myalerts_js = eval($templates->render('myalerts_scripts'));
}

$plugins->add_hook('admin_tabs', 'myalerts_create_instances', -1);
function myalerts_create_instances()
{
    global $mybb, $db, $cache, $lang;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance(
        $db,
        $cache
    );

    MybbStuff_MyAlerts_AlertManager::createInstance($mybb, $db, $cache,
                                                                                       $alertTypeManager
    );

    MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);

    myalerts_register_core_formatters($mybb, $lang);

    register_shutdown_function(array(MybbStuff_MyAlerts_AlertManager::getInstance(), 'commit'));
}

$plugins->add_hook('global_intermediate', 'myalerts_global_intermediate');
function myalerts_global_intermediate()
{
    global $templates, $mybb, $lang, $myalerts_headericon;

    if ($mybb->user['uid']) {
        if (!$lang->myalerts) {
            $lang->load('myalerts');
        }

        $userAlerts = MybbStuff_MyAlerts_AlertManager::getInstance()
            ->getAlerts(0, $mybb->settings['myalerts_dropdown_limit']);

        $alerts = '';

        if ($mybb->user['unreadAlerts']) {
            $newAlertsIndicator = 'alerts--new';
        }

        if (is_array($userAlerts) && !empty($userAlerts)) {
            foreach ($userAlerts as $alertObject) {
                $altbg = alt_trow();

                $alert = parse_alert($alertObject);

                if ($alert['message']) {
                    $alerts .= eval($templates->render('myalerts_alert_row_popup'));
                }

                $readAlerts[] = $alert['id'];
            }
        } else {
            $altbg = 'trow1';

            $alerts = eval($templates->render('myalerts_alert_row_popup_no_alerts'));
        }

        $myalerts_return_link = htmlspecialchars_uni(myalerts_get_current_url());

        $myalerts_headericon = eval($templates->render('myalerts_headericon'));
    }
}

/**
 * Returns the full current URL.
 *
 * @return string The current URL, including query parameters.
 */
function myalerts_get_current_url()
{
    global $mybb;

    $format = $mybb->settings['bburl'] . '/' . THIS_SCRIPT;

    if (!empty($_GET)) {
        $format .= '?';

        foreach ($_GET as $key => $val) {
            $key = urlencode($key);
            $val = urlencode($val);
            $format .= "{$key}={$val}&";
        }
    }

    $format = rtrim($format, '&');

    return $format;
}

$plugins->add_hook('datahandler_user_insert', 'myalerts_datahandler_user_insert');
function myalerts_datahandler_user_insert(&$dataHandler)
{
    global $db;

    $dataHandler->user_insert_data['myalerts_disabled_alert_types'] = $db->escape_string(json_encode(array()));
}

$plugins->add_hook('build_friendly_wol_location_end', 'myalerts_online_location');
function myalerts_online_location(&$plugin_array)
{
    global $lang;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    $inUserCpAlerts = $plugin_array['user_activity']['activity'] == 'usercp' AND my_strpos(
        $plugin_array['user_activity']['location'],
        'alerts'
    );

    $inAlertsPage = $plugin_array['user_activity']['activity'] == 'alerts';

    if ($inUserCpAlerts || $inAlertsPage) {
        $plugin_array['location_name'] = $lang->myalerts_online_location_listing;
    }
}

$plugins->add_hook('reputation_do_add_process', 'myalerts_addAlert_rep');
function myalerts_addAlert_rep()
{
    global $reputation;

    /** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
    $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('rep');

    $alert = new MybbStuff_MyAlerts_Entity_Alert($reputation['uid'], $alertType, 0);

    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
}

$plugins->add_hook('private_do_send_end', 'myalerts_addAlert_pm');
function myalerts_addAlert_pm()
{
    global $pm, $pmhandler, $db;

    if ($pm['saveasdraft'] != 1) {
        if (is_array($pm['bcc'])) {
            $toUsers = array_merge($pm['to'], $pm['bcc']);
        } else {
            $toUsers = $pm['to'];
        }

        $pmUsers = array_map("trim", $toUsers);
        $pmUsers = array_unique($pmUsers);
        $pmUsers = array_filter($pmUsers); // Remove blank entries that may be caused by merging arrays.
        $pmUsers = array_values($pmUsers); // Reset array keys after removing entries

        /** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
        $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('pm');

        $userNames = array_map(array($db, 'escape_string'), $pmUsers);

        $userNames = "'" . implode("','", $userNames) . "'";
        $query = $db->simple_select('users', 'uid,username', "username IN({$userNames})");

        $pmId = 0;

        $alerts = array();
        while ($user = $db->fetch_array($query)) {
            if (is_array($pmhandler->pmid)) {
                $foundKey = array_search($user['username'], $pmUsers);

                if ($foundKey !== false) {
                    if (isset($pmhandler->pmid[$foundKey])) {
                        $pmId = (int) $pmhandler->pmid[$foundKey];
                    } else {
                        $pmId = $pmhandler->pmid[0]; // Really broken, just use first ID in the array and hope for the best...
                    }
                }
            } else {
                $pmId = (int) $pmhandler->pmid;
            }

            $alert = new MybbStuff_MyAlerts_Entity_Alert((int) $user['uid'], $alertType, $pmId);
            $alert->setExtraDetails(
                array(
                    'pm_title' => $pm['subject'],
                )
            );
            $alerts[] = $alert;
        }

        if (!empty($alerts)) {
            MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts($alerts);
        }
    }
}

$plugins->add_hook('usercp_do_editlists_end', 'myalerts_alert_buddylist');
function myalerts_alert_buddylist()
{
    global $mybb, $error_message, $db;

    if ($mybb->get_input('manage') != 'ignored' && !isset($mybb->input['delete']) && empty($error_message)) {
        $addUsers = explode(",", $mybb->input['add_username']);
        $addUsers = array_map("trim", $addUsers);
        $addUsers = array_unique($addUsers);

        if (count($addUsers) > 0) {
            /** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
            $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('buddylist');

            $userNames = array_map(array($db, 'escape_string'), $addUsers);

            $userNames = "'" . implode("','", $userNames) . "'";
            $query = $db->simple_select('users', 'uid', "username IN({$userNames})");

            $alerts = array();
            while ($user = $db->fetch_array($query)) {
                $alert = new MybbStuff_MyAlerts_Entity_Alert((int) $user['uid'], $alertType, 0);
                $alerts[] = $alert;
            }

            if (!empty($alerts)) {
                MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts($alerts);
            }
        }
    }
}

$plugins->add_hook('newreply_do_newreply_end', 'myalerts_alert_quoted');
function myalerts_alert_quoted()
{
    global $pid, $post, $db;

    $message = $post['message'];

    $pattern = "#\[quote=([\"']|&quot;|)(.*?)(?:\\1)(.*?)(?:[\"']|&quot;)?\](.*?)\[/quote\](\r\n?|\n?)#esi";

    preg_match_all($pattern, $message, $match);

    if (!array_key_exists('2', $match)) {
        return;
    }

    foreach ($match as $key => $value) {
        if (empty($value)) {
            unset($match[$key]);
        }
    }

    if (isset($match[2])) {
        $users = array_values($match[2]);

        if (!empty($users)) {
            /** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
            $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('quoted');

            $userNames = array_map(array($db, 'escape_string'), $users);

            $userNames = "'" . implode("','", $userNames) . "'";
            $query = $db->simple_select('users', 'uid', "username IN({$userNames})");

            $alerts = array();
            while ($uid = $db->fetch_array($query)) {
                $forumPerms = forum_permissions($post['fid'], $uid['uid']);

                if ($forumPerms['canview'] != 0 || $forumPerms['canviewthreads'] != 0) {
                    $userList[] = (int) $uid['uid'];
                    $alert = new MybbStuff_MyAlerts_Entity_Alert(
                        (int) $uid['uid'],
                        $alertType,
                        (int) $post['tid']
                    );
                    $alert->setExtraDetails(
                        array(
                            'tid' => $post['tid'],
                            'pid' => $pid,
                            'subject' => $post['subject'],
                            'fid' => (int) $post['fid'],
                        )
                    );
                    $alerts[] = $alert;
                }
            }

            if (!empty($alerts)) {
                MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts($alerts);
            }
        }
    }
}

$plugins->add_hook('datahandler_post_insert_post', 'myalerts_alert_post_threadauthor');
function myalerts_alert_post_threadauthor(&$post)
{
    global $mybb, $db;

    if (!$post->data['savedraft']) {
        /** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
        $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('post_threadauthor');

        if (isset($alertType) && $alertType->getEnabled()) {
            if ($post->post_insert_data['tid'] == 0) {
                $query = $db->simple_select(
                    'threads',
                    'uid,subject,fid',
                    'tid = ' . $post->data['tid'],
                    array('limit' => '1')
                );
                $thread = $db->fetch_array($query);
            } else {
                $query = $db->simple_select(
                    'threads',
                    'uid,subject,fid',
                    'tid = ' . $post->post_insert_data['tid'],
                    array('limit' => '1')
                );
                $thread = $db->fetch_array($query);
            }

            if ($thread['uid'] != $mybb->user['uid']) {
                $forumPerms = forum_permissions($thread['fid'], $thread['uid']);

                // Check forum permissions
                if ($forumPerms['canview'] != 0 || $forumPerms['canviewthreads'] != 0) {
                    //check if alerted for this thread already
                    $query = $db->simple_select(
                        'alerts',
                        'id',
                        'object_id = ' . (int) $post->post_insert_data['tid'] . " AND unread = 1 AND alert_type_id = {$alertType->getId(
                        )}"
                    );

                    if ($db->num_rows($query) == 0) {
                        $alert = new MybbStuff_MyAlerts_Entity_Alert(
                            $thread['uid'],
                            $alertType,
                            (int) $post->post_insert_data['tid']
                        );
                        $alert->setExtraDetails(
                            array(
                                'tid' => $post->post_insert_data['tid'],
                                't_subject' => $thread['subject'],
                                'fid' => (int) $thread['fid'],
                            )
                        );

                        MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                    }
                }
            }
        }
    }
}


$plugins->add_hook('usercp_menu', 'myalerts_usercp_menu', 20);
function myalerts_usercp_menu()
{
    global $mybb, $templates, $theme, $usercpmenu, $lang, $collapsed, $collapsedimg;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    if ($mybb->user['unreadAlerts'] > 0) {
        $lang->myalerts_usercp_nav_alerts = '<strong>' . $lang->myalerts_usercp_nav_alerts . ' (' . my_number_format(
                (int) $mybb->user['unreadAlerts']
            ) . ')</strong>';
    }

    $usercpmenu .= eval($templates->render('myalerts_usercp_nav'));
}

$plugins->add_hook('xmlhttp', 'myalerts_xmlhttp', -1);
function myalerts_xmlhttp()
{
    global $mybb, $lang, $templates, $db;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    myalerts_create_instances();

    if ($mybb->get_input('action') == 'getNewAlerts') {
        header('Content-Type: application/json');

        $newAlerts = MybbStuff_MyAlerts_AlertManager::getInstance()->getUnreadAlerts();

        $alertsListing = '';

        $alertsToReturn = array();

        if (is_array($newAlerts) && !empty($newAlerts)) {
            $toMarkRead = array();

            foreach ($newAlerts as $alertObject) {
                $altbg = alt_trow();

                $alert = parse_alert($alertObject);

                $alertsToReturn[] = $alert;

                if (isset($mybb->input['from']) && $mybb->input['from'] == 'header') {
                    if ($alert['message']) {
                        $alertsListing .= eval($templates->render('myalerts_alert_row_popup', true, false));
                    }
                } else {
                    if ($alert['message']) {
                        $alertsListing .= eval($templates->render('myalerts_alert_row', true, false));
                    }
                }

                $toMarkRead[] = $alertObject->getId();
            }

            MybbStuff_MyAlerts_AlertManager::getInstance()->markRead($toMarkRead);
        } else {
            $from = $mybb->get_input('from', MyBB::INPUT_STRING);

            $altbg = alt_trow();

            if (!empty($from) && $from == 'header') {
                $alertsListing = eval($templates->render('myalerts_alert_row_popup_no_alerts', true, false));
            } else {
                $alertsListing = eval($templates->render('myalerts_alert_row_no_alerts', true, false));
            }
        }

        echo json_encode(array(
                             'alerts' => $alertsToReturn,
                             'template' => $alertsListing,
                         )
        );
    }

    if ($mybb->get_input('action') == 'myalerts_delete') {
        header('Content-Type: application/json');

        $id = $mybb->get_input('id', MyBB::INPUT_INT);
        $userId = (int) $mybb->user['uid'];

        $toReturn = array();

        if ($id > 0) {
            if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
                $toReturn = array(
                    'errors' => array($lang->invalid_post_code),
                );
            } else {
                $db->delete_query('alerts', "id = {$id} AND uid = {$userId}");

                $newAlerts = MybbStuff_MyAlerts_AlertManager::getInstance()->getUnreadAlerts();

                $alertsListing = '';

                $alertsToReturn = array();

                if (is_array($newAlerts) && !empty($newAlerts)) {
                    $toMarkRead = array();

                    foreach ($newAlerts as $alertObject) {
                        $altbg = alt_trow();

                        $alert = parse_alert($alertObject);

                        $alertsToReturn[] = $alert;

                        if (isset($mybb->input['from']) && $mybb->input['from'] == 'header') {
                            if ($alert['message']) {
                                $alertsListing .= eval($templates->render('myalerts_alert_row_popup', true, false));
                            }
                        } else {
                            if ($alert['message']) {
                                $alertsListing .= eval($templates->render('myalerts_alert_row', true, false));
                            }
                        }

                        $toMarkRead[] = $alertObject->getId();
                    }

                    MybbStuff_MyAlerts_AlertManager::getInstance()->markRead($toMarkRead);
                } else {
                    $from = $mybb->get_input('from', MyBB::INPUT_STRING);

                    $altbg = alt_trow();

                    if (!empty($from) && $from == 'header') {
                        $alertsListing = eval($templates->render('myalerts_alert_row_popup_no_alerts', true, false));
                    } else {
                        $alertsListing = eval($templates->render('myalerts_alert_row_no_alerts', true, false));
                    }
                }

                $toReturn = array(
                    'success' => true,
                    'template' => $alertsListing,
                );
            }
        } else {
            $toReturn = array(
                'errors' => array($lang->myalerts_error_alert_not_found),
            );
        }

        echo json_encode($toReturn);
    }

    if ($mybb->input['action'] == 'getNumUnreadAlerts') {
        echo MybbStuff_MyAlerts_AlertManager::getInstance()->getNumUnreadAlerts();
    }
}

function myalerts_register_core_formatters($mybb, $lang)
{
    /** @var MybbStuff_Myalerts_AlertFormatterManager $formatterManager */
    $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

    $formatterManager->registerFormatter(new MybbStuff_MyAlerts_Formatter_RepFormatter($mybb, $lang, 'rep'));
    $formatterManager->registerFormatter(
        new MybbStuff_MyAlerts_Formatter_BuddylistFormatter($mybb, $lang, 'buddylist')
    );
    $formatterManager->registerFormatter(new MybbStuff_MyAlerts_Formatter_QuotedFormatter($mybb, $lang, 'quoted'));
    $formatterManager->registerFormatter(new MybbStuff_MyAlerts_Formatter_PrivateMessageFormatter($mybb, $lang, 'pm'));
    $formatterManager->registerFormatter(
        new MybbStuff_MyAlerts_Formatter_ThreadAuthorReplyFormatter($mybb, $lang, 'post_threadauthor')
    );
}

$plugins->add_hook('admin_config_menu', 'myalerts_acp_config_menu');
function myalerts_acp_config_menu(&$sub_menu)
{
    global $lang;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    $sub_menu[] = array(
        'id' => 'myalerts_alert_types',
        'title' => $lang->myalerts_alert_types,
        'link' => 'index.php?module=config-myalerts_alert_types'
    );
}

$plugins->add_hook('admin_config_action_handler', 'myalerts_acp_config_action_handler');
function myalerts_acp_config_action_handler(&$actions)
{
    $actions['myalerts_alert_types'] = array(
        'active' => 'myalerts_alert_types',
        'file' => 'myalerts.php',
    );
}

$plugins->add_hook('admin_config_permissions', 'myalerts_acp_config_permissions');
function myalerts_acp_config_permissions(&$admin_permissions)
{
    global $lang;

    if (!$lang->myalerts) {
        $lang->load('myalerts');
    }

    $admin_permissions['myalerts_alert_types'] = $lang->myalerts_can_manage_alert_types;
}
