<?php
/**
 *	MyAlerts Core Plugin File
 *
 *	A simple notification/alert system for MyBB
 *
 *	@author Euan T. <euan@euantor.com>
 *	@version 1.01
 *	@package MyAlerts
 *  @license http://opensource.org/licenses/mit-license.php MIT license
 */

if (!defined('IN_MYBB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

define('MYALERTS_PLUGIN_PATH', MYBB_ROOT.'inc/plugins/MyAlerts/');

if(!defined("PLUGINLIBRARY"))
{
	define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}

function myalerts_info()
{
	return array(
		'name'          =>  'MyAlerts',
		'description'   =>  'A simple notifications/alerts system for MyBB',
		'website'       =>  'http://euantor.com/myalerts',
		'author'        =>  'euantor',
		'authorsite'    =>  'http://euantor.com',
		'version'       =>  '1.01',
		'guid'          =>  'aba228cf4bd5245ef984ccfde6514ce8',
		'compatibility' =>  '16*',
		);
}

function myalerts_install()
{
	global $db, $cache;

	if (!file_exists(PLUGINLIBRARY))
	{
		flash_message("The selected plugin could not be uninstalled because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
		admin_redirect("index.php?module=config-plugins");
	}

	$PL or require_once PLUGINLIBRARY;

	if ($PL->version < 9)
	{
    	flash_message('This plugin requires PluginLibrary 9 or newer', 'error');
    	admin_redirect('index.php?module=config-plugins');
	}

	$plugin_info = myalerts_info();
	$euantor_plugins = $cache->read('euantor_plugins');
	$euantor_plugins['myalerts'] = array(
		'title'     =>  'MyAlerts',
		'version'   =>  $plugin_info['version'],
		);
	$cache->update('euantor_plugins', $euantor_plugins);

	if (!$db->table_exists('alerts'))
	{
		$collation = $db->build_create_table_collation();
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."alerts(
			id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			uid INT(10) NOT NULL,
			unread TINYINT(4) NOT NULL DEFAULT '1',
			dateline BIGINT(30) NOT NULL,
			alert_type VARCHAR(25) NOT NULL,
			tid INT(10),
			from_id INT(10),
			content TEXT
			) ENGINE=MyISAM{$collation};");
	}

	$db->add_column('users', 'myalerts_settings', 'TEXT NULL');
	$myalertsSettings = array(
		'rep'				=>	1,
		'pm'				=>	1,
		'buddylist'			=>	1,
		'quoted'			=>	1,
		'post_threadauthor'	=>	1,
		);
	$db->update_query('users', array('myalerts_settings' => $db->escape_string(json_encode($myalertsSettings))));
}

function myalerts_is_installed()
{
	global $db;
	return $db->table_exists('alerts');
}

function myalerts_uninstall()
{
	global $db, $lang, $PL;

	if (!file_exists(PLUGINLIBRARY))
	{
		flash_message("The selected plugin could not be uninstalled because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
		admin_redirect("index.php?module=config-plugins");
	}

	$PL or require_once PLUGINLIBRARY;

	$db->drop_table('alerts');
	$PL->settings_delete('myalerts', true);
	$PL->templates_delete('myalerts');
	$db->drop_column('users', 'myalerts_settings');
	$PL->stylesheet_delete('alerts.css');

	if (!$lang->myalerts)
	{
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

	if (!$lang->myalerts)
	{
		$lang->load('myalerts');
	}

	if (!file_exists(PLUGINLIBRARY))
	{
		flash_message($lang->myalerts_pluginlibrary_missing, "error");
		admin_redirect("index.php?module=config-plugins");
	}

	$PL or require_once PLUGINLIBRARY;

	if ($PL->version < 9)
	{
    	flash_message('This plugin requires PluginLibrary 9 or newer', 'error');
    	admin_redirect('index.php?module=config-plugins');
	}

	$plugin_info = myalerts_info();
	$this_version = $plugin_info['version'];
	require_once MYALERTS_PLUGIN_PATH.'/Alerts.class.php';

	if (Alerts::version != $this_version)
	{
		flash_message($lang->sprintf($lang->myalerts_class_outdated, $this_version, Alerts::version), "error");
		admin_redirect("index.php?module=config-plugins");
	}

	$euantor_plugins = $cache->read('euantor_plugins');
	$euantor_plugins['myalerts'] = array(
		'title'     =>  'MyAlerts',
		'version'   =>  (int) $plugin_info['version'],
		);
	$cache->update('euantor_plugins', $euantor_plugins);

	$PL->settings('myalerts',
		$lang->setting_group_myalerts,
		$lang->setting_group_myalerts_desc,
		array(
			'enabled'	=>	array(
				'title'			=>	$lang->setting_myalerts_enabled,
				'description'	=>	$lang->setting_myalerts_enabled_desc,
				'value'			=>	'1',
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
				'optionscode'	=>	'text',
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
			)
	);

	$PL->templates('myalerts',
		'MyAlerts',
		array(
			'page'      =>  '<html>
	<head>
		<title>{$lang->myalerts_page_title} - {$mybb->settings[\'bbname\']}</title>
		<script type="text/javascript">
			<!--
				var myalerts_autorefresh = {$mybb->settings[\'myalerts_autorefresh\']};
			// -->
		</script>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				{$usercpnav}
				<td valign="top">
					<div class="float_right">
						{$multipage}
					</div>
					<div class="clear"></div>
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<thead>
							<tr>
								<th class="thead" colspan="2">
									<strong>{$lang->myalerts_page_title}</strong>
									<div class="float_right">
										<a id="getUnreadAlerts" href="{$mybb->settings[\'bburl\']}/usercp.php?action=alerts">{$lang->myalerts_page_getnew}</a>
									</div>
								 </th>
							</tr>
						</thead>
						<tbody id="latestAlertsListing">
							{$alertsListing}
						</tbody>
					</table>
					<div class="float_right">
						{$multipage}
					</div>
					<br class="clear" />
				</td>
			</tr>
		</table>
		{$footer}
	</body>
	</html>',
			'settings_page'      =>  '<html>
	<head>
		<title>{$lang->myalerts_settings_page_title} - {$mybb->settings[\'bbname\']}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				{$usercpnav}
				<td valign="top">
					<form action="usercp.php?action=alert_settings" method="post">
						<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
						<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
							<thead>
								<tr>
									<th class="thead" colspan="1">
										<strong>{$lang->myalerts_settings_page_title}</strong>
									 </th>
								</tr>
							</thead>
							<tbody>
								{$alertSettings}
							</tbody>
						</table>
						<div style="text-align:center;">
							<input type="submit" value="{$lang->myalerts_settings_save}" />
						</div>
					</form>
				</td>
			</tr>
		</table>
		{$footer}
	</body>
	</html>',
			'setting_row'	=>	'<tr>
	<td class="{$altbg}">
		<label for="input_{$key}"><input type="checkbox" name="{$key}" id="input_{$key}"{$checked} /> &nbsp; {$langline}</label>
	</td>
</tr>',
			'headericon'	=>	'<span class="myalerts_popup_wrapper{$newAlertsIndicator}">
	&mdash; <a href="{$mybb->settings[\'bburl\']}/usercp.php?action=alerts" class="unreadAlerts myalerts_popup_hook" id="unreadAlerts_menu">Alerts ({$mybb->user[\'unreadAlerts\']})</a>
	<div id="unreadAlerts_menu_popup" class="myalerts_popup" style="display:none;">
		<div class="popupTitle">{$lang->myalerts_page_title}</div>
		<ol>
		{$alerts}
		</ol>
		<div class="popupFooter"><a href="usercp.php?action=alerts">{$lang->myalerts_usercp_nav_alerts}</a></div>
	</div>
</span>',
			'alert_row' =>  '<tr class="alert_row {$alert[\'rowType\']}Row{$alert[\'unreadAlert\']}" id="alert_row_{$alert[\'id\']}">
	<td class="{$altbg}" width="50">
		<a class="avatar" href="{$alert[\'userLink\']}"><img src="{$alert[\'avatar\']}" alt="{$alert[\'username\']}\'s avatar" width="48" height="48" /></a>
	</td>
	<td class="{$altbg}">
		{$alert[\'message\']}
		<br />
		<span class="smalltext float_right">
			<a href="{$mybb->settings[\'bburl\']}/usercp.php?action=deleteAlert&amp;id={$alert[\'id\']}&amp;my_post_key={$mybb->post_code}" class="deleteAlertButton" id="delete_alert_{$alert[\'id\']}">Delete</a>
		</span>
		<br class="clear" />
	</td>
</tr>',
			'alert_row_no_alerts' =>  '<tr class="alert_row noAlertsRow">
	<td class="{$altbg}" colspan="2" style="text-align:center;">
		{$lang->myalerts_no_alerts}
	</td>
</tr>',
			'alert_row_popup' =>  '<li class="alert_row {$alert[\'rowType\']}Row{$alert[\'unreadAlert\']}">
	<a class="avatar" href="{$alert[\'userLink\']}"><img src="{$alert[\'avatar\']}" alt="{$alert[\'username\']}\'s avatar" width="24" height="24" /></a>
	<div class="alertContent">
		{$alert[\'message\']}
	</div>
</li>',
			'alert_row_popup_no_alerts' =>  '<li class="alert_row noAlertsRow">
	{$lang->myalerts_no_alerts}
</li>',
			'usercp_nav' => '<tr>
	<td class="tcat">
		<div class="expcolimage">
			<img src="{$theme[\'imgdir\']}/collapse{$collapsedimg[\'usercpalerts\']}.gif" id="usercpalerts_img" class="expander" alt="[-]" title="[-]" />
		</div>
		<div>
			<span class="smalltext">
				<strong>{$lang->myalerts_usercp_nav}</strong>
			</span>
		</div>
	</td>
</tr>
<tbody style="{$collapsed[\'usercpalerts_e\']}" id="usercpalerts_e">
	<tr>
		<td class="trow1 smalltext">
			<a href="usercp.php?action=alerts" class="usercp_nav_item usercp_nav_myalerts">{$lang->myalerts_usercp_nav_alerts}</a>
		</td>
	</tr>
	<tr>
		<td class="trow1 smalltext">
			<a href="usercp.php?action=alert_settings" class="usercp_nav_item usercp_nav_options">{$lang->myalerts_usercp_nav_settings}</a>
		</td>
	</tr>
	<tr>
		<td class="trow1 smalltext">
			<a href="usercp.php?action=deleteReadAlerts&amp;my_post_key={$mybb->post_code}" onclick="return confirm(\'{$lang->myalerts_delete_read_confirm}\')" class="usercp_nav_item usercp_nav_myalerts_delete_read">{$lang->myalerts_usercp_nav_delete_read}</a>
		</td>
	</tr>
	<tr>
		<td class="trow1 smalltext">
			<a href="usercp.php?action=deleteAllAlerts&amp;my_post_key={$mybb->post_code}" onclick="return confirm(\'{$lang->myalerts_delete_all_confirm}\')" class="usercp_nav_item usercp_nav_myalerts_delete_all">{$lang->myalerts_usercp_nav_delete_all}</a>
		</td>
	</tr>
</tbody>',
		)
	);

	$stylesheet = '.unreadAlerts {
	display: inline-block;
}

.usercp_nav_myalerts {
	background:url(images/usercp/transmit_blue.png) no-repeat left center;
}
.usercp_nav_myalerts_delete_all {
	background:url(images/usercp/delete.png) no-repeat left center;
}
.usercp_nav_myalerts_delete_read {
	background:url(images/usercp/bin.png) no-repeat left center;
}

.newAlerts > a {
	color:red !important;
}

.myalerts_popup ol {
	list-style:none;
	margin:0;
	padding:0;
}
	.myalerts_popup li {
		min-height:24px;
		padding:2px 4px;
		border-bottom:1px solid #D4D4D4;
	}
	.myalerts_popup li .avatar {
		float:left;
		height:24px;
		width:24px;
	}
	.myalerts_popup li .alertContent {
		margin-left:30px;
		font-size:11px;
	}
	.unreadAlert {
		font-weight:bold;
		background:#FFFBD9;
	}

.myalerts_popup_wrapper{
	position:relative;
}

.myalerts_popup_wrapper .myalerts_popup {
	background:#fff;
	width:350px;
	max-width:350px;
	box-shadow:0 0 10px rgba(0,0,0,0.2);
	position:absolute;
	left:0;
}
	.myalerts_popup .popupTitle {
		font-weight:bold;
		margin:0 2px;
		padding:2px;
		border-bottom:1px solid #D4D4D4;
	}
	.myalerts_popup .popupFooter {
		padding:4px;
		background:#EFEFEF;
		box-shadow:inset 0 1px 0 0 rgba(255,255,255,0.2);
	}';

	$PL->stylesheet('alerts.css', $stylesheet);

	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	// Add our JS. We need jQuery and myalerts.js. For jQuery, we check it hasn't already been loaded then load 1.7.2 from google's CDN
	find_replace_templatesets('headerinclude', "#".preg_quote('{$stylesheets}')."#i", '<script type="text/javascript">
if (typeof jQuery == \'undefined\')
{
	document.write(unescape("%3Cscript src=\'http://code.jquery.com/jquery-1.7.2.min.js\' type=\'text/javascript\'%3E%3C/script%3E"));
}
</script>
<script type="text/javascript">
	var unreadAlerts = {$mybb->user[\'unreadAlerts\']};
</script>
<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/myalerts.js"></script>'."\n".'{$stylesheets}');
	find_replace_templatesets('header_welcomeblock_member', "#".preg_quote('{$modcplink}')."#i", '<myalerts_headericon>{$modcplink}');

	// Helpdocs
	$query = $db->simple_select('helpsections', 'sid', "name = '".$lang->myalerts_helpsection_name."'");
	if (!$db->num_rows($query))
	{
		$helpsection = $db->insert_query('helpsections', array(
			'name'              =>  $lang->myalerts_helpsection_name,
			'description'       =>  $lang->myalerts_helpsection_desc,
			'usetranslation'    =>  1,
			'enabled'           =>  1,
			'disporder'         =>  3,
			));
	}
	else
	{
		$sid = (int) $db->fetch_field($query, 'sid');
		$helpsection = $db->update_query('helpsections', array(
			'name'              =>  $lang->myalerts_helpsection_name,
			'description'       =>  $lang->myalerts_helpsection_desc,
			'usetranslation'    =>  1,
			'enabled'           =>  1,
			'disporder'         =>  3,
			), "sid = {$sid}");
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

	foreach ($helpDocuments as $document)
	{
		$query = $db->simple_select('helpdocs', 'hid', "name = '{$document['name']}'");
		if (!$db->num_rows($query))
		{
			$db->insert_query('helpdocs', $document);
		}
		else
		{
			$db->update_query('helpdocs', $document, "name = '{$document['name']}'", 1);
		}
		unset($query);
	}

	$taskExists = $db->simple_select('tasks', 'tid', 'file = \'myalerts\'', array('limit' => '1'));
	if ($db->num_rows($taskExists) == 0) {
		require_once MYBB_ROOT.'/inc/functions_task.php';

		$myTask = array(
			'title'			=> $lang->myalerts_task_title,
			'file'			=> 'myalerts',
			'description'	=> $lang->myalerts_task_description,
			'minute'		=> '0',
			'hour'			=> '1',
			'day'			=> '*',
			'weekday'		=> '1',
			'month'			=> '*',
			'nextrun'		=> TIME_NOW + 3600,
			'lastrun'		=> 0,
			'enabled'		=> 1,
			'logging'		=> 1,
			'locked'		=> 0,

		);

        $myTask['nextrun'] = fetch_next_run($myTask);
        $tid = $db->insert_query("tasks", $myTask);
        $plugins->run_hooks('admin_tools_tasks_add_commit');
        $cache->update_tasks();
	}
	else
	{
		require_once MYBB_ROOT.'/inc/functions_task.php';
		$db->update_query('tasks', array('enabled' => 1, 'nextrun' => fetch_next_run($myTask)), 'file = \'myalerts\'');
		$cache->update_tasks();
	}
}

function myalerts_deactivate()
{
	global $Pl, $db;

	$PL or require_once PLUGINLIBRARY;

	$PL->stylesheet_deactivate('alerts.css');

	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('headerinclude', "#".preg_quote('<script type="text/javascript">
if (typeof jQuery == \'undefined\')
{
	document.write(unescape("%3Cscript src=\'http://code.jquery.com/jquery-1.7.2.min.js\' type=\'text/javascript\'%3E%3C/script%3E"));
}
</script>
<script type="text/javascript">
	var unreadAlerts = {$mybb->user[\'unreadAlerts\']};
</script>
<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/myalerts.js"></script>'."\n")."#i", '');
	find_replace_templatesets('header_welcomeblock_member', "#".preg_quote('<myalerts_headericon>')."#i", '');

	$db->update_query('tasks', array('enabled' => 0), 'file = \'myalerts\'');
}

global $settings;

function parse_alert($alert)
{
	global $mybb, $lang, $plugins;

	if (!$lang->myalerts)
	{
		$lang->load('myalerts');
	}

	require_once  MYBB_ROOT.'inc/class_parser.php';
	$parser = new postParser;


	$alert['userLink'] = get_profile_link($alert['uid']);
	$alert['user'] = build_profile_link($alert['username'], $alert['uid']);
	$alert['dateline'] = my_date($mybb->settings['dateformat'], $alert['dateline'])." ".my_date($mybb->settings['timeformat'], $alert['dateline']);

	if ($alert['unread'] == 1)
	{
		$alert['unreadAlert'] = ' unreadAlert';
	}
	else
	{
		$alert['unreadAlert'] = '';
	}

	$plugins->run_hooks('myalerts_alerts_output_start', $alert);

	if ($alert['alert_type'] == 'rep' AND $mybb->settings['myalerts_alert_rep'])
	{
		$alert['message'] = $lang->sprintf($lang->myalerts_rep, $alert['user'], $mybb->user['uid'], $alert['dateline']);
		$alert['rowType'] = 'reputationAlert';
	}
	elseif ($alert['alert_type'] == 'pm' AND $mybb->settings['myalerts_alert_pm'])
	{
		$alert['message'] = $lang->sprintf($lang->myalerts_pm, $alert['user'], "<a href=\"{$mybb->settings['bburl']}/private.php?action=read&amp;pmid=".(int) $alert['content']['pm_id']."\">".htmlspecialchars_uni($parser->parse_badwords($alert['content']['pm_title']))."</a>", $alert['dateline']);
		$alert['rowType'] = 'pmAlert';
	}
	elseif ($alert['alert_type'] == 'buddylist' AND $mybb->settings['myalerts_alert_buddylist'])
	{
		$alert['message'] = $lang->sprintf($lang->myalerts_buddylist, $alert['user'], $alert['dateline']);
		$alert['rowType'] = 'buddylistAlert';
	}
	elseif ($alert['alert_type'] == 'quoted' AND $mybb->settings['myalerts_alert_quoted'])
	{
		$alert['postLink'] = $mybb->settings['bburl'].'/'.get_post_link($alert['content']['pid'], $alert['content']['tid']).'#pid'.$alert['content']['pid'];
		$alert['message'] = $lang->sprintf($lang->myalerts_quoted, $alert['user'], $alert['postLink'], $alert['dateline']);
		$alert['rowType'] = 'quotedAlert';
	}
	elseif ($alert['alert_type'] == 'post_threadauthor' AND $mybb->settings['myalerts_alert_post_threadauthor'])
	{
		$alert['threadLink'] = $mybb->settings['bburl'].'/'.get_thread_link($alert['content']['tid'], 0, 'newpost');
		$alert['message'] = $lang->sprintf($lang->myalerts_post_threadauthor, $alert['user'], $alert['threadLink'], htmlspecialchars_uni($parser->parse_badwords($alert['content']['t_subject'])), $alert['dateline']);
		$alert['rowType'] = 'postAlert';
	}

	$plugins->run_hooks('myalerts_alerts_output_end', $alert);

	return $alert;
}

if ($settings['myalerts_enabled'])
{
	$plugins->add_hook('member_do_register_end', 'myalerts_register_do_end');
}
function myalerts_register_do_end()
{
	global $user_info, $db, $plugins;

	$possible_settings = array(
		'rep',
		'pm',
		'buddylist',
		'quoted',
		'post_threadauthor',
		);
	$plugins->run_hooks('myalerts_possible_settings', $possible_settings);
	$possible_settings = array_flip($possible_settings);
	$possible_settings = array_fill_keys(array_keys($possible_settings), 1);
	$possible_settings = json_encode($possible_settings);

	$db->update_query('users', array('myalerts_settings' => $db->escape_string($possible_settings)), 'uid = '.(int) $user_info['uid']);
}

if ($settings['myalerts_enabled'])
{
	$plugins->add_hook('pre_output_page', 'myalerts_pre_output_page');
}
function myalerts_pre_output_page(&$contents)
{
	global $templates, $mybb, $lang, $myalerts_headericon, $Alerts, $plugins;

	if ($mybb->user['uid'])
	{
		if (!$lang->myalerts)
		{
			$lang->load('myalerts');
		}

		try
		{
			$userAlerts = $Alerts->getAlerts(0, $mybb->settings['myalerts_dropdown_limit']);
		}
		catch (Exception $e)
		{
		}

		$alerts = '';

		if ($mybb->user['unreadAlerts'])
		{
			$newAlertsIndicator = ' newAlerts';
		}

		if (is_array($userAlerts) AND count($userAlerts) > 0)
		{
			foreach ($userAlerts as $alert)
			{
				$alert = array_merge($alert, parse_alert($alert));

				if ($alert['message'])
				{
					eval("\$alerts .= \"".$templates->get('myalerts_alert_row_popup')."\";");
				}

				$readAlerts[] = $alert['id'];
			}
		}
		else
		{
			eval("\$alerts = \"".$templates->get('myalerts_alert_row_no_alerts')."\";");
		}

		eval("\$myalerts_headericon = \"".$templates->get('myalerts_headericon')."\";");

		$contents = str_replace('<myalerts_headericon>', $myalerts_headericon, $contents);

		return $contents;
	}
}

if ($settings['myalerts_enabled'])
{
	$plugins->add_hook('global_start', 'myalerts_global');
}
function myalerts_global()
{
	global $mybb, $templatelist;

	if (isset($templatelist))
	{
		$templatelist .= ',';
	}

	$templatelist .= 'myalerts_headericon,myalerts_popup_row';

	if (THIS_SCRIPT == 'usercp.php')
	{
		$templatelist .= ',myalerts_usercp_nav';
	}

	if (THIS_SCRIPT == 'usercp.php' AND $mybb->input['action'] == 'alerts')
	{
		$templatelist .= ',myalerts_page,myalerts_alert_row,multipage_page_current,multipage_page,multipage_nextpage,multipage';
	}

	if (THIS_SCRIPT == 'usercp.php' AND $mybb->input['action'] == 'alert_settings')
	{
		$templatelist .= ',myalerts_setting_row,myalerts_settings_page';
	}

	if ($mybb->user['uid'])
	{
		global $Alerts, $db, $lang;
		require_once MYALERTS_PLUGIN_PATH.'Alerts.class.php';
		try
		{
			$Alerts = new Alerts($mybb, $db);
		}
		catch (Exception $e)
		{
			die($e->getMessage());
		}

		if (!$lang->myalerts)
		{
			$lang->load('myalerts');
		}

		$mybb->user['myalerts_settings'] = json_decode($mybb->user['myalerts_settings'], true);

		// Sanitize the alerts settings here to make life easy in the future
		foreach ($mybb->user['myalerts_settings'] as $key => $value)
		{
			$mybb->user['myalerts_settings'][$key] = $db->escape_string($value);
		}

		$mybb->user['unreadAlerts'] = my_number_format((int) $Alerts->getNumUnreadAlerts());
	}
}

if ($settings['myalerts_enabled'])
{
	$plugins->add_hook('build_friendly_wol_location_end', 'myalerts_online_location');
}
function myalerts_online_location(&$plugin_array)
{
	global $mybb, $lang;

	if (!$lang->myalerts)
	{
		$lang->load('myalerts');
	}

	if ($plugin_array['user_activity']['activity'] == 'usercp' AND my_strpos($plugin_array['user_activity']['location'], 'alerts'))
	{
		$plugin_array['location_name'] = $lang->myalerts_online_location_listing;
	}
}

if ($settings['myalerts_enabled'])
{
	$plugins->add_hook('misc_help_helpdoc_start', 'myalerts_helpdoc');
}
function myalerts_helpdoc()
{
	global $helpdoc, $lang, $mybb;

	if (!$lang->myalerts)
	{
		$lang->load('myalerts');
	}

	if ($helpdoc['name'] == $lang->myalerts_help_alert_types)
	{
		if ($mybb->settings['myalerts_alert_rep'])
		{
			$helpdoc['document'] .= $lang->myalerts_help_alert_types_rep;
		}

		if ($mybb->settings['myalerts_alert_pm'])
		{
			$helpdoc['document'] .= $lang->myalerts_help_alert_types_pm;
		}

		if ($mybb->settings['myalerts_alert_buddylist'])
		{
			$helpdoc['document'] .= $lang->myalerts_help_alert_types_buddylist;
		}

		if ($mybb->settings['myalerts_alert_quoted'])
		{
			$helpdoc['document'] .= $lang->myalerts_help_alert_types_quoted;
		}

		if ($mybb->settings['myalerts_alert_post_threadauthor'])
		{
			$helpdoc['document'] .= $lang->myalerts_help_alert_types_post_threadauthor;
		}
	}
}

if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_rep'])
{
	$plugins->add_hook('reputation_do_add_process', 'myalerts_addAlert_rep');
}
function myalerts_addAlert_rep()
{
	global $mybb, $Alerts, $reputation;

	$Alerts->addAlert($reputation['uid'], 'rep', 0, $mybb->user['uid'], array());
}

if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_pm'])
{
	$plugins->add_hook('private_do_send_end', 'myalerts_addAlert_pm');
}
function myalerts_addAlert_pm()
{
	global $mybb, $Alerts, $db, $pm, $pmhandler;

	$pmUsers = array_map("trim", $pm['to']);
	$pmUsers = array_unique($pmUsers);

	$users = array();
	$userArray = array();

	foreach ($pmUsers as $user)
	{
		$users[] = $db->escape_string($user);
	}

	if (count($users) > 0)
	{
		$query = $db->simple_select('users', 'uid', "LOWER(username) IN ('".my_strtolower(implode("','", $users))."')");
	}

	$users = array();

	while ($user = $db->fetch_array($query))
	{
		$users[] = $user['uid'];
	}

	$Alerts->addMassAlert($users, 'pm', 0, $mybb->user['uid'], array(
		'pm_title'  =>  $pm['subject'],
		'pm_id'     =>  $pmhandler->pmid,
		)
	);
}

if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_buddylist'])
{
	$plugins->add_hook('usercp_do_editlists_end', 'myalerts_alert_buddylist');
}
function myalerts_alert_buddylist()
{
	global $mybb;

	if ($mybb->input['manage'] != 'ignore' AND !isset($mybb->input['delete']))
	{
		global $Alerts, $db;

		$addUsers = explode(",", $mybb->input['add_username']);
		$addUsers = array_map("trim", $addUsers);
		$addUsers = array_unique($addUsers);

		$users = array();
		$userArray = array();

		foreach ($addUsers as $user)
		{
			$users[] = $db->escape_string($user);
		}

		if (count($users) > 0)
		{
			$query = $db->simple_select('users', 'uid', "LOWER(username) IN ('".my_strtolower(implode("','", $users))."')");

			$user = array();

			while($user = $db->fetch_array($query))
			{
				$userArray[] = $user['uid'];
			}

			$Alerts->addMassAlert($userArray, 'buddylist', 0, $mybb->user['uid'], array());
		}
	}
}

if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_quoted'])
{
	$plugins->add_hook('newreply_do_newreply_end', 'myalerts_alert_quoted');
}
function myalerts_alert_quoted()
{
	global $mybb, $Alerts, $db, $pid, $post;

	$message = $post['message'];

	$pattern = "#\[quote=([\"']|&quot;|)(.*?)(?:\\1)(.*?)(?:[\"']|&quot;)?\](.*?)\[/quote\](\r\n?|\n?)#esi";

	preg_match_all($pattern, $message, $match);

	if (!array_key_exists('2', $match))
	{
		return;
	}

	$matches = array_merge($match[2], $match[3]);

	foreach($matches as $key => $value)
	{
		if (empty($value))
		{
			unset($matches[$key]);
		}
	}

	$users = array_values($matches);

	if (!empty($users) AND is_array($users))
	{
		foreach ($users as $value)
		{
			$queryArray[] = $db->escape_string($value);
		}

		$uids = $db->write_query('SELECT `uid` FROM `'.TABLE_PREFIX.'users` WHERE LOWER(username) IN (\''.my_strtolower(implode("','", $queryArray)).'\') AND uid != '.$mybb->user['uid']);

		$userList = array();

		while ($uid = $db->fetch_array($uids))
		{
			$userList[] = (int) $uid['uid'];
		}

		if (!empty($userList) AND is_array($userList))
		{
			$Alerts->addMassAlert($userList, 'quoted', 0, $mybb->user['uid'], array(
				'tid'       =>  $post['tid'],
				'pid'       =>  $pid,
				'subject'   =>  $post['subject'],
				));
		}
	}
}

if ($settings['myalerts_enabled'] AND $settings['myalerts_alert_post_threadauthor'])
{
	$plugins->add_hook('datahandler_post_insert_post', 'myalerts_alert_post_threadauthor');
}
function myalerts_alert_post_threadauthor(&$post)
{
	global $mybb, $Alerts, $db;

	if (!$post->data['savedraft'])
	{
		if ($post->post_insert_data['tid'] == 0)
		{
			$query = $db->simple_select('threads', 'uid,subject', 'tid = '.$post->data['tid'], array('limit' => '1'));
			$thread = $db->fetch_array($query);
		}
		else
		{
			$query = $db->simple_select('threads', 'uid,subject', 'tid = '.$post->post_insert_data['tid'], array('limit' => '1'));
			$thread = $db->fetch_array($query);
		}

		if ($thread['uid'] != $mybb->user['uid'])
		{
			//check if alerted for this thread already
			$query = $db->simple_select('alerts', 'id', 'tid = '.(int) $post->post_insert_data['tid'].' AND unread = 1 AND alert_type = \'post_threadauthor\'');

			if ($db->num_rows($query) < 1)
			{
				$Alerts->addAlert($thread['uid'], 'post_threadauthor', (int) $post->post_insert_data['tid'], $mybb->user['uid'], array(
					'tid'       =>  $post->post_insert_data['tid'],
					't_subject' =>  $thread['subject'],
					));
			}
		}
	}
}

if ($settings['myalerts_enabled'])
{
	$plugins->add_hook('usercp_menu', 'myalerts_usercp_menu', 20);
}
function myalerts_usercp_menu()
{
	global $mybb, $templates, $theme, $usercpmenu, $lang, $collapsed, $collapsedimg;

	if (!$lang->myalerts)
	{
		$lang->load('myalerts');
	}

	if ($mybb->user['unreadAlerts'] > 0)
	{
		$lang->myalerts_usercp_nav_alerts = '<strong>'.$lang->myalerts_usercp_nav_alerts.' ('.my_number_format((int) $mybb->user['unreadAlerts']).')</strong>';
	}

	eval("\$usercpmenu .= \"".$templates->get('myalerts_usercp_nav')."\";");
}

if ($settings['myalerts_enabled'])
{
	$plugins->add_hook('usercp_start', 'myalerts_page');
}
function myalerts_page()
{
	global $mybb;

	if ($mybb->input['action'] == 'alerts')
	{
		global $Alerts, $db, $lang, $theme, $templates, $headerinclude, $header, $footer, $plugins, $usercpnav;

		if (!$lang->myalerts)
		{
			$lang->load('myalerts');
		}

		add_breadcrumb($lang->nav_usercp, 'usercp.php');
		add_breadcrumb($lang->myalerts_page_title, 'usercp.php?action=alerts');

		$numAlerts = $Alerts->getNumAlerts();
		$page = (int) $mybb->input['page'];
		$pages = ceil($numAlerts / $mybb->settings['myalerts_perpage']);

		if ($page > $pages OR $page <= 0)
		{
			$page = 1;
		}

		if ($page)
		{
			$start = ($page - 1) * $mybb->settings['myalerts_perpage'];
		}
		else
		{
			$start = 0;
			$page = 1;
		}
		$multipage = multipage($numAlerts, $mybb->settings['myalerts_perpage'], $page, "usercp.php?action=alerts");

		try
		{
			$alertsList = $Alerts->getAlerts($start);
		}
		catch (Exception $e)
		{
			die($e->getMessage());
		}

		$readAlerts = array();

		if ($numAlerts > 0)
		{
			foreach ($alertsList as $alert)
			{
				$altbg = alt_trow();

				$alert = array_merge($alert, parse_alert($alert));

				if ($alert['message'])
				{
					eval("\$alertsListing .= \"".$templates->get('myalerts_alert_row')."\";");
				}

				$readAlerts[] = $alert['id'];
			}
		}
		else
		{
			$altbg = 'trow1';
			eval("\$alertsListing = \"".$templates->get('myalerts_alert_row_no_alerts')."\";");
		}

		$Alerts->markRead($readAlerts);

		eval("\$content = \"".$templates->get('myalerts_page')."\";");
		output_page($content);
	}

	if ($mybb->input['action'] == 'alert_settings')
	{
		global $db, $lang, $theme, $templates, $headerinclude, $header, $footer, $plugins, $usercpnav;

		if (!$lang->myalerts)
		{
			$lang->load('myalerts');
		}

		$possible_settings = array(
			'rep',
			'pm',
			'buddylist',
			'quoted',
			'post_threadauthor',
			);
		$plugins->run_hooks('myalerts_possible_settings', $possible_settings);
		$possible_settings = array_flip($possible_settings);
		$possible_settings = array_fill_keys(array_keys($possible_settings), 0);

		if ($mybb->request_method == 'post')
		{
			verify_post_check($mybb->input['my_post_key']);

			$settings = array_intersect_key($mybb->input, $possible_settings);

			//	Seeing as unchecked checkboxes just aren't sent, we need an array of all the possible settings, defaulted to 0 (or off) to merge
			$settings = array_merge($possible_settings, $settings);

			$settings = json_encode($settings);

			if ($db->update_query('users', array('myalerts_settings' => $db->escape_string($settings)), 'uid = '.(int) $mybb->user['uid']))
			{
				redirect('usercp.php?action=alert_settings', $lang->myalerts_settings_updated, $lang->myalerts_settings_updated_title);
			}
		}
		else
		{
			$settings = array_merge($possible_settings, (array) $mybb->user['myalerts_settings']);
			$settings = array_intersect_key($settings, $possible_settings);
			foreach ($settings as $key => $value)
			{
				$temparraykey = 'myalerts_alert_'.$key;

				if ($mybb->settings[$temparraykey])
				{
					$altbg = alt_trow();
					//	variable variables. What fun! http://php.net/manual/en/language.variables.variable.php
					$tempkey = 'myalerts_setting_'.$key;

					$langline = $lang->$tempkey;

					$checked = '';
					if ($value)
					{
						$checked = ' checked="checked"';
					}

					eval("\$alertSettings .= \"".$templates->get('myalerts_setting_row')."\";");
				}
			}

			eval("\$content = \"".$templates->get('myalerts_settings_page')."\";");
			output_page($content);
		}
	}

	if ($mybb->input['action'] == 'deleteAlert' AND $mybb->input['id'])
	{
		global $Alerts, $lang;

		verify_post_check($mybb->input['my_post_key']);

		if (!$lang->myalerts)
		{
			$lang->load('myalerts');
		}

		if ($Alerts->deleteAlerts(array($mybb->input['id'])))
		{
			if ($mybb->input['accessMethod'] == 'js')
			{
				$resp = array(
					'success'	=>	$lang->myalerts_delete_deleted,
					);
				$numAlerts = $Alerts->getNumAlerts();
				if ($numAlerts < 1)
				{
					global $templates;

					$altbg = 'trow1';
					eval("\$resp['template'] = \"".$templates->get('myalerts_alert_row_no_alerts')."\";");
				}

				header('Cache-Control: no-cache, must-revalidate');
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
				header('Content-type: application/json');
				echo json_encode($resp);
			}
			else
			{
				redirect('usercp.php?action=alerts', $lang->myalerts_delete_deleted, $lang->myalerts_delete_deleted);
			}
		}
		else
		{
			if ($mybb->input['accessMethod'] == 'js')
			{
				header('Cache-Control: no-cache, must-revalidate');
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
				header('Content-type: application/json');
				echo json_encode(array('error' =>	$lang->myalerts_delete_error));
			}
			else
			{
				redirect('usercp.php?action=alerts', $lang->myalerts_delete_error, $lang->myalerts_delete_error);
			}
		}
	}

	if ($mybb->input['action'] == 'deleteReadAlerts')
	{
		global $Alerts, $lang;

		verify_post_check($mybb->input['my_post_key']);

		if (!$lang->myalerts)
		{
			$lang->load('myalerts');
		}

		if ($Alerts->deleteAlerts('allRead'))
		{
			redirect('usercp.php?action=alerts', $lang->myalerts_delete_all_read, $lang->myalerts_delete_mass_deleted);
		}
		else
		{
			redirect('usercp.php?action=alerts', $lang->myalerts_delete_mass_error_more, $lang->myalerts_delete_mass_error);
		}
	}

	if ($mybb->input['action'] == 'deleteAllAlerts')
	{
		global $Alerts, $lang;

		verify_post_check($mybb->input['my_post_key']);

		if (!$lang->myalerts)
		{
			$lang->load('myalerts');
		}

		if ($Alerts->deleteAlerts('allAlerts'))
		{
			redirect('usercp.php?action=alerts', $lang->myalerts_delete_all, $lang->myalerts_delete_mass_deleted);
		}
		else
		{
			redirect('usercp.php?action=alerts', $lang->myalerts_delete_mass_error_more, $lang->myalerts_delete_mass_error);
		}
	}
}

if ($settings['myalerts_enabled'])
{
	$plugins->add_hook('xmlhttp', 'myalerts_xmlhttp');
}
function myalerts_xmlhttp()
{
	global $mybb, $db, $lang, $templates, $plugins;

	require_once MYALERTS_PLUGIN_PATH.'Alerts.class.php';
	try
	{
		$Alerts = new Alerts($mybb, $db);
	}
	catch (Exception $e)
	{
		die($e->getMessage());
	}

	if (!$lang->myalerts)
	{
		$lang->load('myalerts');
	}

	if ($mybb->input['action'] == 'getNewAlerts')
	{
		try
		{
			$newAlerts = $Alerts->getUnreadAlerts();
		}
		catch (Exception $e)
		{
			die($e->getMessage());
		}

		if (!empty($newAlerts) AND is_array($newAlerts))
		{
			$alertsListing = '';
			$markRead = array();

			foreach ($newAlerts as $alert)
			{
				$altbg = alt_trow();

				$alert = array_merge($alert, parse_alert($alert));

				if (isset($mybb->input['from']) AND $mybb->input['from'] == 'header')
				{
					if ($alert['message'])
					{
						eval("\$alertsListing .= \"".$templates->get('myalerts_alert_row_popup')."\";");
					}
				}
				else
				{
					if ($alert['message'])
					{
						eval("\$alertsListing .= \"".$templates->get('myalerts_alert_row')."\";");
					}
				}

				$markRead[] = $alert['id'];
			}

			$Alerts->markRead($markRead);
		}
		else
		{
			if ($mybb->input['from'] == 'header')
			{
				$alertinfo = $lang->myalerts_no_new_alerts;

				eval("\$alertsListing = \"".$templates->get('myalerts_alert_row_popup')."\";");
			}
		}

		echo $alertsListing;
	}

	if ($mybb->input['action'] == 'getNumUnreadAlerts')
	{
		echo $Alerts->getNumUnreadAlerts();
	}
}