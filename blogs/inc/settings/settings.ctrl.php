<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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

	param_integer_range( 'user_minpwdlen', 1, 32, T_('Minimun password length must be between %d and %d.') );
	$Settings->set( 'user_minpwdlen', $user_minpwdlen );

	param( 'js_passwd_hashing', 'integer', 0 );
	$Settings->set( 'js_passwd_hashing', $js_passwd_hashing );


	// Session timeout
	$timeout_sessions = param( 'timeout_sessions', 'integer', $Settings->get_default('timeout_sessions') );
	if( $timeout_sessions < 300 )
	{ // lower than 5 minutes: not allowed
		param_error( 'timeout_sessions', sprintf( T_( 'You cannot set a session timeout below %d seconds.' ), 300 ) );
	}
	elseif( $timeout_sessions < 86400 )
	{ // lower than 1 day: notice/warning
		$Messages->add( sprintf( T_( 'Warning: your session timeout is just %d seconds. Your users may have to re-login often!' ), $timeout_sessions ), 'note' );
	}
	$Settings->set( 'timeout_sessions', $timeout_sessions );

	param_integer_range( 'reloadpage_timeout', 0, 99999, T_('Reload-page timeout must be between %d and %d.') );
	$Settings->set( 'reloadpage_timeout', $reloadpage_timeout );

	$new_cache_status = param( 'general_cache_enabled', 'integer', 0 );
	$old_cache_status = $Settings->get('general_cache_enabled');

	load_class( '_core/model/_pagecache.class.php' );
	$PageCache = & new PageCache(  );

	if( $old_cache_status == false && $new_cache_status == true )
	{ // Caching has been turned ON:
		if( $PageCache->cache_create() )
		{
			$Messages->add( T_('General caching has been enabled.'), 'success' );
		}
		else
		{
			$Messages->add( T_('General caching could not be enabled. Check /cache/ folder file permissions.'), 'error' );
			$new_cache_status = 0;
		}
	}
	elseif( $old_cache_status == true && $new_cache_status == false )
	{ // Caching has been turned OFF:
		$PageCache->cache_delete();
		$Messages->add( T_('General caching has been disabled. All general cache contents have been purged.'), 'note' );
	}

	$Settings->set( 'general_cache_enabled', $new_cache_status );

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
$AdminUI->disp_view( 'settings/views/_general.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.3  2008/09/28 08:06:07  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.2  2008/01/21 09:35:34  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:01:18  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.16  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.15  2007/03/25 13:20:52  fplanque
 * cleaned up blog base urls
 * needs extensive testing...
 *
 * Revision 1.14  2007/03/24 20:41:16  fplanque
 * Refactored a lot of the link junk.
 * Made options blog specific.
 * Some junk still needs to be cleaned out. Will do asap.
 *
 * Revision 1.13  2006/12/15 22:54:14  fplanque
 * allow disabling of password hashing
 *
 * Revision 1.12  2006/12/07 00:55:52  fplanque
 * reorganized some settings
 *
 * Revision 1.11  2006/12/04 19:41:11  fplanque
 * Each blog can now have its own "archive mode" settings
 *
 * Revision 1.10  2006/12/04 18:16:50  fplanque
 * Each blog can now have its own "number of page/days to display" settings
 *
 * Revision 1.9  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>