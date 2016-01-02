<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package messaging
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
$schema_queries['T_messaging__thread'] = array(
		'Creating table for message threads',
		"CREATE TABLE T_messaging__thread (
			thrd_ID int(10) unsigned NOT NULL auto_increment,
			thrd_title varchar(255) NOT NULL,
			thrd_datemodified datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
			PRIMARY KEY thrd_ID (thrd_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

$schema_queries['T_messaging__message'] = array(
		'Creating table for messages',
		"CREATE TABLE T_messaging__message (
			msg_ID int(10) unsigned NOT NULL auto_increment,
			msg_author_user_ID int(10) unsigned NOT NULL,
			msg_datetime datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
			msg_thread_ID int(10) unsigned NOT NULL,
			msg_text text,
			msg_renderers VARCHAR(255) COLLATE ascii_general_ci NOT NULL,"/* Do NOT change this field back to TEXT without a very good reason. */."
			PRIMARY KEY msg_ID (msg_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

$schema_queries['T_messaging__prerendering'] = array(
		'Creating message prerendering cache table',
		"CREATE TABLE T_messaging__prerendering(
			mspr_msg_ID              INT(11) UNSIGNED NOT NULL,
			mspr_format              ENUM('htmlbody','entityencoded','xml','text') COLLATE ascii_general_ci NOT NULL,
			mspr_renderers           VARCHAR(255) COLLATE ascii_general_ci NOT NULL,"/* Do NOT change this field back to TEXT without a very good reason. */."
			mspr_content_prerendered MEDIUMTEXT NULL,
			mspr_datemodified        TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
			PRIMARY KEY (mspr_msg_ID, mspr_format)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

// index on tsta_user_ID field
$schema_queries['T_messaging__threadstatus'] = array(
		'Creating table for message threads statuses',
		"CREATE TABLE T_messaging__threadstatus (
			tsta_thread_ID int(10) unsigned NOT NULL,
			tsta_user_ID int(10) unsigned NOT NULL,
			tsta_first_unread_msg_ID int(10) unsigned NULL,
			tsta_thread_leave_msg_ID int(10) unsigned NULL DEFAULT NULL,
			INDEX(tsta_user_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

$schema_queries['T_messaging__contact'] = array(
		'Creating table for messaging contacts',
		"CREATE TABLE T_messaging__contact (
			mct_from_user_ID int(10) unsigned NOT NULL,
			mct_to_user_ID int(10) unsigned NOT NULL,
			mct_blocked tinyint(1) default 0,
			mct_last_contact_datetime datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
			PRIMARY KEY mct_PK (mct_from_user_ID, mct_to_user_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

$schema_queries['T_messaging__contact_groups'] = array(
		'Creating table for groups of messaging contacts',
		"CREATE TABLE T_messaging__contact_groups (
			cgr_ID      int(10) unsigned NOT NULL auto_increment,
			cgr_user_ID int(10) unsigned NOT NULL,
			cgr_name    varchar(50) NOT NULL,
			PRIMARY KEY cgr_ID (cgr_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

$schema_queries['T_messaging__contact_groupusers'] = array(
		'Creating table for group users of messaging contacts',
		"CREATE TABLE T_messaging__contact_groupusers (
			cgu_user_ID int(10) unsigned NOT NULL,
			cgu_cgr_ID  int(10) unsigned NOT NULL,
			PRIMARY KEY cgu_PK (cgu_user_ID, cgu_cgr_ID),
			FOREIGN KEY (cgu_cgr_ID) REFERENCES T_messaging__contact_groups(cgr_ID)
                      ON DELETE CASCADE
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

?>