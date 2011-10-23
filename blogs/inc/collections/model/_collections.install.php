<?php
/**
 * This is the install file for the collections module
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


global $db_storage_charset;


/**
 * The b2evo database scheme.
 *
 * This gets updated through {@link db_delta()} which generates the queries needed to get
 * to this scheme.
 *
 * Please see {@link db_delta()} for things to take care of.
 */
$schema_queries = array_merge( $schema_queries, array(
	'T_skins__skin' => array(
		'Creating table for installed skins',
		"CREATE TABLE T_skins__skin (
				skin_ID      int(10) unsigned                NOT NULL auto_increment,
				skin_name    varchar(32)                     NOT NULL,
				skin_type    enum('normal','feed','sitemap') NOT NULL default 'normal',
				skin_folder  varchar(32)                     NOT NULL,
				PRIMARY KEY skin_ID (skin_ID),
				UNIQUE skin_folder( skin_folder ),
				KEY skin_name( skin_name )
			) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_skins__container' => array(
		'Creating table for skin containers',
		"CREATE TABLE T_skins__container (
				sco_skin_ID   int(10) unsigned      NOT NULL,
				sco_name      varchar(40)           NOT NULL,
				PRIMARY KEY (sco_skin_ID, sco_name)
			) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_blogs' => array(
		'Creating table for Blogs',
		"CREATE TABLE T_blogs (
			blog_ID              int(11) unsigned NOT NULL auto_increment,
			blog_shortname       varchar(255) NULL default '',
			blog_name            varchar(255) NOT NULL default '',
			blog_owner_user_ID   int(11) unsigned NOT NULL default 1,
			blog_advanced_perms  TINYINT(1) NOT NULL default 0,
			blog_tagline         varchar(250) NULL default '',
			blog_description     varchar(250) NULL default '',
			blog_longdesc        TEXT NULL DEFAULT NULL,
			blog_locale          VARCHAR(20) NOT NULL DEFAULT 'en-EU',
			blog_access_type     VARCHAR(10) NOT NULL DEFAULT 'extrapath',
			blog_siteurl         varchar(120) NOT NULL default '',
			blog_urlname         VARCHAR(255) NOT NULL DEFAULT 'urlname',
			blog_notes           TEXT NULL,
			blog_keywords        tinytext,
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
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_coll_settings' => array(
		'Creating collection settings table',
		"CREATE TABLE T_coll_settings (
			cset_coll_ID INT(11) UNSIGNED NOT NULL,
			cset_name    VARCHAR( 50 ) NOT NULL,
			cset_value   VARCHAR( 10000 ) NULL COMMENT 'The AdSense plugin wants to store very long snippets of HTML',
			PRIMARY KEY ( cset_coll_ID, cset_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_widget' => array(
		'Creating components table',
		"CREATE TABLE T_widget (
			wi_ID					INT(10) UNSIGNED auto_increment,
			wi_coll_ID    INT(11) UNSIGNED NOT NULL,
			wi_sco_name   VARCHAR( 40 ) NOT NULL,
			wi_order      INT(10) NOT NULL,
			wi_enabled    TINYINT(1) NOT NULL DEFAULT 1,
			wi_type       ENUM( 'core', 'plugin' ) NOT NULL DEFAULT 'core',
			wi_code       VARCHAR(32) NOT NULL,
			wi_params     TEXT NULL,
			PRIMARY KEY ( wi_ID ),
			UNIQUE wi_order( wi_coll_ID, wi_sco_name, wi_order )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_categories' => array(
		'Creating table for Categories',
		"CREATE TABLE T_categories (
			cat_ID          int(10) unsigned NOT NULL auto_increment,
			cat_parent_ID   int(10) unsigned NULL,
			cat_name        varchar(255) NOT NULL,
			cat_urlname     varchar(255) NOT NULL,
			cat_blog_ID     int(10) unsigned NOT NULL default 2,
			cat_description varchar(255) NULL DEFAULT NULL,
			cat_order       int(11) NULL DEFAULT NULL,
			PRIMARY KEY cat_ID (cat_ID),
			UNIQUE cat_urlname( cat_urlname ),
			KEY cat_blog_ID (cat_blog_ID),
			KEY cat_parent_ID (cat_parent_ID),
			KEY cat_order (cat_order)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	// Add post_hideteaser field for adding checkbox for hide teaser
	'T_items__item' => array(
		'Creating table for Posts',
		"CREATE TABLE T_items__item (
			post_ID                     int(11) unsigned NOT NULL auto_increment,
			post_parent_ID              int(11) unsigned NULL,
			post_creator_user_ID        int(11) unsigned NOT NULL,
			post_lastedit_user_ID       int(11) unsigned NULL,
			post_assigned_user_ID       int(11) unsigned NULL,
			post_dateset                tinyint(1) NOT NULL DEFAULT 1,
			post_datestart              DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00',
			post_datedeadline           datetime NULL,
			post_datecreated            datetime NULL,
			post_datemodified           DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00',
			post_status                 enum('published','deprecated','protected','private','draft','redirected') NOT NULL default 'published',
			post_pst_ID                 int(11) unsigned NULL,
			post_ptyp_ID                int(10) unsigned NOT NULL DEFAULT 1,
			post_locale                 VARCHAR(20) NOT NULL DEFAULT 'en-EU',
			post_content                MEDIUMTEXT NULL,
			post_excerpt                text NULL,
			post_excerpt_autogenerated  TINYINT(1) NULL DEFAULT NULL,
			post_title                  text NOT NULL,
			post_urltitle               VARCHAR(210) NOT NULL,
			post_canonical_slug_ID      int(10) unsigned NULL DEFAULT NULL,
			post_tiny_slug_ID           int(10) unsigned NULL DEFAULT NULL,
			post_titletag               VARCHAR(255) NULL DEFAULT NULL,
			post_metadesc               VARCHAR(255) NULL DEFAULT NULL,
			post_metakeywords           VARCHAR(255) NULL DEFAULT NULL,
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
			post_hideteaser             tinyint(1) NOT NULL DEFAULT 0,
			post_order                  DOUBLE NULL,
			post_double1                DOUBLE NULL COMMENT 'Custom double value 1',
			post_double2                DOUBLE NULL COMMENT 'Custom double value 2',
			post_double3                DOUBLE NULL COMMENT 'Custom double value 3',
			post_double4                DOUBLE NULL COMMENT 'Custom double value 4',
			post_double5                DOUBLE NULL COMMENT 'Custom double value 5',
			post_varchar1               VARCHAR(255) NULL COMMENT 'Custom varchar value 1',
			post_varchar2               VARCHAR(255) NULL COMMENT 'Custom varchar value 2',
			post_varchar3               VARCHAR(255) NULL COMMENT 'Custom varchar value 3',
			post_editor_code						VARCHAR(32) NULL COMMENT 'Plugin code of the editor used to edit this post',
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
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_postcats' => array(
		'Creating table for Categories-to-Posts relationships',
		"CREATE TABLE T_postcats (
			postcat_post_ID int(11) unsigned NOT NULL,
			postcat_cat_ID int(11) unsigned NOT NULL,
			PRIMARY KEY postcat_pk (postcat_post_ID,postcat_cat_ID),
			UNIQUE catpost ( postcat_cat_ID, postcat_post_ID )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_comments' => array(	// Note: pingbacks no longer supported, but previous pingbacks are to be preserved in the DB
		'Creating table for Comments',
		"CREATE TABLE T_comments (
			comment_ID                 int(11) unsigned NOT NULL auto_increment,
			comment_post_ID            int(11) unsigned NOT NULL default '0',
			comment_type               enum('comment','linkback','trackback','pingback') NOT NULL default 'comment',
			comment_status             ENUM('published','deprecated','draft','trash') DEFAULT 'published' NOT NULL,
			comment_in_reply_to_cmt_ID INT(10) unsigned NULL,
			comment_author_ID          int unsigned NULL default NULL,
			comment_author             varchar(100) NULL,
			comment_author_email       varchar(255) NULL,
			comment_author_url         varchar(255) NULL,
			comment_author_IP          varchar(23) NOT NULL default '',
			comment_date               datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
			comment_content            text NOT NULL,
			comment_rating             TINYINT(1) NULL DEFAULT NULL,
			comment_featured           TINYINT(1) NOT NULL DEFAULT 0,
			comment_nofollow           TINYINT(1) NOT NULL DEFAULT 1,
			comment_helpful_addvotes   INT NOT NULL default 0,
			comment_helpful_countvotes INT unsigned NOT NULL default 0,
			comment_spam_addvotes      INT NOT NULL default 0,
			comment_spam_countvotes    INT unsigned NOT NULL default 0,
			comment_karma              INT(11) NOT NULL DEFAULT 0,
			comment_spam_karma         TINYINT NULL,
			comment_allow_msgform      TINYINT NOT NULL DEFAULT 0,
			comment_secret             varchar(32) NULL default NULL,
			comment_notif_status       ENUM('noreq','todo','started','finished') NOT NULL DEFAULT 'noreq' COMMENT 'Have notifications been sent for this comment? How far are we in the process?',
			comment_notif_ctsk_ID      INT(10) unsigned NULL DEFAULT NULL COMMENT 'When notifications for this comment are sent through a scheduled job, what is the job ID?',
			PRIMARY KEY comment_ID (comment_ID),
			KEY comment_post_ID (comment_post_ID),
			KEY comment_date (comment_date),
			KEY comment_type (comment_type)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_comments__votes' => array(
		'Creating table for Comments Votes',
		"CREATE TABLE T_comments__votes (
			cmvt_cmt_ID  int(10) unsigned NOT NULL,
			cmvt_user_ID int(10) unsigned NOT NULL,
			cmvt_helpful TINYINT(1) NULL DEFAULT NULL,
			cmvt_spam    TINYINT(1) NULL DEFAULT NULL,
			PRIMARY KEY (cmvt_cmt_ID, cmvt_user_ID),
			KEY cmvt_cmt_ID (cmvt_cmt_ID),
			KEY cmvt_user_ID (cmvt_user_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__prerendering' => array(
		'Creating item prerendering cache table',
		"CREATE TABLE T_items__prerendering(
			itpr_itm_ID                   INT(11) UNSIGNED NOT NULL,
			itpr_format                   ENUM('htmlbody','entityencoded','xml','text') NOT NULL,
			itpr_renderers                TEXT NOT NULL,
			itpr_content_prerendered      MEDIUMTEXT NULL,
			itpr_datemodified             TIMESTAMP NOT NULL,
			PRIMARY KEY (itpr_itm_ID, itpr_format)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__version' => array(	// fp> made iver_edit_user_ID NULL because of INSERT INTO SELECT statement that can try to write NULL
		'Creating item versions table',
		"CREATE TABLE T_items__version (
			iver_itm_ID        INT UNSIGNED NOT NULL ,
			iver_edit_user_ID  INT UNSIGNED NULL ,
			iver_edit_datetime DATETIME NOT NULL ,
			iver_status        ENUM('published','deprecated','protected','private','draft','redirected') NULL ,
			iver_title         TEXT NULL ,
			iver_content       MEDIUMTEXT NULL ,
			INDEX iver_itm_ID ( iver_itm_ID )
		) ENGINE = innodb ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__status' => array(
		'Creating table for Post Statuses',
		"CREATE TABLE T_items__status (
			pst_ID   int(11) unsigned not null AUTO_INCREMENT,
			pst_name varchar(30)      not null,
			primary key ( pst_ID )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__type' => array(
		'Creating table for Post Types',
		"CREATE TABLE T_items__type (
			ptyp_ID   int(11) unsigned not null auto_increment,
			ptyp_name varchar(30)      not null,
			primary key (ptyp_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__tag' => array(
		'Creating table for Tags',
		"CREATE TABLE T_items__tag (
			tag_ID   int(11) unsigned not null AUTO_INCREMENT,
			tag_name varbinary(50) not null,
			primary key (tag_ID),
			UNIQUE tag_name( tag_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__itemtag' => array(
		'Creating table for Post-to-Tag relationships',
		"CREATE TABLE T_items__itemtag (
			itag_itm_ID int(11) unsigned NOT NULL,
			itag_tag_ID int(11) unsigned NOT NULL,
			PRIMARY KEY (itag_itm_ID, itag_tag_ID),
			UNIQUE tagitem ( itag_tag_ID, itag_itm_ID )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__subscriptions' => array(
		'Creating table for subscriptions to individual blog posts',
		"CREATE TABLE T_items__subscriptions (
			isub_item_ID    int(11) unsigned NOT NULL,
			isub_user_ID    int(11) unsigned NOT NULL,
			isub_comments   tinyint(1) NOT NULL DEFAULT 0 COMMENT 'The user wants to receive notifications for new comments on this post',
			PRIMARY KEY (isub_item_ID, isub_user_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__item_settings' => array(
		'Creating item settings table',
		"CREATE TABLE T_items__item_settings (
			iset_item_ID  int(10) unsigned NOT NULL,
			iset_name     varchar( 50 ) NOT NULL,
			iset_value    varchar( 2000 ) NULL,
			PRIMARY KEY ( iset_item_ID, iset_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_subscriptions' => array(
		'Creating table for subscriptions',
		"CREATE TABLE T_subscriptions (
			sub_coll_ID     int(11) unsigned    not null,
			sub_user_ID     int(11) unsigned    not null,
			sub_items       tinyint(1)          not null,
			sub_comments    tinyint(1)          not null,
			primary key (sub_coll_ID, sub_user_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_coll_user_perms' => array(
		'Creating table for Blog-User permissions',
		"CREATE TABLE T_coll_user_perms (
			bloguser_blog_ID             int(11) unsigned NOT NULL default 0,
			bloguser_user_ID             int(11) unsigned NOT NULL default 0,
			bloguser_ismember            tinyint NOT NULL default 0,
			bloguser_perm_poststatuses   set('published','deprecated','protected','private','draft','redirected') NOT NULL default '',
			bloguser_perm_edit           ENUM('no','own','lt','le','all','redirected') NOT NULL default 'no',
			bloguser_perm_delpost        tinyint NOT NULL default 0,
			bloguser_perm_edit_ts        tinyint NOT NULL default 0,
			bloguser_perm_own_cmts       tinyint NOT NULL default 0,
			bloguser_perm_vote_spam_cmts tinyint NOT NULL default 0,
			bloguser_perm_draft_cmts     tinyint NOT NULL default 0,
			bloguser_perm_publ_cmts      tinyint NOT NULL default 0,
			bloguser_perm_depr_cmts      tinyint NOT NULL default 0,
			bloguser_perm_cats           tinyint NOT NULL default 0,
			bloguser_perm_properties     tinyint NOT NULL default 0,
			bloguser_perm_admin          tinyint NOT NULL default 0,
			bloguser_perm_media_upload   tinyint NOT NULL default 0,
			bloguser_perm_media_browse   tinyint NOT NULL default 0,
			bloguser_perm_media_change   tinyint NOT NULL default 0,
			bloguser_perm_page           tinyint NOT NULL default 0,
			bloguser_perm_intro          tinyint NOT NULL default 0,
			bloguser_perm_podcast        tinyint NOT NULL default 0,
			bloguser_perm_sidebar        tinyint NOT NULL default 0,
			PRIMARY KEY bloguser_pk (bloguser_blog_ID,bloguser_user_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_coll_group_perms' => array(
		'Creating table for blog-group permissions',
		"CREATE TABLE T_coll_group_perms (
			bloggroup_blog_ID             int(11) unsigned NOT NULL default 0,
			bloggroup_group_ID            int(11) unsigned NOT NULL default 0,
			bloggroup_ismember            tinyint NOT NULL default 0,
			bloggroup_perm_poststatuses   set('published','deprecated','protected','private','draft','redirected') NOT NULL default '',
			bloggroup_perm_edit           ENUM('no','own','lt','le','all','redirected') NOT NULL default 'no',
			bloggroup_perm_delpost        tinyint NOT NULL default 0,
			bloggroup_perm_edit_ts        tinyint NOT NULL default 0,
			bloggroup_perm_own_cmts       tinyint NOT NULL default 0,
			bloggroup_perm_vote_spam_cmts tinyint NOT NULL default 0,
			bloggroup_perm_draft_cmts     tinyint NOT NULL default 0,
			bloggroup_perm_publ_cmts      tinyint NOT NULL default 0,
			bloggroup_perm_depr_cmts      tinyint NOT NULL default 0,
			bloggroup_perm_cats           tinyint NOT NULL default 0,
			bloggroup_perm_properties     tinyint NOT NULL default 0,
			bloggroup_perm_admin          tinyint NOT NULL default 0,
			bloggroup_perm_media_upload   tinyint NOT NULL default 0,
			bloggroup_perm_media_browse   tinyint NOT NULL default 0,
			bloggroup_perm_media_change   tinyint NOT NULL default 0,
			bloggroup_perm_page           tinyint NOT NULL default 0,
			bloggroup_perm_intro          tinyint NOT NULL default 0,
			bloggroup_perm_podcast        tinyint NOT NULL default 0,
			bloggroup_perm_sidebar        tinyint NOT NULL default 0,
			PRIMARY KEY bloggroup_pk (bloggroup_blog_ID,bloggroup_group_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_links' => array(
		'Creating table for Post Links',
		"CREATE TABLE T_links (
			link_ID               int(11) unsigned  not null AUTO_INCREMENT,
			link_datecreated      datetime          not null DEFAULT '2000-01-01 00:00:00',
			link_datemodified     datetime          not null DEFAULT '2000-01-01 00:00:00',
			link_creator_user_ID  int(11) unsigned  NULL,
			link_lastedit_user_ID int(11) unsigned  NULL,
			link_itm_ID           int(11) unsigned  NULL,
			link_cmt_ID           int(11) unsigned  NULL COMMENT 'Used for linking files to comments (comment attachments)',
			link_dest_itm_ID      int(11) unsigned  NULL,
			link_file_ID          int(11) unsigned  NULL,
			link_ltype_ID         int(11) unsigned  NOT NULL default 1,
			link_external_url     VARCHAR(255)      NULL,
			link_title            TEXT              NULL,
			link_position         varchar(10)       NOT NULL,
			link_order            int(11) unsigned  NOT NULL,
			PRIMARY KEY (link_ID),
			UNIQUE link_itm_ID_order (link_itm_ID, link_order),
			INDEX link_itm_ID( link_itm_ID ),
			INDEX link_cmt_ID( link_cmt_ID ),
			INDEX link_dest_itm_ID (link_dest_itm_ID),
			INDEX link_file_ID (link_file_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),
) );

/*
 * $Log$
 * Revision 1.46  2011/10/23 09:19:42  efy-yurybakh
 * Implement new permission for comment editing
 *
 * Revision 1.45  2011/09/27 13:30:14  efy-yurybakh
 * spam vote checkbox
 *
 * Revision 1.44  2011/09/25 07:06:21  efy-yurybakh
 * Implement new permission for spam voting
 *
 * Revision 1.43  2011/09/23 14:01:58  fplanque
 * Quick/temporary fixes so we can work in the meantime
 *
 * Revision 1.42  2011/09/22 05:03:11  efy-yurybakh
 * 4 new fileds in the table T_comments
 *
 * Revision 1.41  2011/09/22 03:20:54  fplanque
 * minor
 *
 * Revision 1.40  2011/09/21 13:01:09  efy-yurybakh
 * feature "Was this comment helpful?"
 *
 * Revision 1.39  2011/09/19 23:23:43  fplanque
 * Db fixes
 *
 * Revision 1.38  2011/09/17 22:16:05  fplanque
 * cleanup
 *
 * Revision 1.37  2011/09/10 00:57:23  fplanque
 * doc
 *
 * Revision 1.36  2011/09/08 17:58:08  lxndral
 * Comments task fix (table sql fix)
 *
 * Revision 1.35  2011/09/08 05:22:40  efy-asimo
 * Remove item attending and add item settings
 *
 * Revision 1.34  2011/09/04 22:13:14  fplanque
 * copyright 2011
 *
 * Revision 1.33  2011/09/04 21:32:16  fplanque
 * minor MFB 4-1
 *
 * Revision 1.32  2011/08/25 07:31:14  efy-asimo
 * DB documentation
 *
 * Revision 1.31  2011/08/25 02:54:12  efy-james
 * Add checkbox for no teaser
 *
 * Revision 1.30  2011/08/25 01:02:10  fplanque
 * doc/minor
 *
 * Revision 1.24  2011/03/03 12:47:29  efy-asimo
 * comments attachments
 *
 * Revision 1.23  2011/03/02 09:45:59  efy-asimo
 * Update collection features allow_comments, disable_comments_bypost, allow_attachments, allow_rating
 *
 * Revision 1.22  2011/02/14 14:13:24  efy-asimo
 * Comments trash status
 *
 * Revision 1.21  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.17.2.6  2010/10/19 01:04:48  fplanque
 * doc
 *
 * Revision 1.3  2009/08/30 12:31:44  tblue246
 * Fixed CVS keywords
 *
 * Revision 1.1  2009/08/30 00:34:15  fplanque
 * increased modularity
 *
 */
?>