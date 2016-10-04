<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package messaging
 */

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
	$Messages->add( T_('You are not allowed to view messages.') );
	header_redirect( $admin_url );
}

// Get action parameter from request:
param_action();

/**
 * @var set TRUE if we want to see a messages as abuse manager
 */
global $perm_abuse_management;

$tab = param( 'tab', 'string' );
if( $tab == 'abuse' && $current_User->check_perm( 'perm_messaging', 'abuse' ) )
{	// We go from abuse management and have a permissions
	$perm_abuse_management = true;
}
else
{
	$perm_abuse_management = false;
}

if( param( 'thrd_ID', 'integer', '', true ) )
{ // Load thread from cache:
	$ThreadCache = & get_ThreadCache();
	if( ( $edited_Thread = & $ThreadCache->get_by_ID( $thrd_ID, false ) ) === false )
	{ // Thread doesn't exists with this ID
		unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		$Messages->add( T_('The private conversation you are trying to access does not exist any longer.'), 'error' );
		$action = 'nil';
	}
	else if( ! $edited_Thread->check_thread_recipient( $current_User->ID ) && ! $perm_abuse_management )
	{ // Current user is not recipient of this thread and he is not abuse manager
		unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		$Messages->add( T_('You are not allowed to view this thread.'), 'error' );
		$action = 'nil';
	}
}

if( param( 'msg_ID', 'integer', '', true ) )
{ // Load message from cache:
	$MessageCache = & get_MessageCache();
	if( ( $edited_Message = & $MessageCache->get_by_ID( $msg_ID, false ) ) === false )
	{
		unset( $edited_Message );
		forget_param( 'msg_ID' );
		$Messages->add( T_('The requested message does not exist any longer.'), 'error' );
		$action = 'nil';
	}
}

if( ! $Messages->has_errors() && ( empty( $thrd_ID ) || empty( $edited_Thread ) ) )
{ // Display this error only when no error above
	$Messages->add( T_( 'Can\'t show messages without thread!' ), 'error' );
	$action = 'nil';
}
else
{
	// Preload users to show theirs avatars
	load_messaging_thread_recipients( $thrd_ID );
}


$param_tab = '';
if( $perm_abuse_management )
{	// After completing of the action ( create | delete ) we want back to the abuse managment
	$param_tab = '&tab=abuse';
}

switch( $action )
{
	case 'create': // Record new message
		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_messages' );

		// Try to create the new message
		if( create_new_message( $thrd_ID ) )
		{
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=messages&thrd_ID='.$thrd_ID.$param_tab, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'preview': // Preview new message
		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_messages' );

		// Try to create the new message object without inserting in DB
		$creating_success = create_new_message( $thrd_ID, 'preview' );
		break;

	case 'delete':
		// Delete message:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_messages' );

		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'delete', true );

		// Make sure we got an msg_ID:
		param( 'msg_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$edited_Message->dbdelete();
			unset( $edited_Message );
			forget_param( 'msg_ID' );
			$Messages->add( T_('Message deleted.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=messages&thrd_ID='.$thrd_ID.$param_tab, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Message->check_delete( T_('Cannot delete message.') ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

	default:
		// View messages, this not require crumb check

		if( empty( $edited_Thread ) )
		{ // there are no thread what to show
			break;
		}

		// Mark this edited Thread as read by current User, because all messages will be displayed
		// No need to check permission because if the given user is not part of the thread the update won't modify anything.
		mark_as_read_by_user( $edited_Thread->ID, $current_User->ID );
		break;

}

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Messages'), '?ctrl=threads' );
if( $perm_abuse_management )
{	// We see a messages from abuse management
	$AdminUI->breadcrumbpath_add( T_('Abuse Management'), '?ctrl=abuse' );
	$AdminUI->set_path( 'messaging', 'abuse' );
}
else
{	// Set options path:
	$AdminUI->set_path( 'messaging', 'threads' );
}

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'messages-view-thread' );

init_plugins_js( 'rsc_url', $AdminUI->get_template( 'tooltip_plugin' ) );

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
		if( $perm_abuse_management )
		{	// Save a tab param for hidden fields of the form
			memorize_param( 'tab', 'string', 'abuse' );
		}
		// We need to ask for confirmation:
		$edited_Message->confirm_delete( T_('Delete message?'),
				'messaging_messages', $action, get_memorized( 'action' ) );
	default:
		// No specific request, list all messages:
		// Cleanup context:
		forget_param( 'msg_ID' );
		// Display messages list:
		$action = $action == 'preview' ? $action : 'create';
		$AdminUI->disp_view( 'messaging/views/_message_list.view.php', array(
				'messages_list_form_start'  => '',
				'messages_list_form_end'    => '',
				'messages_list_body_start'  => '',
				'messages_list_body_end'    => '',
			) );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>