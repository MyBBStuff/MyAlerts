<?php
/**
 *  MyAlerts Core Plugin File
 *
 *  A simple notification/alert system for MyBB
 *
 * @package MyAlerts
 * @author  Euan T. <euan@euantor.com>
 * @license http://opensource.org/licenses/mit-license.php MIT license
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

if (!is_readable(PLUGINLIBRARY)) {
	die('The MyAlerts plugin is aborting execution because it could not read the required PluginLibrary file at "'.PLUGINLIBRARY.'". Please install PluginLibrary from <a href="https://community.mybb.com/mods.php?action=view&pid=573">here</a> before continuing.');
}

if (!is_readable(MYBBSTUFF_CORE_PATH . 'ClassLoader.php')) {
	die('The MyAlerts plugin is aborting execution because it could not read the required MyBBStuff Plugins.Core ClassLoader file at "'.MYBBSTUFF_CORE_PATH.'ClassLoader.php". Please install that file from <a href="https://raw.githubusercontent.com/MyBBStuff/Plugins.Core/master/ClassLoader.php">here</a> before continuing.');
}

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
		'version'       => '2.1.0',
		'compatibility' => '18*',
		'codename'      => 'mybbstuff_myalerts',
	);
}

function myalerts_install()
{
	global $db, $cache, $plugins;

	$plugin_info = myalerts_info();
	$euantor_plugins = $cache->read('euantor_plugins');
	if (empty($euantor_plugins)) {
		$euantor_plugins = array();
	}
	$euantor_plugins['myalerts'] = array(
		'title'   => 'MyAlerts',
		'version' => $plugin_info['version'],
	);
	$cache->update('euantor_plugins', $euantor_plugins);

	$collation = $db->build_create_table_collation();

	if (!$db->table_exists('alerts')) {
		switch ($db->type) {
		case 'pgsql':
			$db->write_query("
				CREATE TABLE " . TABLE_PREFIX . "alerts(
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
			$db->write_query("
				CREATE TABLE " . TABLE_PREFIX . "alerts(
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
			$db->write_query("
				CREATE TABLE " . TABLE_PREFIX . "alert_types(
					id serial,
					code varchar(100) NOT NULL DEFAULT '' UNIQUE,
					enabled smallint NOT NULL DEFAULT '1',
					can_be_user_disabled smallint NOT NULL DEFAULT '1',
					default_user_enabled smallint NOT NULL DEFAULT '1',
					PRIMARY KEY (id)
				);"
			);
			break;
		default:
			$db->write_query("
				CREATE TABLE " . TABLE_PREFIX . "alert_types(
					`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`code` varchar(100) NOT NULL DEFAULT '',
					`enabled` tinyint(4) NOT NULL DEFAULT '1',
					`can_be_user_disabled` tinyint(4) NOT NULL DEFAULT '1',
					`default_user_enabled` tinyint(4) NOT NULL DEFAULT '1',
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
		$alertType->setDefaultUserEnabled(true);

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

	// The `default_user_enabled` column was added in version 2.1.0
	if (!$db->field_exists('default_user_enabled', 'alert_types')) {
		switch ($db->type) {
		case 'pgsql':
			$db->add_column(
				'alert_types',
				'default_user_enabled',
				"smallint NOT NULL DEFAULT '1'",
			);
			break;
		default:
			$db->add_column(
				'alert_types',
				'default_user_enabled',
				"tinyint(4) NOT NULL DEFAULT '1'",
			);
			break;
		}
	}
	reload_mybbstuff_myalerts_alert_types();

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
			'autorefresh_header_interval' => array(
				'title'       => $lang->setting_myalerts_autorefresh_header_interval,
				'description' => $lang->setting_myalerts_autorefresh_header_interval_desc,
				'value'       => '0',
				'optionscode' => 'text',
			),
			'avatar_size'    => array(
				'title'       => $lang->setting_myalerts_avatar_size,
				'description' => $lang->setting_myalerts_avatar_size_desc,
				'value'       => '64|64',
				'optionscode' => 'text',
			),
			'bc_mode'        => array(
				'title'       => $lang->setting_myalerts_bc_mode,
				'description' => $lang->setting_myalerts_bc_mode_desc,
				'value'       => '0',
				'optionscode' => 'onoff',
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
		$db->write_query("
			CREATE TABLE " . TABLE_PREFIX . "alert_types(
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
		->getFormatterForAlertType($alertToParse->getType()->getCode());

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
			'relative',
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

	if (defined('THIS_SCRIPT')) {
		if (THIS_SCRIPT == 'usercp.php' || THIS_SCRIPT == 'alerts.php') {
			$templatelist .= ',myalerts_usercp_nav';
		}

		if (THIS_SCRIPT == 'alerts.php') { // Hack to load User CP menu items in alerts.php without querying for templates
			$templatelist .= ',usercp_nav_messenger,usercp_nav_messenger_tracking,usercp_nav_messenger_compose,usercp_nav_messenger_folder,usercp_nav_changename,usercp_nav_editsignature,usercp_nav_profile,usercp_nav_attachments,usercp_nav_misc,usercp_nav';
		}

		if (THIS_SCRIPT == 'alerts.php') {
			$templatelist .= ',myalerts_page,myalerts_alert_row,multipage_page_current,multipage_page,multipage_nextpage,multipage';
		}

		if (THIS_SCRIPT == 'alerts.php' && !empty($mybb->input['action']) && $mybb->input['action'] == 'settings') {
			$templatelist .= ',myalerts_setting_row,myalerts_settings_page';
		}
	}

	$mybb->user['unreadAlerts'] = 0;

	if ($mybb->user['uid'] > 0) {
		global $lang;

		if (!isset($lang->myalerts)) {
			$lang->load('myalerts');
		}

		if (!empty($mybb->user['myalerts_disabled_alert_types']))
		{
			$mybb->user['myalerts_disabled_alert_types'] = json_decode(
				$mybb->user['myalerts_disabled_alert_types']
			);
		}
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

		$newAlertsIndicator = '';
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
	global $db, $cache;

	$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
	if (is_null($alertTypeManager) || $alertTypeManager === false) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance(
			$db,
			$cache
		);
	}
	$alertTypes = $alertTypeManager->getAlertTypes();
	$disabledTypes = [];
	foreach ($alertTypes as $alertType) {
		if (empty($alertType['default_user_enabled'])) {
			$disabledTypes[] = $alertType['id'];
		}
	}
	$dataHandler->user_insert_data['myalerts_disabled_alert_types'] = $db->escape_string(
		json_encode($disabledTypes)
	);
}

$plugins->add_hook(
	'fetch_wol_activity_end',
	'myalerts_online_activity'
);
function myalerts_online_activity($user_activity)
{
	if ($user_activity['activity'] == 'unknown') {
		$split_loc = explode('.php', $user_activity['location']);
		$filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), '/'));
		if ($filename == 'alerts') {
			$user_activity['activity'] = $filename;
		}
	}

	return $user_activity;
}

$plugins->add_hook(
	'build_friendly_wol_location_end',
	'myalerts_online_location'
);
function myalerts_online_location(&$plugin_array)
{
	if ($plugin_array['user_activity']['activity'] == 'alerts') {
		global $lang;

		if (!isset($lang->myalerts)) {
			$lang->load('myalerts');
		}

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

$plugins->add_hook('private_delete_end', 'myalerts_delete_pm_alert');
function myalerts_delete_pm_alert()
{
	global $mybb, $db;

	$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('pm');
	if ($alertType) {
		$typeid = $alertType->getId();
		$pmid = $mybb->get_input('pmid', MyBB::INPUT_INT);
		$db->delete_query('alerts', "object_id='{$pmid}' AND alert_type_id='{$typeid}'");
	}
}

$plugins->add_hook('private_read_end', 'myalerts_mark_pm_alert_read');
function myalerts_mark_pm_alert_read()
{
	global $mybb, $db;

	$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('pm');
	if ($alertType) {
		$typeid = $alertType->getId();
		$pmid = $mybb->get_input('pmid', MyBB::INPUT_INT);
		$db->update_query('alerts', ['unread' => 0], "object_id='{$pmid}' AND alert_type_id='{$typeid}'");
	}
}

$plugins->add_hook('usercp_do_editlists_end', 'myalerts_alert_buddylist');
function myalerts_alert_buddylist()
{
	global $mybb, $error_message, $db;

	if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
		return;
	}

	if ($mybb->get_input('manage') != 'ignored' && !isset($mybb->input['delete']) && empty($error_message)) {
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

$plugins->add_hook('datahandler_post_update', 'myalerts_update_quoted');
function myalerts_update_quoted($dataHandler)
{
	global $db;

	if (!empty($dataHandler->data['savedraft'])) {
		return;
	}

	$fid              = $dataHandler->data['fid'];
	$pid              = $dataHandler->data['pid'];
	$existing_post    = get_post($pid);
	$tid              = $existing_post['tid'];
	$existing_msg     = $existing_post['message'];
	$updated_msg      = $dataHandler->data['message'];
	$author           = get_user($dataHandler->data['uid']);
	$subject          = $dataHandler->data['subject'] ? $dataHandler->data['subject'] : $existing_post['subject'];
	$existing_quoted  = myalerts_get_quoted_usernames($existing_msg, $author['username']);
	$updated_quoted   = myalerts_get_quoted_usernames( $updated_msg, $author['username']);
	$quoted_to_delete = array_diff($existing_quoted,  $updated_quoted);
	$quoted_to_add    = array_diff( $updated_quoted, $existing_quoted);

	if ($quoted_to_delete || $quoted_to_add) {
		myalerts_create_instances();
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
		$alertType = $alertTypeManager->getByCode('quoted');
		if ($alertType != null && $alertType->getEnabled()) {
			if ($quoted_to_add) {
				// Add alerts for newly-quoted members in the edited version of the post
				$quoted_uids_to_add = myalerts_usernames_to_uids($quoted_to_add);
				$uids_to_add_permchk = [];
				foreach ($quoted_uids_to_add as $uid) {
					if (myalerts_can_view_thread($fid, $author['uid'], $uid)) {
						$uids_to_add_permchk[] = $uid;
					}
				}
				$alerts = [];
				foreach ($uids_to_add_permchk as $uid) {
					$alert = new MybbStuff_MyAlerts_Entity_Alert(
						$uid,
						$alertType,
						$tid
					);
					$alert->setExtraDetails(
						array(
							'tid'     => $tid,
							'pid'     => $pid,
							'subject' => $subject,
							'fid'     => $fid,
						)
					);
					$alerts[] = $alert;
				}
				if ($alerts) {
					MybbStuff_MyAlerts_AlertManager::getInstance()->addAlerts($alerts);
				}
			}

			if ($quoted_to_delete) {
				// Delete alerts for no-longer-quoted members quoted in the original post
				$uids_for_del_cs = implode(',', myalerts_usernames_to_uids($quoted_to_delete));
				$ids_for_del = [];
				$type_id = $alertType->getId();
				$query = $db->simple_select('alerts', 'id, extra_details', "alert_type_id = {$type_id} AND object_id = {$tid} AND uid IN ({$uids_for_del_cs})");
				while ($alert = $db->fetch_array($query)) {
					$extra_details = json_decode($alert['extra_details'], true);
					if ($extra_details['pid'] == $pid) {
						$ids_for_del[] = $alert['id'];
					}
				}
				if ($ids_for_del) {
					$ids_for_del_cs = implode(',', $ids_for_del);
					$db->delete_query('alerts', "id in ({$ids_for_del_cs})");
				}
			}
		}
	}
}

function myalerts_get_quoted_usernames($message, $authorName)
{
	$userNames = [];

	$pattern = "#\\[quote=(?:\"|'|&quot;|)(?<username>.*?)(?:\"|'|&quot;|)(?: pid=(?:\"|'|&quot;|)[\\d]*(?:\"|'|&quot;|))?(?:\"|'|&quot;|)(?: dateline=(?:\"|'|&quot;|)[\\d]*(?:\"|'|&quot;|))?(?:\"|'|&quot;|)\](?<message>.*?)\\[\\/quote\\]#si";

	preg_match_all($pattern, $message, $matches);

	$matches = array_filter($matches);

	if (isset($matches['username'])) {
		$users = array_unique(array_values($matches['username']));

		if (!empty($users)) {
			foreach ($users as $user) {
				if ($user != $authorName) {
					// Convert any multibyte non-breaking space characters to ordinary spaces.
					$userNames[] = str_replace("\xc2\xa0", ' ', $user);
				}
			}
		}
	}

	return $userNames;
}

function myalerts_usernames_to_uids($userNames)
{
	global $db;

	$uids = [];
	$userNames = array_map('stripslashes', $userNames);
	$userNames = array_map(array($db, 'escape_string'), $userNames);
	$userNames = "'".implode("','", $userNames)."'";

	$query = $db->simple_select(
		'users',
		'uid',
		"username IN({$userNames})"
	);
	while ($uid = $db->fetch_field($query, 'uid')) {
		$uids[] = $uid;
	}
	$db->free_result($query);

	return $uids;
}

$plugins->add_hook('newreply_do_newreply_end', 'myalerts_alert_quoted');
function myalerts_alert_quoted()
{
	global $mybb, $pid, $post, $db;

	if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1 || $post['savedraft']) {
		return;
	}

	$quoted_usernames = myalerts_get_quoted_usernames($post['message'], $mybb->user['username']);
	$quoted_uids = myalerts_usernames_to_uids($quoted_usernames);

	if ($quoted_uids) {
		myalerts_create_instances();
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		/** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
		$alertType = $alertTypeManager->getByCode('quoted');

		if ($alertType != null && $alertType->getEnabled()) {
			$alerts = array();
			foreach ($quoted_uids as $uid) {
				// Check forum permissions
				if (myalerts_can_view_thread($post['fid'], $post['uid'], $uid)) {
					$alert = new MybbStuff_MyAlerts_Entity_Alert(
						(int) $uid,
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
				// Check forum permissions
				if (myalerts_can_view_thread($thread['fid'], $thread['uid'], $thread['uid'])) {
					//check if alerted for this thread already
					$query = $db->simple_select(
						'alerts',
						'id',
						'object_id = ' . (int) $post->post_insert_data['tid'] . " AND unread = 1 AND alert_type_id = {$alertType->getId()}"
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

						MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
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
			// Check forum permissions
			if (myalerts_can_view_thread($thread['fid'], $thread['uid'], $thread['uid'])) {
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
			// Check forum permissions
			if (myalerts_can_view_thread($thread['fid'], $thread['uid'], $thread['uid'])) {
				$alert = new MybbStuff_MyAlerts_Entity_Alert(
					$thread['uid'],
					$alertType,
					(int) $poll['pid']
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

	if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1 || $post['savedraft']) {
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

		$query = $db->query("
			SELECT s.uid FROM " . TABLE_PREFIX . "threadsubscriptions s
			LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid=s.uid)
			WHERE (s.notification = 0 OR s.notification = 1) AND s.tid='{$post['tid']}'
			AND s.uid != '{$post['uid']}'
			AND u.lastactive>'{$thread['lastpost']}'"
		);

		while ($poster = $db->fetch_array($query)) {
			// Check forum permissions
			if (myalerts_can_view_thread($thread['fid'], $thread['uid'], $poster['uid'])) {
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


$plugins->add_hook('class_moderation_delete_thread', 'myalerts_on_delete_thread');
function myalerts_on_delete_thread($tid)
{
	global $db;

	$type_ids = [];
	$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
	$alertTypeScrThr = $alertTypeManager->getByCode('subscribed_thread');
	if ($alertTypeScrThr) {
		$type_ids[] = $alertTypeScrThr->getId();
	}
	$alertTypePostThrAuth = $alertTypeManager->getByCode('post_threadauthor');
	if ($alertTypePostThrAuth) {
		$type_ids[] = $alertTypePostThrAuth->getId();
	}
	$alertTypeRateAuth = $alertTypeManager->getByCode('rated_threadauthor');
	if ($alertTypeRateAuth) {
		$type_ids[] = $alertTypeRateAuth->getId();
	}
	$alertTypeVoted = $alertTypeManager->getByCode('voted_threadauthor');
	if ($alertTypeVoted) {
		$type_ids[] = $alertTypeVoted->getId();
	}
	$alertTypeQuoted = $alertTypeManager->getByCode('quoted');
	if ($alertTypeQuoted) {
		$type_ids[] = $alertTypeQuoted->getId();
	}
	if ($type_ids) {
		$type_ids = implode(',', $type_ids);
		$db->delete_query('alerts', "alert_type_id in ({$type_ids}) AND object_id = {$tid}");
	}
}


$plugins->add_hook('class_moderation_delete_post_start', 'myalerts_save_tid');
function myalerts_save_tid($pid)
{
	global $db, $myalerts_saved_tid;

	$query = $db->simple_select('posts', 'tid', "pid={$pid}");
	$myalerts_saved_tid = $db->fetch_field($query, 'tid');
	$db->free_result($query);
}


$plugins->add_hook('class_moderation_delete_post', 'myalerts_on_delete_post');
function myalerts_on_delete_post($pid)
{
	global $db, $myalerts_saved_tid;

	$alertTypeQuoted = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('quoted');
	if ($alertTypeQuoted) {
		$type_id = $alertTypeQuoted->getId();
		$ids_for_del = [];
		$query = $db->simple_select('alerts', 'id, extra_details', "alert_type_id = {$type_id} AND object_id = {$myalerts_saved_tid}");
		while ($alert = $db->fetch_array($query)) {
			$extra_details = json_decode($alert['extra_details'], true);
			if ($extra_details['pid'] == $pid) {
				$ids_for_del[] = $alert['id'];
			}
		}
		if ($ids_for_del) {
			$ids_for_del = implode(',', $ids_for_del);
			$db->delete_query('alerts', "id in ({$ids_for_del})");
		}
	}
}

$plugins->add_hook('showthread_linear'  , 'myalerts_auto_mark_read_for_thread');
$plugins->add_hook('showthread_threaded', 'myalerts_auto_mark_read_for_thread');
function myalerts_auto_mark_read_for_thread()
{
	global $db, $mybb, $tid, $pids;

	if ($mybb->user['uid']) {
		$alertTypeManager   = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
		$t_quoted_id        = $alertTypeManager->getByCode('quoted'           )->getId();
		$t_post_ta_id       = $alertTypeManager->getByCode('post_threadauthor')->getId();
		$t_subscribed_t_id  = $alertTypeManager->getByCode('subscribed_thread')->getId();

		$alert_ids_to_mark_read = [];

		$query = $db->query("
SELECT id, alert_type_id, object_id, extra_details
FROM   {$db->table_prefix}alerts
WHERE  alert_type_id in ($t_quoted_id, $t_post_ta_id, $t_subscribed_t_id)
       AND
       uid = {$mybb->user['uid']}
       AND
       object_id = {$tid}
       AND
       unread = 1
");
		while ($alert = $db->fetch_array($query)) {
			if ($alert['alert_type_id'] == $t_quoted_id) {
				$extra = json_decode($alert['extra_details'], true);

				// If it is set (meaning we're in linear mode), then convert
				// the $pids string "pid IN ('1', '2', ...)" into an array of pids.
				$pids_a = empty($pids)
				  ? []
				  : array_map(
					function($el) {
						return substr($el, 1, -1);
					},
					explode(',', substr($pids, 7, -1))
				    );

				// Auto-mark as read the unread alerts of type 'quoted' for the viewing member
				// for the viewed thread *if* the post ID of the quoting post matches that of
				// the viewed post (in threaded mode) OR the post ID of the quoting post occurs
				// in the global $pids list (in linear mode).
				if ($mybb->get_input('mode') == 'threaded' && $mybb->input['pid'] == $extra['pid']
				    ||
				    $mybb->get_input('mode') != 'threaded' && in_array($extra['pid'], $pids_a)
				) {
					$alert_ids_to_mark_read[] = $alert['id'];
				}
			} else {
				// Auto-mark as read the unread alerts of type 'post_threadauthor' and
				// 'subscribed_thread' for the viewing member for the viewed thread.
				//
				// We would really like to only mark these alerts as read if the member is viewing
				// unread *posts*, but (1) that's tricky to determine, especially because (2) core code
				// marks a thread as read regardless of whether any unread posts in it are actually
				// being viewed, and regardless of whether, if new posts *are* actually being viewed,
				// more exist beyond the currently-viewed page. We reluctantly, then, auto-mark the
				// alert as read on the sole condition that *some* page of its alerted thread is being
				// viewed.
				$alert_ids_to_mark_read[] = $alert['id'];
			}
		}

		if ($alert_ids_to_mark_read) {
			$db->update_query('alerts', ['unread' => 0], 'id IN ('.implode(',', $alert_ids_to_mark_read).')');

			// Regenerate the header and headerinclude templates, because the number of unread alerts
			// has changed.
			//
			// We might cause PHP 8 to generate warnings here due to global variables relied upon
			// by template insertions by other plugins being undeclared here (and those plugin-generated
			// parts of the header might thus be mangled). That is the unfortunate price we pay for MyBB
			// rendering the header template near the start of processing without providing a reliable,
			// canonical means of updating it later.
			global $templates, $lang, $theme, $header, $headerinclude, $menu_portal, $menu_search, $menu_memberlist, $menu_calendar, $quicksearch, $welcomeblock, $pm_notice, $remote_avatar_notice, $bannedwarning, $bbclosedwarning, $modnotice, $pending_joinrequests, $awaitingusers, $usercplink, $modcplink, $admincplink, $buddylink, $searchlink, $pmslink, $myalerts_js, $myalerts_headericon, $myalerts_return_link, $charset, $stylesheets, $jsTemplates;

			$mybb->user['unreadAlerts'] = my_number_format(
				(int) MybbStuff_MyAlerts_AlertManager::getInstance()
								->getNumUnreadAlerts(true)
			);
			$newAlertsIndicator = '';
			if ($mybb->user['unreadAlerts']) {
				$newAlertsIndicator = 'alerts--new';
			}
			$myalerts_return_link = htmlspecialchars_uni(urlencode(myalerts_get_current_url()));
			$myalerts_headericon = eval($templates->render('myalerts_headericon'));
			$welcomeblock = eval($templates->render('header_welcomeblock_member'));
			$header = eval($templates->render('header'));

			$myalerts_js = eval($templates->render('myalerts_js_popup'));
			$headerinclude = eval($templates->render('headerinclude'));
		}
	}
}


$plugins->add_hook('usercp_menu', 'myalerts_usercp_menu', 20);
function myalerts_usercp_menu()
{
	global $mybb, $templates, $theme, $usercpmenu, $lang, $collapse, $collapsed, $collapsedimg;

	if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
		return;
	}

	if (!($lang->myalerts)) {
		$lang->load('myalerts');
	}

	if (!isset($collapsedimg['usercpalerts'])) {
		$collapsedimg['usercpalerts'] = '';
	}
	if (!isset($collapsed['usercpalerts_e'])) {
		$collapsed['usercpalerts_e'] = '';
	}
	$expaltext = (in_array('usercpalerts', $collapse)) ? $lang->expcol_expand : $lang->expcol_collapse;

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
	global $mybb, $lang, $templates, $db, $plugins;

	if (!isset($mybb->user['uid']) || $mybb->user['uid'] < 1) {
		return;
	}

	if (!isset($lang->myalerts)) {
		$lang->load('myalerts');
	}

	myalerts_create_instances();

	if ($mybb->get_input('action') == 'markAllRead') {
		if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
			header('Content-Type: application/json');
			echo json_encode(array('error' => $lang->invalid_post_code));
			exit;
		}

		MybbStuff_MyAlerts_AlertManager::getInstance()->markAllRead();

		$mybb->input['from'] = 'header';
	}

	if (in_array($mybb->get_input('action'), array('getLatestAlerts', 'markAllRead'))) {
		header('Content-Type: application/json');

		$inModal = ($mybb->get_input('modal') == '1');
		$perpage = $mybb->settings[$inModal ? 'myalerts_dropdown_limit' : 'myalerts_perpage'];
		$had_one_page_only = ($mybb->get_input('pages', MyBB::INPUT_INT) == 1);
		$num_to_get = $perpage;
		if ($had_one_page_only) {
			$num_to_get++;
		}
		$unreadOnly = !empty($mybb->cookies['myalerts_unread_only']) && $mybb->cookies['myalerts_unread_only'] != '0';
		$latestAlerts = MybbStuff_MyAlerts_AlertManager::getInstance()->getAlerts(
			0,
			$num_to_get,
			$inModal && $unreadOnly
		);

		$alertsListing = '';

		$alertsToReturn = array();

		if (is_array($latestAlerts) && !empty($latestAlerts)) {
			$more = (count($latestAlerts) > $perpage);
			if ($more) {
				array_pop($latestAlerts);
			}

			foreach ($latestAlerts as $alertObject) {
				$altbg = alt_trow();

				$alert = parse_alert($alertObject);

				$alertsToReturn[] = $alert;

				if ($alertObject->getUnread()) {
					$markReadHiddenClass = '';
					$markUnreadHiddenClass = ' hidden';
				} else {
					$markReadHiddenClass = ' hidden';
					$markUnreadHiddenClass = '';
				}

				if ($inModal) {
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
			}

			if (!$inModal && $more && $had_one_page_only) {
				// This simple clickable message saves us from having to generate
				// and return pagination items and update the page with them via
				// Javascript
				$alertsListing .= eval($templates->render(
					'myalerts_alert_row_more',
					true,
					false
				));
			}
		} else {
			$from = $mybb->get_input('from', MyBB::INPUT_STRING);

			$altbg = alt_trow();

			if ($inModal) {
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

		$unread_count = (int) MybbStuff_MyAlerts_AlertManager::getInstance()->getNumUnreadAlerts();

		echo json_encode(
			array(
				'alerts'   => $alertsToReturn,
				'template' => $alertsListing,
				'unread_count' => $unread_count,
				'unread_count_fmt' => my_number_format($unread_count)
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
				$unread_count = (int) MybbStuff_MyAlerts_AlertManager::getInstance()->getNumUnreadAlerts();
				$toReturn = array(
					'success'  => true,
					'unread_count' => $unread_count,
					'unread_count_fmt' => my_number_format($unread_count)
				);
			}
		} else {
			$toReturn = array(
				'errors' => array($lang->myalerts_error_alert_not_found),
			);
		}

		echo json_encode($toReturn);
	}

	if ($mybb->get_input('action') == 'get_num_unread_alerts') {
		header('Content-Type: application/json');

		$userId = (int) $mybb->user['uid'];

		if ($userId > 0) {
			$unread_count = (int) MybbStuff_MyAlerts_AlertManager::getInstance()->getNumUnreadAlerts();
			$toReturn = array(
				'unread_count' => $unread_count,
				'unread_count_fmt' => my_number_format($unread_count)
			);
		} else {
			$toReturn = array(
				'unread_count' => 0,
				'unread_count_fmt' => my_number_format(0)
			);
		}

		echo json_encode($toReturn);
	}

	function myalerts_mark_read_or_unread($markRead = true) {
		global $mybb, $lang;

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
				$method = $markRead ? 'markRead' : 'markUnread';
				MybbStuff_MyAlerts_AlertManager::getInstance()->$method([$id]);
				$unread_count = (int) MybbStuff_MyAlerts_AlertManager::getInstance()->getNumUnreadAlerts();

				$toReturn = array(
					'success'  => true,
					'unread_count' => $unread_count,
					'unread_count_fmt' => my_number_format($unread_count)
				);
			}
		} else {
			$toReturn = array(
				'errors' => array($lang->myalerts_error_alert_not_found),
			);
		}

		echo json_encode($toReturn);
	}

	if ($mybb->get_input('action') == 'myalerts_mark_read') {
		myalerts_mark_read_or_unread(true);
	} else if ($mybb->get_input('action') == 'myalerts_mark_unread') {
		myalerts_mark_read_or_unread(false);
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

function myalerts_can_view_thread($fid, $thread_uid, $alerted_uid) {
	// Get forum permissions for the potentially alerted member.
	$forumPerms = forum_permissions($fid, $alerted_uid);

	// The potentially alerted member can't view the thread if his/her forum permissions stipulate
	// that (s)he doesn't have permission to view the forum or the threads within it,
	// or that members can only view their own threads, and this is not his/her own thread.
	if ($forumPerms['canview'] == 0
	    ||
	    $forumPerms['canviewthreads'] == 0
	    ||
	    $forumPerms['canonlyviewownthreads'] == 1 && $thread_uid != $alerted_uid
	) {
		return false;
	}

	// Otherwise, the potentially alerted member can view the thread.
	return true;
}
