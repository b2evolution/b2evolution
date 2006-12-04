<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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


$AdminUI->set_path( 'options', 'general' );

param( 'action', 'string' );
param( 'edit_locale', 'string' );
param( 'loc_transinfo', 'integer', 0 );

if( in_array( $action, array( 'update', 'reset', 'updatelocale', 'createlocale', 'deletelocale', 'extract', 'prioup', 'priodown' )) )
{ // We have an action to do..
	// Check permission:
	$current_User->check_perm( 'options', 'edit', true );

	// clear settings cache
	$cache_settings = '';

	// UPDATE general settings:

	param( 'newusers_canregister', 'integer', 0 );
	$Settings->set( 'newusers_canregister', $newusers_canregister );

	param( 'newusers_mustvalidate', 'integer', 0 );
	$Settings->set( 'newusers_mustvalidate', $newusers_mustvalidate );

	param( 'newusers_revalidate_emailchg', 'integer', 0 );
	$Settings->set( 'newusers_revalidate_emailchg', $newusers_revalidate_emailchg );

	param( 'newusers_grp_ID', 'integer', true );
	$Settings->set( 'newusers_grp_ID', $newusers_grp_ID );

	param_integer_range( 'newusers_level', 0, 9, T_('User level must be between %d and %d.') );
	$Settings->set( 'newusers_level', $newusers_level );

	param( 'default_blog_ID', 'integer', true );
	$Settings->set( 'default_blog_ID', $default_blog_ID );

	param( 'AutoBR', 'integer', 0 );
	$Settings->set( 'AutoBR', $AutoBR );


	param( 'links_extrapath', 'string', 'disabled' );
	$Settings->set( 'links_extrapath', $links_extrapath );

	param( 'permalink_type', 'string', true );
	$Settings->set( 'permalink_type', $permalink_type );

	param_integer_range( 'user_minpwdlen', 1, 32, T_('Minimun password length must be between %d and %d.') );
	$Settings->set( 'user_minpwdlen', $user_minpwdlen );

	param_integer_range( 'reloadpage_timeout', 0, 99999, T_('Reload-page timeout must be between %d and %d.') );
	$Settings->set( 'reloadpage_timeout', $reloadpage_timeout );

	if( ! $Messages->count('error') )
	{
		if( $Settings->dbupdate() )
		{
			$Messages->add( T_('General settings updated.'), 'success' );
		}
	}

}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'settings/_set_general.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.11  2006/12/04 19:41:11  fplanque
 * Each blog can now have its own "archive mode" settings
 *
 * Revision 1.10  2006/12/04 18:16:50  fplanque
 * Each blog can now have its own "number of page/days to display" settings
 *
 * Revision 1.9  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.8  2006/09/10 20:59:18  fplanque
 * extended extra path info setting
 *
 * Revision 1.7  2006/08/20 22:25:20  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.6  2006/04/24 18:12:54  blueyed
 * Added Setting to invalidate a user account on email address change.
 *
 * Revision 1.5  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.4  2006/04/19 20:13:49  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.3  2006/04/14 19:25:32  fplanque
 * evocore merge with work app
 *
 * Revision 1.2  2006/03/12 23:08:57  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:56  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.8  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.7  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.6  2005/10/28 02:37:37  blueyed
 * Normalized AbstractSettings API
 *
 * Revision 1.5  2005/08/24 13:24:27  fplanque
 * no message
 *
 * Revision 1.4  2005/08/24 10:24:21  blueyed
 * Fixed saving of b2evo-only settings
 *
 * Revision 1.3  2005/08/22 19:14:12  fplanque
 * rollback of incomplete registration module
 *
 * Revision 1.2  2005/08/21 09:23:03  yabs
 * Moved registration settings to own tab and increased options
 *
 * Revision 1.1  2005/06/06 17:59:39  fplanque
 * user dialog enhancements
 *
 * Revision 1.92  2005/06/03 15:12:31  fplanque
 * error/info message cleanup
 *
 * Revision 1.91  2005/03/16 19:58:14  fplanque
 * small AdminUI cleanup tasks
 *
 * Revision 1.90  2005/03/15 19:19:46  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.89  2005/03/07 00:06:16  blueyed
 * admin UI refactoring, part three
 *
 * Revision 1.88  2005/03/04 18:40:26  fplanque
 * added Payload display wrappers to admin skin object
 *
 * Revision 1.87  2005/02/28 09:06:39  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.86  2005/02/27 20:34:49  blueyed
 * Admin UI refactoring
 *
 * Revision 1.85  2005/02/23 21:58:10  blueyed
 * fixed updating of locales
 *
 * Revision 1.84  2005/02/23 04:26:21  blueyed
 * moved global $start_of_week into $locales properties
 *
 * Revision 1.83  2005/02/21 00:34:36  blueyed
 * check for defined DB_USER!
 *
 * Revision 1.82  2004/12/17 20:38:51  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 */
?>