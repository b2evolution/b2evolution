<?php
/**
 * This file holds the b2evo database scheme.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * The b2evo database scheme.
 *
 * This gets updated through {@link db_delta()} which generates the queries needed to get
 * to this scheme.
 *
 * Please see {@link db_delta()} for things to take care of.
 *
 * @global array
 */
global $schema_queries;

$schema_queries = array(
	'T_groups' => array(
		'Creating table for Groups',
		"CREATE TABLE T_groups (
			grp_ID int(11) NOT NULL auto_increment,
			grp_name varchar(50) NOT NULL default '',
			grp_perm_admin enum('none','hidden','visible') NOT NULL default 'visible',
			grp_perm_blogs enum('user','viewall','editall') NOT NULL default 'user',
			grp_perm_stats enum('none','view','edit') NOT NULL default 'none',
			grp_perm_spamblacklist enum('none','view','edit') NOT NULL default 'none',
			grp_perm_options enum('none','view','edit') NOT NULL default 'none',
			grp_perm_users enum('none','view','edit') NOT NULL default 'none',
			grp_perm_templates TINYINT NOT NULL DEFAULT 0,
			grp_perm_files enum('none','view','add','edit','all') NOT NULL default 'none',
			PRIMARY KEY grp_ID (grp_ID)
		)" ),

	'T_coll_user_perms' => array(
		'Creating table for Blog-User permissions',
		"CREATE TABLE T_coll_user_perms (
			bloguser_blog_ID int(11) unsigned NOT NULL default 0,
			bloguser_user_ID int(11) unsigned NOT NULL default 0,
			bloguser_ismember tinyint NOT NULL default 0,
			bloguser_perm_poststatuses set('published','deprecated','protected','private','draft') NOT NULL default '',
			bloguser_perm_delpost tinyint NOT NULL default 0,
			bloguser_perm_comments tinyint NOT NULL default 0,
			bloguser_perm_cats tinyint NOT NULL default 0,
			bloguser_perm_properties tinyint NOT NULL default 0,
			bloguser_perm_media_upload tinyint NOT NULL default 0,
			bloguser_perm_media_browse tinyint NOT NULL default 0,
			bloguser_perm_media_change tinyint NOT NULL default 0,
			PRIMARY KEY bloguser_pk (bloguser_blog_ID,bloguser_user_ID)
		)" ),

	'T_settings' => array(
		'Creating table for Settings',
		"CREATE TABLE T_settings (
			set_name VARCHAR( 30 ) NOT NULL ,
			set_value VARCHAR( 255 ) NULL ,
			PRIMARY KEY ( set_name )
		)" ),

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
			dateYMDhour datetime NOT NULL,
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
			user_validated TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY user_ID (user_ID),
			UNIQUE user_login (user_login),
			KEY user_grp_ID (user_grp_ID)
		)" ),

	'T_skins' => array(
		'Creating table for installed skins',
		"CREATE TABLE T_skin (
				skin_ID      int(11) unsigned      NOT NULL auto_increment,
				skin_name    varchar(32)           NOT NULL,
				skin_type    enum('normal','feed') NOT NULL default 'normal',
				skin_folder  varchar(32)           NOT NULL,
				PRIMARY KEY skin_ID (skin_ID),
				UNIQUE skin_folder( skin_folder ),
				KEY skin_name( skin_name )
			)" ),

	'T_blogs' => array(
		'Creating table for Blogs',
		"CREATE TABLE T_blogs (
			blog_ID int(11) unsigned NOT NULL auto_increment,
			blog_shortname varchar(12) NULL default '',
			blog_name varchar(50) NOT NULL default '',
			blog_tagline varchar(250) NULL default '',
			blog_description varchar(250) NULL default '',
			blog_longdesc TEXT NULL DEFAULT NULL,
			blog_locale VARCHAR(20) NOT NULL DEFAULT 'en-EU',
			blog_access_type VARCHAR(10) NOT NULL DEFAULT 'index.php',
			blog_siteurl varchar(120) NOT NULL default '',
			blog_staticfilename varchar(30) NULL default NULL,
			blog_stub VARCHAR(255) NOT NULL DEFAULT 'stub',
			blog_urlname VARCHAR(255) NOT NULL DEFAULT 'urlname',
			blog_notes TEXT NULL,
			blog_keywords tinytext,
			blog_allowcomments VARCHAR(20) NOT NULL default 'post_by_post',
			blog_allowtrackbacks TINYINT(1) NOT NULL default 1,
			blog_allowblogcss TINYINT(1) NOT NULL default 1,
			blog_allowusercss TINYINT(1) NOT NULL default 1,
			blog_default_skin VARCHAR(30) NOT NULL DEFAULT 'custom',
			blog_disp_bloglist TINYINT(1) NOT NULL DEFAULT 1,
			blog_in_bloglist TINYINT(1) NOT NULL DEFAULT 1,
			blog_links_blog_ID INT(11) NULL DEFAULT NULL,
			blog_commentsexpire INT(4) NOT NULL DEFAULT 0,
			blog_media_location ENUM( 'default', 'subdir', 'custom', 'none' ) DEFAULT 'default' NOT NULL,
			blog_media_subdir VARCHAR( 255 ) NULL,
			blog_media_fullpath VARCHAR( 255 ) NULL,
			blog_media_url VARCHAR( 255 ) NULL,
			blog_UID VARCHAR(20),
			PRIMARY KEY blog_ID (blog_ID),
			UNIQUE KEY blog_urlname (blog_urlname)
		)" ),

	'T_coll_settings' => array(
		'Creating collection settings table',
		"CREATE TABLE T_coll_settings (
			cset_coll_ID INT(11) UNSIGNED NOT NULL,
			cset_name    VARCHAR( 30 ) NOT NULL,
			cset_value   VARCHAR( 255 ) NULL,
			PRIMARY KEY ( cset_coll_ID, cset_name )
		)" ),

	'T_categories' => array(
		'Creating table for Categories',
		"CREATE TABLE T_categories (
			cat_ID int(11) unsigned NOT NULL auto_increment,
			cat_parent_ID int(11) unsigned NULL,
			cat_name tinytext NOT NULL,
			cat_urlname varchar(255) NOT NULL,
			cat_blog_ID int(11) unsigned NOT NULL default 2,
			cat_description VARCHAR(250) NULL DEFAULT NULL,
			cat_longdesc TEXT NULL DEFAULT NULL,
			cat_icon VARCHAR(30) NULL DEFAULT NULL,
			PRIMARY KEY cat_ID (cat_ID),
			UNIQUE cat_urlname( cat_urlname ),
			KEY cat_blog_ID (cat_blog_ID),
			KEY cat_parent_ID (cat_parent_ID)
		)" ),

	'T_posts' => array(
		'Creating table for Posts',
		"CREATE TABLE T_posts (
			post_ID                     int(11) unsigned NOT NULL auto_increment,
			post_parent_ID              int(11) unsigned NULL,
			post_creator_user_ID        int(11) unsigned NOT NULL,
			post_lastedit_user_ID       int(11) unsigned NULL,
			post_assigned_user_ID       int(11) unsigned NULL,
			post_datestart              datetime NOT NULL,
			post_datedeadline           datetime NULL,
			post_datecreated            datetime NULL,
			post_datemodified           datetime NOT NULL,
			post_status                 enum('published','deprecated','protected','private','draft') NOT NULL default 'published',
			post_pst_ID                 int(11) unsigned NULL,
			post_ptyp_ID                int(11) unsigned NULL,
			post_locale                 VARCHAR(20) NOT NULL DEFAULT 'en-EU',
			post_content                text NULL,
			post_title                  text NOT NULL,
			post_urltitle               VARCHAR(50) NULL DEFAULT NULL,
			post_url                    VARCHAR(255) NULL DEFAULT NULL,
			post_main_cat_ID            int(11) unsigned NOT NULL,
			post_notifications_status   ENUM('noreq','todo','started','finished') NOT NULL DEFAULT 'noreq',
			post_notifications_ctsk_ID  INT(10) unsigned NULL DEFAULT NULL,
			post_views                  INT(11) UNSIGNED NOT NULL DEFAULT 0,
			post_wordcount              int(11) default NULL,
			post_comment_status         ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open',
			post_commentsexpire         DATETIME DEFAULT NULL,
			post_renderers              TEXT NOT NULL,
			post_priority               int(11) unsigned null,
			PRIMARY KEY post_ID( post_ID ),
			UNIQUE post_urltitle( post_urltitle ),
			INDEX post_datestart( post_datestart ),
			INDEX post_main_cat_ID( post_main_cat_ID ),
			INDEX post_creator_user_ID( post_creator_user_ID ),
			INDEX post_status( post_status ),
			INDEX post_parent_ID( post_parent_ID ),
			INDEX post_assigned_user_ID( post_assigned_user_ID ),
			INDEX post_ptyp_ID( post_ptyp_ID ),
			INDEX post_pst_ID( post_pst_ID )
		)" ),

	'T_postcats' => array(
		'Creating table for Categories-to-Posts relationships',
		"CREATE TABLE T_postcats (
			postcat_post_ID int(11) unsigned NOT NULL,
			postcat_cat_ID int(11) unsigned NOT NULL,
			PRIMARY KEY postcat_pk (postcat_post_ID,postcat_cat_ID),
			UNIQUE catpost ( postcat_cat_ID, postcat_post_ID )
		)" ),

	'T_comments' => array(	// Note: pingbacks no longer supported, but previous pingbacks are to be preserved in the DB
		'Creating table for Comments',
		"CREATE TABLE T_comments (
			comment_ID        int(11) unsigned NOT NULL auto_increment,
			comment_post_ID   int(11) unsigned NOT NULL default '0',
			comment_type enum('comment','linkback','trackback','pingback') NOT NULL default 'comment',
			comment_status ENUM('published', 'deprecated', 'protected', 'private', 'draft') DEFAULT 'published' NOT NULL,
			comment_author_ID int unsigned NULL default NULL,
			comment_author varchar(100) NULL,
			comment_author_email varchar(255) NULL,
			comment_author_url varchar(255) NULL,
			comment_author_IP varchar(23) NOT NULL default '',
			comment_date datetime NOT NULL,
			comment_content text NOT NULL,
			comment_karma int(11) NOT NULL default '0',
			comment_spam_karma TINYINT NULL,
			comment_allow_msgform TINYINT NOT NULL DEFAULT '0',
			PRIMARY KEY comment_ID (comment_ID),
			KEY comment_post_ID (comment_post_ID),
			KEY comment_date (comment_date),
			KEY comment_type (comment_type)
		)" ),

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
		) COMMENT='saves available locales'
		" ),

	'T_antispam' => array(
		'Creating table for Antispam Blacklist',
		"CREATE TABLE T_antispam (
			aspm_ID bigint(11) NOT NULL auto_increment,
			aspm_string varchar(80) NOT NULL,
			aspm_source enum( 'local','reported','central' ) NOT NULL default 'reported',
			PRIMARY KEY aspm_ID (aspm_ID),
			UNIQUE aspm_string (aspm_string)
		)" ),

	'T_sessions' => array(
		'Creating table for active sessions',
		"CREATE TABLE T_sessions (
			sess_ID        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			sess_key       CHAR(32) NULL,
			sess_lastseen  DATETIME NOT NULL,
			sess_ipaddress VARCHAR(15) NOT NULL DEFAULT '',
			sess_user_ID   INT(10) DEFAULT NULL,
			sess_data      TEXT DEFAULT NULL,
			PRIMARY KEY( sess_ID )
		)" ), // NOTE: sess_lastseen is only relevant/used by Sessions class (+ stats) and results in a quite large index (file size wise)

	'T_usersettings' => array(
		'Creating user settings table',
		"CREATE TABLE T_usersettings (
			uset_user_ID INT(11) UNSIGNED NOT NULL,
			uset_name    VARCHAR( 30 ) NOT NULL,
			uset_value   VARCHAR( 255 ) NULL,
			PRIMARY KEY ( uset_user_ID, uset_name )
		)" ),

	'T_item__prerendering' => array(
		'Creating item prerendering cache table',
		'CREATE TABLE T_item__prerendering(
			itpr_itm_ID                   INT(11) UNSIGNED NOT NULL,
			itpr_format                   ENUM(\'htmlbody\', \'entityencoded\', \'xml\', \'text\') NOT NULL,
			itpr_renderers                TEXT NOT NULL,
			itpr_content_prerendered      TEXT NULL,
			itpr_datemodified             TIMESTAMP NOT NULL,
			PRIMARY KEY (itpr_itm_ID, itpr_format)
		)' ),

	'T_itemstatuses' => array(
		'Creating table for Post Statuses',
		"CREATE TABLE T_itemstatuses (
			pst_ID   int(11) unsigned not null AUTO_INCREMENT,
			pst_name varchar(30)      not null,
			primary key ( pst_ID )
		)" ),

	'T_itemtypes' => array(
		'Creating table for Post Types',
		"CREATE TABLE T_itemtypes (
			ptyp_ID   int(11) unsigned not null AUTO_INCREMENT,
			ptyp_name varchar(30)      not null,
			primary key (ptyp_ID)
		)" ),

	'T_files' => array(
		'Creating table for File Meta Data',
		"CREATE TABLE T_files (
			file_ID        int(11) unsigned  not null AUTO_INCREMENT,
			file_root_type enum('absolute','user','group','collection','skins') not null default 'absolute',
			file_root_ID   int(11) unsigned  not null default 0,
			file_path      varchar(255)      not null default '',
			file_title     varchar(255),
			file_alt       varchar(255),
			file_desc      text,
			primary key (file_ID),
			unique file (file_root_type, file_root_ID, file_path)
		)" ),

	'T_basedomains' => array(
		'Creating table for base domains',
		"CREATE TABLE T_basedomains (
			dom_ID     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			dom_name   VARCHAR(250) NOT NULL DEFAULT '',
			dom_status ENUM('unknown','whitelist','blacklist') NOT NULL DEFAULT 'unknown',
			dom_type   ENUM('unknown','normal','searcheng','aggregator') NOT NULL DEFAULT 'unknown',
			PRIMARY KEY     (dom_ID),
			UNIQUE dom_name (dom_name),
			INDEX dom_type  (dom_type)
		)" ),

	'T_useragents' => array(
		'Creating table for user agents',
		"CREATE TABLE T_useragents (
			agnt_ID        INT UNSIGNED NOT NULL AUTO_INCREMENT,
			agnt_signature VARCHAR(250) NOT NULL,
			agnt_type      ENUM('rss','robot','browser','unknown') DEFAULT 'unknown' NOT NULL ,
			PRIMARY KEY (agnt_ID),
			INDEX agnt_type ( agnt_type )
		)" ),

	'T_hitlog' => array(
		'Creating table for Hit-Logs',
		"CREATE TABLE T_hitlog (
			hit_ID             INT(11) NOT NULL AUTO_INCREMENT,
			hit_sess_ID        INT UNSIGNED,
			hit_datetime       DATETIME NOT NULL,
			hit_uri            VARCHAR(250) DEFAULT NULL,
			hit_referer_type   ENUM('search','blacklist','referer','direct','self','admin') NOT NULL,
			hit_referer        VARCHAR(250) DEFAULT NULL,
			hit_referer_dom_ID INT UNSIGNED DEFAULT NULL,
			hit_blog_ID        int(11) UNSIGNED NULL DEFAULT NULL,
			hit_remote_addr    VARCHAR(40) DEFAULT NULL,
			hit_agnt_ID        INT UNSIGNED NULL,
			PRIMARY KEY         (hit_ID),
			INDEX hit_agnt_ID        ( hit_agnt_ID ),
			INDEX hit_blog_ID        ( hit_blog_ID ),
			INDEX hit_uri            ( hit_uri ),
			INDEX hit_referer_dom_ID ( hit_referer_dom_ID )
		)" ),

	'T_subscriptions' => array(
		'Creating table for subscriptions',
		"CREATE TABLE T_subscriptions (
			sub_coll_ID     int(11) unsigned    not null,
			sub_user_ID     int(11) unsigned    not null,
			sub_items       tinyint(1)          not null,
			sub_comments    tinyint(1)          not null,
			primary key (sub_coll_ID, sub_user_ID)
		)" ),

	'T_coll_group_perms' => array(
		'Creating table for blog-group permissions',
		"CREATE TABLE T_coll_group_perms (
			bloggroup_blog_ID int(11) unsigned NOT NULL default 0,
			bloggroup_group_ID int(11) unsigned NOT NULL default 0,
			bloggroup_ismember tinyint NOT NULL default 0,
			bloggroup_perm_poststatuses set('published','deprecated','protected','private','draft') NOT NULL default '',
			bloggroup_perm_delpost tinyint NOT NULL default 0,
			bloggroup_perm_comments tinyint NOT NULL default 0,
			bloggroup_perm_cats tinyint NOT NULL default 0,
			bloggroup_perm_properties tinyint NOT NULL default 0,
			bloggroup_perm_media_upload tinyint NOT NULL default 0,
			bloggroup_perm_media_browse tinyint NOT NULL default 0,
			bloggroup_perm_media_change tinyint NOT NULL default 0,
			PRIMARY KEY bloggroup_pk (bloggroup_blog_ID,bloggroup_group_ID)
		)" ),

	'T_links' => array(
		'Creating table for Post Links',
		"CREATE TABLE T_links (
			link_ID               int(11) unsigned  not null AUTO_INCREMENT,
			link_datecreated      datetime          not null,
			link_datemodified     datetime          not null,
			link_creator_user_ID  int(11) unsigned  not null,
			link_lastedit_user_ID int(11) unsigned  not null,
			link_itm_ID           int(11) unsigned  NOT NULL,
			link_dest_itm_ID      int(11) unsigned  NULL,
			link_file_ID          int(11) unsigned  NULL,
			link_ltype_ID         int(11) unsigned  NOT NULL default 1,
			link_external_url     VARCHAR(255)      NULL,
			link_title            TEXT              NULL,
			PRIMARY KEY (link_ID),
			INDEX link_itm_ID( link_itm_ID ),
			INDEX link_dest_itm_ID (link_dest_itm_ID),
			INDEX link_file_ID (link_file_ID)
		)" ),

	'T_filetypes' => array(
		'Creating table for file types',
		'CREATE TABLE T_filetypes (
			ftyp_ID int(11) unsigned NOT NULL auto_increment,
			ftyp_extensions varchar(30) NOT NULL,
			ftyp_name varchar(30) NOT NULL,
			ftyp_mimetype varchar(50) NOT NULL,
			ftyp_icon varchar(20) default NULL,
			ftyp_viewtype varchar(10) NOT NULL,
			ftyp_allowed tinyint(1) NOT NULL default 0,
			PRIMARY KEY (ftyp_ID)
		)' ),

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
		)" ),

	'T_pluginsettings' => array(
		'Creating plugin settings table',
		'CREATE TABLE T_pluginsettings (
			pset_plug_ID INT(11) UNSIGNED NOT NULL,
			pset_name VARCHAR( 30 ) NOT NULL,
			pset_value TEXT NULL,
			PRIMARY KEY ( pset_plug_ID, pset_name )
		)' ),

	'T_pluginusersettings' => array(
		'Creating plugin user settings table',
		'CREATE TABLE T_pluginusersettings (
			puset_plug_ID INT(11) UNSIGNED NOT NULL,
			puset_user_ID INT(11) UNSIGNED NOT NULL,
			puset_name VARCHAR( 30 ) NOT NULL,
			puset_value TEXT NULL,
			PRIMARY KEY ( puset_plug_ID, puset_user_ID, puset_name )
		)' ),

	'T_pluginevents' => array(
		'Creating plugin events table',
		'CREATE TABLE T_pluginevents(
			pevt_plug_ID INT(11) UNSIGNED NOT NULL,
			pevt_event VARCHAR(40) NOT NULL,
			pevt_enabled TINYINT NOT NULL DEFAULT 1,
			PRIMARY KEY( pevt_plug_ID, pevt_event )
		)' ),

	'T_cron__task' => array(
		'Creating cron tasks table',
		'CREATE TABLE T_cron__task(
			ctsk_ID              int(10) unsigned      not null AUTO_INCREMENT,
			ctsk_start_datetime  datetime              not null,
			ctsk_repeat_after    int(10) unsigned,
			ctsk_name            varchar(50)           not null,
			ctsk_controller      varchar(50)           not null,
			ctsk_params          text,
			PRIMARY KEY (ctsk_ID)
		)' ),

	'T_cron__log' => array(
		'Creating cron tasks table',
		'CREATE TABLE T_cron__log(
			clog_ctsk_ID              int(10) unsigned   not null,
			clog_realstart_datetime   datetime           not null,
			clog_realstop_datetime    datetime,
			clog_status               enum(\'started\',\'finished\',\'error\',\'timeout\') not null default \'started\',
			clog_messages             text,
			PRIMARY KEY (clog_ctsk_ID)
		)' ),

);


/*
 * $Log$
 * Revision 1.44  2006/12/29 01:10:06  fplanque
 * basic skin registering
 *
 * Revision 1.43  2006/12/07 20:03:33  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.42  2006/12/07 16:06:24  fplanque
 * prepared new file editing permission
 *
 * Revision 1.41  2006/12/04 21:25:18  fplanque
 * removed user skin switching
 *
 * Revision 1.40  2006/11/30 22:34:16  fplanque
 * bleh
 *
 * Revision 1.39  2006/11/05 20:13:57  fplanque
 * minor
 *
 * Revision 1.38  2006/10/05 02:42:22  blueyed
 * Remove index hit_datetime, because its slow on INSERT (e.g. 1s)
 *
 * Revision 1.37  2006/10/01 22:11:42  blueyed
 * Ping services as plugins.
 *
 * Revision 1.36  2006/09/30 23:42:06  blueyed
 * Allow editing the plugin name and short desc of installed plugins
 *
 * Revision 1.35  2006/09/25 20:25:49  blueyed
 * Extended date- and time-format fields for locales to 20 chars. See http://forums.b2evolution.net//viewtopic.php?p=44335#44335
 *
 * Revision 1.34  2006/09/23 19:32:06  blueyed
 * Schema for pre-rendered changes
 *
 * Revision 1.33  2006/09/22 17:26:32  blueyed
 * itpr_format should be NOT NULL (MySQL3 requires this also for a PK and it will never be NULL anyway)
 *
 * Revision 1.32  2006/09/21 16:54:26  blueyed
 * Experimental caching of pre-rendered item content. Added table T_item__prerendering.
 *
 * Revision 1.31  2006/09/10 17:33:02  fplanque
 * started to steam up the categories/chapters
 *
 * Revision 1.30  2006/08/29 22:59:09  blueyed
 * doc
 *
 * Revision 1.29  2006/08/26 16:33:02  fplanque
 * enhanced stats
 *
 * Revision 1.28  2006/08/21 16:07:44  fplanque
 * refactoring
 *
 * Revision 1.27  2006/08/19 02:15:08  fplanque
 * Half kille dthe pingbacks
 * Still supported in DB in case someone wants to write a plugin.
 *
 * Revision 1.26  2006/08/04 22:13:23  blueyed
 * Finished de-abstraction
 *
 * Revision 1.25  2006/08/03 19:39:06  blueyed
 * hit_uri index (needed when checking for page reload)
 *
 * Revision 1.24  2006/08/03 01:53:40  blueyed
 * whitespace
 *
 * Revision 1.23  2006/07/23 17:19:52  blueyed
 * MFB
 *
 * Revision 1.22  2006/07/06 19:59:08  fplanque
 * better logs, better stats, better pruning
 *
 * Revision 1.21  2006/07/03 19:19:14  blueyed
 * Added indices
 *
 * Revision 1.20  2006/06/13 21:49:16  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.19.2.1  2006/06/13 18:27:51  fplanque
 * fixes
 *
 * Revision 1.19  2006/05/02 23:46:07  blueyed
 * Validate/fixed plugin spam weight handling.
 *
 * Revision 1.18  2006/05/02 04:36:25  blueyed
 * Spam karma changed (-100..100 instead of abs/max); Spam weight for plugins; publish/delete threshold
 *
 * Revision 1.17  2006/04/22 02:58:05  blueyed
 * Added Id keyword
 *
 * Revision 1.16  2006/04/22 02:36:39  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.15  2006/04/20 17:59:02  blueyed
 * Removed "spam" from hit_referer_type (DB) and summary stats
 *
 * Revision 1.14  2006/04/20 16:31:30  fplanque
 * comment moderation (finished for 1.8)
 *
 * Revision 1.13  2006/04/19 15:56:02  blueyed
 * Renamed T_posts.post_comments to T_posts.post_comment_status (DB column rename!);
 * and Item::comments to Item::comment_status (Item API change)
 *
 * Revision 1.12  2006/04/11 22:39:50  blueyed
 * Fixed installation of basic plugins, though again more complicated (IMHO)
 *
 * Revision 1.11  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>