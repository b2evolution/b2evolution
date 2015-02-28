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
if( !$current_User->check_perm( 'perm_messaging', 'abuse' ) )
{
	$Messages->add( 'Sorry, you are not allowed to abuse management!' );
	header_redirect( $admin_url );
}

/**
 * @var set TRUE if we want to see a messages as abuse manager
 */
global $perm_abuse_management;

$perm_abuse_management = true;

// Set options path:
$AdminUI->set_path( 'messaging', 'abuse' );

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

switch( $action )
{
	case 'delete':
		// Delete thread:
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_threads' );

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
			header_redirect( '?ctrl=abuse', 303 ); // Will EXIT
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
$AdminUI->breadcrumbpath_add( T_('Abuse Management'), '?ctrl=abuse' );

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
