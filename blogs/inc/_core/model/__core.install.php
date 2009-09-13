<?php
/**
 * This is the install file for the core modules
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
			grp_perm_admin enum('none','hidden','visible') NOT NULL default 'visible',
			grp_perm_blogs enum('user','viewall','editall') NOT NULL default 'user',
			grp_perm_bypass_antispam         TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtmlvalidation         VARCHAR(10) NOT NULL default 'always',
			grp_perm_xhtmlvalidation_xmlrpc  VARCHAR(10) NOT NULL default 'always',
			grp_perm_xhtml_css_tweaks        TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtml_iframes           TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtml_javascript        TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_xhtml_objects           TINYINT(1) NOT NULL DEFAULT 0,
			grp_perm_stats enum('none','user','view','edit') NOT NULL default 'none',
			grp_perm_spamblacklist enum('none','view','edit') NOT NULL default 'none',
			grp_perm_options enum('none','view','edit') NOT NULL default 'none',
			grp_perm_users enum('none','view','edit') NOT NULL default 'none',
			grp_perm_templates TINYINT NOT NULL DEFAULT 0,
			grp_perm_files enum('none','view','add','edit','all') NOT NULL default 'none',
			grp_perm_messaging enum('none','write','delete') NOT NULL default 'none',
			PRIMARY KEY grp_ID (grp_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_settings' => array(
		'Creating table for Settings',
		"CREATE TABLE T_settings (
			set_name VARCHAR( 30 ) NOT NULL ,
			set_value VARCHAR( 255 ) NULL ,
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
			user_firstname varchar(50) NULL,
			user_lastname varchar(50) NULL,
			user_nickname varchar(50) NULL,
			user_icq int(11) unsigned NULL,
			user_email varchar(255) NOT NULL,
			user_url varchar(255) NULL,
			user_ip varchar(15) NULL,
			user_domain varchar(200) NULL,
			user_browser varchar(200) NULL,
			dateYMDhour datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
			user_level int unsigned DEFAULT 0 NOT NULL,
			user_aim varchar(50) NULL,
			user_msn varchar(100) NULL,
			user_yim varchar(50) NULL,
			user_locale varchar(20) DEFAULT 'en-EU' NOT NULL,
			user_idmode varchar(20) NOT NULL DEFAULT 'login',
			user_allow_msgform TINYINT NOT NULL DEFAULT '1',
			user_notify tinyint(1) NOT NULL default 1,
			user_showonline tinyint(1) NOT NULL default 1,
			user_grp_ID int(4) NOT NULL default 1,
			user_validated tinyint(1) NOT NULL DEFAULT 0,
			user_avatar_file_ID int(10) unsigned default NULL,
			user_ctry_ID int(10) unsigned NULL,
			PRIMARY KEY user_ID (user_ID),
			UNIQUE user_login (user_login),
			KEY user_grp_ID (user_grp_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__fielddefs' => array(
		'Creating table for User field definitions',
		"CREATE TABLE T_users__fielddefs (
			ufdf_ID int(10) unsigned NOT NULL,
			ufdf_type char(8) NOT NULL,
			ufdf_name varchar(255) NOT NULL,
			PRIMARY KEY (ufdf_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_users__fields' => array(
		'Creating table for User fields',
		"CREATE TABLE T_users__fields (
		  uf_ID      int(10) unsigned NOT NULL auto_increment,
		  uf_user_ID int(10) unsigned NOT NULL,
		  uf_ufdf_ID int(10) unsigned NOT NULL,
		  uf_varchar varchar(255) NOT NULL,
		  PRIMARY KEY (uf_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

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

	'T_usersettings' => array(
		'Creating user settings table',
		"CREATE TABLE T_usersettings (
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
			plug_apply_rendering ENUM( 'stealth', 'always', 'opt-out', 'opt-in', 'lazy', 'never' ) NOT NULL DEFAULT 'never',
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
			ctsk_name            varchar(50)           not null,
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

	'T_country' => array(
		'Creating Countries table',
		"CREATE TABLE T_country (
			ctry_ID int(10) unsigned NOT NULL auto_increment,
			ctry_code char(2) NOT NULL,
			ctry_name varchar(40) NOT NULL,
			ctry_curr_ID int(10) unsigned NULL,
			PRIMARY KEY ctry_ID (ctry_ID),
			UNIQUE ctry_code (ctry_code)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_currency' => array(
		'Creating Currencies table',
		"CREATE TABLE T_currency (
			curr_ID int(10) unsigned NOT NULL auto_increment,
			curr_code char(3) NOT NULL,
			curr_shortcut varchar(30) NOT NULL,
			curr_name varchar(40) NOT NULL,
			PRIMARY KEY curr_ID (curr_ID),
			UNIQUE curr_code (curr_code)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" )
);

/*
 * $Log$
 * Revision 1.37  2009/09/13 15:56:12  fplanque
 * minor
 *
 * Revision 1.36  2009/09/10 13:10:37  efy-maxim
 * int(11) has been changed to int(10) for PKs of T_country, T_currency tables
 *
 * Revision 1.35  2009/09/07 23:35:47  fplanque
 * cleanup
 *
 * Revision 1.34  2009/09/07 14:26:46  efy-maxim
 * Country field has been added to User form (but without updater)
 *
 * Revision 1.33  2009/09/05 18:34:47  fplanque
 * minor
 *
 * Revision 1.32  2009/09/05 11:29:28  efy-maxim
 * Create default currencies and countries. Upgrade currencies and countries.
 *
 * Revision 1.31  2009/09/03 10:43:37  efy-maxim
 * Countries tab in Global Settings section
 *
 * Revision 1.30  2009/09/02 06:23:59  efy-maxim
 * Currencies Tab in Global Settings
 *
 * Revision 1.29  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 * Revision 1.28  2009/08/22 20:31:01  tblue246
 * New feature: Post type permissions
 *
 * Revision 1.27  2009/08/06 14:14:17  fplanque
 * doc
 *
 * Revision 1.26  2009/07/13 00:14:07  fplanque
 * fixing default dates
 *
 * Revision 1.25  2009/07/12 23:18:22  fplanque
 * upgrading tables to innodb
 *
 * Revision 1.24  2009/07/10 20:02:08  fplanque
 * using innodb by default for most tables now.
 * enabled transactions by default.
 *
 * Revision 1.23  2009/07/10 19:48:01  fplanque
 * clean up a little bit
 *
 * Revision 1.22  2009/07/10 17:18:27  sam2kb
 * minor
 *
 * Revision 1.21  2009/07/10 16:30:16  sam2kb
 * b2evo tables created with DEFAULT CHARSET based on selected locale
 *
 * Revision 1.20  2009/07/07 00:34:42  fplanque
 * Remember whether or not the TinyMCE editor was last used on a per post and per blog basis.
 *
 * Revision 1.19  2009/06/20 17:19:33  leeturner2701
 * meta desc and meta keywords per blog post
 *
 * Revision 1.18  2009/05/31 17:04:41  sam2kb
 * blog_shortname field extended to 255 characters
 * Please change the new_db_version
 *
 * Revision 1.17  2009/05/18 21:01:05  sam2kb
 * No collation should defined here. Returns MySQL error.
 *
 * Revision 1.16  2009/05/18 02:51:05  fplanque
 * minor
 *
 * Revision 1.15  2009/03/08 23:57:40  fplanque
 * 2009
 *
 * Revision 1.14  2009/02/25 19:31:10  blueyed
 * Fix indent. Please use just spaces for inner indenting.
 *
 * Revision 1.13  2009/02/25 01:31:14  fplanque
 * upgrade stuff
 *
 * Revision 1.12  2009/02/24 22:58:19  fplanque
 * Basic version history of post edits
 *
 * Revision 1.11  2009/02/05 22:41:15  tblue246
 * Add column wi_enabled (enabling/disabling widgets) when creating tables.
 *
 * Revision 1.10  2009/01/28 21:23:21  fplanque
 * Manual ordering of categories
 *
 * Revision 1.9  2009/01/23 18:32:15  fplanque
 * versioning
 *
 * Revision 1.8  2009/01/13 23:45:59  fplanque
 * User fields proof of concept
 *
 * Revision 1.7  2008/12/28 17:35:51  fplanque
 * increase blog name max length to 255 chars
 *
 * Revision 1.6  2008/10/06 01:55:06  fplanque
 * User fields proof of concept.
 * Needs UserFieldDef and UserFieldDefCache + editing of fields.
 * Does anyone want to take if from there?
 *
 * Revision 1.5  2008/09/29 08:30:36  fplanque
 * Avatar support
 *
 * Revision 1.4  2008/09/23 06:18:33  fplanque
 * File manager now supports a shared directory (/media/shared/global/)
 *
 * Revision 1.3  2008/07/03 09:55:07  yabs
 * widget UI
 *
 * Revision 1.2  2008/05/10 22:59:09  fplanque
 * keyphrase logging
 *
 * Revision 1.1  2008/04/06 19:19:29  fplanque
 * Started moving some intelligence to the Modules.
 * 1) Moved menu structure out of the AdminUI class.
 * It is part of the app structure, not the UI. Up to this point at least.
 * Note: individual Admin skins can still override the whole menu.
 * 2) Moved DB schema to the modules. This will be reused outside
 * of install for integrity checks and backup.
 * 3) cleaned up config files
 *
 * Revision 1.86  2008/03/23 23:40:42  fplanque
 * no message
 *
 * Revision 1.85  2008/03/22 19:39:28  fplanque
 * <title> tag support
 *
 * Revision 1.84  2008/03/21 16:07:03  fplanque
 * longer post slugs
 *
 * Revision 1.83  2008/02/19 11:11:19  fplanque
 * no message
 *
 * Revision 1.82  2008/02/09 20:14:14  fplanque
 * custom fields management
 *
 * Revision 1.81  2008/02/09 17:36:15  fplanque
 * better handling of order, including approximative comparisons
 *
 * Revision 1.80  2008/02/09 03:04:01  fplanque
 * usability shortcut
 *
 * Revision 1.79  2008/02/09 02:56:00  fplanque
 * explicit order by field
 *
 * Revision 1.78  2008/02/07 00:35:52  fplanque
 * cleaned up install
 *
 * Revision 1.77  2008/01/20 18:20:23  fplanque
 * Antispam per group setting
 *
 * Revision 1.76  2008/01/20 15:31:12  fplanque
 * configurable validation/security rules
 *
 * Revision 1.75  2008/01/19 10:57:10  fplanque
 * Splitting XHTML checking by group and interface
 *
 * Revision 1.74  2008/01/10 19:57:37  fplanque
 * moved to v-3-0
 *
 * Revision 1.73  2008/01/09 00:25:51  blueyed
 * Vastly improve performance in CommentList for large number of comments:
 * - add index comment_date_ID; and force it in the SQL (falling back to comment_date)
 *
 * Revision 1.72  2007/11/30 01:46:12  fplanque
 * db upgrade
 *
 * Revision 1.71  2007/11/28 17:29:44  fplanque
 * Support for getting updates from b2evolution.net
 *
 * Revision 1.70  2007/11/03 22:38:34  fplanque
 * no message
 *
 * Revision 1.69  2007/11/03 21:04:27  fplanque
 * skin cleanup
 *
 * Revision 1.68  2007/11/02 01:52:51  fplanque
 * comment ratings
 *
 * Revision 1.67  2007/09/19 02:54:16  fplanque
 * bullet proof upgrade
 */
?>
