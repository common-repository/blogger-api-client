-- table for Blogger API Client plugin
-- Blogger API Client, version 0.1 and up
CREATE TABLE bac_wp_post_map (
	ID bigint(20) unsigned NOT NULL auto_increment,
	post_ID bigint(20) unsigned NOT NULL,
	blogger_ID text NOT NULL,
	PRIMARY KEY (ID)
);
