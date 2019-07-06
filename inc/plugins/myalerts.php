<?php
/**
 *  MyAlerts Core Plugin File
 *
 *  A simple notification/alert system for MyBB
 *
 * @package MyAlerts
 * @author  Euan T. <euan@euantor.com>
 * @license http://opensource.org/licenses/mit-license.php MIT license
 * @version 2.0.3
 */

if (!defined('IN_MYBB')) {
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

defined(
	'MYBBSTUFF_CORE_PATH'
) or define('MYBBSTUFF_CORE_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/Core/');
define('MYALERTS_PLUGIN_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/MyAlerts');

defined(
	'PLUGINLIBRARY'
) or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

require_once MYBBSTUFF_CORE_PATH . 'ClassLoader.php';

$classLoader = new MybbStuff_Core_ClassLoader();
$classLoader->registerNamespace(
	'MybbStuff_MyAlerts',
	array(MYALERTS_PLUGIN_PATH . '/src')
);
$classLoader->register();

function myalerts_info()
{
	return array(
		'name'          => 'MyAlerts',
		'description'   => 'A simple notifications/alerts system for MyBB.',
		'website'       => 'http://mybbstuff.com/myalerts',
		'author'        => 'Euan T',
		'authorsite'    => 'http://euantor.com',
		'version'       => '2.0.4',
		'compatibility' => '18*',
		'codename'      => 'mybbstuff_myalerts',
	);
}

function myalerts_install()
{
	global $db, $cache, $plugins;

	$plugin_info = myalerts_info();
	$euantor_plugins = $cache->read('euantor_plugins');
	$euantor_plugins['myalerts'] = array(
		'title'   => 'MyAlerts',
		'version' => $plugin_info['version'],
	);
	$cache->update('euantor_plugins', $euantor_plugins);

	$collation = $db->build_create_table_collation();

	if (!$db->table_exists('alerts')) {
        switch ($db->type) {
            case 'pgsql':
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "alerts(
                        id serial,
                        uid int NOT NULL,
                        unread smallint NOT NULL DEFAULT '1',
                        dateline timestamp NOT NULL,
                        alert_type_id int NOT NULL,
                        object_id int NOT NULL DEFAULT '0',
                        from_user_id int DEFAULT NULL,
                        forced smallint NOT NULL DEFAULT '0',
                        extra_details text,
                        PRIMARY KEY (id)
                    );"
                );
                $db->write_query("CREATE INDEX uid_index ON " . TABLE_PREFIX . "alerts (uid);");
                break;
            default:
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
                        KEY `uid_index` (`uid`)
                    ) ENGINE=MyISAM{$collation};"
                );
                break;
        }
	}

	if (!$db->table_exists('alert_types')) {
        switch ($db->type) {
            case 'pgsql':
                $db->write_query(
                    "CREATE TABLE " . TABLE_PREFIX . "alert_types(
                        id serial,
                        code varchar(100) NOT NULL DEFAULT '' UNIQUE,
                        enabled smallint NOT NULL DEFAULT '1',
                        can_be_user_disabled smallint NOT NULL DEFAULT '1',
                        PRIMARY KEY (id)
                    );"
                );
                break;
            default:
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
                break;
        }
	}

	if (!$db->field_exists('myalerts_disabled_alert_types', 'users')) {
		$db->add_column(
			'users',
			'myalerts_disabled_alert_types',
			'TEXT'
		);
	}

	$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance(
		$db,
		$cache
	);

	$insertArray = array(
		'rep',
		'pm',
		'buddylist',
		'quoted',
		'post_threadauthor',
		'subscribed_thread',
		'rated_threadauthor',
		'voted_threadauthor'
	);
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

	return $db->table_exists('alerts');
}

function myalerts_uninstall()
{
	global $db, $lang, $cache, $PL, $plugins;

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

	if ($db->field_exists('myalerts_disabled_alert_types', 'users')) {
		$db->drop_column('users', 'myalerts_disabled_alert_types');
	}

	$PL->settings_delete('myalerts', true);
	$PL->templates_delete('myalerts');
	$PL->stylesheet_delete('alerts.css');

	$db->delete_query('tasks', 'file = \'myalerts\'');

    $cache->delete('mybbstuff_myalerts_alert_types');
}

function myalerts_activate()
{
	global $db, $lang, $PL, $plugins, $cache;

	if (!isset($lang->myalerts)) {
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

	$euantorPlugins = $cache->read('euantor_plugins');

	if (!empty($euantorPlugins) && isset($euantorPlugins['myalerts'])) {
		$oldVersion = $euantorPlugins['myalerts'];

		if ($oldVersion['version'] == '1.05') {
			myalerts_upgrade_105_200();
		}
	}

	$euantorPlugins['myalerts'] = array(
		'title'   => 'MyAlerts',
		'version' => $plugin_info['version'],
	);
	$cache->update('euantor_plugins', $euantorPlugins);

	$PL->settings(
		'myalerts',
		$lang->setting_group_myalerts,
		$lang->setting_group_myalerts_desc,
		array(
			'perpage'        => array(
				'title'       => $lang->setting_myalerts_perpage,
				'description' => $lang->setting_myalerts_perpage_desc,
				'value'       => '10',
				'optionscode' => 'text',
			),
			'dropdown_limit' => array(
				'title'       => $lang->setting_myalerts_dropdown_limit,
				'description' => $lang->setting_myalerts_dropdown_limit_desc,
				'value'       => '5',
				'optionscode' => 'text',
			),
			'autorefresh'    => array(
				'title'       => $lang->setting_myalerts_autorefresh,
				'description' => $lang->setting_myalerts_autorefresh_desc,
				'value'       => '0',
				'optionscode' => 'text',
			),
			'avatar_size'    => array(
				'title'       => $lang->setting_myalerts_avatar_size,
				'description' => $lang->setting_myalerts_avatar_size_desc,
				'value'       => '64|64',
				'optionscode' => 'text',
			),
		)
	);

	$dir = new DirectoryIterator(MYALERTS_PLUGIN_PATH . '/templates');
	$templates = array();
	foreach ($dir as $file) {
		if (!$file->isDot() && !$file->isDir() && pathinfo(
				$file->getPathname(),
				PATHINFO_EXTENSION
			) === 'html'
		) {
			$templateName = $file->getPathname();
			$templateName = basename($templateName, '.html');
			$templates[$templateName] = file_get_contents($file->getPathname());
		}
	}

	$PL->templates(
		'myalerts',
		'MyAlerts',
		$templates
	);

	$stylesheet = file_get_contents(
		MYALERTS_PLUGIN_PATH . '/stylesheets/alerts.css'
	);

	$PL->stylesheet('alerts.css', $stylesheet);

	// Attach usercp.css to alerts.php
	$query = $db->simple_select(
		'themestylesheets',
		'sid,attachedto,tid',
		"name = 'usercp.css'"
	);

	while ($userCpStylesheet = $db->fetch_array($query)) {
		$sid = (int) $userCpStylesheet['sid'];

		$db->update_query(
			'themestylesheets',
			array(
				'attachedto' => $db->escape_string(
					$userCpStylesheet['attachedto'] . '|alerts.php'
				),
			),
			"sid = {$sid}"
		);

		update_theme_stylesheet_list((int) $userCpStylesheet['tid']);
	}

	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';


	find_replace_templatesets('headerinclude', '/$/', '{$myalerts_js}');
	find_replace_templatesets(
		'header_welcomeblock_member',
		"#" . preg_quote('{$modcplink}') . "#i",
		'{$myalerts_headericon}{$modcplink}'
	);
	find_replace_templatesets(
		'footer',
		'/$/',
		'{$myalerts_modal}'
	);

	$taskExists = $db->simple_select(
		'tasks',
		'tid',
		'file = \'myalerts\'',
		array('limit' => '1')
	);
	if ($db->num_rows($taskExists) == 0) {
		require_once MYBB_ROOT . '/inc/functions_task.php';

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
		$theTask = $db->fetch_array(
			$db->simple_select('tasks', '*', 'tid = ' . (int) $task_id, 1)
		);
		$nextrun = fetch_next_run($theTask);
		$db->update_query(
			'tasks',
			'nextrun = ' . $nextrun,
			'tid = ' . (int) $task_id
		);
		$plugins->run_hooks('admin_tools_tasks_add_commit');
		$cache->update_tasks();
	} else {
		require_once MYBB_ROOT . '/inc/functions_task.php';
		$theTask = $db->fetch_array(
			$db->simple_select('tasks', '*', 'file = \'myalerts\'', 1)
		);
		$db->update_query(
			'tasks',
			array(
				'enabled' => 1,
				'nextrun' => fetch_next_run($theTask)
			),
			'file = \'myalerts\''
		);
		$cache->update_tasks();
	}

	$plugins->run_hooks('myalerts_activate');
}

function myalerts_upgrade_105_200()
{
	global $db, $lang, $cache, $plugins;

	if (!$db->field_exists('alert_type_id', 'alerts')) {
		$db->add_column('alerts', 'alert_type_id', 'INT(10) unsigned');
	}

	if ($db->field_exists('alert_type', 'alerts')) {
		$db->drop_column('alerts', 'alert_type');
	}

	$db->modify_column('alerts', 'dateline', 'DATETIME');

	if ($db->field_exists('tid', 'alerts')) {
		$db->rename_column('alerts', 'tid', 'object_id', 'INT(10)');
	}

	if ($db->field_exists('from_id', 'alerts')) {
		$db->rename_column('alerts', 'from_id', 'from_user_id', 'INT(10)');
	}

	// Check if the 'forced' column exists due to earlier issues with the upgrade script in past releases
	if (!$db->field_exists('forced', 'alerts')) {
		$db->add_column('alerts', 'forced', "INT(1) NOT NULL DEFAULT '0'");
	}

	if ($db->field_exists('content', 'alerts')) {
		$db->rename_column('alerts', 'content', 'extra_details', 'TEXT');
	}

	if ($db->table_exists('alert_settings')) {
		$db->drop_table('alert_settings');
	}

	if ($db->table_exists('alert_setting_values')) {
		$db->drop_table('alert_setting_values');
	}

	$collation = $db->build_create_table_collation();

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
		$db->add_column(
			'users',
			'myalerts_disabled_alert_types',
			'TEXT NOT NULL'
		);
	}

	$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance(
		$db,
		$cache
	);

	$insertArray = array(
		'rep',
		'pm',
		'buddylist',
		'quoted',
		'post_threadauthor',
		'subscribed_thread'
	);
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

	flash_message($lang->myalerts_upgraded, 'success');
}

function myalerts_deactivate()
{
	global $PL, $db, $lang, $plugins;

	if (!isset($lang->myalerts)) {
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
	$query = $db->simple_select(
		'themestylesheets',
		'sid,attachedto,tid',
		"name = 'usercp.css'"
	);

	while ($userCpStylesheet = $db->fetch_array($query)) {
		$sid = (int) $userCpStylesheet['sid'];

		$attachedTo = str_replace(
			'|alerts.php',
			'',
			$userCpStylesheet['attachedto']
		);

		$db->update_query(
			'themestylesheets',
			array(
				'attachedto' => $db->escape_string($attachedTo),
			),
			"sid = {$sid}"
		);

		update_theme_stylesheet_list((int) $userCpStylesheet['tid']);
	}

	require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";

	find_replace_templatesets(
		'headerinclude',
		"#" . preg_quote('{$myalerts_js}') . "#i",
		''
	);
	find_replace_templatesets(
		'header_welcomeblock_member',
		"#" . preg_quote('{$myalerts_headericon}') . "#i",
		''
	);
	find_replace_templatesets(
		'footer',
		"#" . preg_quote('{$myalerts_modal}') . "#i",
		''
	);

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
 * @return bool Whether MyAlerts is activated and installed.
 */
function myalerts_is_activated()
{
	global $cache;

	$plugins = $cache->read('plugins');
	$activePlugins = $plugins['active'];

	$isActive = false;

	if (in_array('myalerts', $activePlugins)) {
		$isActive = true;
	}

	return myalerts_is_installed() && $isActive;
}

/**
 * Cache reload function.
 *
 * MyBB's cache page in the ACP checks for a function named
 * "reload_{$cacheitem['title']}" to add the reload button for a cache. Having
 * this function in place fixes that.
 */
function reload_mybbstuff_myalerts_alert_types()
{
    myalerts_create_instances();

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

	$alertTypeManager->getAlertTypes(true);
}

function parse_alert(MybbStuff_MyAlerts_Entity_Alert $alertToParse)
{
	global $mybb, $lang, $plugins;

	if (!isset($lang->myalerts)) {
		$lang->load('myalerts');
	}

    myalerts_create_instances();

	/** @var MybbStuff_MyAlerts_Formatter_AbstractFormatter $formatter */
	$formatter = MybbStuff_MyAlerts_AlertFormatterManager::getInstance()
	                                                     ->getFormatterForAlertType(
		                                                     $alertToParse->getType(
		                                                     )->getCode()
	                                                     );

	$outputAlert = array();

	if ($formatter != null) {
		$plugins->run_hooks('myalerts_alerts_output_start', $alert);

		$formatter->init();

		$fromUser = $alertToParse->getFromUser();

		$maxDimensions = str_replace(
			'|',
			'x',
			$mybb->settings['myalerts_avatar_size']
		);

		$outputAlert['avatar'] = format_avatar(
			$fromUser['avatar'],
			$mybb->settings['myalerts_avatar_size'],
			$maxDimensions
		);
		$outputAlert['avatar']['image'] = htmlspecialchars_uni(
			$outputAlert['avatar']['image']
		);

		$outputAlert['id'] = $alertToParse->getId();
		$outputAlert['username'] = htmlspecialchars_uni($fromUser['username']);
		$outputAlert['from_user'] = format_name(
			htmlspecialchars_uni($fromUser['username']),
			$fromUser['usergroup'],
			$fromUser['displaygroup']
		);
		$outputAlert['from_user_raw_profilelink'] = get_profile_link(
			(int) $fromUser['uid']
		); // htmlspecialchars_uni done by get_profile_link
		$outputAlert['from_user_profilelink'] = build_profile_link(
			$outputAlert['from_user'],
			$fromUser['uid']
		);

		$outputAlert['alert_status'] = ' alert--read';
		if ($alertToParse->getUnread()) {
			$outputAlert['alert_status'] = ' alert--unread';
		}

		$outputAlert['message'] = $formatter->formatAlert(
			$alertToParse,
			$outputAlert
		);

		$outputAlert['alert_code'] = $alertToParse->getType()->getCode();

		$outputAlert['received_at'] = my_date(
			$mybb->settings['dateformat'],
			$alertToParse->getCreatedAt()->getTimestamp()
		);

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
	global $mybb, $templatelist, $templates;

	if (isset($templatelist)) {
		$templatelist .= ',';
	}

	$templatelist .= 'myalerts_headericon,myalerts_modal,myalerts_popup_row,myalerts_alert_row_no_alerts,myalerts_js_popup';

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

		if (!isset($lang->myalerts)) {
			$lang->load('myalerts');
		}

		$mybb->user['myalerts_disabled_alert_types'] = json_decode(
			$mybb->user['myalerts_disabled_alert_types']
		);
		if (!empty($mybb->user['myalerts_disabled_alert_types']) && is_array(
				$mybb->user['myalerts_disabled_alert_types']
			)
		) {
			$mybb->user['myalerts_disabled_alert_types'] = array_map(
				'intval',
				$mybb->user['myalerts_disabled_alert_types']
			);
		} else {
			$mybb->user['myalerts_disabled_alert_types'] = array();
		}

		myalerts_create_instances();

		$mybb->user['unreadAlerts'] = my_number_format(
			(int) MybbStuff_MyAlerts_AlertManager::getInstance()
			                                     ->getNumUnreadAlerts()
		);
	}
}

$plugins->add_hook('admin_tabs', 'myalerts_create_instances', -1);
function myalerts_create_instances()
{
	global $mybb, $db, $cache, $lang, $plugins;

	if (!isset($lang->myalerts)) {
		$lang->load('myalerts');
	}

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

    if (is_null($alertTypeManager) || $alertTypeManager === false) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance(
            $db,
            $cache
        );
    }

    $alertManager = MybbStuff_MyAlerts_AlertManager::getInstance();

    if (is_null($alertManager) || $alertManager === false) {
        $alertManager = MybbStuff_MyAlerts_AlertManager::createInstance(
            $mybb,
            $db,
            $cache,
            $plugins,
            $alertTypeManager
        );
    }

    $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

    if (is_null($formatterManager) || $formatterManager === false) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
    }

	myalerts_register_core_formatters($mybb, $lang);

	if (!MybbStuff_MyAlerts_AlertManager::$isCommitRegistered) {
		register_shutdown_function(
			array(MybbStuff_MyAlerts_AlertManager::getInstance(), 'commit')
		);
		MybbStuff_MyAlerts_AlertManager::$isCommitRegistered = true;
	}
}

$plugins->add_hook('global_intermediate', 'myalerts_global_intermediate');
function myalerts_global_intermediate()
{
	global $templates, $mybb, $lang, $myalerts_return_link, $myalerts_headericon, $myalerts_modal, $myalerts_js, $theme;

	$myalerts_js = '';

	if (isset($mybb->user['uid']) && $mybb->user['uid'] > 0) {
		if (!isset($lang->myalerts)) {
			$lang->load('myalerts');
		}

		if ($mybb->user['unreadAlerts']) {
			$newAlertsIndicator = 'alerts--new';
		}

		$myalerts_return_link = htmlspecialchars_uni(urlencode(myalerts_get_current_url()));

		$myalerts_headericon = eval($templates->render('myalerts_headericon'));

		$myalerts_js = eval($templates->render('myalerts_js_popup'));
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

    $uri = explode('?', $_SERVER['REQUEST_URI']);
    $link = $mybb->settings['homeurl'] . htmlspecialchars($uri[0], ENT_QUOTES);

    if (!empty($_GET)) {
        $link .= '?' . http_build_query($_GET);
    }

    return $link;
}

$plugins->add_hook(
	'datahandler_user_insert',
	'myalerts_datahandler_user_insert'
);
function myalerts_datahandler_user_insert(&$dataHandler)
{
	global $db;

	$dataHandler->user_insert_data['myalerts_disabled_alert_types'] = $db->escape_string(
		json_encode(array())
	);
}

$plugins->add_hook(
	'build_friendly_wol_location_end',
	'myalerts_online_location'
);
function myalerts_online_location(&$plugin_array)
{
	global $lang;

	if (!isset($lang->myalerts)) {
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
	global $mybb, $reputation;

    if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
        return;
    }

    myalerts_create_instances();

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

	/** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
	$alertType = $alertTypeManager->getByCode('rep');

	if ($alertType != null && $alertType->getEnabled()) {
		$alert = new MybbStuff_MyAlerts_Entity_Alert(
			$reputation['uid'],
			$alertType,
			0
		);

		MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
	}
}

$plugins->add_hook('datahandler_pm_insert_commit', 'myalerts_addAlert_pm');
function myalerts_addAlert_pm($PMDataHandler)
{
    if ($PMDataHandler->pm_insert_data['fromid'] < 1) {
        return;
    }

    myalerts_create_instances();

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

    /** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
    $alertType = $alertTypeManager->getByCode('pm');

    if ($alertType != null && $alertType->getEnabled()) {
        $pmId = current(array_slice($PMDataHandler->pmid, -1));

        $alert = new MybbStuff_MyAlerts_Entity_Alert(
            (int) $PMDataHandler->pm_insert_data['uid'],
            $alertType,
            $pmId
        );
        $alert->setExtraDetails(
            array(
                'pm_title' => $PMDataHandler->pm_insert_data['subject'],
            )
        );

        MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
    }
}

$plugins->add_hook('usercp_do_editlists_end', 'myalerts_alert_buddylist');
function myalerts_alert_buddylist()
{
	global $mybb, $error_message, $db;

    if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
        return;
    }

	if ($mybb->get_input(
			'manage'
		) != 'ignored' && !isset($mybb->input['delete']) && empty($error_message)
	) {
		$addUsers = explode(",", $mybb->input['add_username']);
		$addUsers = array_map("trim", $addUsers);
		$addUsers = array_unique($addUsers);

		if (count($addUsers) > 0) {
            myalerts_create_instances();

            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

			/** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
			$alertType = $alertTypeManager->getByCode('buddylist');

			if ($alertType != null && $alertType->getEnabled()) {
				$userNames = array_map(array($db, 'escape_string'), $addUsers);

				$userNames = "'" . implode("','", $userNames) . "'";
				$query = $db->simple_select(
					'users',
					'uid',
					"username IN({$userNames})"
				);

				$alerts = array();
				while ($user = $db->fetch_array($query)) {
					$alert = new MybbStuff_MyAlerts_Entity_Alert(
						(int) $user['uid'], $alertType, 0
					);
					$alerts[] = $alert;
				}

				if (!empty($alerts)) {
					MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts(
						$alerts
					);
				}
			}
		}
	}
}

$plugins->add_hook('newreply_do_newreply_end', 'myalerts_alert_quoted');
function myalerts_alert_quoted()
{
	global $mybb, $pid, $post, $db;

	if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1 || $post['savedraft']) {
		return;
	}

	$author = (int) $mybb->user['uid'];
	$message = $post['message'];

	$pattern = "#\\[quote=(?:\"|'|&quot;|)(?<username>.*?)(?:\"|'|&quot;|)(?: pid=(?:\"|'|&quot;|)[\\d]*(?:\"|'|&quot;|))?(?:\"|'|&quot;|)(?: dateline=(?:\"|'|&quot;|)[\\d]*(?:\"|'|&quot;|))?(?:\"|'|&quot;|)\](?<message>.*?)\\[\\/quote\\]#si";

	preg_match_all($pattern, $message, $matches);

	$matches = array_filter($matches);

	if (isset($matches['username'])) {
		$users = array_unique(array_values($matches['username']));

		if (!empty($users)) {
            myalerts_create_instances();

            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

			/** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
			$alertType = $alertTypeManager->getByCode('quoted');

			if ($alertType != null && $alertType->getEnabled()) {
				$userNames = array_map('stripslashes', $users);
				$userNames = array_map(array($db, 'escape_string'), $userNames);

				$userNames = "'" . implode(
						"','",
						$userNames
					) . "'"; // TODO: When imploding, usernames with quotes in them might be breaking the query...

				$query = $db->simple_select(
					'users',
					'uid, username',
					"username IN({$userNames}) AND uid <> '{$author}'"
				);

				$alerts = array();
				while ($uid = $db->fetch_array($query)) {
					$forumPerms = forum_permissions($post['fid'], $uid['uid']);

					if ($forumPerms['canview'] != 0 && $forumPerms['canviewthreads'] != 0) {
						$userList[] = (int) $uid['uid'];
						$alert = new MybbStuff_MyAlerts_Entity_Alert(
							(int) $uid['uid'],
							$alertType,
							(int) $post['tid']
						);
						$alert->setExtraDetails(
							array(
								'tid'     => $post['tid'],
								'pid'     => $pid,
								'subject' => $post['subject'],
								'fid'     => (int) $post['fid'],
							)
						);
						$alerts[] = $alert;
					}
				}

				if (!empty($alerts)) {
					MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts(
						$alerts
					);
				}
			}
		}
	}
}

$plugins->add_hook(
	'datahandler_post_insert_post',
	'myalerts_alert_post_threadauthor'
);
function myalerts_alert_post_threadauthor(&$post)
{
	global $mybb, $db;

    if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
        return;
    }

	if (!$post->data['savedraft']) {
        myalerts_create_instances();

        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if (is_null($alertTypeManager) || $alertTypeManager === false) {
            global $cache;

            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }

		/** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
		$alertType = $alertTypeManager->getByCode('post_threadauthor');

		if ($alertType != null && $alertType->getEnabled()) {
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
				if ($forumPerms['canview'] != 0 && $forumPerms['canviewthreads'] != 0) {
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
								'tid'       => $post->post_insert_data['tid'],
								't_subject' => $thread['subject'],
								'fid'       => (int) $thread['fid'],
							)
						);

						MybbStuff_MyAlerts_AlertManager::getInstance()
						                               ->addAlert($alert);
					}
				}
			}
		}
	}
}

$plugins->add_hook(
	'ratethread_process',
	'myalerts_alert_rated_threadauthor'
);
function myalerts_alert_rated_threadauthor()
{
	global $mybb, $db, $tid;

    if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
        return;
    }

    myalerts_create_instances();

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

    if (is_null($alertTypeManager) || $alertTypeManager === false) {
        global $cache;

        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
    }

    $alertType = $alertTypeManager->getByCode('rated_threadauthor');

    if ($alertType != null && $alertType->getEnabled()) {
        $thread = get_thread($tid);

        if ($thread['uid'] != $mybb->user['uid']) {
            $forumPerms = forum_permissions($thread['fid'], $thread['uid']);

            // Check forum permissions
            if ($forumPerms['canview'] != 0 && $forumPerms['canviewthreads'] != 0) {
                $alert = new MybbStuff_MyAlerts_Entity_Alert(
                    $thread['uid'],
                    $alertType,
                    (int) $tid
                );
                $alert->setExtraDetails(
                    array(
                        'tid'       => $tid,
                        't_subject' => $thread['subject'],
                    )
                );

                $alertManager = MybbStuff_MyAlerts_AlertManager::getInstance();

                if (is_null($alertManager) || $alertManager === false) {
                    global $cache;

                    $alertManager = MybbStuff_MyAlerts_AlertManager::createInstance($mybb, $db, $cache, $plugins, $alertTypeManager);
                }

                $alertManager->addAlert($alert);
            }
        }
    }
}

$plugins->add_hook(
	'polls_vote_end',
	'myalerts_alert_voted_threadauthor'
);
function myalerts_alert_voted_threadauthor()
{
	global $mybb, $db, $cache, $plugins, $poll;

    if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1 || $poll['public'] == 0) {
        return;
    }

    myalerts_create_instances();

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

    if (is_null($alertTypeManager) || $alertTypeManager === false) {
        global $cache;

        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
    }

    $alertType = $alertTypeManager->getByCode('voted_threadauthor');

    if ($alertType != null && $alertType->getEnabled()) {
        $thread = get_thread($poll['tid']);

        if ($thread['uid'] != $mybb->user['uid']) {
            $forumPerms = forum_permissions($thread['fid'], $thread['uid']);

            // Check forum permissions
            if ($forumPerms['canview'] != 0 && $forumPerms['canviewthreads'] != 0) {
                $alert = new MybbStuff_MyAlerts_Entity_Alert(
                    $thread['uid'],
                    $alertType,
                    (int) $tid
                );
                $alert->setExtraDetails(
                    array(
                        'tid'       => $poll['tid'],
                        't_subject' => $thread['subject'],
                    )
                );

                $alertManager = MybbStuff_MyAlerts_AlertManager::getInstance();

                if (is_null($alertManager) || $alertManager === false) {
                    global $cache;

                    $alertManager = MybbStuff_MyAlerts_AlertManager::createInstance($mybb, $db, $cache, $plugins, $alertTypeManager);
                }

                $alertManager->addAlert($alert);
            }
        }
    }
}

$plugins->add_hook('datahandler_post_insert_post', 'myalertsrow_subscribed');
function myalertsrow_subscribed(&$dataHandler)
{
	global $mybb, $db, $post;

    if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
        return;
    }

    myalerts_create_instances();

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

	$alertType = $alertTypeManager->getByCode('subscribed_thread');

	if ($alertType != null && $alertType->getEnabled()) {
		$thread = get_thread($post['tid']);
		$alerts = array();

		$post['tid'] = (int) $post['tid'];
		$post['uid'] = (int) $post['uid'];
		$thread['lastpost'] = (int) $thread['lastpost'];

		$query = $db->query(
			"SELECT s.uid FROM " . TABLE_PREFIX . "threadsubscriptions s
            LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid=s.uid)
            WHERE (s.notification = 0 OR s.notification = 1) AND s.tid='{$post['tid']}'
            AND s.uid != '{$post['uid']}'
            AND u.lastactive>'{$thread['lastpost']}'"
		);

		while ($poster = $db->fetch_array($query)) {
			$forumPerms = forum_permissions($thread['fid'], $poster['uid']);

			if ($forumPerms['canview'] != 0 && $forumPerms['canviewthreads'] != 0) {
				$alert = new MybbStuff_MyAlerts_Entity_Alert(
					(int) $poster['uid'], $alertType, $thread['tid']
				);
				$alert->setExtraDetails(
					array(
						'thread_title' => $thread['subject'],
						'tid'          => (int) $thread['tid']
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


$plugins->add_hook('usercp_menu', 'myalerts_usercp_menu', 20);
function myalerts_usercp_menu()
{
	global $mybb, $templates, $theme, $usercpmenu, $lang, $collapsed, $collapsedimg;

    if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
        return;
    }

	if (!($lang->myalerts)) {
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

    if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
        return;
    }

	if (!isset($lang->myalerts)) {
		$lang->load('myalerts');
	}

	myalerts_create_instances();

	if ($mybb->get_input('action') == 'getNewAlerts') {
		header('Content-Type: application/json');

		$newAlerts = MybbStuff_MyAlerts_AlertManager::getInstance()->getAlerts(
			0,
			$mybb->settings['myalerts_dropdown_limit']
		);

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
						$alertsListing .= eval($templates->render(
							'myalerts_alert_row_popup',
							true,
							false
						));
					}
				} else {
					if ($alert['message']) {
						$alertsListing .= eval($templates->render(
							'myalerts_alert_row',
							true,
							false
						));
					}
				}

				$toMarkRead[] = $alertObject->getId();
			}

			MybbStuff_MyAlerts_AlertManager::getInstance()->markRead(
				$toMarkRead
			);
		} else {
			$from = $mybb->get_input('from', MyBB::INPUT_STRING);

			$altbg = alt_trow();

			if (!empty($from) && $from == 'header') {
				$alertsListing = eval($templates->render(
					'myalerts_alert_row_popup_no_alerts',
					true,
					false
				));
			} else {
				$alertsListing = eval($templates->render(
					'myalerts_alert_row_no_alerts',
					true,
					false
				));
			}
		}

		echo json_encode(
			array(
				'alerts'   => $alertsToReturn,
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

				$newAlerts = MybbStuff_MyAlerts_AlertManager::getInstance()
				                                            ->getUnreadAlerts();

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
								$alertsListing .= eval($templates->render(
									'myalerts_alert_row_popup',
									true,
									false
								));
							}
						} else {
							if ($alert['message']) {
								$alertsListing .= eval($templates->render(
									'myalerts_alert_row',
									true,
									false
								));
							}
						}

						$toMarkRead[] = $alertObject->getId();
					}

					MybbStuff_MyAlerts_AlertManager::getInstance()->markRead(
						$toMarkRead
					);
				} else {
					$from = $mybb->get_input('from', MyBB::INPUT_STRING);

					$altbg = alt_trow();

					if (!empty($from) && $from == 'header') {
						$alertsListing = eval($templates->render(
							'myalerts_alert_row_popup_no_alerts',
							true,
							false
						));
					} else {
						$alertsListing = eval($templates->render(
							'myalerts_alert_row_no_alerts',
							true,
							false
						));
					}
				}

				$toReturn = array(
					'success'  => true,
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
		echo MybbStuff_MyAlerts_AlertManager::getInstance()->getNumUnreadAlerts(
		);
	}
}

function myalerts_register_core_formatters($mybb, $lang)
{
	/** @var MybbStuff_Myalerts_AlertFormatterManager $formatterManager */
	$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

	$formatterManager->registerFormatter(
		new MybbStuff_MyAlerts_Formatter_RepFormatter($mybb, $lang, 'rep')
	);
	$formatterManager->registerFormatter(
		new MybbStuff_MyAlerts_Formatter_BuddylistFormatter(
			$mybb,
			$lang,
			'buddylist'
		)
	);
	$formatterManager->registerFormatter(
		new MybbStuff_MyAlerts_Formatter_QuotedFormatter($mybb, $lang, 'quoted')
	);
	$formatterManager->registerFormatter(
		new MybbStuff_MyAlerts_Formatter_PrivateMessageFormatter(
			$mybb,
			$lang,
			'pm'
		)
	);
	$formatterManager->registerFormatter(
		new MybbStuff_MyAlerts_Formatter_ThreadAuthorReplyFormatter(
			$mybb,
			$lang,
			'post_threadauthor'
		)
	);
	$formatterManager->registerFormatter(
		new MybbStuff_MyAlerts_Formatter_SubscribedThreadFormatter(
			$mybb,
			$lang,
			'subscribed_thread'
		)
	);
	$formatterManager->registerFormatter(
		new MybbStuff_MyAlerts_Formatter_ThreadAuthorRatedFormatter(
			$mybb,
			$lang,
			'rated_threadauthor'
		)
	);
	$formatterManager->registerFormatter(
		new MybbStuff_MyAlerts_Formatter_ThreadAuthorVotedFormatter(
			$mybb,
			$lang,
			'voted_threadauthor'
		)
	);
}

$plugins->add_hook('admin_config_menu', 'myalerts_acp_config_menu');
function myalerts_acp_config_menu(&$sub_menu)
{
	global $lang;

	if (!isset($lang->myalerts)) {
		$lang->load('myalerts');
	}

	$sub_menu[] = array(
		'id'    => 'myalerts_alert_types',
		'title' => $lang->myalerts_alert_types,
		'link'  => 'index.php?module=config-myalerts_alert_types'
	);
}

$plugins->add_hook(
	'admin_config_action_handler',
	'myalerts_acp_config_action_handler'
);
function myalerts_acp_config_action_handler(&$actions)
{
	$actions['myalerts_alert_types'] = array(
		'active' => 'myalerts_alert_types',
		'file'   => 'myalerts.php',
	);
}

$plugins->add_hook(
	'admin_config_permissions',
	'myalerts_acp_config_permissions'
);
function myalerts_acp_config_permissions(&$admin_permissions)
{
	global $lang;

	if (!isset($lang->myalerts)) {
		$lang->load('myalerts');
	}

	$admin_permissions['myalerts_alert_types'] = $lang->myalerts_can_manage_alert_types;
}
