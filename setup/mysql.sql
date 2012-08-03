CREATE TABLE IF NOT EXISTS `{prefix}plugin_pc_subscription` (
  `site` tinyint(3) unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(10) unsigned NOT NULL,
  `hash` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `site` (`site`,`email`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;