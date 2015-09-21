<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load classes
load_class( 'messaging/model/_thread.class.php', 'Thread' );
load_class( 'messaging/model/_message.class.php', 'Message' );

/**
 * @var User
 */
global $current_User;

// Check minimum permission:
if( !$current_User->check_perm( 'perm_messaging', 'reply' ) )
{
	$Messages->add( 'Sorry, you are not allowed to view threads!' );
	header_redirect( $admin_url );
}

// Set options path:
$AdminUI->set_path( 'messaging', 'threads' );

// Get action parameter from request:
param_action();

if( param( 'thrd_ID', 'integer', '', true) )
{// Load thread from cache:
	$ThreadCache = & get_ThreadCache();
	if( ($edited_Thread = & $ThreadCache->get_by_ID( $thrd_ID, false )) === false )
	{	unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Thread') ), 'error' );
		$action = 'nil';
	}
}

// check params
switch( $action )
{
	case 'create':
	case 'preview':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_threads' );
		break;
	case 'delete':
	case 'leave':
	case 'close':
	case 'close_and_block':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_threads' );

		// Make sure we got a thrd_ID:
		param( 'thrd_ID', 'integer', true );
		break;
}

// handle action
switch( $action )
{
	case 'new':
		if( has_cross_country_restriction( 'users' ) && empty( $current_User->ctry_ID ) )
		{ // Cross country contact is restricted but user country is not set
			$Messages->add( T_('Please specify your country before attempting to contact other users.') );
			header_redirect( get_user_profile_url() );
		}

		if( check_create_thread_limit( true ) )
		{ // user has already reached his limit, don't allow to create new thread
			$action = '';
			break;
		}

		if( ! isset($edited_Message) )
		{	// We don't have a model to use, start with blank object:
			$edited_Thread = new Thread();
			$edited_Message = new Message();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_Thread = duplicate( $edited_Message->Thread ); // PHP4/5 abstraction
			$edited_Message = duplicate( $edited_Message ); // PHP4/5 abstraction
			$edited_Message->ID = 0;
		}
		$edited_Message->Thread = & $edited_Thread;

		init_tokeninput_js();

		break;

	case 'create': // Record new thread
		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		if( check_create_thread_limit() )
		{ // max new threads limit reached, don't allow to create new thread
			debug_die( 'Invalid request, new conversation limit already reached!' );
		}

		// the create_new_thread() funciton will create required Thread and Message objects
		if( create_new_thread() )
		{ // new thread has been created successful
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=threads', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		init_tokeninput_js();

		break;

	case 'preview': // Preview new thread
		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		if( check_create_thread_limit() )
		{ // max new threads limit reached, don't allow to create new thread
			debug_die( 'Invalid request, new conversation limit already reached!' );
		}

		// Create required Thread and Message objects without inserting in DB
		$creating_success = create_new_thread();

		init_tokeninput_js();

		break;

	case 'delete': // Delete thread:
		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'delete', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Thread &laquo;%s&raquo; deleted.'), $edited_Thread->dget('title') );
			$edited_Thread->dbdelete();
			unset( $edited_Thread );
			unset( $edited_Message );
			forget_param( 'thrd_ID' );
			forget_param( 'msg_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			$redirect_to = param( 'redirect_to', 'url', '?ctrl=threads' );
			header_redirect( $redirect_to, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{ // not confirmed, Check for restrictions:
			if( ! $edited_Thread->check_delete( sprintf( T_('Cannot delete thread &laquo;%s&raquo;'), $edited_Thread->dget('title') ) ) )
			{ // There are restrictions:
				$action = 'view';
			}
		}
		break;

	case 'leave': // Leave thread:
		leave_thread( $edited_Thread->ID, $current_User->ID, false );

		$Messages->add( sprintf( T_( 'You have successfuly left the &laquo;%s&raquo; conversation!' ), $edited_Thread->get( 'title' ) ), 'success' );
		break;

	case 'close': // Close thread:
	case 'close_and_block': // Close thread and block contact:
		leave_thread( $edited_Thread->ID, $current_User->ID, true );

		$Messages->add( sprintf( T_( 'You have successfuly closed the &laquo;%s&raquo; conversation!' ), $edited_Thread->get( 'title' ) ), 'success' );
		if( $action == 'close_and_block' )
		{ // also block the given contact
			$block_user_ID = param( 'block_ID', 'integer', true );
			$UserCache = & get_UserCache();
			$blocked_User = $UserCache->get_by_ID( $block_user_ID );

			set_contact_blocked( $block_user_ID, true );
			$Messages->add( sprintf( T_( '&laquo;%s&raquo; was blocked.' ), $blocked_User->get( 'login' ) ), 'success' );
		}
		break;
}

init_plugins_js( 'rsc_url', $AdminUI->get_template( 'tooltip_plugin' ) );

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Messages'), '?ctrl=threads' );
$AdminUI->breadcrumbpath_add( T_('Conversations'), '?ctrl=threads' );

// Set an url for manual page:
switch( $action )
{
	case 'new':
	case 'create':
	case 'preview':
		$AdminUI->set_page_manual_link( 'messages-new-thread' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'conversations-list' );
		break;
}

// Display messages depending on user email status
display_user_email_status_message();

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;

	case 'delete':
		// We need to ask for confirmation:
		$edited_Thread->confirm_delete(
				sprintf( T_('Delete thread &laquo;%s&raquo;?'), $edited_Thread->dget('title') ),
				'messaging_threads', $action, get_memorized( 'action' ) );
		$AdminUI->disp_view( 'messaging/views/_thread_list.view.php' );
		break;

	case 'new':
	case 'create':
	case 'preview':
		$AdminUI->disp_view( 'messaging/views/_thread.form.php' );
		break;

	default:
		// No specific request, list all threads:
		// Cleanup context:
		forget_param( 'thrd_ID' );
		forget_param( 'msg_ID' );
		// Display threads list:
		$AdminUI->disp_view( 'messaging/views/_thread_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>
