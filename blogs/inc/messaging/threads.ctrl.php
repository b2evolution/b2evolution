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

// Preload users to show theirs avatars
load_messaging_threads_recipients( $current_User->ID );

switch( $action )
{
	case 'new':
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

		break;

	case 'create': // Record new thread
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'thread' );

		if( create_new_thread() )
		{ // new thread has been created successful
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=threads', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		// Couldn't create the new Thread, reset variables to create another. 
		$edited_Thread = new Thread();
		$edited_Message = new Message();
		$edited_Message->Thread = & $edited_Thread;
		break;

	case 'delete':
		// Delete thread:
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'thread' );

		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'delete', true );

		// Make sure we got an thrd_ID:
		param( 'thrd_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Thread &laquo;%s&raquo; deleted.'), $edited_Thread->dget('title') );
			$edited_Thread->dbdelete( true );
			unset( $edited_Thread );
			unset( $edited_Message );
			forget_param( 'thrd_ID' );
			forget_param( 'msg_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=threads', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Thread->check_delete( sprintf( T_('Cannot delete thread &laquo;%s&raquo;'), $edited_Thread->dget('title') ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

}

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Messages'), '?ctrl=threads' );
$AdminUI->breadcrumbpath_add( T_('Conversations'), '?ctrl=threads' );

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
				'thread', $action, get_memorized( 'action' ) );
		$AdminUI->disp_view( 'messaging/views/_thread_list.view.php' );
		break;

	case 'new':
	case 'create':
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

/*
 * $Log$
 * Revision 1.18  2011/10/07 05:43:45  efy-asimo
 * Check messaging availability before display
 *
 * Revision 1.17  2011/08/11 09:05:09  efy-asimo
 * Messaging in front office
 *
 * Revision 1.16  2010/01/30 18:55:32  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.15  2010/01/15 16:57:38  efy-yury
 * update messaging: crumbs
 *
 * Revision 1.14  2010/01/03 12:03:17  fplanque
 * More crumbs...
 *
 * Revision 1.13  2009/12/06 22:55:20  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.12  2009/10/08 20:05:52  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.11  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.10  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.9  2009/09/19 20:31:38  efy-maxim
 * 'Reply' permission : SQL queries to check permission ; Block/Unblock functionality; Error messages on insert thread/message
 *
 * Revision 1.8  2009/09/19 11:29:05  efy-maxim
 * Refactoring
 *
 * Revision 1.7  2009/09/18 16:16:50  efy-maxim
 * comments tab in messaging module
 *
 * Revision 1.6  2009/09/18 10:38:31  efy-maxim
 * 15x15 icons next to login in messagin module
 *
 * Revision 1.5  2009/09/16 22:03:40  fplanque
 * doc
 *
 */
?>
