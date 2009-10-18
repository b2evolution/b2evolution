<?php
/**
 * Backup - This is a LINEAR controller
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load Backup class (PHP4):
load_class( 'backup/model/_backup.class.php', 'Backup' );

// Set options path:
$AdminUI->set_path( 'tools', 'backup' );

// Get action parameter from request:
param_action( 'start' );

// Create instance of Backup class
$current_Backup = & new Backup();

// Load backup settings from request
if( $action == 'backup' && !$current_Backup->load_from_Request() )
{
	$action = 'new';
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
	case 'start':
		// Display backup settings form
		$AdminUI->disp_view( 'backup/views/_backupsettings.form.php' );
		break;

	case 'backup':
		$Form = & new Form( NULL, 'backup_progress', 'post' );

		// Interactive / flush() backup should start here
		$Form->begin_form( 'fform', T_('System backup is in progress...') );

		flush();

		// Start backup
		$current_Backup->start_backup();

		global $Messages;
		$Messages->display( NULL, NULL, true, 'all', NULL, NULL, 'action_messages' );

		$Form->end_form();
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.5  2009/10/18 17:20:58  fplanque
 * doc/messages/minor refact
 *
 * Revision 1.4  2009/10/18 15:32:53  efy-maxim
 * 1. new maintenance mode switcher. 2. flush
 *
 * Revision 1.3  2009/10/18 10:24:28  efy-maxim
 * backup
 *
 * Revision 1.2  2009/10/18 08:11:37  efy-maxim
 * log test
 *
 */

?>