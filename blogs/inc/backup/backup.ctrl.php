<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load Currency class (PHP4):
load_class( 'backup/model/_backupsettings.class.php', 'BackupSettings' );

// Preload backup configuration
load_funcs( 'backup/_backup_config.php' );

// Set options path:
$AdminUI->set_path( 'tools', 'backup' );

// Get action parameter from request:
param_action();

/**
 * @var instance of BackupSettings class
 */
global $backup_Settings;

// Create instance of BackupSettings class
$backup_Settings = & new BackupSettings();

switch( $action )
{
	case 'backup':

		// Load backup settings from request
		$backup_Settings->load_from_Request();
		// Start backup
		if( $backup_Settings->backup() )
		{	// Redirect to avoid double post
			header_redirect( '?ctrl=backup', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
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
	case 'backup':
	default:
		// Display backup settings form
		$AdminUI->disp_view( 'backup/views/_backupsettings.form.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.2  2009/10/18 08:11:37  efy-maxim
 * log test
 *
 */

?>