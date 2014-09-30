ALTER TABLE {db}."{prefix}plugin_pc_subscription"
	ADD COLUMN "ln" VARCHAR(2) NOT NULL;

UPDATE {db}."{prefix}plugin_pc_subscription"
	SET "ln" = (SELECT "ln" FROM {db}."{prefix}languages" WHERE "site" = {db}."{prefix}plugin_pc_subscription"."site" ORDER by "nr" LIMIT 1)
	WHERE "ln"='';
