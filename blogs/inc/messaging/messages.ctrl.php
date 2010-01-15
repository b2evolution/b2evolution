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

// Load classes
load_class( 'messaging/model/_thread.class.php', 'Thread' );
load_class( 'messaging/model/_message.class.php', 'Message' );


/**
 * @var User
 */
global $current_User;

// Set options path:
$AdminUI->set_path( 'messaging', 'messages' );

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

// Check minimum permission:
$current_User->check_perm( 'perm_messaging', 'write', true, $thrd_ID );

if( param( 'msg_ID', 'integer', '', true) )
{// Load message from cache:
	$MessageCache = & get_MessageCache();
	if( ($edited_Message = & $MessageCache->get_by_ID( $msg_ID, false )) === false )
	{	unset( $edited_Message );
		forget_param( 'msg_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Message') ), 'error' );
		$action = 'nil';
	}
}

// Preload users to show theirs avatars

load_messaging_thread_recipients( $thrd_ID );

switch( $action )
{
	case 'create': // Record new message
		
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'message' );
		
		// Insert new message:
		$edited_Message = & new Message();
		$edited_Message->thread_ID = $thrd_ID;

		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'write', true );

		// Load data from request
		if( $edited_Message->load_from_Request() )
		{	// We could load data from form without errors:

			if( $current_User->check_perm( 'perm_messaging', 'reply' ) )
			{
				$non_blocked_contacts = $edited_Thread->load_contacts();
				if( empty( $non_blocked_contacts ) )
				{
					param_error( '', T_( 'You don\'t have permission to reply here.' ) );
				}
			}

			if( ! param_errors_detected() )
			{
				// Insert in DB:
				$edited_Message->dbinsert_message();
				$Messages->add( T_('New message created.'), 'success' );

				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=messages&thrd_ID='.$thrd_ID, 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}
		break;

	case 'delete':
		// Delete message:
		
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'message' );

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
			header_redirect( '?ctrl=messages&thrd_ID='.$thrd_ID, 303 ); // Will EXIT
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

}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Messages'), '?ctrl=threads' );


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
		$edited_Message->confirm_delete( T_('Delete message?'),
				'message', $action, get_memorized( 'action' ) );
	default:
		// No specific request, list all messages:
		// Cleanup context:
		forget_param( 'msg_ID' );
		// Display messages list:
		$action = 'create';
		$AdminUI->disp_view( 'messaging/views/_message_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.16  2010/01/15 16:57:38  efy-yury
 * update messaging: crumbs
 *
 * Revision 1.15  2010/01/03 12:03:17  fplanque
 * More crumbs...
 *
 * Revision 1.14  2009/12/06 22:55:20  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.13  2009/10/08 20:05:52  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.12  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.11  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.10  2009/09/19 20:31:38  efy-maxim
 * 'Reply' permission : SQL queries to check permission ; Block/Unblock functionality; Error messages on insert thread/message
 *
 * Revision 1.9  2009/09/19 11:29:05  efy-maxim
 * Refactoring
 *
 * Revision 1.8  2009/09/18 16:16:50  efy-maxim
 * comments tab in messaging module
 *
 * Revision 1.7  2009/09/18 10:38:31  efy-maxim
 * 15x15 icons next to login in messagin module
 *
 * Revision 1.6  2009/09/18 06:14:33  efy-maxim
 * fix for very very bad security issue
 *
 * Revision 1.5  2009/09/14 07:31:43  efy-maxim
 * 1. Messaging permissions have been fully implemented
 * 2. Messaging has been added to evo bar menu
 *
 * Revision 1.4  2009/09/12 18:44:11  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.3  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>
