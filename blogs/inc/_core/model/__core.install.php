<?php
/**
 * This is the install file for the core modules
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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
			grp_ID int(11) NOT NULL auto_increment,
			grp_name varchar(50) NOT NULL default '',
			grp_perm_blogs enum('user','viewall','editall') NOT NULL default 'user',
			grp_perm_bypass_antispam         TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtmlvalidation         VARCHAR(10) NOT NULL default 'always',
			grp_perm_xhtmlvalidation_xmlrpc  VARCHAR(10) NOT NULL default 'always',
			grp_perm_xhtml_css_tweaks        TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtml_iframes           TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtml_javascript        TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtml_objects           TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_stats enum('none','user','view','edit') NOT NULL default 'none',
			PRIMARY KEY grp_ID (grp_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_groups__groupsettings' => array(
		'Creating table for Group Settings',
		"CREATE TABLE T_groups__groupsettings (
			gset_grp_ID INT(11) UNSIGNED NOT NULL,
			gset_name VARCHAR(30) NOT NULL,
			gset_value VARCHAR(255) NULL,
			PRIMARY KEY (gset_grp_ID, gset_name)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_settings' => array(
		'Creating table for Settings',
		"CREATE TABLE T_settings (
			set_name VARCHAR( 30 ) NOT NULL ,
			set_value VARCHAR( 5000 ) NULL ,
			PRIMARY KEY ( set_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_global__cache' => array(
		'Creating table for Caches',
		"CREATE TABLE T_global__cache (
			cach_name VARCHAR( 30 ) NOT NULL ,
			cach_cache MEDIUMBLOB NULL ,
			PRIMARY KEY ( cach_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users' => array(
		'Creating table for Users',
		"CREATE TABLE T_users (
			user_ID int(11) unsigned NOT NULL auto_increment,
			user_login varchar(20) NOT NULL,
			user_pass CHAR(32) NOT NULL,
			user_grp_ID int(4) NOT NULL default 1,
			user_email varchar(255) NOT NULL,
			user_status enum( 'activated', 'autoactivated', 'closed', 'deactivated', 'emailchanged', 'failedactivation', 'new' ) NOT NULL default 'new',
			user_avatar_file_ID int(10) unsigned default NULL,
			user_firstname varchar(50) NULL,
			user_lastname varchar(50) NULL,
			user_nickname varchar(50) NULL,
			user_url varchar(255) NULL,
			user_level int unsigned DEFAULT 0 NOT NULL,
			user_locale varchar(20) DEFAULT 'en-EU' NOT NULL,
			user_unsubscribe_key varchar(32) NOT NULL default '' COMMENT 'A specific key, it is used when a user wants to unsubscribe from a post comments without signing in',
			user_gender char(1) NULL,
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
			ufdf_type       char(8) NOT NULL,
			ufdf_name       varchar(255) NOT NULL,
			ufdf_options    text NOT NULL,
			ufdf_required   enum('hidden','optional','recommended','require') NOT NULL default 'optional',
			ufdf_duplicated enum('forbidden','allowed','list') NOT NULL default 'allowed',
			ufdf_order      int(11) NOT NULL,
			ufdf_suggest    tinyint(1) NOT NULL DEFAULT 0,
			ufdf_bubbletip  varchar(2000) NULL,
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
			urep_status         enum( 'fake', 'guidelines', 'harass', 'spam', 'other' ),
			urep_info           varchar(240),
			urep_datetime		datetime NOT NULL,
			PRIMARY KEY ( urep_target_user_ID, urep_reporter_ID )
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
			loc_charset varchar(15) NOT NULL default 'iso-8859-1',
			loc_datefmt varchar(20) NOT NULL default 'y-m-d',
			loc_timefmt varchar(20) NOT NULL default 'H:i:s',
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
			aspm_source enum( 'local','reported','central' ) NOT NULL default 'reported',
			PRIMARY KEY aspm_ID (aspm_ID),
			UNIQUE aspm_string (aspm_string)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_antispam__iprange' => array(
		'Creating table for Antispam IP Ranges',
		"CREATE TABLE T_antispam__iprange (
			aipr_ID          int(10) unsigned NOT NULL auto_increment,
			aipr_IPv4start   int(10) unsigned NOT NULL,
			aipr_IPv4end     int(10) unsigned NOT NULL,
			aipr_user_count  int(10) unsigned DEFAULT 0,
			aipr_status      enum( 'trusted', 'suspect', 'blocked' ) NULL DEFAULT NULL,
			aipr_block_count int(10) unsigned DEFAULT 0,
			PRIMARY KEY aipr_ID (aipr_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__usersettings' => array(
		'Creating user settings table',
		"CREATE TABLE T_users__usersettings (
			uset_user_ID INT(11) UNSIGNED NOT NULL,
			uset_name    VARCHAR( 30 ) NOT NULL,
			uset_value   VARCHAR( 255 ) NULL,
			PRIMARY KEY ( uset_user_ID, uset_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_plugins' => array(
		'Creating plugins table',
		"CREATE TABLE T_plugins (
			plug_ID              INT(11) UNSIGNED NOT NULL auto_increment,
			plug_priority        TINYINT NOT NULL default 50,
			plug_classname       VARCHAR(40) NOT NULL default '',
			plug_code            VARCHAR(32) NULL,
			plug_version         VARCHAR(42) NOT NULL default '0',
			plug_name            VARCHAR(255) NULL default NULL,
			plug_shortdesc       VARCHAR(255) NULL default NULL,
			plug_status          ENUM( 'enabled', 'disabled', 'needs_config', 'broken' ) NOT NULL,
			plug_spam_weight     TINYINT UNSIGNED NOT NULL DEFAULT 1,
			PRIMARY KEY ( plug_ID ),
			UNIQUE plug_code( plug_code ),
			INDEX plug_status( plug_status )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_pluginsettings' => array(
		'Creating plugin settings table',
		"CREATE TABLE T_pluginsettings (
			pset_plug_ID INT(11) UNSIGNED NOT NULL,
			pset_name VARCHAR( 30 ) NOT NULL,
			pset_value TEXT NULL,
			PRIMARY KEY ( pset_plug_ID, pset_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_pluginusersettings' => array(
		'Creating plugin user settings table',
		"CREATE TABLE T_pluginusersettings (
			puset_plug_ID INT(11) UNSIGNED NOT NULL,
			puset_user_ID INT(11) UNSIGNED NOT NULL,
			puset_name VARCHAR( 30 ) NOT NULL,
			puset_value TEXT NULL,
			PRIMARY KEY ( puset_plug_ID, puset_user_ID, puset_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_pluginevents' => array(
		'Creating plugin events table',
		"CREATE TABLE T_pluginevents(
			pevt_plug_ID INT(11) UNSIGNED NOT NULL,
			pevt_event VARCHAR(40) NOT NULL,
			pevt_enabled TINYINT NOT NULL DEFAULT 1,
			PRIMARY KEY( pevt_plug_ID, pevt_event )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_cron__task' => array(
		'Creating cron tasks table',
		"CREATE TABLE T_cron__task(
			ctsk_ID              int(10) unsigned      not null AUTO_INCREMENT,
			ctsk_start_datetime  datetime              not null DEFAULT '2000-01-01 00:00:00',
			ctsk_repeat_after    int(10) unsigned,
			ctsk_name            varchar(255)          not null,
			ctsk_controller      varchar(50)           not null,
			ctsk_params          text,
			PRIMARY KEY (ctsk_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_cron__log' => array(
		'Creating cron tasks table',
		"CREATE TABLE T_cron__log(
			clog_ctsk_ID              int(10) unsigned   not null,
			clog_realstart_datetime   datetime           not null DEFAULT '2000-01-01 00:00:00',
			clog_realstop_datetime    datetime,
			clog_status               enum('started','finished','error','timeout') not null default 'started',
			clog_messages             text,
			PRIMARY KEY (clog_ctsk_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_regional__country' => array(
		'Creating Countries table',
		"CREATE TABLE T_regional__country (
			ctry_ID        int(10) unsigned NOT NULL auto_increment,
			ctry_code      char(2) NOT NULL,
			ctry_name      varchar(40) NOT NULL,
			ctry_curr_ID   int(10) unsigned NULL,
			ctry_enabled   tinyint(1) NOT NULL DEFAULT 1,
			ctry_preferred tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY ctry_ID (ctry_ID),
			UNIQUE ctry_code (ctry_code)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_regional__region' => array(
		'Creating Regions table',
		"CREATE TABLE T_regional__region (
			rgn_ID        int(10) unsigned NOT NULL auto_increment,
			rgn_ctry_ID   int(10) unsigned NOT NULL,
			rgn_code      char(6) NOT NULL,
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
			subrg_code      char(6) NOT NULL,
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
			city_postcode   char(12) NOT NULL,
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
			curr_code char(3) NOT NULL,
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
			slug_title varchar(255) NOT NULL COLLATE ascii_bin,
			slug_type	char(6) NOT NULL DEFAULT 'item',
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
			emlog_to        VARCHAR(255) DEFAULT NULL,
			emlog_result    ENUM ( 'ok', 'error', 'blocked' ) NOT NULL DEFAULT 'ok',
			emlog_subject   VARCHAR(255) DEFAULT NULL,
			emlog_headers   TEXT DEFAULT NULL,
			emlog_message   TEXT DEFAULT NULL,
			PRIMARY KEY     (emlog_ID)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" ),

	'T_email__returns' => array(
		'Creating email returns table',
		"CREATE TABLE T_email__returns (
			emret_ID        INT(10) UNSIGNED NOT NULL auto_increment,
			emret_address   VARCHAR(255) DEFAULT NULL,
			emret_errormsg  VARCHAR(255) DEFAULT NULL,
			emret_timestamp TIMESTAMP NOT NULL,
			emret_headers   TEXT DEFAULT NULL,
			emret_message   TEXT DEFAULT NULL,
			emret_errtype   CHAR(1) NOT NULL DEFAULT 'U',
			PRIMARY KEY     (emret_ID)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" ),

	'T_email__blocked' => array(
		'Creating blocked emails table',
		"CREATE TABLE T_email__blocked (
			emblk_ID                    INT(10) UNSIGNED NOT NULL auto_increment,
			emblk_address               VARCHAR(255) DEFAULT NULL,
			emblk_status                ENUM ( 'unknown', 'warning', 'suspicious1', 'suspicious2', 'suspicious3', 'prmerror', 'spammer' ) NOT NULL DEFAULT 'unknown',
			emblk_sent_count            INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emblk_sent_last_returnerror INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emblk_prmerror_count        INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emblk_tmperror_count        INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emblk_spamerror_count       INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emblk_othererror_count      INT(10) UNSIGNED NOT NULL DEFAULT 0,
			emblk_last_sent_ts          TIMESTAMP NULL,
			emblk_last_error_ts         TIMESTAMP NULL,
			PRIMARY KEY                 (emblk_ID),
			UNIQUE                      emblk_address (emblk_address)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" )
);

?>