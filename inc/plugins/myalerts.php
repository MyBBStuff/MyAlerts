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

    if (!$lang->myalerts)
    {
        $lang->load('myalerts');
    }

    if(!file_exists(PLUGINLIBRARY))
    {
        flash_message($lang->myalerts_pluginlibrary_missing, "error");
        admin_redirect("index.php?module=config-plugins");
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
                                <th class="thead" colspan="1">
                                    <strong>{$lang->myalerts_page_title}</strong>
                                    <div class="float_right">
                                        <a id="getUnreadAlerts" href="{$mybb->settings[\'bburl\']}/usercp.php?action=alerts">{$lang->myalerts_page_getnew}</a>
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
                </td>
            </tr>
        </table>
        {$footer}
    </body>
    </html>',
            'alert_row' =>  '<div class="alert_row">
    {$alertinfo}
</div>',
            'alert_row_popup' =>  '<div class="popup_item_container">
    <span class="popup_item">{$alertinfo}</span>
</div>',
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
</tbody>',
        )
    );

    //  Add our stylesheet to make our alerts notice look nicer. Making use of CSS3 gradients here because I'm lazy. based on the default theme's colours

    $stylesheet = '.unreadAlerts {
    -webkit-border-radius: 4em;
    -moz-border-radius: 4em;
    border-radius: 4em;
    color: #ffffff !important;
    text-shadow: 1px 1px 0 rgb(0,29,47);
    border: 1px solid rgb(0,29,47);
    width: 2em;
    height: 2em;
    line-height:2em;
    vertical-align:middle;
    text-align: center;
    font-size: 11px;
    font-weight: bold;
    display: inline-block;
    background:#026CB1 url(images/thead_bg.gif) top left repeat-x;
    background:-webkit-linear-gradient(top, rgb(2,108,177) 0%,rgb(3,84,136) 100%);
    background:-moz-linear-gradient(top, rgb(2,108,177) 0%,rgb(3,84,136) 100%);
    background:-o-linear-gradient(top, rgb(2,108,177) 0%,rgb(3,84,136) 100%);
    background:-ms-linear-gradient(top, rgb(2,108,177) 0%,rgb(3,84,136) 100%);
    background:linear-gradient(top, rgb(2,108,177) 0%,rgb(3,84,136) 100%);
    box-shadow:inset 0 0 0 1px rgba(255, 255, 255, 0.3);
    margin:5px;
    text-decoration:none;
}
    .unreadAlerts:hover,.unreadAlerts:active{
        text-decoration:none;
    }';

    $insertArray = array(
        'name'          => 'Alerts.css',
        'tid'           => '1',
        'stylesheet'    => $db->escape_string($stylesheet),
        'cachefile'     => 'Alerts.css',
        'lastmodified'  => TIME_NOW
    );

    require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';

    $sid = $db->insert_query('themestylesheets', $insertArray);

    if(!cache_stylesheet($theme['tid'], 'Alerts.css', $stylesheet))
    {
        $db->update_query('themestylesheets', array('cachefile' => "css.php?stylesheet={$sid}"), "sid='{$sid}'", 1);
    }

    $query = $db->simple_select('themes', 'tid');
    while($theme = $db->fetch_array($query))
    {
        update_theme_stylesheet_list($theme['tid']);
    }

    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
    // Add our JS. We need jQuery and myalerts.js. For jQuery, we check it hasn't already been loaded then load 1.7.2 from google's CDN
    find_replace_templatesets('headerinclude', "#".preg_quote('{$stylesheets}')."#i", '<script type="text/javascript">
if (typeof jQuery == \'undefined\')
{
    document.write(unescape("%3Cscript src=\'http://code.jquery.com/jquery-1.7.2.min.js\' type=\'text/javascript\'%3E%3C/script%3E"));
}
</script>
<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/myalerts.js"></script>'."\n".'{$stylesheets}');
    find_replace_templatesets('header_welcomeblock_member', "#".preg_quote('{$admincplink}')."#i", '{$admincplink}'."\n".'<a href="{$mybb->settings[\'bburl\']}/usercp.php?action=alerts" class="unreadAlerts" id="unreadAlerts_menu">{$mybb->user[\'unreadAlerts\']}</a>
<div id="unreadAlerts_menu_popup" class="popup_menu" style="display: none;">
    <span class="popup_item">{$lang->myalerts_loading}</span>
</div>
<script type="text/javascript">
// <!--
if(use_xmlhttprequest == "1")
{
new PopupMenu("unreadAlerts_menu");
}
// -->
</script>'."\n");

    // Helpdocs
    $helpsection = $db->insert_query('helpsections', array(
        'name'              =>  $lang->myalerts_helpsection_name,
        'description'       =>  $lang->myalerts_helpsection_desc,
        'usetranslation'    =>  1,
        'enabled'           =>  1,
        'disporder'         =>  3,
        ));

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
        );

    foreach ($helpDocuments as $document)
    {
        $db->insert_query('helpdocs', $document);
    }
}

function myalerts_deactivate()
{
    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets('headerinclude', "#".preg_quote('<script type="text/javascript">
if (typeof jQuery == \'undefined\')
{
    document.write(unescape("%3Cscript src=\'http://code.jquery.com/jquery-1.7.2.min.js\' type=\'text/javascript\'%3E%3C/script%3E"));
}
</script>
<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/myalerts.js"></script>'."\n")."#i", '');
    find_replace_templatesets('header_welcomeblock_member', "#".preg_quote("\n".'<a href="{$mybb->settings[\'bburl\']}/usercp.php?action=alerts" class="unreadAlerts" id="unreadAlerts_menu">{$mybb->user[\'unreadAlerts\']}</a>
<div id="unreadAlerts_menu_popup" class="popup_menu" style="display: none;">
    <span class="popup_item">{$lang->myalerts_loading}</span>
</div>
<script type="text/javascript">
// <!--
if(use_xmlhttprequest == "1")
{
new PopupMenu("unreadAlerts_menu");
}
// -->
</script>'."\n")."#i", '');
}

global $settings;

if ($settings['myalerts_enabled'])
{
    $plugins->add_hook('global_start', 'myalerts_global');
}
function myalerts_global()
{
    global $mybb, $templatelist;

    if (THIS_SCRIPT == 'usercp.php')
    {
        $templatelist .= ',myalerts_usercp_nav';
    }

    if (THIS_SCRIPT == 'usercp.php' AND $mybb->input['action'] == 'alerts')
    {
        $templatelist .= ',myalerts_page,myalerts_alert_row,multipage_page_current,multipage_page,multipage_nextpage,multipage';
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

    if ($plugin_array['user_activity']['activity'] == 'usercp' AND my_strpos($plugin_array['user_activity']['location'], 'alerts'))
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
    $plugins->add_hook('usercp_menu', 'myalerts_usercp_menu', 20);
}
function myalerts_usercp_menu()
{
    global $mybb, $templates, $theme, $usercpmenu, $lang, $collapsed, $collapsedimg;

    if (!$lang->myalerts)
    {
        $lang->load('myalerts');
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

        add_breadcrumb('Alerts', 'usercp.php?action=alerts');

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

                $plugins->run_hooks('myalerts_page_output_start');

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

                $plugins->run_hooks('myalerts_page_output_end');

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
    global $mybb, $db, $lang, $templates, $plugins;

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

                $plugins->run_hooks('myalerts_xmlhttp_output_start');

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

                $plugins->run_hooks('myalerts_xmlhttp_output_end');

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
