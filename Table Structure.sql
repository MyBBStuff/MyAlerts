CREATE TABLE `mybb_alert_setting_values` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `setting_id` int(10) NOT NULL,
  `value` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `mybb_alert_settings` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `code` varchar(75) NOT NULL,
  `is_core` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `mybb_alert_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL DEFAULT '',
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `mybb_alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `unread` tinyint(4) NOT NULL DEFAULT '1',
  `dateline` datetime NOT NULL,
  `alert_type_id` int(10) unsigned NOT NULL,
  `object_id` int(10) unsigned NOT NULL DEFAULT '0',
  `from_user_id` int(10) unsigned DEFAULT NULL,
  `forced` int(1) NOT NULL DEFAULT '0',
  `extra_details` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
