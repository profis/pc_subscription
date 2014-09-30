CREATE TABLE IF NOT EXISTS `{prefix}plugin_pc_subscription` (
  `site` tinyint(3) unsigned NOT NULL,
  `ln` VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(10) unsigned NOT NULL,
  `hash` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `site` (`site`, `email`, `ln`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT IGNORE INTO `{prefix}db_version` (`plugin`, `version`) VALUES ('pc_subscription', '1.2.2');
