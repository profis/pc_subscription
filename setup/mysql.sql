CREATE TABLE IF NOT EXISTS `{prefix}plugin_pc_subscription` (
  `site` tinyint(3) unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(10) unsigned NOT NULL,
  `hash` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `site` (`site`,`email`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `{prefix}plugin_pc_subscription` ADD `ln` VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `site` ;

ALTER TABLE `{prefix}plugin_pc_subscription` DROP INDEX `site` ,
ADD UNIQUE `site` ( `site` , `email` , `ln` );

UPDATE {prefix}plugin_pc_subscription
SET ln = (SELECT ln FROM {prefix}languages WHERE site = {prefix}plugin_pc_subscription.site ORDER by nr LIMIT 1)
WHERE ln='';