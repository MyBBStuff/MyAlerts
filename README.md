MyAlerts: Alerts System for MyBB
==========================

Current Version: 1.04

Dependencies: [Plugin Library](http://mods.mybb.com/view/pluginlibrary)

Creator: [Euan T.](http://www.euantor.com)

General:
-----------
MyAlerts is a plugin for MyBB created and maintained by [Euan T.](http://www.euantor.com). It is a system that provides visual alerts for events in a forum on a user-by-user basis.

jQuery or Prototype:
-----------
As we all know, MyBB ships with Prototype as it's core JS framework (unless you've somehow got hold of a 1.8 build...). I personally prefer to use jQuery and as such it's the default library of choice for MyAlerts. I understand, however, that a few people don't want to load jQuery too (looking at you Leefish). As such, I've included a Prototype version of the MyAlerts JS. To make use of prototype, edit the headerinclude and remove any mentions of MyAlerts then add the following below your other JavaScript inclusions:

	<script type="text/javascript" src="{$mybb->settings['bburl']}/jscripts/scriptaculous.js?load=effects"></script>
	<script type="text/javascript" src="{$mybb->settings['bburl']}/jscripts/myalerts.prototype.js"></script>

Events:
----------
The currently supported events are:

+  When you're quoted in a post
+  When somebody replies to a thread you started
+  When somebody adds you to their buddy list
+  When somebody PMs you

More events are currently in development or planned to be added in the near future. If you have suggestions for events to be added to the core, post them in an issue here on GitHub.

Extensibility:
----------------
MyAlerts is extensible. This means that any external MyBB plugin can create alerts and add them to a user's feed. Compatible plugins can be found on the wiki, [here](https://github.com/euantor/MyAlerts/wiki/Compatible-Plugins)

Contributing, questions, and more:
----------------------------------------------
If you'd like to contribute to MyAlerts, feel free to submit pull requests for features you feel would improve the plugin. If you have an idea or find a bug, we'd love if you'd create an issue so we can all discuss it. It'd also be great if you helped document MyAlerts on the GitHub Wiki by writing about the uses of certain features, creating lists of useful information, or expanding on other's writings.