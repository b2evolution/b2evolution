<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: time.ctrl.php 13 2011-10-24 23:42:53Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

// Memorize this as the last "tab" used in the Global Settings:
$UserSettings->set( 'pref_glob_settings_tab', $ctrl );
$UserSettings->set( 'pref_glob_regional_tab', $ctrl );
$UserSettings->dbupdate();

$AdminUI->set_path( 'options', 'regional', 'time' );

param( 'action', 'string' );
param( 'edit_locale', 'string' );
param( 'loc_transinfo', 'integer', 0 );

// Load all available locale defintions:
locales_load_available_defs();

switch( $action )
{
	case 'update':
		// UPDATE regional settings

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'time' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'newtime_difference', 'string', '' );
		$newtime_difference = trim($newtime_difference);
		if( $newtime_difference == '' )
		{
			$newtime_difference = 0;
		}
		if( strpos($newtime_difference, ':') !== false )
		{ // hh:mm:ss format:
			$ntd = explode(':', $newtime_difference);
			if( count($ntd) > 3 )
			{
				param_error( 'newtime_difference', T_('Invalid time format.') );
			}
			else
			{
				$newtime_difference = $ntd[0]*3600 + ($ntd[1]*60);

				if( count($ntd) == 3 )
				{ // add seconds:
					$newtime_difference += $ntd[2];
				}
			}
		}
		else
		{ // just hours:
			$newtime_difference = $newtime_difference*3600;
		}

		$Settings->set( 'time_difference', $newtime_difference );

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('Time settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=time', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		break;

}

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('Regional settings'), '?ctrl=locales' );
$AdminUI->breadcrumbpath_add( T_('Time'), '?ctrl=time' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'regional/views/_time.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>