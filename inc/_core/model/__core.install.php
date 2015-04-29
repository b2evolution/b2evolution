<?php
/**
 * This is the install file for the core modules
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


global $db_storage_charset;
// fp> TODO: upgrade procedure should check for proper charset. (and for ENGINE too)
// fp> TODO: we should actually use a DEFAULT COLLATE, maybe have a DD::php_to_mysql_collate( $php_charset ) -> returning a Mysql collation


/**
 * The b2evo database scheme.
 *
 * This gets updated through {@link db_delta()} which generates the queries needed to get
 * to this scheme.
 *
 * Please see {@link db_delta()} for things to take care of.
 */
$schema_queries = array(
	'T_groups' => array(
		'Creating table for Groups',
		"CREATE TABLE T_groups (
			grp_ID                           int(11) NOT NULL auto_increment,
			grp_name                         varchar(50) NOT NULL default '',
			grp_level                        int unsigned DEFAULT 0 NOT NULL,
			grp_perm_blogs                   enum('user','viewall','editall') COLLATE ascii_general_ci NOT NULL default 'user',
			grp_perm_bypass_antispam         TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtmlvalidation         VARCHAR(10) COLLATE ascii_general_ci NOT NULL default 'always',
			grp_perm_xhtmlvalidation_xmlrpc  VARCHAR(10) COLLATE ascii_general_ci NOT NULL default 'always',
			grp_perm_xhtml_css_tweaks        TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtml_iframes           TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtml_javascript        TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtml_objects           TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_stats                   enum('none','user','view','edit') COLLATE ascii_general_ci NOT NULL default 'none',
			PRIMARY KEY grp_ID (grp_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_groups__groupsettings' => array(
		'Creating table for Group Settings',
		"CREATE TABLE T_groups__groupsettings (
			gset_grp_ID INT(11) UNSIGNED NOT NULL,
			gset_name VARCHAR(30) COLLATE ascii_general_ci NOT NULL,
			gset_value VARCHAR(255) NULL,
			PRIMARY KEY (gset_grp_ID, gset_name)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_settings' => array(
		'Creating table for Settings',
		"CREATE TABLE T_settings (
			set_name VARCHAR(30) COLLATE ascii_general_ci NOT NULL,
			set_value VARCHAR(5000) NULL,
			PRIMARY KEY ( set_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_global__cache' => array(
		'Creating table for Caches',
		"CREATE TABLE T_global__cache (
			cach_name VARCHAR(30) COLLATE ascii_general_ci NOT NULL,
			cach_cache MEDIUMBLOB NULL,
			PRIMARY KEY ( cach_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users' => array(
		'Creating table for Users',
		"CREATE TABLE T_users (
			user_ID int(11) unsigned NOT NULL auto_increment,
			user_login varchar(20) NOT NULL,
			user_pass BINARY(16) NOT NULL,
			user_salt CHAR(8) NOT NULL default '',
			user_grp_ID int(4) NOT NULL default 1,
			user_email varchar(255) COLLATE ascii_general_ci NOT NULL,
			user_status enum( 'activated', 'autoactivated', 'closed', 'deactivated', 'emailchanged', 'failedactivation', 'new' ) COLLATE ascii_general_ci NOT NULL default 'new',
			user_avatar_file_ID int(10) unsigned default NULL,
			user_firstname varchar(50) NULL,
			user_lastname varchar(50) NULL,
			user_nickname varchar(50) NULL,
			user_url varchar(255) NULL,
			user_level int unsigned DEFAULT 0 NOT NULL,
			user_locale varchar(20) DEFAULT 'en-EU' NOT NULL,
			user_unsubscribe_key CHAR(32) COLLATE ascii_general_ci NOT NULL default '' COMMENT 'A specific key, it is used when a user wants to unsubscribe from a post comments without signing in',
			user_gender char(1) COLLATE ascii_general_ci NULL,
			user_age_min int unsigned NULL,
			user_age_max int unsigned NULL,
			user_reg_ctry_ID int(10) unsigned NULL,
			user_ctry_ID int(10) unsigned NULL,
			user_rgn_ID int(10) unsigned NULL,
			user_subrg_ID int(10) unsigned NULL,
			user_city_ID int(10) unsigned NULL,
			user_source varchar(30) NULL,
			user_created_datetime datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
			user_lastseen_ts timestamp NULL,
			user_email_dom_ID int(10) unsigned NULL,
			user_profileupdate_date date NOT NULL DEFAULT '2000-01-01',
			PRIMARY KEY user_ID (user_ID),
			UNIQUE user_login (user_login),
			KEY user_grp_ID (user_grp_ID),
			INDEX user_email ( user_email )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__fielddefs' => array(
		'Creating table for User field definitions',
		"CREATE TABLE T_users__fielddefs (
			ufdf_ID int(10) unsigned NOT NULL auto_increment,
			ufdf_ufgp_ID    int(10) unsigned NOT NULL,
			ufdf_type       char(8) COLLATE ascii_general_ci NOT NULL,
			ufdf_name       varchar(255) NOT NULL,
			ufdf_options    VARCHAR(255) NULL DEFAULT NULL,"/* Do NOT change this field back to TEXT without a very good reason. */."
			ufdf_required   enum('hidden','optional','recommended','require') COLLATE ascii_general_ci NOT NULL default 'optional',
			ufdf_duplicated enum('forbidden','allowed','list') COLLATE ascii_general_ci NOT NULL default 'allowed',
			ufdf_order      int(11) NOT NULL,
			ufdf_suggest    tinyint(1) NOT NULL DEFAULT 0,
			ufdf_bubbletip  varchar(2000) NULL,
			ufdf_icon_name  varchar(100) COLLATE ascii_general_ci NULL,
			ufdf_code       varchar(20) COLLATE ascii_general_ci UNIQUE NOT NULL,
			PRIMARY KEY (ufdf_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__fieldgroups' => array(
		'Creating table for Groups of user field definitions',
		"CREATE TABLE T_users__fieldgroups (
			ufgp_ID int(10) unsigned NOT NULL auto_increment,
			ufgp_name varchar(255) NOT NULL,
			ufgp_order int(11) NOT NULL,
			PRIMARY KEY (ufgp_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__fields' => array(
		'Creating table for User fields',
		"CREATE TABLE T_users__fields (
			uf_ID      int(10) unsigned NOT NULL auto_increment,
			uf_user_ID int(10) unsigned NOT NULL,
			uf_ufdf_ID int(10) unsigned NOT NULL,
			uf_varchar varchar(10000) NOT NULL,
			PRIMARY KEY (uf_ID),
			INDEX uf_ufdf_ID ( uf_ufdf_ID ),
			INDEX uf_varchar ( uf_varchar (255) )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__reports' => array(
		'Creating table for User reports',
		"CREATE TABLE T_users__reports (
			urep_target_user_ID int(11) unsigned NOT NULL,
			urep_reporter_ID    int(11) unsigned NOT NULL,
			urep_status         enum( 'fake', 'guidelines', 'harass', 'spam', 'other' ) COLLATE ascii_general_ci,
			urep_info           varchar(240),
			urep_datetime		datetime NOT NULL,
			PRIMARY KEY ( urep_target_user_ID, urep_reporter_ID )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__postreadstatus' => array(
		'Creating table for User post read status',
		"CREATE TABLE T_users__postreadstatus (
			uprs_user_ID int(11) unsigned NOT NULL,
			uprs_post_ID int(11) unsigned NOT NULL,
			uprs_read_post_ts TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
			uprs_read_comment_ts TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
			PRIMARY KEY ( uprs_user_ID, uprs_post_ID )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__invitation_code' => array(
		'Creating table for User invitation codes',
		"CREATE TABLE T_users__invitation_code (
			ivc_ID        int(11) unsigned NOT NULL auto_increment,
			ivc_code      varchar(32) COLLATE ascii_general_ci NOT NULL,
			ivc_expire_ts TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
			ivc_source    varchar(30) NULL,
			ivc_grp_ID    int(4) NOT NULL,
			PRIMARY KEY ( ivc_ID ),
			UNIQUE ivc_code ( ivc_code )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__organization' => array(
		'Creating table for User organizations',
		"CREATE TABLE T_users__organization (
			org_ID   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			org_name VARCHAR(255) NOT NULL,
			org_url  VARCHAR(2000) NULL,
			PRIMARY KEY ( org_ID ),
			UNIQUE org_name ( org_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__user_org' => array(
		'Creating table for relations users with organizations',
		"CREATE TABLE T_users__user_org (
			uorg_user_ID  INT(11) UNSIGNED NOT NULL,
			uorg_org_ID   INT(11) UNSIGNED NOT NULL,
			uorg_accepted TINYINT(1) DEFAULT 0,
			uorg_role     VARCHAR(255) NULL,
			PRIMARY KEY ( uorg_user_ID, uorg_org_ID )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_i18n_original_string' => array(
		'Creating table for a latest version of the POT file',
		"CREATE TABLE T_i18n_original_string (
			iost_ID        int(10) unsigned NOT NULL auto_increment,
			iost_string    varchar(10000) NOT NULL default '',
			iost_inpotfile tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (iost_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_i18n_translated_string' => array(
		'Creating table for a latest versions of the PO files',
		"CREATE TABLE T_i18n_translated_string (
			itst_ID       int(10) unsigned NOT NULL auto_increment,
			itst_iost_ID  int(10) unsigned NOT NULL,
			itst_locale   varchar(20) NOT NULL default '',
			itst_standard varchar(10000) NOT NULL default '',
			itst_custom   varchar(10000) NULL,
			itst_inpofile tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (itst_ID)
		) ENGINE = innodb DEFAULT CHARSET = utf8" ),

	'T_locales' => array(
		'Creating table for Locales',
		"CREATE TABLE T_locales (
			loc_locale varchar(20) NOT NULL default '',
			loc_datefmt varchar(20) COLLATE ascii_general_ci NOT NULL default 'y-m-d',
			loc_timefmt varchar(20) COLLATE ascii_general_ci NOT NULL default 'H:i:s',
			loc_shorttimefmt varchar(20) COLLATE ascii_general_ci NOT NULL default 'H:i',
			loc_startofweek TINYINT UNSIGNED NOT NULL DEFAULT 1,
			loc_name varchar(40) NOT NULL default '',
			loc_messages varchar(20) NOT NULL default '',
			loc_priority tinyint(4) UNSIGNED NOT NULL default '0',
			loc_transliteration_map varchar(10000) NOT NULL default '',
			loc_enabled tinyint(4) NOT NULL default '1',
			PRIMARY KEY loc_locale( loc_locale )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset COMMENT='saves available locales'
		" ),

	'T_antispam' => array(
		'Creating table for Antispam Blacklist',
		"CREATE TABLE T_antispam (
			aspm_ID bigint(11) NOT NULL auto_increment,
			aspm_string varchar(80) NOT NULL,
			aspm_source enum( 'local','reported','central' ) COLLATE ascii_general_ci NOT NULL default 'reported',
			PRIMARY KEY aspm_ID (aspm_ID),
			UNIQUE aspm_string (aspm_string)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_antispam__iprange' => array(
		'Creating table for Antispam IP Ranges',
		"CREATE TABLE T_antispam__iprange (
			aipr_ID                  int(10) unsigned NOT NULL auto_increment,
			aipr_IPv4start           int(10) unsigned NOT NULL,
			aipr_IPv4end             int(10) unsigned NOT NULL,
			aipr_user_count          int(10) unsigned DEFAULT 0,
			aipr_contact_email_count int(10) unsigned DEFAULT 0,
			aipr_status              enum( 'trusted', 'suspect', 'blocked' ) COLLATE ascii_general_ci NULL DEFAULT NULL,
			aipr_block_count         int(10) unsigned DEFAULT 0,
			PRIMARY KEY aipr_ID (aipr_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__usersettings' => array(
		'Creating user settings table',
		"CREATE TABLE T_users__usersettings (
			uset_user_ID INT(11) UNSIGNED NOT NULL,
			uset_name    VARCHAR( 30 ) COLLATE ascii_general_ci NOT NULL,
			uset_value   VARCHAR( 255 ) NULL,
			PRIMARY KEY ( uset_user_ID, uset_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_plugins' => array(
		'Creating plugins table',
		"CREATE TABLE T_plugins (
			plug_ID              INT(11) UNSIGNED NOT NULL auto_increment,
			plug_priority        TINYINT NOT NULL default 50,
			plug_classname       VARCHAR(40) COLLATE ascii_general_ci NOT NULL default '',
			plug_code            VARCHAR(32) COLLATE ascii_general_ci NULL,
			plug_version         VARCHAR(42) COLLATE ascii_general_ci NOT NULL default '0',
			plug_name            VARCHAR(255) NULL default NULL,
			plug_shortdesc       VARCHAR(255) NULL default NULL,
			plug_status          ENUM( 'enabled', 'disabled', 'needs_config', 'broken' ) COLLATE ascii_general_ci NOT NULL,
			plug_spam_weight     TINYINT UNSIGNED NOT NULL DEFAULT 1,
			PRIMARY KEY ( plug_ID ),
			UNIQUE plug_code( plug_code ),
			INDEX plug_status( plug_status )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_pluginsettings' => array(
		'Creating plugin settings table',
		"CREATE TABLE T_pluginsettings (
			pset_plug_ID INT(11) UNSIGNED NOT NULL,
			pset_name VARCHAR( 30 ) COLLATE ascii_general_ci NOT NULL,
			pset_value TEXT NULL,
			PRIMARY KEY ( pset_plug_ID, pset_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_pluginusersettings' => array(
		'Creating plugin user settings table',
		"CREATE TABLE T_pluginusersettings (
			puset_plug_ID INT(11) UNSIGNED NOT NULL,
			puset_user_ID INT(11) UNSIGNED NOT NULL,
			puset_name VARCHAR( 30 ) COLLATE ascii_general_ci NOT NULL,
			puset_value TEXT NULL,
			PRIMARY KEY ( puset_plug_ID, puset_user_ID, puset_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_pluginevents' => array(
		'Creating plugin events table',
		"CREATE TABLE T_pluginevents(
			pevt_plug_ID INT(11) UNSIGNED NOT NULL,
			pevt_event VARCHAR(40) COLLATE ascii_general_ci NOT NULL,
			pevt_enabled TINYINT NOT NULL DEFAULT 1,
			PRIMARY KEY( pevt_plug_ID, pevt_event )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_cron__task' => array(
		'Creating cron tasks table',
		"CREATE TABLE T_cron__task(
			ctsk_ID              int(10) unsigned not null AUTO_INCREMENT,
			ctsk_start_datetime  datetime not null DEFAULT '2000-01-01 00:00:00',
			ctsk_repeat_after    int(10) unsigned,
			ctsk_key             varchar(50) COLLATE ascii_general_ci not null,
			ctsk_name            varchar(255) null COMMENT 'Specific name of this task. This value is set only if this job name was modified by an admin user',
			ctsk_params          varchar(255),
			PRIMARY KEY (ctsk_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_cron__log' => array(
		'Creating cron logs table',
		"CREATE TABLE T_cron__log(
			clog_ctsk_ID              int(10) unsigned   not null,
			clog_realstart_datetime   datetime           not null DEFAULT '2000-01-01 00:00:00',
			clog_realstop_datetime    datetime,
			clog_status               enum('started','finished','error','timeout') COLLATE ascii_general_ci not null default 'started',
			clog_messages             text,
			PRIMARY KEY (clog_ctsk_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_regional__country' => array(
		'Creating Countries table',
		"CREATE TABLE T_regional__country (
			ctry_ID          int(10) unsigned NOT NULL auto_increment,
			ctry_code        char(2) COLLATE ascii_general_ci NOT NULL,
			ctry_name        varchar(40) NOT NULL,
			ctry_curr_ID     int(10) unsigned NULL,
			ctry_enabled     tinyint(1) NOT NULL DEFAULT 1,
			ctry_preferred   tinyint(1) NOT NULL DEFAULT 0,
			ctry_status      enum( 'trusted', 'suspect', 'blocked' ) COLLATE ascii_general_ci NULL DEFAULT NULL,
			ctry_block_count int(10) unsigned DEFAULT 0,
			PRIMARY KEY ctry_ID (ctry_ID),
			UNIQUE ctry_code (ctry_code)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_regional__region' => array(
		'Creating Regions table',
		"CREATE TABLE T_regional__region (
			rgn_ID        int(10) unsigned NOT NULL auto_increment,
			rgn_ctry_ID   int(10) unsigned NOT NULL,
			rgn_code      char(6) COLLATE ascii_general_ci NOT NULL,
			rgn_name      varchar(40) NOT NULL,
			rgn_enabled   tinyint(1) NOT NULL DEFAULT 1,
			rgn_preferred tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY rgn_ID (rgn_ID),
			UNIQUE rgn_ctry_ID_code (rgn_ctry_ID, rgn_code)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_regional__subregion' => array(
		'Creating Sub-regions table',
		"CREATE TABLE T_regional__subregion (
			subrg_ID        int(10) unsigned NOT NULL auto_increment,
			subrg_rgn_ID    int(10) unsigned NOT NULL,
			subrg_code      char(6) COLLATE ascii_general_ci NOT NULL,
			subrg_name      varchar(40) NOT NULL,
			subrg_enabled   tinyint(1) NOT NULL DEFAULT 1,
			subrg_preferred tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY subrg_ID (subrg_ID),
			UNIQUE subrg_rgn_ID_code (subrg_rgn_ID, subrg_code)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_regional__city' => array(
		'Creating Cities table',
		"CREATE TABLE T_regional__city (
			city_ID         int(10) unsigned NOT NULL auto_increment,
			city_ctry_ID    int(10) unsigned NOT NULL,
			city_rgn_ID     int(10) unsigned NULL,
			city_subrg_ID   int(10) unsigned NULL,
			city_postcode   char(12) COLLATE ascii_general_ci NOT NULL,
			city_name       varchar(40) NOT NULL,
			city_enabled    tinyint(1) NOT NULL DEFAULT 1,
			city_preferred  tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY city_ID (city_ID),
			INDEX city_ctry_ID_postcode ( city_ctry_ID, city_postcode ),
			INDEX city_rgn_ID_postcode ( city_rgn_ID, city_postcode ),
			INDEX city_subrg_ID_postcode ( city_subrg_ID, city_postcode )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_regional__currency' => array(
		'Creating Currencies table',
		"CREATE TABLE T_regional__currency (
			curr_ID int(10) unsigned NOT NULL auto_increment,
			curr_code char(3) COLLATE ascii_general_ci NOT NULL,
			curr_shortcut varchar(30) NOT NULL,
			curr_name varchar(40) NOT NULL,
			curr_enabled tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY curr_ID (curr_ID),
			UNIQUE curr_code (curr_code)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_slug' => array(
		'Creating table for slugs',
		"CREATE TABLE T_slug (
			slug_ID int(10) unsigned NOT NULL auto_increment,
			slug_title varchar(255) COLLATE ascii_bin NOT NULL,
			slug_type	char(6) COLLATE ascii_bin NOT NULL DEFAULT 'item',
			slug_itm_ID	int(11) unsigned,
			PRIMARY KEY slug_ID (slug_ID),
			UNIQUE	slug_title (slug_title)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_email__log' => array(
		'Creating email log table',
		"CREATE TABLE T_email__log (
			emlog_ID        INT(10) UNSIGNED NOT NULL auto_increment,
			emlog_timestamp TIMESTAMP NOT NULL,
			emlog_user_ID   INT(10) UNSIGNED DEFAULT NULL,
			emlog_to        VARCHAR(255) COLLATE ascii_general_ci DEFAULT NULL,
			emlog_result    ENUM( 'ok', 'error', 'blocked' ) COLLATE ascii_general_ci NOT NULL DEFAULT 'ok',
			emlog_subject   VARCHAR(255) DEFAULT NULL,
			emlog_headers   TEXT DEFAULT NULL,
			emlog_message   TEXT DEFAULT NULL,
			PRIMARY KEY     (emlog_ID)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" ),

	'T_email__returns' => array(
		'Creating email returns table',
		"CREATE TABLE T_email__returns (
			emret_ID        INT(10) UNSIGNED NOT NULL auto_increment,
			emret_address   VARCHAR(255) COLLATE ascii_general_ci DEFAULT NULL,
			emret_errormsg  VARCHAR(255) DEFAULT NULL,
			emret_timestamp TIMESTAMP NOT NULL,
			emret_headers   TEXT DEFAULT NULL,
			emret_message   TEXT DEFAULT NULL,
			emret_errtype   CHAR(1) COLLATE ascii_general_ci NOT NULL DEFAULT 'U',
			PRIMARY KEY     (emret_ID)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" ),

	'T_email__address' => array(
		'Creating email addresses table',
		"CREATE TABLE T_email__address (
			emadr_ID                    INT(10) UNSIGNED NOT NULL auto_increment,
			emadr_address               VARCHAR(255) COLLATE ascii_general_ci DEFAULT NULL,
			emadr_status                ENUM( 'unknown', 'redemption', 'warning', 'suspicious1', 'suspicious2', 'suspicious3', 'prmerror', 'spammer' ) COLLATE ascii_general_ci NOT NULL DEFAULT 'unknown',
			emadr_sent_count            INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emadr_sent_last_returnerror INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emadr_prmerror_count        INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emadr_tmperror_count        INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emadr_spamerror_count       INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emadr_othererror_count      INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emadr_last_sent_ts          TIMESTAMP NULL,
			emadr_last_error_ts         TIMESTAMP NULL,
			PRIMARY KEY                 (emadr_ID),
			UNIQUE                      emadr_address (emadr_address)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" ),

	'T_email__campaign' => array(
		'Creating email campaigns table',
		"CREATE TABLE T_email__campaign (
			ecmp_ID          INT NOT NULL AUTO_INCREMENT,
			ecmp_date_ts     TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
			ecmp_name        VARCHAR(255) NOT NULL,
			ecmp_email_title VARCHAR(255) NULL,
			ecmp_email_html  TEXT NULL,
			ecmp_email_text  TEXT NULL,
			ecmp_sent_ts     TIMESTAMP NULL,
			PRIMARY KEY      (ecmp_ID)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" ),

	'T_email__campaign_send' => array(
		'Creating email campaign send data table',
		"CREATE TABLE T_email__campaign_send (
			csnd_camp_ID  INT(11) UNSIGNED NOT NULL,
			csnd_user_ID  INT(11) UNSIGNED NOT NULL,
			csnd_emlog_ID INT(11) UNSIGNED NULL,
			PRIMARY KEY   csnd_PK ( csnd_camp_ID, csnd_user_ID )
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" ),

	'T_syslog' => array(
		'Creating system log table',
		"CREATE TABLE T_syslog (
			slg_ID        INT NOT NULL AUTO_INCREMENT,
			slg_timestamp TIMESTAMP NOT NULL,
			slg_user_ID   INT UNSIGNED NULL,
			slg_type      ENUM('info', 'warning', 'error', 'critical_error') COLLATE ascii_general_ci NOT NULL DEFAULT 'info',
			slg_origin    ENUM('core', 'plugin') COLLATE ascii_general_ci,
			slg_origin_ID INT UNSIGNED NULL,
			slg_object    ENUM('comment', 'item', 'user', 'file') COLLATE ascii_general_ci,
			slg_object_ID INT UNSIGNED NULL,
			slg_message   VARCHAR(255) NOT NULL,
			PRIMARY KEY   (slg_ID),
			INDEX         slg_object (slg_object, slg_object_ID)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" )
);

?>