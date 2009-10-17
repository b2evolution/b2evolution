<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load Currency class (PHP4):
load_class( 'upgrade/model/_updater.class.php', 'Updater' );

// Set options path:
$AdminUI->set_path( 'tools', 'upgrade' );

// Get action parameter from request:
param_action();

/**
 * @var instance of Updater class
 */
global $current_Updater;

// Create instance of Updater class
$current_Updater = & new Updater();

switch( $action )
{
	case 'upgrade':

		if( $current_Updater->start_upgrade() )
		{
			// Redirect to avoid double post
			header_redirect( '?ctrl=upgrade', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		break;

	default:

		// Check if upfates are available
		$current_Updater->check_for_updates();

		break;
}

$action = 'new';

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
	case 'new':
	default:
		// Display updates checker form
		$AdminUI->disp_view( 'upgrade/views/_updater.form.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>