<?php
/**
 *	MyAlerts Core Plugin File
 *
 *	A simple notification/alert system for MyBB
 *
 *	@author Euan T. <euan@euantor.com>
 *	@version 0.01
 *	@package MyAlerts
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
        'website'       =>  'http://euantor.com',
        'author'        =>  'euantor',
        'authorsite'    =>  '',
        'version'       =>  '0.01',
        'guid'          =>  '',
        'compatibility' =>  '16*',
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

    if (!$db->table_exists('alerts'))
    {
        $db->write_query('CREATE TABLE `'.TABLE_PREFIX.'alerts` (
                `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `uid` INT(10) NOT NULL,
                `unread` TINYINT(4) NOT NULL DEFAULT \'1\',
                `content` TEXT NOT NULL
            ) ENGINE=MyISAM '.$db->build_create_table_collation().';');
    }
}

function myalerts_is_installed()
{
    global $db;
    return $db->table_exists('alerts');
}

function myalerts_uninstall()
{
    global $db;

    if ($db->table_exists('alerts'))
    {
        $db->write_query('DROP TABLE '.TABLE_PREFIX.'alerts');
    }
}

function myalerts_activate()
{
    global $mybb, $db;

    if(!file_exists(PLUGINLIBRARY))
    {
        flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
        admin_redirect("index.php?module=config-plugins");
    }

    $this_version = myalerts_info();
    $this_version = $this_version['version'];
    require_once MYALERTS_PLUGIN_PATH.'/Alerts.class.php';

    if (Alerts::getVersion() != $this_version)
    {
        flash_message("It seems the Alerts class is not up to date. Please ensure the /inc/plugins/MyAlerts/ folder is up to date. (MyAlerts version: {$this_version}, MyAlerts Class version: ".Alerts::getVersion().")", "error");
        admin_redirect("index.php?module=config-plugins");
    }

    global $PL;
    $PL or require_once PLUGINLIBRARY;

    $PL->settings('myalerts',
    	'MyAlerts Settings',
    	'Settings for the MyAlerts plugin',
    	array(
    		'enabled'	=>	array(
    			'title'			=>	'Enable MyAlerts?',
    			'description'	=>	'This switch can be used to globally disable all MyAlerts features',
    			'value'			=>	'1',
    			),
            'perpage'   =>  array(
                'title'         =>  'Alerts per page',
                'description'   =>  'How many alerts do you wish to display on the alerts listing page? (default is 10)',
                'value'         =>  '10',
                'optionscode'   =>  'text',
                ),
            'alert_rep' =>  array(
                'title'         =>  'Alert on reputation?',
                'description'   =>  'Do you wish for users to receive a new alert when somebody gives them a reputation?',
                'value'         =>  '1',
                ),
            'alert_pm'  =>  array(
                'title'         =>  'Alert on Private Message?',
                'description'   =>  'Do you wish for users to receive an alert when they are sent a new Private Message (PM)?',
                'value'         =>  '1',
                ),
    		)
    	);

    $PL->templates('myalerts',
        'MyAlerts',
        array(
            'page'      =>  '<html>
    <head>
        <title>Alerts - {$mybb->settings[\'bbname\']}</title>
            {$headerinclude}
    </head>
    <body>
        {$header}
        <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
            <thead>
                <tr>
                    <th class="thead" colspan="1">
                        <strong>Recent Alerts</strong>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="trow1" id="latestAlertsListing">
                        {$alertsListing}
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="float_right">
            {$multipage}
        </div>
        <br class="clear" />
        {$footer}
    </body>
</html>',
            'alert_row' =>  '<div class="alert_row">
    {$alertinfo}
</div>',
            )
        );
}

function myalerts_deactivate()
{
	if(!file_exists(PLUGINLIBRARY))
    {
        flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
        admin_redirect("index.php?module=config-plugins");
    }

    global $PL;
    $PL or require_once PLUGINLIBRARY;

    $PL->settings_delete('myalerts');
    $PL->templates_delete('myalerts');
}

$plugins->add_hook('global_start', 'myalerts_global');
function myalerts_global()
{
	global $db, $mybb;

	if ($mybb->settings['myalerts_enabled'])
	{
		global $Alerts;
		require_once MYALERTS_PLUGIN_PATH.'Alerts.class.php';
		$Alerts = new Alerts($mybb, $db);
	}
}

$plugins->add_hook('reputation_do_add_process', 'myalerts_addAlert_rep');
function myalerts_addAlert_rep()
{
    global $mybb, $reputation;

    if ($mybb->settings['myalerts_enabled'] AND $mybb->settings['myalerts_alert_rep'])
    {
        global $Alerts;

        $Alerts->addAlert($reputation['uid'], array(
            'type'      =>  'rep',
            'from'      =>  array(
                    'uid'       =>  intval($mybb->user['uid']),
                    'username'  =>  $mybb->user['username'],
                    ),
            'dateline'  =>  TIME_NOW,
            )
        );
    }
}

$plugins->add_hook('private_do_send_end', 'myalerts_addAlert_pm');
function myalerts_addAlert_pm()
{
    global $mybb, $pm;

    if ($mybb->settings['myalerts_enabled'] AND $mybb->settings['myalerts_alert_pm'])
    {
        global $Alerts;

        $Alerts->addAlert($pm['to'], array(
            'type'      =>  'pm',
            'from'      =>  array(
                    'uid'       =>  intval($mybb->user['uid']),
                    'username'  =>  $mybb->user['username'],
                    ),
            'pm_title'  =>  $pm['subject'],
            'dateline'  =>  TIME_NOW,
            )
        );
    }
}

$plugins->add_hook('misc_start', 'myalerts_page');
function myalerts_page()
{
    global $mybb, $db, $theme, $templates, $headerinclude, $header, $footer;

    if ($mybb->settings['myalerts_enabled'])
    {
        global $Alerts;

        if ($mybb->input['action'] == 'myalerts')
        {
            add_breadcrumb('Alerts', 'misc.php?action=myalerts');

            $numAlerts = $Alerts->getNumAlerts();
            $page = intval($mybb->input['page']);
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
            $multipage = multipage($numAlerts, $mybb->settings['myalerts_perpage'], $page, "misc.php?action=myalerts");

            $alertsList = $Alerts->getAlerts($start);

            if ($numAlerts > 0)
            {
                foreach ($alertsList as $alert)
                {
                    $alert['user'] = build_profile_link($alert['content']['from']['username'], $alert['content']['from']['uid']);
                    $alert['dateline'] = my_date($mybb->settings['dateformat'], $alert['content']['dateline']);

                    if ($alert['content']['type'] == 'rep')
                    {
                        $alert['message'] = $alert['user'].' has given you a reputation. (Received: '.$alert['dateline'].')';
                    }
                    elseif ($alert['content']['type'] == 'pm')
                    {
                        $alert['message'] = $alert['user'].' sent you a new private message titled "'.$alert['content']['pm_title'].'". (Received: '.$alert['dateline'].')';
                    }

                    $alertinfo = $alert['message'];

                    eval("\$alertsListing .= \"".$templates->get('myalerts_alert_row')."\";");
                }
            }

            eval("\$content .= \"".$templates->get('myalerts_page')."\";");
            output_page($content);
        }
    }
}

$plugins->add_hook('xmlhttp', 'myalerts_xmlhttp');
function myalerts_xmlhttp()
{
	global $mybb, $db;

	if ($mybb->settings['myalerts_enabled'])
	{
		global $Alerts;

		if ($mybb->input['action'] == 'getAlerts')
		{
			$newAlerts = $Alerts->getAlerts();
			header('Content-Type: text/javascript');
			echo json_encode($newAlerts);
		}

		if ($mybb->input['action'] == 'getNewAlerts')
		{
			$newAlerts = $Alerts->getUnreadAlerts();
			header('Content-Type: text/javascript');
			echo json_encode($newAlerts);
		}

		if ($mybb->input['action'] == 'markAlertsRead')
		{
			if ($Alerts->markRead($db->escape_string($mybb->input['alertsList'])))
			{
				header('Content-Type: text/javascript');
				echo json_encode(array('response' => 'success'));
			}
			else
			{
				header('Content-Type: text/javascript');
				echo json_encode(array('response' => 'error'));
			}
		}

		if ($mybb->input['action'] == 'deleteAlerts')
		{
			if ($Alerts->deleteAlerts($db->escape_string($mybb->input['alertsList'])))
			{
				header('Content-Type: text/javascript');
				echo json_encode(array('response' => 'success'));
			}
			else
			{
				header('Content-Type: text/javascript');
				echo json_encode(array('response' => 'error'));
			}
		}
	}
}
?>