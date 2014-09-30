CREATE TABLE {db}."{prefix}plugin_pc_subscription" (
"site" int2 NOT NULL,
"ln" varchar(2) NOT NULL,
"email" varchar(255) NOT NULL,
"date" int4 NOT NULL,
"hash" varchar(32) NOT NULL,
"domain" varchar(255) NOT NULL
)
WITH (OIDS=FALSE);

COMMENT ON COLUMN {db}."{prefix}_plugin_pc_subscription"."site" IS 'Site for which this subscriber belongs';
COMMENT ON COLUMN {db}."{prefix}_plugin_pc_subscription"."email" IS 'Email address of the subcriber';
COMMENT ON COLUMN {db}."{prefix}_plugin_pc_subscription"."date" IS 'Date when subscription was made';
COMMENT ON COLUMN {db}."{prefix}_plugin_pc_subscription"."hash" IS 'Hash of the subscriber (hash is calculated by using "salt" configured in system and email combination then encoded with md5)';
COMMENT ON COLUMN {db}."{prefix}_plugin_pc_subscription"."domain" IS 'Site domain in which this subscription was made (required for creating "Unsubscribe" links in sent newsletters)';

INSERT INTO {db}."{prefix}db_version" ("plugin", "version") VALUES ('pc_subscription', '1.2.2');
