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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Extract list of contacts of current user from his message threads
 *
 * @param current user ID
 */
function load_messaging_threads_recipients( $user_ID )
{
	global $DB;

	$SQL = new SQL();

	$SQL->SELECT( 'DISTINCT u.*' );

	$SQL->FROM( 'T_messaging__threadstatus ts
					LEFT OUTER JOIN T_messaging__threadstatus tsr
						ON ts.tsta_thread_ID = tsr.tsta_thread_ID
					LEFT OUTER JOIN T_users u
						ON tsr.tsta_user_ID = u.user_ID' );

	$SQL->WHERE( 'ts.tsta_user_ID = '.$user_ID );

	$UserCache = & get_UserCache();

	foreach( $DB->get_results( $SQL->get() ) as $row )
	{
		if( !isset($UserCache->cache[$row->user_ID]) )
		{
			$UserCache->add( new User( $row ) );
		}
	}
}


/**
 * Load all of the recipients of current thread
 *
 * @param current thread ID
 */
function load_messaging_thread_recipients( $thrd_ID )
{
	global $DB;

	$SQL = new SQL();

	$SQL->SELECT( 'u.*' );

	$SQL->FROM( 'T_messaging__threadstatus ts
					LEFT OUTER JOIN T_users u
						ON ts.tsta_user_ID = u.user_ID' );

	$SQL->WHERE( 'ts.tsta_thread_ID = '.$thrd_ID );

	$UserCache = & get_UserCache();

	foreach( $DB->get_results( $SQL->get() ) as $row )
	{
		if( !isset($UserCache->cache[$row->user_ID]) )
		{
			$UserCache->add( new User( $row ) );
		}
	}
}


/**
 * Check blocked contacts in recipients list
 *
 * @param recipients list
 * @return blocked contacts array
 */
function check_blocked_contacts( $recipients_list )
{
	global $DB, $current_User;

	$SQL = new SQL();

	$SQL->SELECT( 'u.user_login' );

	$SQL->FROM( 'T_users u
					LEFT OUTER JOIN T_messaging__contact mcu
						ON u.user_ID = mcu.mct_from_user_ID
    					AND mcu.mct_to_user_ID = '.$current_User->ID.'
    					AND mcu.mct_blocked = 0' );

	$SQL->WHERE( 'u.user_ID <> '.$current_User->ID );
	$SQL->WHERE_and( 'mcu.mct_from_user_ID IS NULL' );
	$SQL->WHERE_and( 'u.user_ID IN ('.implode( ',', $recipients_list ).')' );

	$SQL->ORDER_BY( 'u.user_login' );

	$blocked_contacts = array();
	foreach( $DB->get_results( $SQL->get() ) as $row )
	{
		$blocked_contacts[] = $row->user_login;
	}

	return $blocked_contacts;
}

/**
 * Send a private message to a user
 *
 * @param string recipient user login name
 * @param string message title/subject
 * @param string message text
 * @return boolean true on success
 */
function send_private_message( $recipient, $subject, $text )
{
	global $current_User, $Messages;

	if( !is_logged_in() )
	{
		debug_die( 'Active user not found.' );
	}

	$Group = & $current_User->get_Group();
	if( ! $Group->check_messaging_perm() )
	{ // current User are has no access to messages or to the admin interface
		$Messages->add( T_('You don\'t have permission to send private messages.') );
		return false;
	}

	if( $current_User->get( 'login' ) == $recipient )
	{ // user wants to send a private message to himself
		$Messages->add( T_('You cannot send a private message to yourself.') );
		return false;
	}

	load_class( 'messaging/model/_message.class.php', 'Message' );
	load_class( 'messaging/model/_thread.class.php', 'Thread' );
	// new thread:
	$edited_Thread = new Thread();
	$edited_Message = new Message();
	$edited_Message->Thread = & $edited_Thread;

	// set nessage attributes
	$edited_Message->set( 'text', $text );
	$edited_Thread->set( 'title', $subject );
	$edited_Thread->set( 'recipients', $recipient );
	$edited_Thread->param_check__recipients( 'thrd_recipients', $recipient );

	// send the message
	return $edited_Message->dbinsert_discussion();
}

/*
 * $Log$
 * Revision 1.12  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.11  2010/07/14 09:06:14  efy-asimo
 * todo fp>asimo modifications
 *
 * Revision 1.10  2010/06/24 08:54:05  efy-asimo
 * PHP 4 compatibility
 *
 * Revision 1.9  2010/05/05 09:37:08  efy-asimo
 * add _login.disp.php and change groups&users messaging perm
 *
 * Revision 1.8  2010/04/23 11:37:57  efy-asimo
 * send messages - fix
 *
 * Revision 1.7  2010/04/16 10:42:11  efy-asimo
 * users messages options- send private messages to users from front-office - task
 *
 * Revision 1.6  2010/01/30 18:55:32  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.5  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.4  2009/09/25 07:32:53  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.3  2009/09/19 20:47:08  fplanque
 * doc
 *
 */
?>