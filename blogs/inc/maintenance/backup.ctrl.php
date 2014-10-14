<?php
/**
 * Backup - This is a LINEAR controller
 *
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package maintenance
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: backup.ctrl.php 7341 2014-09-30 09:47:23Z yura $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of User class
 */
global $current_User;

// Check minimum permission:
$current_User->check_perm( 'perm_maintenance', 'backup', true );

// Load Backup class (PHP4):
load_class( 'maintenance/model/_backup.class.php', 'Backup' );

// Set options path:
$AdminUI->set_path( 'options', 'misc', 'backup' );

// Get action parameter from request:
param_action( 'start' );

// Create instance of Backup class
$current_Backup = new Backup();

// Load backup settings from request
if( $action == 'backup' && !$current_Backup->load_from_Request() )
{
	$action = 'new';
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Maintenance'), '?ctrl=tools' );
$AdminUI->breadcrumbpath_add( T_('Backup'), '?ctrl=backup' );


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
		$AdminUI->disp_view( 'maintenance/views/_backup.form.php' );
		break;

	case 'backup':
		if( $demo_mode )
		{
			$Messages->clear();
			$Messages->add( T_( 'This feature is disabled on the demo server.' ), 'error' );
			$Messages->display();
			break;
		}

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'backup' );

		$Form = new Form( NULL, 'backup_progress', 'post' );

		// Interactive / flush() backup should start here
		$Form->begin_form( 'fform', T_('System backup is in progress...') );

		evo_flush();

		$success = true;
		if( $maintenance_mode = param( 'bk_maintenance_mode', 'boolean' ) )
		{	// Enable maintenance mode
			$success = switch_maintenance_mode( true, 'all', T_( 'System backup is in progress. Please reload this page in a few minutes.' ) );

			// Make sure we exit the maintenance mode if PHP dies
			register_shutdown_function( 'switch_maintenance_mode', false, '', true );
		}

		if( $success )
		{	// We can start backup
			set_max_execution_time( 1800 ); // 30 minutes
			$current_Backup->start_backup();
		}

		if( $maintenance_mode )
		{	// Disable maintenance mode
			switch_maintenance_mode( false, 'all' );
		}

		$Form->end_form();
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>