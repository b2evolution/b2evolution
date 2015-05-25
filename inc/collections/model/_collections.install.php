<?php
/**
 * This is the install file for the collections module
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
				skin_ID      int(10) unsigned NOT NULL auto_increment,
				skin_name    varchar(32) NOT NULL,
				skin_type    enum('normal','feed','sitemap','mobile','tablet') COLLATE ascii_general_ci NOT NULL default 'normal',
				skin_folder  varchar(32) NOT NULL,
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
			blog_shortdesc       varchar(250) NULL default '',
			blog_longdesc        TEXT NULL DEFAULT NULL,
			blog_locale          VARCHAR(20) NOT NULL DEFAULT 'en-EU',
			blog_access_type     VARCHAR(10) COLLATE ascii_general_ci NOT NULL DEFAULT 'extrapath',
			blog_siteurl         varchar(120) NOT NULL default '',
			blog_urlname         VARCHAR(255) COLLATE ascii_general_ci NOT NULL DEFAULT 'urlname',
			blog_notes           TEXT NULL,
			blog_keywords        tinytext,
			blog_allowtrackbacks TINYINT(1) NOT NULL default 0,
			blog_allowblogcss    TINYINT(1) NOT NULL default 1,
			blog_allowusercss    TINYINT(1) NOT NULL default 1,
			blog_in_bloglist     ENUM( 'public', 'logged', 'member', 'never' ) COLLATE ascii_general_ci DEFAULT 'public' NOT NULL,
			blog_links_blog_ID   INT(11) NULL DEFAULT NULL,
			blog_media_location  ENUM( 'default', 'subdir', 'custom', 'none' ) COLLATE ascii_general_ci DEFAULT 'default' NOT NULL,
			blog_media_subdir    VARCHAR( 255 ) NULL,
			blog_media_fullpath  VARCHAR( 255 ) NULL,
			blog_media_url       VARCHAR( 255 ) NULL,
			blog_type            ENUM( 'main', 'std', 'photo', 'group', 'forum', 'manual' ) COLLATE ascii_general_ci DEFAULT 'std' NOT NULL,
			blog_order           int(11) NULL DEFAULT NULL,
			blog_favorite        TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY blog_ID (blog_ID),
			UNIQUE KEY blog_urlname (blog_urlname)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_coll_settings' => array(
		'Creating collection settings table',
		"CREATE TABLE T_coll_settings (
			cset_coll_ID INT(11) UNSIGNED NOT NULL,
			cset_name    VARCHAR( 50 ) COLLATE ascii_general_ci NOT NULL,
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
			wi_type       ENUM( 'core', 'plugin' ) COLLATE ascii_general_ci NOT NULL DEFAULT 'core',
			wi_code       VARCHAR(32) COLLATE ascii_general_ci NOT NULL,
			wi_params     TEXT NULL,
			PRIMARY KEY ( wi_ID ),
			UNIQUE wi_order( wi_coll_ID, wi_sco_name, wi_order )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_categories' => array(
		'Creating table for Categories',
		"CREATE TABLE T_categories (
			cat_ID              int(10) unsigned NOT NULL auto_increment,
			cat_parent_ID       int(10) unsigned NULL,
			cat_name            varchar(255) NOT NULL,
			cat_urlname         varchar(255) COLLATE ascii_general_ci NOT NULL,
			cat_blog_ID         int(10) unsigned NOT NULL default 2,
			cat_description     varchar(255) NULL DEFAULT NULL,
			cat_order           int(11) NULL DEFAULT NULL,
			cat_subcat_ordering enum('parent', 'alpha', 'manual') COLLATE ascii_general_ci NULL DEFAULT NULL,
			cat_meta            tinyint(1) NOT NULL DEFAULT 0,
			cat_lock            tinyint(1) NOT NULL DEFAULT 0,
			cat_last_touched_ts TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
			PRIMARY KEY cat_ID (cat_ID),
			UNIQUE cat_urlname( cat_urlname ),
			KEY cat_blog_ID (cat_blog_ID),
			KEY cat_parent_ID (cat_parent_ID),
			KEY cat_order (cat_order)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

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
			post_datecreated            TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
			post_datemodified           TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
			post_last_touched_ts        TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
			post_status                 enum('published','community','deprecated','protected','private','review','draft','redirected') COLLATE ascii_general_ci NOT NULL default 'published',
			post_pst_ID                 int(11) unsigned NULL,
			post_ityp_ID                int(10) unsigned NOT NULL DEFAULT 1,
			post_locale                 VARCHAR(20) NOT NULL DEFAULT 'en-EU',
			post_content                MEDIUMTEXT NULL,
			post_excerpt                text NULL,
			post_excerpt_autogenerated  TINYINT(1) NULL DEFAULT NULL,
			post_title                  VARCHAR(255) NOT NULL,"/* Do NOT change this field back to TEXT without a very good reason. */."
			post_urltitle               VARCHAR(210) COLLATE ascii_general_ci NOT NULL,
			post_canonical_slug_ID      int(10) unsigned NULL DEFAULT NULL,
			post_tiny_slug_ID           int(10) unsigned NULL DEFAULT NULL,
			post_titletag               VARCHAR(255) NULL DEFAULT NULL,
			post_url                    VARCHAR(255) NULL DEFAULT NULL,
			post_main_cat_ID            int(11) unsigned NOT NULL,
			post_notifications_status   ENUM('noreq','todo','started','finished') COLLATE ascii_general_ci NOT NULL DEFAULT 'noreq',
			post_notifications_ctsk_ID  INT(10) unsigned NULL DEFAULT NULL,
			post_wordcount              int(11) default NULL,
			post_comment_status         ENUM('disabled', 'open', 'closed') COLLATE ascii_general_ci NOT NULL DEFAULT 'open',
			post_renderers              VARCHAR(255) COLLATE ascii_general_ci NOT NULL,"/* Do NOT change this field back to TEXT without a very good reason. */."
			post_priority               int(11) unsigned null COMMENT 'Task priority in workflow',
			post_featured               tinyint(1) NOT NULL DEFAULT 0,
			post_order                  DOUBLE NULL,
			post_ctry_ID                INT(10) UNSIGNED NULL,
			post_rgn_ID                 INT(10) UNSIGNED NULL,
			post_subrg_ID               INT(10) UNSIGNED NULL,
			post_city_ID                INT(10) UNSIGNED NULL,
			PRIMARY KEY post_ID( post_ID ),
			UNIQUE post_urltitle( post_urltitle ),
			INDEX post_datestart( post_datestart ),
			INDEX post_main_cat_ID( post_main_cat_ID ),
			INDEX post_creator_user_ID( post_creator_user_ID ),
			INDEX post_status( post_status ),
			INDEX post_parent_ID( post_parent_ID ),
			INDEX post_assigned_user_ID( post_assigned_user_ID ),
			INDEX post_ityp_ID( post_ityp_ID ),
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
			comment_item_ID            int(11) unsigned NOT NULL default 0,
			comment_type               enum('comment','linkback','trackback','pingback','meta') COLLATE ascii_general_ci NOT NULL default 'comment',
			comment_status             ENUM('published','community','deprecated','protected','private','review','draft','trash') COLLATE ascii_general_ci DEFAULT 'published' NOT NULL,
			comment_in_reply_to_cmt_ID INT(10) unsigned NULL,
			comment_author_user_ID     int unsigned NULL default NULL,
			comment_author             varchar(100) NULL,
			comment_author_email       varchar(255) COLLATE ascii_general_ci NULL,
			comment_author_url         varchar(255) NULL,
			comment_author_IP          varchar(45) COLLATE ascii_general_ci NOT NULL default '',"/* IPv4 mapped IPv6 addresses maximum length is 45 chars: ex. ABCD:ABCD:ABCD:ABCD:ABCD:ABCD:192.168.158.190 */."
			comment_IP_ctry_ID         int(10) unsigned NULL,
			comment_date               datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
			comment_last_touched_ts    TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
			comment_content            text NOT NULL,
			comment_renderers          VARCHAR(255) COLLATE ascii_general_ci NOT NULL,"/* Do NOT change this field back to TEXT without a very good reason. */."
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
			comment_secret             CHAR(32) COLLATE ascii_general_ci NULL default NULL,
			comment_notif_status       ENUM('noreq','todo','started','finished') COLLATE ascii_general_ci NOT NULL DEFAULT 'noreq' COMMENT 'Have notifications been sent for this comment? How far are we in the process?',
			comment_notif_ctsk_ID      INT(10) unsigned NULL DEFAULT NULL COMMENT 'When notifications for this comment are sent through a scheduled job, what is the job ID?',
			PRIMARY KEY comment_ID (comment_ID),
			KEY comment_item_ID (comment_item_ID),
			KEY comment_date (comment_date),
			KEY comment_type (comment_type),
			KEY comment_status(comment_status)
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
			itpr_format                   ENUM('htmlbody','entityencoded','xml','text') COLLATE ascii_general_ci NOT NULL,
			itpr_renderers                VARCHAR(255) COLLATE ascii_general_ci NOT NULL,"/* Do NOT change this field back to TEXT without a very good reason. */."
			itpr_content_prerendered      MEDIUMTEXT NULL,
			itpr_datemodified             TIMESTAMP NOT NULL,
			PRIMARY KEY (itpr_itm_ID, itpr_format)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_comments__prerendering' => array(
		'Creating comment prerendering cache table',
		"CREATE TABLE T_comments__prerendering(
			cmpr_cmt_ID                   INT(11) UNSIGNED NOT NULL,
			cmpr_format                   ENUM('htmlbody','entityencoded','xml','text') COLLATE ascii_general_ci NOT NULL,
			cmpr_renderers                VARCHAR(255) COLLATE ascii_general_ci NOT NULL,"/* Do NOT change this field back to TEXT without a very good reason. */."
			cmpr_content_prerendered      MEDIUMTEXT NULL,
			cmpr_datemodified             TIMESTAMP NOT NULL,
			PRIMARY KEY (cmpr_cmt_ID, cmpr_format)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__version' => array(	// fp> made iver_edit_user_ID NULL because of INSERT INTO SELECT statement that can try to write NULL
		'Creating item versions table',
		"CREATE TABLE T_items__version (
			iver_ID            INT UNSIGNED NOT NULL,
			iver_itm_ID        INT UNSIGNED NOT NULL,
			iver_edit_user_ID  INT UNSIGNED NULL,
			iver_edit_datetime DATETIME NOT NULL,
			iver_status        ENUM('published','community','deprecated','protected','private','review','draft','redirected') COLLATE ascii_general_ci NULL,
			iver_title         VARCHAR(255) NULL,"/* Do NOT change this field back to TEXT without a very good reason. */."
			iver_content       MEDIUMTEXT NULL,
			INDEX iver_ID_itm_ID ( iver_ID , iver_itm_ID )
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
			ityp_ID                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			ityp_name              VARCHAR(30) NOT NULL,
			ityp_description       TEXT NULL DEFAULT NULL,
			ityp_backoffice_tab    VARCHAR(30) NULL DEFAULT NULL,
			ityp_template_name     VARCHAR(40) NULL DEFAULT NULL,
			ityp_use_title         ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'required',
			ityp_use_url           ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'optional',
			ityp_use_text          ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'optional',
			ityp_allow_html        TINYINT DEFAULT 1,
			ityp_allow_attachments TINYINT DEFAULT 1,
			ityp_use_excerpt       ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'optional',
			ityp_use_title_tag     ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'optional',
			ityp_use_meta_desc     ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'optional',
			ityp_use_meta_keywds   ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'optional',
			ityp_use_tags          ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'optional',
			ityp_allow_featured    TINYINT DEFAULT 1,
			ityp_use_country       ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'never',
			ityp_use_region        ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'never',
			ityp_use_sub_region    ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'never',
			ityp_use_city          ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'never',
			ityp_use_coordinates   ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'never',
			ityp_use_custom_fields TINYINT DEFAULT 1,
			ityp_use_comments      TINYINT DEFAULT 1,
			ityp_allow_closing_comments   TINYINT DEFAULT 1,
			ityp_allow_disabling_comments TINYINT DEFAULT 0,
			ityp_use_comment_expiration   ENUM( 'required', 'optional', 'never' ) COLLATE ascii_general_ci DEFAULT 'optional',
			PRIMARY KEY ( ityp_ID )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__type_custom_field' => array(
		'Creating table for custom fields of Post Types',
		"CREATE TABLE T_items__type_custom_field (
			itcf_ID      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			itcf_ityp_ID INT(11) UNSIGNED NOT NULL,
			itcf_label   VARCHAR(255) NOT NULL,
			itcf_name    VARCHAR(255) COLLATE ascii_general_ci NOT NULL,
			itcf_type    ENUM( 'double', 'varchar' ) COLLATE ascii_general_ci NOT NULL,
			itcf_order   INT NULL,
			PRIMARY KEY ( itcf_ID ),
			UNIQUE itcf_ityp_ID_name( itcf_ityp_ID, itcf_name )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_items__tag' => array(
		'Creating table for Tags',
		"CREATE TABLE T_items__tag (
			tag_ID   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			tag_name VARCHAR(50) COLLATE utf8_bin NOT NULL,
			PRIMARY KEY (tag_ID),
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
			iset_name     varchar( 50 ) COLLATE ascii_general_ci NOT NULL,
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

	// Important if you change the perm_poststatuses or perm_cmtstatuses set content or order you must change the get_statuse_permvalue() content respectively
	'T_coll_user_perms' => array(
		'Creating table for Blog-User permissions',
		"CREATE TABLE T_coll_user_perms (
			bloguser_blog_ID              int(11) unsigned NOT NULL default 0,
			bloguser_user_ID              int(11) unsigned NOT NULL default 0,
			bloguser_ismember             tinyint NOT NULL default 0,
			bloguser_can_be_assignee      tinyint NOT NULL default 0,
			bloguser_perm_poststatuses    set('review','draft','private','protected','deprecated','community','published','redirected') COLLATE ascii_general_ci NOT NULL default '',
			bloguser_perm_edit            ENUM('no','own','lt','le','all') COLLATE ascii_general_ci NOT NULL default 'no',
			bloguser_perm_delpost         tinyint NOT NULL default 0,
			bloguser_perm_edit_ts         tinyint NOT NULL default 0,
			bloguser_perm_delcmts         tinyint NOT NULL default 0,
			bloguser_perm_recycle_owncmts tinyint NOT NULL default 0,
			bloguser_perm_vote_spam_cmts  tinyint NOT NULL default 0,
			bloguser_perm_cmtstatuses     set('review','draft','private','protected','deprecated','community','published') COLLATE ascii_general_ci NOT NULL default '',
			bloguser_perm_edit_cmt        ENUM('no','own','anon','lt','le','all') COLLATE ascii_general_ci NOT NULL default 'no',
			bloguser_perm_cats            tinyint NOT NULL default 0,
			bloguser_perm_properties      tinyint NOT NULL default 0,
			bloguser_perm_admin           tinyint NOT NULL default 0,
			bloguser_perm_media_upload    tinyint NOT NULL default 0,
			bloguser_perm_media_browse    tinyint NOT NULL default 0,
			bloguser_perm_media_change    tinyint NOT NULL default 0,
			bloguser_perm_page            tinyint NOT NULL default 0,
			bloguser_perm_intro           tinyint NOT NULL default 0,
			bloguser_perm_podcast         tinyint NOT NULL default 0,
			bloguser_perm_sidebar         tinyint NOT NULL default 0,
			PRIMARY KEY bloguser_pk (bloguser_blog_ID,bloguser_user_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	// Important if you change the perm_poststatuses or perm_cmtstatuses set content or order you must change the get_status_permvalue() content respectively
	'T_coll_group_perms' => array(
		'Creating table for blog-group permissions',
		"CREATE TABLE T_coll_group_perms (
			bloggroup_blog_ID              int(11) unsigned NOT NULL default 0,
			bloggroup_group_ID             int(11) unsigned NOT NULL default 0,
			bloggroup_ismember             tinyint NOT NULL default 0,
			bloggroup_can_be_assignee      tinyint NOT NULL default 0,
			bloggroup_perm_poststatuses    set('review','draft','private','protected','deprecated','community','published','redirected') COLLATE ascii_general_ci NOT NULL default '',
			bloggroup_perm_edit            ENUM('no','own','lt','le','all') COLLATE ascii_general_ci NOT NULL default 'no',
			bloggroup_perm_delpost         tinyint NOT NULL default 0,
			bloggroup_perm_edit_ts         tinyint NOT NULL default 0,
			bloggroup_perm_delcmts         tinyint NOT NULL default 0,
			bloggroup_perm_recycle_owncmts tinyint NOT NULL default 0,
			bloggroup_perm_vote_spam_cmts  tinyint NOT NULL default 0,
			bloggroup_perm_cmtstatuses     set('review','draft','private','protected','deprecated','community','published') COLLATE ascii_general_ci NOT NULL default '',
			bloggroup_perm_edit_cmt        ENUM('no','own','anon','lt','le','all') COLLATE ascii_general_ci NOT NULL default 'no',
			bloggroup_perm_cats            tinyint NOT NULL default 0,
			bloggroup_perm_properties      tinyint NOT NULL default 0,
			bloggroup_perm_admin           tinyint NOT NULL default 0,
			bloggroup_perm_media_upload    tinyint NOT NULL default 0,
			bloggroup_perm_media_browse    tinyint NOT NULL default 0,
			bloggroup_perm_media_change    tinyint NOT NULL default 0,
			bloggroup_perm_page            tinyint NOT NULL default 0,
			bloggroup_perm_intro           tinyint NOT NULL default 0,
			bloggroup_perm_podcast         tinyint NOT NULL default 0,
			bloggroup_perm_sidebar         tinyint NOT NULL default 0,
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
			link_usr_ID           int(11) unsigned  NULL COMMENT 'Used for linking files to users (user profile picture)',
			link_file_ID          int(11) unsigned  NULL,
			link_ltype_ID         int(11) unsigned  NOT NULL default 1,
			link_position         varchar(10) COLLATE ascii_general_ci NOT NULL,
			link_order            int(11) unsigned  NOT NULL,
			PRIMARY KEY (link_ID),
			UNIQUE link_itm_ID_order (link_itm_ID, link_order),
			INDEX link_itm_ID( link_itm_ID ),
			INDEX link_cmt_ID( link_cmt_ID ),
			INDEX link_usr_ID( link_usr_ID ),
			INDEX link_file_ID (link_file_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_links__vote' => array(
		'Creating table for File Links Votes',
		"CREATE TABLE T_links__vote (
			lvot_link_ID       int(11) UNSIGNED NOT NULL,
			lvot_user_ID       int(11) UNSIGNED NOT NULL,
			lvot_like          tinyint(1),
			lvot_inappropriate tinyint(1),
			lvot_spam          tinyint(1),
			primary key (lvot_link_ID, lvot_user_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),
) );

?>