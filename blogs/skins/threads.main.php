<?php
/**
 * This file is the template that includes required css files to display threads
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @version $Id: threads.main.php 6426 2014-04-08 16:26:27Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $htsrv_url, $Messages, $current_User, $Skin;

if( !is_logged_in() )
{ // Redirect to the login page for anonymous users
	$Messages->add( T_( 'You must log in to read your messages.' ) );
	header_redirect( get_login_url('cannot see messages'), 302 );
	// will have exited
}

if( !$current_User->check_status( 'can_view_threads' ) )
{ // user status does not allow to view threads
	if( $current_User->check_status( 'can_be_validated' ) )
	{ // user is logged in but his/her account is not activate yet
		$Messages->add( T_( 'You must activate your account before you can read & send messages. <b>See below:</b>' ) );
		header_redirect( get_activate_info_url(), 302 );
		// will have exited
	}

	$Messages->add( 'You are not allowed to view Messages!' );

	$blogurl = $Blog->gen_blogurl();
	// If it was a front page request or the front page is set to display 'threads' then we must not redirect to the front page because it is forbidden for the current User
	$redirect_to = ( is_front_page() || ( $Blog->get_setting( 'front_disp' ) == 'threads' ) ) ? url_add_param( $blogurl, 'disp=404', '&' ) : $blogurl;
	header_redirect( $redirect_to, 302 );
	// will have exited
}

if( !$current_User->check_perm( 'perm_messaging', 'reply' ) )
{ // Redirect to the blog url for users without messaging permission
	$Messages->add( 'You are not allowed to view Messages!' );
	$blogurl = $Blog->gen_blogurl();
	// If it was a front page request or the front page is set to display 'threads' then we must not redirect to the front page because it is forbidden for the current User
	$redirect_to = ( is_front_page() || ( $Blog->get_setting( 'front_disp' ) == 'threads' ) ) ? url_add_param( $blogurl, 'disp=403', '&' ) : $blogurl;
	header_redirect( $redirect_to, 302 );
	// will have exited
}

$action = param( 'action', 'string', 'view' );
if( $action == 'new' )
{ // Before new message form is displayed ...
	if( has_cross_country_restriction( 'contact' ) && empty( $current_User->ctry_ID ) )
	{ // Cross country contact restriction is enabled, but user country is not set yet
		$Messages->add( T_('Please specify your country before attempting to contact other users.') );
		header_redirect( get_user_profile_url() );
	}
	elseif( check_create_thread_limit( true ) )
	{ // don't allow to create new thread, because the new thread limit was already reached
		set_param( 'action', 'view' );
	}
}

// var bgxy_expand is used by toggle_filter_area() and toggle_clickopen()
// var htsrv_url is used for AJAX callbacks
add_js_headline( "// Paths used by JS functions:
		var bgxy_expand = '".get_icon( 'expand', 'xy' )."';
		var bgxy_collapse = '".get_icon( 'collapse', 'xy' )."';" );

// Require results.css to display thread query results in a table
require_css( 'results.css' ); // Results/tables styles

// Load classes
load_class( 'messaging/model/_thread.class.php', 'Thread' );
load_class( 'messaging/model/_message.class.php', 'Message' );

// Get action parameter from request:
$action = param_action( 'view' );

switch( $action )
{
	case 'new':
		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'reply', true );

		$edited_Thread = new Thread();
		$edited_Message = new Message();
		$edited_Message->Thread = & $edited_Thread;

		modules_call_method( 'update_new_thread', array( 'Thread' => & $edited_Thread ) );

		if( ( $unsaved_message_params = get_message_params_from_session() ) !== NULL )
		{ // set Message and Thread saved params from Session
			$edited_Message->text = $unsaved_message_params[ 'message' ];
			$edited_Thread->title = $unsaved_message_params[ 'subject' ];
			$edited_Thread->recipients = $unsaved_message_params[ 'thrd_recipients' ];
			$thrd_recipients_array = $unsaved_message_params[ 'thrd_recipients_array' ];
			$thrdtype = $unsaved_message_params[ 'thrdtype' ];
		}
		else
		{
			if( empty( $edited_Thread->recipients ) )
			{
				$edited_Thread->recipients = param( 'thrd_recipients', 'string', '' );
			}
			if( empty( $edited_Thread->title ) )
			{
				$edited_Thread->title = param( 'subject', 'string', '' );
			}
		}

		init_tokeninput_js( 'blog' );
		break;

	default:
		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'reply', true );
		break;
}

init_plugins_js( 'blog', $Skin->get_template( 'tooltip_plugin' ) );

// Require functions.js to show/hide a panel with filters
require_js( 'functions.js', 'blog' );
// Include this file to expand/collapse the filters panel when JavaScript is disabled
require_once $inc_path.'_filters.inc.php';

require $ads_current_skin_path.'index.main.php';

?>