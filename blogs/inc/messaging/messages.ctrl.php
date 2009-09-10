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
$current_User->check_perm( 'options', 'edit', true );

// Set options path:
$AdminUI->set_path( 'messaging' );

// Get action parameter from request:
param_action();

param( 'thrd_ID', 'integer', '', true);

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

switch( $action )
{
	case 'create': // Record new message

		// Insert new message:
		$edited_Message = & new Message();
		$edited_Message->thread_ID = $thrd_ID;

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Load data from request
		if( $edited_Message->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$edited_Message->dbinsert();
			$Messages->add( T_('New message created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=messages&thrd_ID='.$thrd_ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'delete':
		// Delete message:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

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
				$action, get_memorized( 'action' ) );
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

?>
