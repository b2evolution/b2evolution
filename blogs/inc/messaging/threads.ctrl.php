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
$current_User->check_perm( 'messaging', 'write', true );

// Set options path:
$AdminUI->set_path( 'messaging', 'messages' );

// Get action parameter from request:
param_action();

if( param( 'thrd_ID', 'integer', '', true) )
{// Load thread from cache:
	$ThreadCache = & get_Cache( 'ThreadCache' );
	if( ($edited_Thread = & $ThreadCache->get_by_ID( $thrd_ID, false )) === false )
	{	unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Thread') ), 'error' );
		$action = 'nil';
	}
}

if( param( 'msg_ID', 'integer', '', true) )
{// Load message from cache:
	$MessageCache = & get_Cache( 'MessageCache' );
	if( ($edited_Message = & $MessageCache->get_by_ID( $msg_ID, false )) === false )
	{	unset( $edited_Message );
		forget_param( 'msg_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Message') ), 'error' );
		$action = 'nil';
	}
}

// Preload users to show theirs avatars

load_messaging_threads_recipients( $current_User->ID );

switch( $action )
{
	case 'new':
		// Check permission:
		$current_User->check_perm( 'messaging', 'write', true );

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

		// Insert new thread:
		$edited_Thread = & new Thread();
		$edited_Message = & new Message();
		$edited_Message->Thread = & $edited_Thread;

		// Check permission:
		$current_User->check_perm( 'messaging', 'write', true );

		param( 'thrd_recipients', 'string' );

		// Load data from request
		if( $edited_Message->load_from_Request() )
		{	// We could load data from form without errors:

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

			// What next?
			switch( $action )
			{
				case 'create':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=threads', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
			}
		}
		break;

	case 'delete':
		// Delete thread:

		// Check permission:
		$current_User->check_perm( 'messaging', 'delete', true );

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
				$action, get_memorized( 'action' ) );
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
