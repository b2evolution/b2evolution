<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

// Memorize this as the last "tab" used in the Blog Settings:
$UserSettings->set( 'pref_glob_settings_tab', $ctrl );
$UserSettings->dbupdate();

$AdminUI->set_path( 'options', 'general' );

param( 'action', 'string' );

switch( $action )
{
	case 'update':
		// UPDATE general settings:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'globalsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Lock system
		if( $current_User->check_perm( 'users', 'edit' ) )
		{
			$system_lock = param( 'system_lock', 'integer', 0 );
			if( $Settings->get( 'system_lock' ) && ( ! $system_lock ) && ( ! $Messages->has_errors() ) && ( 1 == $Messages->count() ) )
			{ // System lock was turned off and there was no error, remove the warning about the system lock
				$Messages->clear();
			}
			$Settings->set( 'system_lock', $system_lock );
		}

		// General cache
		$new_cache_status = param( 'general_cache_enabled', 'integer', 0 );
		if( ! $Messages->has_errors() )
		{
			load_funcs( 'collections/model/_blog.funcs.php' );
			$result = set_cache_enabled( 'general_cache_enabled', $new_cache_status, NULL, false );
			if( $result != NULL )
			{ // general cache setting was changed
				list( $status, $message ) = $result;
				$Messages->add( $message, $status );
			}
		}

		// fp> Restore defaults has been removed because it's extra maintenance work and no real benefit to the user.

		// Online help
		param( 'webhelp_enabled', 'integer', 0 );
		$Settings->set( 'webhelp_enabled', $webhelp_enabled );

		// Hit & Session logs
		$Settings->set( 'log_public_hits', param( 'log_public_hits', 'integer', 0 ) );
		$Settings->set( 'log_admin_hits', param( 'log_admin_hits', 'integer', 0 ) );
		$Settings->set( 'log_spam_hits', param( 'log_spam_hits', 'integer', 0 ) );

		param( 'auto_prune_stats_mode', 'string', true );
		$Settings->set( 'auto_prune_stats_mode',  get_param('auto_prune_stats_mode') );

		// TODO: offer to set-up cron job if mode == 'cron' and to remove cron job if mode != 'cron'

		param( 'auto_prune_stats', 'integer', $Settings->get_default('auto_prune_stats'), false, false, true, false );
		$Settings->set( 'auto_prune_stats', get_param('auto_prune_stats') );

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('General settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=gensettings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'update_tools':
		// UPDATE general settings from tools:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'globalsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Lock system
		if( $current_User->check_perm( 'users', 'edit' ) )
		{
			$system_lock = param( 'system_lock', 'integer', 0 );
			if( $Settings->get( 'system_lock' ) && ( ! $system_lock ) && ( ! $Messages->has_errors() ) && ( 1 == $Messages->count() ) )
			{ // System lock was turned off and there was no error, remove the warning about the system lock
				$Messages->clear();
			}
			$Settings->set( 'system_lock', $system_lock );
		}

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('General settings updated.'), 'success' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=tools', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('General'), '?ctrl=gensettings' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'settings/views/_general.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.33  2013/11/06 08:04:45  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>