<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
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

// msta_status can be one of the following: 0=author 1=read and 2=unread
// fp> TODO: we probably neede indexes on this table
$schema_queries['T_messaging__msgstatus'] = array(
		'Creating table for message statuses',
		"CREATE TABLE T_messaging__msgstatus (
			msta_thread_ID int(10) unsigned NOT NULL,
			msta_msg_ID int(10) unsigned NOT NULL,
			msta_user_ID int(10) unsigned NOT NULL,
			msta_status tinyint(1) unsigned NOT NULL
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

/*
 * $Log$
 * Revision 1.2  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>