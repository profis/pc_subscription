ALTER TABLE `{prefix}plugin_pc_subscription`
	ADD `ln` VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `site`;

ALTER TABLE `{prefix}plugin_pc_subscription`
	DROP INDEX `site`,
	ADD UNIQUE `site` (`site`, `email`, `ln`);

UPDATE {prefix}plugin_pc_subscription
	SET ln = (SELECT ln FROM {prefix}languages WHERE site = {prefix}plugin_pc_subscription.site ORDER by nr LIMIT 1)
	WHERE ln='';
