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

	if( $current_User->check_perm( 'perm_messaging', 'write', false ) )
	{ // get blocked contacts for user with write permission
		$sub_SQL = new SQL();

		// Select users blocked by current_User
		$sub_SQL->SELECT( 'mct_to_user_ID as user_ID' );
		$sub_SQL->FROM( 'T_messaging__contact' );
		$sub_SQL->WHERE( 'mct_from_user_ID = '.$current_User->ID );
		$sub_SQL->WHERE_and( 'mct_blocked = 1' );

		// Union the two query result
		$sub_query = '( '.$sub_SQL->get().' UNION DISTINCT ';

		// Select users who has blocked current_User
		$sub_SQL->SELECT( 'mct_from_user_ID as user_ID' );
		$sub_SQL->WHERE( 'mct_to_user_ID = '.$current_User->ID );
		$sub_SQL->WHERE_and( 'mct_blocked = 1' );

		$sub_query .= $sub_SQL->get().' )';

		// Select users from sub query result
		$SQL->FROM( 'T_users u' );
		$SQL->WHERE( 'u.user_ID IN '.$sub_query );
	}
	else
	{ // get every user, except non blocked contacts, for users with only reply permission
		// asimo> !!! This will select users who has blocked current user, but users blocked by current User won't be selected.
		$SQL->FROM( 'T_users u
						LEFT OUTER JOIN T_messaging__contact mcu
							ON u.user_ID = mcu.mct_from_user_ID
	    					AND mcu.mct_to_user_ID = '.$current_User->ID.'
	    					AND mcu.mct_blocked = 0' );

		$SQL->WHERE( 'u.user_ID <> '.$current_User->ID );
		$SQL->WHERE_and( 'mcu.mct_from_user_ID IS NULL' );
	}

	// check if recipient list contains blocked contacts, if yes return them
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
 * Block or unblock contact
 * 
 * @param integer contact user id
 * @param boolean true to block | false to unblock
 */
function set_contact_blocked( $user_ID, $blocked )
{
	global $current_User, $DB;

	$sql = 'UPDATE T_messaging__contact
				SET mct_blocked = '.$blocked.'
					WHERE mct_from_user_ID = '.$current_User->ID.'
					AND mct_to_user_ID = '.$user_ID;

	$DB->query( $sql );
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


/**
 * Create new messaging thread from request
 *
 * @return boolean true on success
 */
function create_new_thread()
{
	global $current_User, $Messages, $edited_Thread, $edited_Message;

	// Insert new thread:
	$edited_Thread = new Thread();
	$edited_Message = new Message();
	$edited_Message->Thread = & $edited_Thread;

	// Check permission:
	$current_User->check_perm( 'perm_messaging', 'reply', true );

	param( 'thrd_recipients', 'string' );

	// Load data from request
	if( $edited_Message->load_from_Request() )
	{	// We could load data from form without errors:

		if( ! $current_User->check_perm( 'perm_messaging', 'delete' ) )
		{ // Current user doesn't have delete permission, so needs to check if the contacts from recipients list are blocked or not.
			$blocked_contacts = check_blocked_contacts( $edited_Thread->recipients_list );
			if( !empty( $blocked_contacts ) )
			{ // There is at least one blocked recipient ( it is blocked or not a contact yet )
				param_error( 'thrd_recipients', T_( 'You don\'t have permission to initiate conversations with the following users: ' ). implode( ', ', $blocked_contacts ) );
			}
		}

		if( ! param_errors_detected() )
		{
			// Insert in DB:
			if( param( 'thrdtype', 'string', 'discussion' ) == 'discussion' )
			{
				$edited_Message->dbinsert_discussion();
			}
			else
			{
				$edited_Message->dbinsert_individual();
			}

			$Messages->add( T_('New thread created.'), 'success' );

			return true;
		}
	}
	return false;
}


/**
 * Create a new message from request in the given thread
 * 
 * @param integer thread ID
 * @param boolean is user able to reply or not, because every contact is blocked
 * @return boolean true on success
 */
function create_new_message( $thrd_ID, $has_non_blocked_contacts )
{
	global $current_User, $Messages, $edited_Message;

	// Insert new message:
	$edited_Message = new Message();
	$edited_Message->thread_ID = $thrd_ID;

	// Check permission:
	$current_User->check_perm( 'perm_messaging', 'reply', true );

	// Load data from request
	if( $edited_Message->load_from_Request() )
	{	// We could load data from form without errors:

		if( !$current_User->check_perm( 'perm_messaging', 'delete' ) )
		{ // Current user doesn't have delete permission, so needs to check if the contacts from recipients list are all blocked or not.
			if( !$has_non_blocked_contacts )
			{ // all recipient are blocked
				param_error( '', T_( 'You don\'t have permission to reply here.' ) );
			}
		}

		if( ! param_errors_detected() )
		{
			// Insert in DB:
			$edited_Message->dbinsert_message();
			$Messages->add( T_('New message created.'), 'success' );

			return true;
		}
	}

	return false;
}


/**
 * Get messaging menu urls
 * 
 * @param string specific sub entry url, possible values: 'threads', 'contacts', 'messages'
 */
function get_messaging_url( $disp = 'threads' )
{
	global $admin_url, $is_admin_page, $Blog;
	if( $is_admin_page || empty( $Blog ) )
	{
		return $admin_url.'?ctrl='.$disp;
	}
	return url_add_param( $Blog->gen_blogurl(), 'disp='.$disp );
}


/**
 * Get messaging menu sub entries
 * 
 * @param boolean true to get admin interface messaging sub menu entries, false to get front office messaging sub menu entries
 * @param integer owner user ID
 * @return array user sub entries
 */
function get_messaging_sub_entries( $is_admin )
{
	global $Blog;

	if( $is_admin )
	{
		$url = '?ctrl=';
	}
	else
	{
		$url = $Blog->gen_blogurl().'?disp=';
	}

	return array( 'threads' => array(
						'text' => T_('Messages'),
						'href' => $url.'threads' ),
					'contacts' => array(
						'text' => T_('Contacts'),
						'href' => $url.'contacts' ),
	);
}


/**
 * Save message form params into the current Session
 * 
 * @param Array message form params
 */
function save_message_params_to_session( $unsaved_message_Array )
{
	global $Session;
	$Session->set( 'core.unsaved_message_Array', $unsaved_message_Array );
}


/**
 * Get message form params from the current Session
 * 
 * @return Array|NULL message form params array if Session core.unsaved_message_Array is set, NULL otherwise 
 */
function get_message_params_from_session()
{
	global $Session;
	if( ( $unsaved_message_Array = $Session->get( 'core.unsaved_message_Array' ) ) && is_array( $unsaved_message_Array ) )
	{
		$Session->delete( 'core.unsaved_message_Array' );
		return $unsaved_message_Array;
	}
	return NULL;
}

/*
 * $Log$
 * Revision 1.14  2011/10/04 08:39:30  efy-asimo
 * Comment and message forms save/reload content in case of error
 *
 * Revision 1.13  2011/08/11 09:05:09  efy-asimo
 * Messaging in front office
 *
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