MyAlerts: Alerts System for MyBB
==========================

Current Version: 2.0.4

Dependencies: [Plugin Library](http://mods.mybb.com/view/pluginlibrary), [Plugins.Core](https://github.com/MyBBStuff/Plugins.Core)

Creator: [Euan T.](http://www.euantor.com)

General:
-----------
MyAlerts is a plugin for MyBB created and maintained by [Euan T.](http://www.euantor.com). It is a system that provides visual alerts for events in a forum on a user-by-user basis.

Events:
----------
The currently supported events are:

+  When you're quoted in a post
+  When somebody replies to a thread you started
+  When somebody adds you to their buddy list
+  When somebody PMs you
+  When somebody replies to a thread you are subscribed to
+  When somebody votes in a public poll
+  When somebody rates your thread
+  When somebody changes your reputation

More events are currently in development or planned to be added in the near future. If you have suggestions for events to be added to the core, post them in an issue here on GitHub.

Extensibility:
----------------
MyAlerts is extensible. This means that any external MyBB plugin can create alerts and add them to a user's feed. Compatible plugins can be found on the wiki, [here](https://github.com/euantor/MyAlerts/wiki/Compatible-Plugins)

Contributing, questions, and more:
----------------------------------------------
If you'd like to contribute to MyAlerts, feel free to submit pull requests for features you feel would improve the plugin. If you have an idea or find a bug, we'd love if you'd create an issue so we can all discuss it. It'd also be great if you helped document MyAlerts on the GitHub Wiki by writing about the uses of certain features, creating lists of useful information, or expanding on other's writings.

FAQs:
----------------------------------------------
+  How to change text color of "Alerts" link in a header template if there is a new alert?

Go to your ACP -> Templates & Styles -> Themes -> Your theme -> alerts.css -> find `.alerts--new` class and change the color to whatever you want, you can also customize it.

A note to developers of client plugins:
---------------------------------------
Versions of MyAlerts above 2.0.4 introduce a new hook, `myalerts_register_client_alert_formatters`, which MyAlerts uses to register client plugin alert formatters on demand. Client plugin developers are encouraged to switch to this hook but retain backwards compatibility with older versions of MyAlerts by adhering to the following template code in (or included from) their main plugin file, which incorporates conservative safety-checking and redundancy:

```php
// Backwards-compatible alert formatter registration hook-ins.
$plugins->add_hook('global_start'                             , 'yourcoolplugin_register_myalerts_formatter_back_compat');
$plugins->add_hook('xmlhttp'                                  , 'yourcoolplugin_register_myalerts_formatter_back_compat', -2/* Prioritised one higher (more negative) than the MyAlerts hook into xmlhttp */);

// Current and forward-compatible alert formatter registration hook-in.
$plugins->add_hook('myalerts_register_client_alert_formatters', 'yourcoolplugin_register_myalerts_formatter');

// Backwards-compatible alert formatter registration.
function yourcoolplugin_register_myalerts_formatter_back_compat()
{
	if (function_exists('myalerts_info')) {
		$myalerts_info = myalerts_info();
		if (version_compare($myalerts_info['version'], '2.0.4') <= 0) {
			yourcoolplugin_register_myalerts_formatter();
		}
	}
}

// Alert formatter registration.
function yourcoolplugin_register_myalerts_formatter()
{
	global $mybb, $lang;

	if (class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter') &&
	    class_exists('MybbStuff_MyAlerts_AlertFormatterManager') &&
	    !class_exists('YourCoolPluginAlertFormatter')
	) {
		class YourCoolPluginAlertFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
		{
			/* Define your formatter class here */
		}

		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
		if (!$formatterManager) {
		        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}
		if ($formatterManager) {
			$formatterManager->registerFormatter(new YourCoolPluginAlertFormatter($mybb, $lang, 'yourcoolalertcode'));
		}
	}
}
```
