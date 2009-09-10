<?php
/**
 * This is the install file for the messaging module
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 *
 * @version _messaging.install.php,v 1.0 2009/09/08 12:31:44 Exp
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
		'Creating table for messaging thread',
		"CREATE TABLE T_messaging__thread (
			thrd_ID int(10) unsigned NOT NULL auto_increment,
			thrd_title varchar(255) NOT NULL,
			thrd_datemodified datetime NOT NULL,
			PRIMARY KEY thrd_ID (thrd_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

$schema_queries['T_messaging__message'] = array(
		'Creating table for messaging message',
		"CREATE TABLE T_messaging__message (
			msg_ID int(10) unsigned NOT NULL auto_increment,
			msg_author_user_ID int(10) unsigned NOT NULL,
			msg_datetime datetime NOT NULL,
			msg_thread_ID int(10) unsigned NOT NULL,
			msg_text text,
			PRIMARY KEY msg_ID (msg_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

// msta_status can be one of the following: 0=author 1=read and 2=unread
$schema_queries['T_messaging__msgstatus'] = array(
		'Creating table for messaging status',
		"CREATE TABLE T_messaging__msgstatus (
			msta_thread_ID int(10) unsigned NOT NULL,
			msta_msg_ID int(10) unsigned NOT NULL,
			msta_user_ID int(10) unsigned NOT NULL,
			msta_status tinyint(1) unsigned NOT NULL
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );
?>