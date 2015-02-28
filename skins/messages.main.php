<?php
/**
 * This file is the template that includes required css files to display messages
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $htsrv_url, $Messages, $current_User, $Skin;

// fp> The correct place to get thrd_ID is here, because we want it in redirect_to in case we need to ask for login.
param( 'thrd_ID', 'integer', '', true );

if( !is_logged_in() )
{ // Redirect to the login page for anonymous users
	$Messages->add( T_( 'You must log in to read your messages.' ) );
	header_redirect( get_login_url('cannot see messages'), 302 );
	// will have exited
}

// check if user status allow to view messages
if( !$current_User->check_status( 'can_view_messages' ) )
{ // user status does not allow to view messages
	if( $current_User->check_status( 'can_be_validated' ) )
	{ // user is logged in but his/her account is not activate yet
		$Messages->add( T_( 'You must activate your account before you can read & send messages. <b>See below:</b>' ) );
		header_redirect( get_activate_info_url(), 302 );
		// will have exited
	}

	$Messages->add( 'You are not allowed to view Messages!' );
	header_redirect( $Blog->gen_blogurl(), 302 );
	// will have exited
}

// check if user permissions allow to view messages
if( !$current_User->check_perm( 'perm_messaging', 'reply' ) )
{ // Redirect to the blog url for users without messaging permission
	$Messages->add( 'You are not allowed to view Messages!' );
	header_redirect( $Blog->gen_blogurl(), 302 );
	// will have exited
}

if( !empty( $thrd_ID ) )
{ // if this thread exists and current user is part of this thread update status because won't be any unread messages on this conversation
	// we need to mark this early to make sure the unread message count will be correct in the evobar
	mark_as_read_by_user( $thrd_ID, $current_User->ID );
}

if( ( $unsaved_message_params = get_message_params_from_session() ) !== NULL )
{ // set Message and Thread saved params from Session
	global $edited_Message, $action;
	load_class( 'messaging/model/_message.class.php', 'Message' );
	$edited_Message = new Message();
	$edited_Message->text = $unsaved_message_params[ 'message' ];
	$edited_Message->original_text = $unsaved_message_params[ 'message_original' ];
	$edited_Message->set_renderers( $unsaved_message_params[ 'renderers' ] );
	$edited_Message->thread_ID = $thrd_ID;
	$action = $unsaved_message_params[ 'action' ];
}

init_plugins_js( 'blog', $Skin->get_template( 'tooltip_plugin' ) );

// Require results.css to display message query results in a table
require_css( 'results.css' ); // Results/tables styles

// Require functions.js to show/hide a panel with filters
require_js( 'functions.js', 'blog' );
// Include this file to expand/collapse the filters panel when JavaScript is disabled
require_once $inc_path.'_filters.inc.php';

require $ads_current_skin_path.'index.main.php';

?>