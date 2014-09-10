<?php
/**
 * This file is the template that includes required css files to display msgform ( contact form )
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @version $Id: $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Messages, $current_User, $disp;

// get expected message form type
$msg_type = param( 'msg_type', 'string', '' );
// initialize
$recipient_User = NULL;
$Comment = NULL;
$allow_msgform = NULL;

// get possible params
$recipient_id = param( 'recipient_id', 'integer', 0, true );
$comment_id = param( 'comment_id', 'integer', 0, true );
$post_id = param( 'post_id', 'integer', 0, true );
$subject = param( 'subject', 'string', '' );

// try to init recipient_User
if( !empty( $recipient_id ) )
{
	$UserCache = & get_UserCache();
	$recipient_User = & $UserCache->get_by_ID( $recipient_id );
}
elseif( !empty( $comment_id ) )
{ // comment id is set, try to get comment author user
	$CommentCache = & get_CommentCache();
	$Comment = $CommentCache->get_by_ID( $comment_id, false );

	if( $Comment = $CommentCache->get_by_ID( $comment_id, false ) )
	{
		$recipient_User = & $Comment->get_author_User();
		if( empty( $recipient_User ) && ( $Comment->allow_msgform ) && ( is_email( $Comment->get_author_email() ) ) )
		{ // set allow message form to email because comment author (not registered) accepts email
			$allow_msgform = 'email';
			param( 'recipient_address', 'string', $Comment->get_author_email() );
			param( 'recipient_name', 'string', $Comment->get_author_name() );
		}
	}
}
else
{ // Recipient was not defined, try set the blog owner as recipient
	global $Blog;
	if( empty( $Blog ) )
	{ // Blog is not set, this is an invalid request
		debug_die( 'Invalid send message request!');
	}
	$recipient_User = $Blog->get_owner_User();
}

if( $recipient_User )
{ // recipient User is set
	// get_msgform_possibility returns NULL (false), only if there is no messaging option between current_User and recipient user
	$allow_msgform = $recipient_User->get_msgform_possibility();
	if( $allow_msgform == 'login' )
	{ // user must login first to be able to send a message to this User
		$disp = 'login';
		param( 'action', 'string', 'req_login' );
		// override redirect to param
		param( 'redirect_to', 'url', regenerate_url(), true, true );
		$Messages->add( T_( 'You must log in before you can contact this user' ) );
	}
	elseif( ( $allow_msgform == 'PM' ) && check_user_status( 'can_be_validated' ) )
	{ // user is not activated
		if( $recipient_User->accepts_email() )
		{ // recipient User accepts email allow to send email
			$allow_msgform = 'email';
			$msg_type = 'email';
			$activateinfo_link = 'href="'.get_activate_info_url().'"';
			$Messages->add( sprintf( T_( 'You must activate your account before you can send a private message to %s. However you can send them an email if you\'d like. <a %s>More info &raquo;</a>' ), $recipient_User->get( 'login' ), $activateinfo_link ), 'warning' );
		}
		else
		{ // Redirect to the activate info page for not activated users
			$Messages->add( T_( 'You must activate your account before you can contact a user. <b>See below:</b>' ) );
			header_redirect( get_activate_info_url(), 302 );
			// will have exited
		}
	}
	elseif( ( $msg_type == 'PM' ) && ( $allow_msgform == 'email' ) )
	{ // only email is allowed but user expect private message form
		if( ( !empty( $current_User ) ) && ( $recipient_id == $current_User->ID ) )
		{
			$Messages->add( T_( 'You cannot send a private message to yourself. However you can send yourself an email if you\'d like.' ), 'warning' );
		}
		else
		{
			$Messages->add( sprintf( T_( 'You cannot send a private message to %s. However you can send them an email if you\'d like.' ), $recipient_User->get( 'login' ) ), 'warning' );
		}
	}
	elseif( ( $msg_type != 'email' ) && ( $allow_msgform == 'PM' ) )
	{ // private message form should be displayed, change display to create new individual thread with the given recipient user
		// check if creating new PM is allowed
		if( check_create_thread_limit( true ) )
		{ // thread limit reached
			header_redirect();
			// exited here
		}

		// Load classes
		load_class( 'messaging/model/_thread.class.php', 'Thread' );
		load_class( 'messaging/model/_message.class.php', 'Message' );

		// Set global variable to auto define the FB autocomplete plugin field
		$recipients_selected = array( array(
				'id'    => $recipient_User->ID,
				'title' => $recipient_User->login,
			) );

		init_tokeninput_js( 'blog' );

		$disp = 'threads';
		$edited_Thread = new Thread();
		$edited_Message = new Message();
		$edited_Message->Thread = & $edited_Thread;
		$edited_Thread->recipients = $recipient_User->login;
		param( 'action', 'string', 'new', true );
		param( 'thrdtype', 'string', 'individual', true );
	}

	if( $allow_msgform == 'email' )
	{ // set recippient user param
		set_param( 'recipient_id', $recipient_User->ID );
	}
}

if( $allow_msgform == NULL )
{ // should be Prevented by UI
	if( !empty( $recipient_User ) )
	{
		$Messages->add( T_( 'The user does not want to get contacted through the message form.' ) );
	}
	elseif( !empty( $Comment ) )
	{
		$Messages->add( T_( 'This commentator does not want to get contacted through the message form.' ) );
	}
	header_redirect();
	// exited here
}

if( $allow_msgform == 'PM' || $allow_msgform == 'email' )
{ // Some message form is available
	// Get the suggested subject for the email:
	if( empty($subject) )
	{ // no subject provided by param:
		if( ! empty($comment_id) )
		{
			$row = $DB->get_row( '
				SELECT post_title
				  FROM T_items__item, T_comments
				 WHERE comment_ID = '.$DB->quote($comment_id).'
				   AND post_ID = comment_item_ID' );

			if( $row )
			{
				$subject = T_('Re:').' '.sprintf( /* TRANS: Used as mail subject; %s gets replaced by an item's title */ T_( 'Comment on %s' ), $row->post_title );
			}
		}

		if( empty($subject) && ! empty($post_id) )
		{
			$row = $DB->get_row( '
					SELECT post_title
					  FROM T_items__item
					 WHERE post_ID = '.$post_id );
			if( $row )
			{
				$subject = T_('Re:').' '.$row->post_title;
			}
		}
	}
	if( $allow_msgform == 'PM' )
	{
		$edited_Thread->title = $subject;
	}
	else
	{
		param( 'subject', 'string', $subject, true );
	}
}

require $ads_current_skin_path.'index.main.php';

?>