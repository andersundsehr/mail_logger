#
# Table structure for table 'tx_maillogger_domain_model_mailtemplate'
#
CREATE TABLE tx_maillogger_domain_model_mailtemplate (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	typo_script_key varchar(255) DEFAULT '' NOT NULL,
	dkim_key varchar(255) DEFAULT '' NOT NULL,
	template_path_key varchar(255) DEFAULT '' NOT NULL,
	title varchar(255) DEFAULT '' NOT NULL,
	subject varchar(255) DEFAULT '' NOT NULL,
	message longtext NOT NULL,
	mail_from_name varchar(500) DEFAULT '' NOT NULL,
	mail_from_address varchar(500) DEFAULT '' NOT NULL,
	mail_to_names varchar(500) DEFAULT '' NOT NULL,
	mail_to_addresses varchar(500) DEFAULT '' NOT NULL,
	mail_copy_addresses varchar(500) DEFAULT '' NOT NULL,
	mail_blind_copy_addresses varchar(500) DEFAULT '' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,

	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumblob,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (l10n_parent,sys_language_uid)
);

#
# Table structure for table 'tx_maillogger_domain_model_maillog'
#
CREATE TABLE tx_maillogger_domain_model_maillog (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	typo_script_key varchar(255) DEFAULT '' NOT NULL,
	subject varchar(255) DEFAULT '' NOT NULL,
	message mediumtext NOT NULL,
	mail_from varchar(500) DEFAULT '' NOT NULL,
	mail_to varchar(500) DEFAULT '' NOT NULL,
	mail_copy varchar(500) DEFAULT '' NOT NULL,
	mail_blind_copy varchar(500) DEFAULT '' NOT NULL,
	result varchar(500) DEFAULT '' NOT NULL,
	debug text NOT NULL,
	status int(11) unsigned DEFAULT '0' NOT NULL,
	headers text NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
