<?php
/**
 * This is the template that displays a thread messages and message form
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load classes
load_class( 'messaging/model/_thread.class.php', 'Thread' );
load_class( 'messaging/model/_message.class.php', 'Message' );

// init variables
global $inc_path;
global $Messages;
global $DB;
global $Skin;
global $thrd_ID;

if( !empty( $Skin ) ) {
	$display_params = array_merge( $Skin->get_template( 'Results' ), $Skin->get_template( 'messages' ) );
} else {
	$display_params = NULL;
}

if( !is_logged_in() )
{
	debug_die( 'User must be logged in to see this page.' );
}

// Check minimum permission:
$current_User->check_perm( 'perm_messaging', 'reply', true );

// Save to know if errors already exist
$error_messages_exist = $Messages->has_errors();

// Clear messages to avoid duplicate appearence, since they were already displayed
$Messages->clear();

$thread_is_missed = false;
if( ! empty( $thrd_ID ) )
{ // Load thread from cache:
	$ThreadCache = & get_ThreadCache();
	if( ( $edited_Thread = & $ThreadCache->get_by_ID( $thrd_ID, false ) ) === false )
	{ // Thread doesn't exists with this ID
		unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		if( ! $error_messages_exist )
		{ // Display this error only when no error above
			$Messages->add( T_('The requested thread does not exist any longer.'), 'error' );
		}
		$thread_is_missed = true;
	}
	else if( ! $edited_Thread->check_thread_recipient( $current_User->ID ) )
	{ // Current user is not recipient of this thread
		unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		if( ! $error_messages_exist )
		{ // Display this error only when no error above
			$Messages->add( T_('You are not allowed to view this thread.'), 'error' );
		}
	}
}

if( ! $error_messages_exist && ! $Messages->has_errors() && ( empty( $thrd_ID ) || empty( $edited_Thread ) ) )
{ // Display this error only when no error above
	$Messages->add( T_('Can\'t show messages without thread!'), 'error' );
	$thread_is_missed = true;
}
else
{	// Preload users to show theirs avatars
	load_messaging_thread_recipients( $thrd_ID );
}

$Messages->display();

if( $thread_is_missed )
{ // If thread is missed by some reeason we should inform user why it happens
	echo '<div class="deleted_thread_explanation">'
			.'<p>'.T_('It is likely that the message you are trying to access has been identified as a spam or scam message and therefore has been deleted by a moderator.').'</p>'
			.'<p>'.T_('This may have happened in the time between you received a notification for this new message and the time you\'re now trying to read the message.').'</p>'
			.'<p>'.T_('We are striving to keep this site free of inappropriate messages and misbehaving users. Despite our efforts, if you identify misbehaving users, please be sure to report them by using the \'Report this user\' feature that you will find on their profile.').'</p>'
		.'</div>';
}

// init params
if( !isset( $params ) )
{
	$params = array();
}
$params = array_merge( array(
	'form_class_msg' => 'bComment',
	'form_action' => $samedomain_htsrv_url.'action.php?mname=messaging',
	'form_name' => '',
	'form_layout' => NULL,
	'cols' => 35,
	'display_navigation' => true,
	'display_title' => true,
	'messages_list_start' => '<div class="messages_list">',
	'messages_list_end' => '</div>',
	'messages_list_title' => T_('Previous messages in this conversation'),
	), $params );

// Display messages list:
if( isset( $edited_Thread ) )
{
	global $action;
	$action = !empty( $action ) ? $action : 'create';
	require $inc_path.'messaging/views/_message_list.view.php';
}

?>