<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _messaging.install.php 6135 2014-03-08 07:54:05Z manuel $
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
			thrd_datemodified datetime NOT NULL,
			PRIMARY KEY thrd_ID (thrd_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

$schema_queries['T_messaging__message'] = array(
		'Creating table for messages',
		"CREATE TABLE T_messaging__message (
			msg_ID int(10) unsigned NOT NULL auto_increment,
			msg_author_user_ID int(10) unsigned NOT NULL,
			msg_datetime datetime NOT NULL,
			msg_thread_ID int(10) unsigned NOT NULL,
			msg_text text,
			PRIMARY KEY msg_ID (msg_ID)
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
			mct_last_contact_datetime datetime NOT NULL,
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