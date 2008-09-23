<?php
/**
 * This is the install file for the core modules
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
			PRIMARY KEY grp_ID (grp_ID)
		)" ),

	'T_settings' => array(
		'Creating table for Settings',
		"CREATE TABLE T_settings (
			set_name VARCHAR( 30 ) NOT NULL ,
			set_value VARCHAR( 255 ) NULL ,
			PRIMARY KEY ( set_name )
		)" ),

	'T_global__cache' => array(
		'Creating table for Caches',
		"CREATE TABLE T_global__cache (
			cach_name VARCHAR( 30 ) NOT NULL ,
			cach_cache MEDIUMBLOB NULL ,
			PRIMARY KEY ( cach_name )
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
			user_validated TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY user_ID (user_ID),
			UNIQUE user_login (user_login),
			KEY user_grp_ID (user_grp_ID)
		)" ),

	'T_skins__skin' => array(
		'Creating table for installed skins',
		"CREATE TABLE T_skins__skin (
				skin_ID      int(10) unsigned      NOT NULL auto_increment,
				skin_name    varchar(32)           NOT NULL,
				skin_type    enum('normal','feed') NOT NULL default 'normal',
				skin_folder  varchar(32)           NOT NULL,
				PRIMARY KEY skin_ID (skin_ID),
				UNIQUE skin_folder( skin_folder ),
				KEY skin_name( skin_name )
			)" ),

	'T_skins__container' => array(
		'Creating table for skin containers',
		"CREATE TABLE T_skins__container (
				sco_skin_ID   int(10) unsigned      NOT NULL,
				sco_name      varchar(40)           NOT NULL,
				PRIMARY KEY (sco_skin_ID, sco_name)
			)" ),

	'T_blogs' => array(
		'Creating table for Blogs',
		"CREATE TABLE T_blogs (
			blog_ID              int(11) unsigned NOT NULL auto_increment,
			blog_shortname       varchar(12) NULL default '',
			blog_name            varchar(50) NOT NULL default '',
			blog_owner_user_ID   int(11) unsigned NOT NULL default 1,
			blog_advanced_perms  TINYINT(1) NOT NULL default 0,
			blog_tagline         varchar(250) NULL default '',
			blog_description     varchar(250) NULL default '',
			blog_longdesc        TEXT NULL DEFAULT NULL,
			blog_locale          VARCHAR(20) NOT NULL DEFAULT 'en-EU',
			blog_access_type     VARCHAR(10) NOT NULL DEFAULT 'index.php',
			blog_siteurl         varchar(120) NOT NULL default '',
			blog_urlname         VARCHAR(255) NOT NULL DEFAULT 'urlname',
			blog_notes           TEXT NULL,
			blog_keywords        tinytext,
			blog_allowcomments   VARCHAR(20) NOT NULL default 'post_by_post',
			blog_allowtrackbacks TINYINT(1) NOT NULL default 0,
			blog_allowblogcss    TINYINT(1) NOT NULL default 1,
			blog_allowusercss    TINYINT(1) NOT NULL default 1,
			blog_skin_ID         INT(10) UNSIGNED NOT NULL DEFAULT 1,
			blog_in_bloglist     TINYINT(1) NOT NULL DEFAULT 1,
			blog_links_blog_ID   INT(11) NULL DEFAULT NULL,
			blog_media_location  ENUM( 'default', 'subdir', 'custom', 'none' ) DEFAULT 'default' NOT NULL,
			blog_media_subdir    VARCHAR( 255 ) NULL,
			blog_media_fullpath  VARCHAR( 255 ) NULL,
			blog_media_url       VARCHAR( 255 ) NULL,
			blog_UID             VARCHAR(20),
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

	'T_widget' => array(
		'Creating components table',
		"CREATE TABLE T_widget (
			wi_ID					INT(10) UNSIGNED auto_increment,
			wi_coll_ID    INT(11) UNSIGNED NOT NULL,
			wi_sco_name   VARCHAR( 40 ) NOT NULL,
			wi_order			INT(10) NOT NULL,
			wi_type       ENUM( 'core', 'plugin' ) NOT NULL DEFAULT 'core',
			wi_code       VARCHAR(32) NOT NULL,
			wi_params     TEXT NULL,
			PRIMARY KEY ( wi_ID ),
			UNIQUE wi_order( wi_coll_ID, wi_sco_name, wi_order )
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

	'T_items__item' => array(
		'Creating table for Posts',
		"CREATE TABLE T_items__item (
			post_ID                     int(11) unsigned NOT NULL auto_increment,
			post_parent_ID              int(11) unsigned NULL,
			post_creator_user_ID        int(11) unsigned NOT NULL,
			post_lastedit_user_ID       int(11) unsigned NULL,
			post_assigned_user_ID       int(11) unsigned NULL,
			post_datestart              DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00',
			post_datedeadline           datetime NULL,
			post_datecreated            datetime NULL,
			post_datemodified           DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00',
			post_status                 enum('published','deprecated','protected','private','draft','redirected') NOT NULL default 'published',
			post_pst_ID                 int(11) unsigned NULL,
			post_ptyp_ID                int(11) unsigned NULL,
			post_locale                 VARCHAR(20) NOT NULL DEFAULT 'en-EU',
			post_content                MEDIUMTEXT NULL,
			post_excerpt                text NULL,
			post_title                  text NOT NULL,
			post_urltitle               VARCHAR(210) NULL DEFAULT NULL,
			post_titletag               VARCHAR(255) NULL DEFAULT NULL,
			post_url                    VARCHAR(255) NULL DEFAULT NULL,
			post_main_cat_ID            int(11) unsigned NOT NULL,
			post_notifications_status   ENUM('noreq','todo','started','finished') NOT NULL DEFAULT 'noreq',
			post_notifications_ctsk_ID  INT(10) unsigned NULL DEFAULT NULL,
			post_views                  INT(11) UNSIGNED NOT NULL DEFAULT 0,
			post_wordcount              int(11) default NULL,
			post_comment_status         ENUM('disabled', 'open', 'closed') NOT NULL DEFAULT 'open',
			post_commentsexpire         DATETIME DEFAULT NULL,
			post_renderers              TEXT NOT NULL,
			post_priority               int(11) unsigned null COMMENT 'Task priority in workflow',
			post_featured               tinyint(1) NOT NULL DEFAULT 0,
			post_order                  DOUBLE NULL,
			post_double1                DOUBLE NULL COMMENT 'Custom double value 1',
			post_double2                DOUBLE NULL COMMENT 'Custom double value 2',
			post_double3                DOUBLE NULL COMMENT 'Custom double value 3',
			post_double4                DOUBLE NULL COMMENT 'Custom double value 4',
			post_double5                DOUBLE NULL COMMENT 'Custom double value 5',
			post_varchar1               VARCHAR(255) NULL COMMENT 'Custom varchar value 1',
			post_varchar2               VARCHAR(255) NULL COMMENT 'Custom varchar value 2',
			post_varchar3               VARCHAR(255) NULL COMMENT 'Custom varchar value 3',
			PRIMARY KEY post_ID( post_ID ),
			UNIQUE post_urltitle( post_urltitle ),
			INDEX post_datestart( post_datestart ),
			INDEX post_main_cat_ID( post_main_cat_ID ),
			INDEX post_creator_user_ID( post_creator_user_ID ),
			INDEX post_status( post_status ),
			INDEX post_parent_ID( post_parent_ID ),
			INDEX post_assigned_user_ID( post_assigned_user_ID ),
			INDEX post_ptyp_ID( post_ptyp_ID ),
			INDEX post_pst_ID( post_pst_ID ),
			INDEX post_order( post_order )
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
			comment_ID            int(11) unsigned NOT NULL auto_increment,
			comment_post_ID       int(11) unsigned NOT NULL default '0',
			comment_type          enum('comment','linkback','trackback','pingback') NOT NULL default 'comment',
			comment_status        ENUM('published','deprecated','protected','private','draft','redirected') DEFAULT 'published' NOT NULL,
			comment_author_ID     int unsigned NULL default NULL,
			comment_author        varchar(100) NULL,
			comment_author_email  varchar(255) NULL,
			comment_author_url    varchar(255) NULL,
			comment_author_IP     varchar(23) NOT NULL default '',
			comment_date          datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
			comment_content       text NOT NULL,
			comment_rating        TINYINT(1) NULL DEFAULT NULL,
			comment_featured      TINYINT(1) NOT NULL DEFAULT 0,
			comment_nofollow      TINYINT(1) NOT NULL DEFAULT 1,
			comment_karma         INT(11) NOT NULL DEFAULT 0,
			comment_spam_karma    TINYINT NULL,
			comment_allow_msgform TINYINT NOT NULL DEFAULT 0,
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

	'T_usersettings' => array(
		'Creating user settings table',
		"CREATE TABLE T_usersettings (
			uset_user_ID INT(11) UNSIGNED NOT NULL,
			uset_name    VARCHAR( 30 ) NOT NULL,
			uset_value   VARCHAR( 255 ) NULL,
			PRIMARY KEY ( uset_user_ID, uset_name )
		)" ),

	'T_items__prerendering' => array(
		'Creating item prerendering cache table',
		'CREATE TABLE T_items__prerendering(
			itpr_itm_ID                   INT(11) UNSIGNED NOT NULL,
			itpr_format                   ENUM(\'htmlbody\', \'entityencoded\', \'xml\', \'text\') NOT NULL,
			itpr_renderers                TEXT NOT NULL,
			itpr_content_prerendered      MEDIUMTEXT NULL,
			itpr_datemodified             TIMESTAMP NOT NULL,
			PRIMARY KEY (itpr_itm_ID, itpr_format)
		)' ),

	'T_items__status' => array(
		'Creating table for Post Statuses',
		"CREATE TABLE T_items__status (
			pst_ID   int(11) unsigned not null AUTO_INCREMENT,
			pst_name varchar(30)      not null,
			primary key ( pst_ID )
		)" ),

	'T_items__type' => array(
		'Creating table for Post Types',
		"CREATE TABLE T_items__type (
			ptyp_ID   int(11) unsigned not null AUTO_INCREMENT,
			ptyp_name varchar(30)      not null,
			primary key (ptyp_ID)
		)" ),

	'T_items__tag' => array(
		'Creating table for Tags',
		"CREATE TABLE T_items__tag (
			tag_ID   int(11) unsigned not null AUTO_INCREMENT,
			tag_name varchar(50)      not null,
			primary key (tag_ID),
			UNIQUE tag_name( tag_name )
		)" ),

	'T_items__itemtag' => array(
		'Creating table for Post-to-Tag relationships',
		"CREATE TABLE T_items__itemtag (
			itag_itm_ID int(11) unsigned NOT NULL,
			itag_tag_ID int(11) unsigned NOT NULL,
			PRIMARY KEY (itag_itm_ID, itag_tag_ID),
			UNIQUE tagitem ( itag_tag_ID, itag_itm_ID )
		)" ),

	'T_files' => array(
		'Creating table for File Meta Data',
		"CREATE TABLE T_files (
			file_ID        int(11) unsigned  not null AUTO_INCREMENT,
			file_root_type enum('absolute','user','collection','shared','skins') not null default 'absolute',
			file_root_ID   int(11) unsigned  not null default 0,
			file_path      varchar(255)      not null default '',
			file_title     varchar(255),
			file_alt       varchar(255),
			file_desc      text,
			primary key (file_ID),
			unique file (file_root_type, file_root_ID, file_path)
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

	'T_coll_user_perms' => array(
		'Creating table for Blog-User permissions',
		"CREATE TABLE T_coll_user_perms (
			bloguser_blog_ID           int(11) unsigned NOT NULL default 0,
			bloguser_user_ID           int(11) unsigned NOT NULL default 0,
			bloguser_ismember          tinyint NOT NULL default 0,
			bloguser_perm_poststatuses set('published','deprecated','protected','private','draft','redirected') NOT NULL default '',
			bloguser_perm_edit         ENUM('no','own','lt','le','all','redirected') NOT NULL default 'no',
			bloguser_perm_delpost      tinyint NOT NULL default 0,
			bloguser_perm_comments     tinyint NOT NULL default 0,
			bloguser_perm_cats         tinyint NOT NULL default 0,
			bloguser_perm_properties   tinyint NOT NULL default 0,
			bloguser_perm_admin        tinyint NOT NULL default 0,
			bloguser_perm_media_upload tinyint NOT NULL default 0,
			bloguser_perm_media_browse tinyint NOT NULL default 0,
			bloguser_perm_media_change tinyint NOT NULL default 0,
			PRIMARY KEY bloguser_pk (bloguser_blog_ID,bloguser_user_ID)
		)" ),

	'T_coll_group_perms' => array(
		'Creating table for blog-group permissions',
		"CREATE TABLE T_coll_group_perms (
			bloggroup_blog_ID           int(11) unsigned NOT NULL default 0,
			bloggroup_group_ID          int(11) unsigned NOT NULL default 0,
			bloggroup_ismember          tinyint NOT NULL default 0,
			bloggroup_perm_poststatuses set('published','deprecated','protected','private','draft','redirected') NOT NULL default '',
			bloggroup_perm_edit         ENUM('no','own','lt','le','all','redirected') NOT NULL default 'no',
			bloggroup_perm_delpost      tinyint NOT NULL default 0,
			bloggroup_perm_comments     tinyint NOT NULL default 0,
			bloggroup_perm_cats         tinyint NOT NULL default 0,
			bloggroup_perm_properties   tinyint NOT NULL default 0,
			bloggroup_perm_admin        tinyint NOT NULL default 0,
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