<?php
$l['myalerts'] = "MyAlerts";
$l['myalerts_pluginlibrary_missing'] = "The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.";

$l['setting_group_myalerts'] = "MyAlerts Settings";
$l['setting_group_myalerts_desc'] = "Settings for the MyAlerts plugin";
$l['setting_myalerts_perpage'] = "Alerts per page";
$l['setting_myalerts_perpage_desc'] = "How many alerts do you wish to display on the alerts listing page? (default is 10)";
$l['setting_myalerts_dropdown_limit'] = "Number of alerts to show in dropdown";
$l['setting_myalerts_dropdown_limit_desc'] = "How many alerts do you wish to display in the global alerts dropdown? (default is 5)";
$l['setting_myalerts_headericon'] = "Alert notification visibility mode (header)";
$l['setting_myalerts_headericon_desc'] = "Display the alert notification in a forum header only if an unread alert is available, otherwise the text will be visible all the time (default is OFF)";
$l['setting_myalerts_autorefresh'] = "MyAlerts page AJAX autorefresh";
$l['setting_myalerts_autorefresh_desc'] = "How often (in seconds) do you wish the MyAlerts page in User control panel to refresh the alerts listing via AJAX? (0 for no autorefresh)";
$l['setting_myalerts_avatar_size'] = "Avatar Dimensions";
$l['setting_myalerts_avatar_size_desc'] = "The dimensions to use when displaying avatars in alert listings. (In the form width|height. Example: 64|64.)";
$l['setting_myalerts_bc_mode'] = "Backwards compatibility mode";
$l['setting_myalerts_bc_mode_desc'] = "To support client plugins which do not yet register their alerts formatters via this plugin's `myalerts_register_client_alert_formatters` hook. Turning this mode on will resolve the problem of empty alerts rows in the modal dialogue for some client alert types after clicking, for example, 'Mark All Read'.";

$l['myalerts_task_cleanup_ran'] = 'Read alerts over a week old were deleted successfully!';
$l['myalerts_task_cleanup_error'] = 'Something went wrong while cleaning up the alerts...';

$l['myalerts_task_title'] = 'MyAlerts Cleanup';
$l['myalerts_task_description'] = 'A task to cleanup old read alerts. This is required as otherwise the alerts table could swell to massive sizes.';

$l['myalerts_alert_types'] = 'Alert Types';
$l['myalerts_can_manage_alert_types'] = 'Can manage alert types?';

$l['myalerts_alert_type_code'] = 'Code';
$l['myalerts_alert_type_enabled'] = 'Enabled?';
$l['myalerts_alert_type_can_be_user_disabled'] = 'Can be disabled by users?';
$l['myalerts_no_alert_types'] = 'No alert types found!';
$l['myalerts_update_alert_types'] = 'Update Alert Types';
$l['myalerts_alert_types_updated'] = 'Alert types updated!';

$l['myalerts_upgraded'] = 'MyAlerts has been upgraded. All old user alert settings have been lost - make sure you warn your users!';
