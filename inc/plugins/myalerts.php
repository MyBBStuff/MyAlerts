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
            `dateline` BIGINT(30) NOT NULL,
            `type` VARCHAR(25) NOT NULL,
            `tid` INT(10),
            `from` INT(10),
            `content` TEXT
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

    if(!file_exists(PLUGINLIBRARY))
    {
        flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
        admin_redirect("index.php?module=config-plugins");
    }

    global $PL;
    $PL or require_once PLUGINLIBRARY;

    $db->drop_table('alerts');
    $PL->settings_delete('myalerts', true);
    $PL->templates_delete('myalerts');
}

function myalerts_activate()
{
    global $mybb, $db, $lang;

    if(!file_exists(PLUGINLIBRARY))
    {
        flash_message($lang->myalerts_pluginlibrary_missing, "error");
        admin_redirect("index.php?module=config-plugins");
    }

    if (!$lang->myalerts)
    {
        $lang->load('myalerts');
    }

    $this_version = myalerts_info();
    $this_version = $this_version['version'];
    require_once MYALERTS_PLUGIN_PATH.'/Alerts.class.php';

    if (Alerts::getVersion() != $this_version)
    {
        flash_message($lang->sprintf($lang->myalerts_class_outdated, $this_version, Alerts::getVersion()), "error");
        admin_redirect("index.php?module=config-plugins");
    }

    global $PL;
    $PL or require_once PLUGINLIBRARY;

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
        <div class="float_right">
            {$multipage}
        </div>
        <br class="clear" />
        <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
            <thead>
                <tr>
                    <th class="thead" colspan="1">
                        <strong>{$lang->myalerts_page_title}</strong>
                        <div class="float_right">
                            <a id="getUnreadAlerts" href="{$mybb->settings[\'bburl\']}/misc.php?action=myalerts">{$lang->myalerts_page_getnew}</a>
                        </div>
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
            'alert_row_popup' =>  '<div class="popup_item_container">
    <span class="popup_item">{$alertinfo}</span>
</div>',
        )
    );
}

function myalerts_deactivate()
{
}

global $settings;

if ($settings['myalerts_enabled'])
{
    $plugins->add_hook('global_start', 'myalerts_global');
}
function myalerts_global()
{
    global $mybb, $templatelist;

    if (THIS_SCRIPT == 'misc.php' && $mybb->input['action'] == 'myalerts')
    {
        $templatelist .= ',myalerts_page,multipage_page_current,multipage_page,multipage_nextpage,multipage';
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
        }

        if (!$lang->myalerts)
        {
            $lang->load('myalerts');
        }

        $mybb->user['unreadAlerts'] = $Alerts->getNumUnreadAlerts();
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

    if ($plugin_array['user_activity']['activity'] == 'misc' AND my_strpos($plugin_array['user_activity']['location'], 'myalerts'))
    {
        $plugin_array['location_name'] = $lang->myalerts_online_location_listing;
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

    if ($mybb->input['manage'] != 'ignore' && !isset($mybb->input['delete']))
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
        }

        $user = array();

        while($user = $db->fetch_array($query))
        {
            $userArray[] = $user['uid'];
        }

        $Alerts->addMassAlert($userArray, 'buddylist', 0, $mybb->user['uid'], array());
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

    $matches = array_merge($match[2], $match[3]);

    foreach($matches as $key => $value)
    {
        if (empty($value))
        {
            unset($matches[$key]);
        }
    }

    $users = array_values($matches);

    if (!empty($users))
    {
        foreach ($users as $value)
        {
            $queryArray[] = $db->escape_string($value);
        }

        $uids = $db->write_query('SELECT `uid` FROM `'.TABLE_PREFIX.'users` WHERE username IN (\''.my_strtolower(implode("','", $queryArray)).'\') AND uid != '.$mybb->user['uid']);

        $userList = array();

        while ($uid = $db->fetch_array($uids))
        {
            $userList[] = (int) $uid['uid'];
        }

        if (!empty($userList) && is_array($userList))
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

    $query = $db->simple_select('threads', 'uid,subject', 'tid = '.$post->post_insert_data['tid'], array('limit' => '1'));
    $thread = $db->fetch_array($query);

    if ($thread['uid'] != $mybb->user['uid'])
    {
        //check if alerted for this thread already
        $query = $db->simple_select('alerts', 'id', 'tid = '.(int) $post->post_insert_data['tid'].' AND unread = 1');

        if ($db->num_rows($query) < 1)
        {
            $Alerts->addAlert($thread['uid'], 'post_threadauthor', (int) $post->post_insert_data['tid'], $mybb->user['uid'], array(
                'tid'       =>  $post->post_insert_data['tid'],
                't_subject' =>  $thread['subject'],
                ));
        }
    }
}

if ($settings['myalerts_enabled'])
{
    $plugins->add_hook('misc_start', 'myalerts_page');
}
function myalerts_page()
{
    global $mybb;

    if ($mybb->user['uid'] == 0 )
    {
        error_no_permission();
        die();
    }

    if ($mybb->input['action'] == 'myalerts')
    {
        global $Alerts, $db, $lang, $theme, $templates, $headerinclude, $header, $footer;

        if (!$lang->myalerts)
        {
            $lang->load('myalerts');
        }

        add_breadcrumb('Alerts', 'misc.php?action=myalerts');

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
        $multipage = multipage($numAlerts, $mybb->settings['myalerts_perpage'], $page, "misc.php?action=myalerts");

        try
        {
            $alertsList = $Alerts->getAlerts($start);
        }
        catch (Exception $e)
        {
            error_no_permission();
            die();
        }

        $readAlerts = array();

        if ($numAlerts > 0)
        {
            foreach ($alertsList as $alert)
            {
                $alert['user'] = build_profile_link($alert['username'], $alert['uid']);
                $alert['dateline'] = my_date($mybb->settings['dateformat'], $alert['dateline'])." ".my_date($mybb->settings['timeformat'], $alert['dateline']);

                if ($alert['type'] == 'rep' AND $mybb->settings['myalerts_alert_rep'])
                {
                    $alert['message'] = $lang->sprintf($lang->myalerts_rep, $alert['user'], $alert['dateline']);
                }
                elseif ($alert['type'] == 'pm' AND $mybb->settings['myalerts_alert_pm'])
                {
                    $alert['message'] = $lang->sprintf($lang->myalerts_pm, $alert['user'], "<a href=\"{$mybb->settings['bburl']}/private.php?action=read&amp;pmid=".(int) $alert['content']['pm_id']."\">".htmlspecialchars_uni($alert['content']['pm_title'])."</a>", $alert['dateline']);
                }
                elseif ($alert['type'] == 'buddylist' AND $mybb->settings['myalerts_alert_buddylist'])
                {
                    $alert['message'] = $lang->sprintf($lang->myalerts_buddylist, $alert['user'], $alert['dateline']);
                }
                elseif ($alert['type'] == 'quoted' AND $mybb->settings['myalerts_alert_quoted'])
                {
                    $alert['postLink'] = $mybb->settings['bburl'].'/'.get_post_link($alert['content']['pid'], $alert['content']['tid']).'#pid'.$alert['content']['pid'];
                    $alert['message'] = $lang->sprintf($lang->myalerts_quoted, $alert['user'], $alert['postLink'], $alert['dateline']);
                }
                elseif ($alert['type'] == 'post_threadauthor' AND $mybb->settings['myalerts_alert_post_threadauthor'])
                {
                    $alert['threadLink'] = $mybb->settings['bburl'].'/'.get_thread_link($alert['content']['tid'], 0, 'newpost');
                    $alert['message'] = $lang->sprintf($lang->myalerts_post_threadauthor, $alert['user'], $alert['threadLink'], htmlspecialchars_uni($alert['content']['t_subject']), $alert['dateline']);
                }

                $alertinfo = $alert['message'];

                eval("\$alertsListing .= \"".$templates->get('myalerts_alert_row')."\";");

                $readAlerts[] = $alert['id'];
            }
        }
        else
        {
            $alertinfo = $lang->myalerts_no_alerts;
            eval("\$alertsListing = \"".$templates->get('myalerts_alert_row')."\";");
        }

        $Alerts->markRead($readAlerts);

        eval("\$content = \"".$templates->get('myalerts_page')."\";");
        output_page($content);
    }
}

if ($settings['myalerts_enabled'])
{
    $plugins->add_hook('xmlhttp', 'myalerts_xmlhttp');
}
function myalerts_xmlhttp()
{
    global $mybb, $db, $lang, $templates;

    require_once MYALERTS_PLUGIN_PATH.'Alerts.class.php';
    $Alerts = new Alerts($mybb, $db);

    if (!$lang->myalerts)
    {
        $lang->load('myalerts');
    }

	if ($mybb->input['action'] == 'getNewAlerts')
	{
		$newAlerts = $Alerts->getUnreadAlerts();

        if (!empty($newAlerts) AND is_array($newAlerts))
        {
            $alertsListing = '';
            $markRead = array();

            foreach ($newAlerts as $alert)
            {
                $alert['user'] = build_profile_link($alert['username'], $alert['uid']);
                $alert['dateline'] = my_date($mybb->settings['dateformat'], $alert['dateline'])." ".my_date($mybb->settings['timeformat'], $alert['dateline']);
                if (!is_array($alert['content']))
                {
                    $alert['content'] = unserialize($alert['content']);
                }

                if ($alert['type'] == 'rep' AND $mybb->settings['myalerts_alert_rep'])
                {
                    $alert['message'] = $lang->sprintf($lang->myalerts_rep, $alert['user'], $alert['dateline']);
                }
                elseif ($alert['type'] == 'pm' AND $mybb->settings['myalerts_alert_pm'])
                {
                    $alert['message'] = $lang->sprintf($lang->myalerts_pm, $alert['user'], "<a href=\"{$mybb->settings['bburl']}/private.php?action=read&amp;pmid=".(int) $alert['content']['pm_id']."\">".htmlspecialchars_uni($alert['content']['pm_title'])."</a>", $alert['dateline']);
                }
                elseif ($alert['type'] == 'buddylist' AND $mybb->settings['myalerts_alert_buddylist'])
                {
                    $alert['message'] = $lang->sprintf($lang->myalerts_buddylist, $alert['user'], $alert['dateline']);
                }
                elseif ($alert['type'] == 'quoted' AND $mybb->settings['myalerts_alert_quoted'])
                {
                    $alert['postLink'] = $mybb->settings['bburl'].'/'.get_post_link($alert['content']['pid'], $alert['content']['tid']).'#pid'.$alert['content']['pid'];
                    $alert['message'] = $lang->sprintf($lang->myalerts_quoted, $alert['user'], $alert['postLink'], $alert['dateline']);
                }
                elseif ($alert['type'] == 'post_threadauthor' AND $mybb->settings['myalerts_alert_post_threadauthor'])
                {
                    $alert['threadLink'] = $mybb->settings['bburl'].'/'.get_thread_link($alert['content']['tid'], 0, 'newpost');
                    $alert['message'] = $lang->sprintf($lang->myalerts_post_threadauthor, $alert['user'], $alert['threadLink'], htmlspecialchars_uni($alert['content']['t_subject']), $alert['dateline']);
                }

                $alertinfo = $alert['message'];

                if ($mybb->input['from'] == 'header')
                {
                    eval("\$alertsListing .= \"".$templates->get('myalerts_alert_row_popup')."\";");
                }
                else
                {
                    eval("\$alertsListing .= \"".$templates->get('myalerts_alert_row')."\";");
                }

                $markRead[] = $alert['id'];
            }

            $Alerts->markRead($markRead);
        }
        else
        {
            if ($mybb->input['from'] == 'header')
            {
                $alertinfo = $lang->myalerts_no_alerts;

                eval("\$alertsListing = \"".$templates->get('myalerts_alert_row_popup')."\";");
            }
        }

		echo $alertsListing;
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

    if ($mybb->input['action'] == 'getNumUnreadAlerts')
    {
        echo $Alerts->getNumUnreadAlerts();
    }
}
?>
