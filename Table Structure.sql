CREATE TABLE `mybb_alert_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL DEFAULT '',
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `can_be_user_disabled` tinyint(4) NOT NULL DEFAULT '1',
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
