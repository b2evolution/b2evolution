<?php
/**
 * Backup - This is a LINEAR controller
 */

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


// We don't check if updates are available her ebecause API call could take a long time
// and we don't want the user to be waiting on a blank screen.


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
	default:
		$block_item_Widget = & new Widget( 'block_item' );
		$block_item_Widget->title = T_('Updates from b2evolution.net');
		$block_item_Widget->disp_template_replaced( 'block_start' );


		// Note: hopefully, the update swill have been downloaded in the shutdown function of a previous page (including the login screen)
		// However if we have outdated info, we will load updates here.
		load_funcs( 'dashboard/model/_dashboard.funcs.php' );
		// Let's clear any remaining messages that should already have been displayed before...
		$Messages->clear( 'all' );
		b2evonet_get_updates();

		// Display info & error messages
		echo $Messages->display( NULL, NULL, false, 'all', NULL, NULL, 'action_messages' );


		/**
		 * @var AbstractSettings
		 */
		global $global_Cache;

		// Display the current version info for now. We may remove this in the future.
		$version_status_msg = $global_Cache->get( 'version_status_msg' );
		if( !empty($version_status_msg) )
		{	// We have managed to get updates (right now or in the past):
			echo '<p>'.$version_status_msg.'</p>';
			$extra_msg = $global_Cache->get( 'extra_msg' );
			if( !empty($extra_msg) )
			{
				echo '<p>'.$extra_msg.'</p>';
			}
		}

		// Extract available updates:
		$updates = $global_Cache->get( 'updates' );
		$current_Updater->updates = $updates;

		$block_item_Widget->disp_template_replaced( 'block_end' );

		// Display updates checker form
		$AdminUI->disp_view( 'upgrade/views/_updater.form.php' );
		break;

	case 'download':
		// proceed with download + ask confirmation on upgarde (last chance to quit)
		break;

	case 'upgrade':
		// proceed with upgrade issuing flush() all the time to track progress...
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.4  2009/10/18 17:20:58  fplanque
 * doc/messages/minor refact
 *
 * Revision 1.3  2009/10/18 08:16:55  efy-maxim
 * log
 *
 */

?>