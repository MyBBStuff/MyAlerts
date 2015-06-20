CREATE TABLE `mybb_alert_types` (
  `id`                   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`                 VARCHAR(100)     NOT NULL DEFAULT '',
  `enabled`              TINYINT(4)       NOT NULL DEFAULT '1',
  `can_be_user_disabled` TINYINT(4)       NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `mybb_alerts` (
  `id`            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid`           INT(10) UNSIGNED NOT NULL,
  `unread`        TINYINT(4)       NOT NULL DEFAULT '1',
  `dateline`      DATETIME         NOT NULL,
  `alert_type_id` INT(10) UNSIGNED NOT NULL,
  `object_id`     INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `from_user_id`  INT(10) UNSIGNED          DEFAULT NULL,
  `forced`        INT(1)           NOT NULL DEFAULT '0',
  `extra_details` TEXT,
  PRIMARY KEY (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8;
