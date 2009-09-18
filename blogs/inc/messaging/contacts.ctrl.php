<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;

// Check minimum permission:
$current_User->check_perm( 'messaging', 'write', true );

// Set options path:
$AdminUI->set_path( 'messaging', 'contacts' );

// Get action parameter from request:
param_action();

// Preload users to show theirs avatars

$UserCache = & get_Cache( 'UserCache' );
$UserCache->load_messaging_threads_recipients( $current_User->ID );

switch( $action )
{
	case 'block': // Block selected contact

		// Check permission:
		$current_User->check_perm( 'messaging', 'write', true );

		break;

	case 'unblock': // Unblock selected contact

		// Check permission:
		$current_User->check_perm( 'messaging', 'write', true );

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

	case 'block':
	case 'unblock':
	default:
		// Display contacts:
		$AdminUI->disp_view( 'messaging/views/_contact_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>
