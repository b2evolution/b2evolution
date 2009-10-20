<?php
/**
 * Upgrade - This is a LINEAR controller
 *
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of User class
 */
global $current_User;

/**
 * @vars string paths
 */
global $basepath, $upgrade_path, $install_path;

// Check minimum permission:
$current_User->check_perm( 'perm_maintenance', 'upgrade', true );

// Set options path:
$AdminUI->set_path( 'tools', 'upgrade' );

// Get action parameter from request:
param_action();

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

		$block_item_Widget->disp_template_replaced( 'block_end' );

		// Display updates checker and download form
		$AdminUI->disp_view( 'maintenance/views/_upgrade.form.php' );
		break;

	case 'download':
		$block_item_Widget = & new Widget( 'block_item' );
		$block_item_Widget->title = T_('Downloading and unpacking package...');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		// Clear all of the messages
		$Messages->clear( 'all' );

		// Prepare upgrade directory before upgrade downloading
		if( prepare_maintenance_dir( $upgrade_path, true ) )
		{
			// Set maximum execution time
			set_max_execution_time( 1800 ); // 30 minutes

			$download_url = param( 'upd_url', 'string' ); // http://ubidev.com/b2evolution-1.0.0.zip
			$download_url = str_replace( '\\', '/', $download_url );
			// TODO: check is url valid

			$slash_pos = strrpos( $download_url, '/' );
			$point_pos = strrpos( $download_url, '.' );
			if( $slash_pos < $point_pos )
			{	// Construct upgrade file name
				$download_name = substr( $download_url, $slash_pos + 1, $point_pos - $slash_pos - 1 );
				if( !empty( $download_name ) )
				{
					$upgrade_name = $download_name.'-'.date( 'Y-m-d', $servertimenow );
					$upgrade_file = $upgrade_path.$upgrade_name.'.zip';

					echo '<p>'.sprintf( T_( 'Downloading package to &laquo;<strong>%s</strong>&raquo;...' ), $upgrade_file ).'</p>';
					flush();

					// Download upgrade to upgrade directory
					if( copy( $download_url, $upgrade_file ) )
					{	// Upgrade downloaded and we can unpack it
						echo '<p>'.sprintf( T_( 'Unpacking package to &laquo;<strong>%s</strong>&raquo;...' ), $upgrade_path.$upgrade_name ).'</p>';
						flush();

						// Unpack downloaded upgrade
						if( !unpack_archive( $upgrade_file, $upgrade_path.$upgrade_name, true ) )
						{
							$Messages->add( sprintf( T_( 'Unable to unpack &laquo;%s&raquo; ZIP archive.' ), $upgrade_path.$upgrade_name ), 'error' );
						}
					}
					else
					{
						$Messages->add( sprintf( T_( 'Unable to download package from &laquo;%s&raquo;' ), $download_url ), 'error' );
					}
				}
				else
				{
					$Messages->add( sprintf( T_( 'Invalid download URL: &laquo;%s&raquo;' ), $download_url ), 'error' );
				}
			}
		}

		// Display info & error messages
		echo $Messages->display( NULL, NULL, false, 'all', NULL, NULL, 'action_messages' );

		$block_item_Widget->disp_template_replaced( 'block_end' );

		if( $Messages->count() == 0 )
		{	// There are no errors and we can show form with Upgrade button
			$upgrade_dir = $upgrade_name;
		}

		// Display upgrade form
		// ask confirmation on upgarde (last chance to quit)
		$AdminUI->disp_view( 'maintenance/views/_upgrade.form.php' );
		break;

	case 'upgrade':
		$block_item_Widget = & new Widget( 'block_item' );
		$block_item_Widget->title = T_('Upgrading...');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		// Clear all of the messages
		$Messages->clear( 'all' );

		// Enable maintenance mode
		switch_maintenance_mode( true, T_( 'System upgrade is in progress. Please reload this page in a few minutes.' ) );
		flush();

		// Get upgrade directory from request
		$upgrade_dir = param( 'upgrade_dir', 'string' );

		// Set maximum execution time
		set_max_execution_time( 1800 ); // 30 minutes

		// Verify that all destination files can be overwritten
		echo '<h4 style="color:green">'.T_( 'Verifying that all destination files can be overwritten...' ).'</h4>';
		flush();

		$read_only_list = array();
		verify_overwrite( $upgrade_path.$upgrade_dir, no_trailing_slash( $basepath ), 'Verifying', false, $read_only_list );
		if( empty( $read_only_files ) )
		{	// We can do backup files and database

			// Load Backup class (PHP4):
			load_class( 'maintenance/model/_backup.class.php', 'Backup' );

			// Create instance of Backup class
			$Backup = & new Backup();

			// Backup all of the folders and files
			$Backup->include_all();
			$Backup->pack_backup_files = false; // temporary

			// Start backup
			if( $Backup->start_backup() )
			{	// We can upgrade files and database

				echo '<h4 style="color:green">'.T_( 'Copying new folders and files...' ).'</h4>';
				flush();

				verify_overwrite( $upgrade_path.$upgrade_dir, no_trailing_slash( $basepath ), 'Copying', true );

				// Upgrade database using regular upgrader script
				require_once( $install_path.'/_functions_install.php' );
				require_once( $install_path.'/_functions_evoupgrade.php' );

				echo '<h4 style="color:green">'.T_( 'Upgrading data in existing b2evolution database...' ).'</h4>';
				flush();

				global $DB;

				$DB->begin();
				if( upgrade_b2evo_tables() )
				{
					$DB->commit();
				}
				else
				{
					$Messages->add( T_( 'Database upgrade failed.' ), 'error' );
					$DB->rollback();
				}
			}
		}
		else
		{
			$Messages->add( T_( 'Some old files can\'t be overwritten.' ), 'error' );

			echo '<p>'.T_( '<strong>The following folders and files can\'t be overwritten:</strong>' ).'</p>';
			foreach( $read_only_files as $read_only_file )
			{
				echo $read_only_file.'<br/>';
			}
		}

		// Disable maintenance mode
		switch_maintenance_mode( false );

		if( $Messages->count() == 0 )
		{
			$Messages->add( T_( 'Upgrade completed successfully!' ), 'success' );
		}

		// Display info & error messages
		echo $Messages->display( NULL, NULL, false, 'all', NULL, NULL, 'action_messages' );

		$block_item_Widget->disp_template_replaced( 'block_end' );

		// Display upgrade form
		$AdminUI->disp_view( 'maintenance/views/_upgrade.form.php' );

		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.2  2009/10/20 14:38:54  efy-maxim
 * maintenance modulde: downloading - unpacking - verifying destination files - backing up - copying new files - upgrade database using regular script (Warning: it is very unstable version! Please, don't use maintenance modulde, because it can affect your data )
 *
 * Revision 1.1  2009/10/18 20:15:51  efy-maxim
 * 1. backup, upgrade have been moved to maintenance module
 * 2. maintenance module permissions
 *
 */
?>