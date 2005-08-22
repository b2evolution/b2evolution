<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require dirname(__FILE__).'/_header.php';

$AdminUI->setPath( 'options', 'general' );

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

	param( 'newusers_grp_ID', 'integer', true );
	$Settings->set( 'newusers_grp_ID', $newusers_grp_ID );

	$Request->param_integer_range( 'newusers_level', 0, 9, T_('User level must be between %d and %d.') );
	$Settings->set( 'newusers_level', $newusers_level );

	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	/* b2evo only:
	param( 'default_blog_ID', 'integer', true );
	$Settings->set( 'default_blog_ID', $default_blog_ID );

	$Request->param_integer_range( 'posts_per_page', 1, 9999, T_('Items/days per page must be between %d and %d.') );
	$Settings->set( 'posts_per_page', $posts_per_page );

	param( 'what_to_show', 'string', true );
	$Settings->set( 'what_to_show', $what_to_show );

	param( 'archive_mode', 'string', true );
	$Settings->set( 'archive_mode', $archive_mode );

	param( 'AutoBR', 'integer', 0 );
	$Settings->set( 'AutoBR', $AutoBR );


	param( 'links_extrapath', 'integer', 0 );
	$Settings->set( 'links_extrapath', $links_extrapath );

	param( 'permalink_type', 'string', true );
	$Settings->set( 'permalink_type', $permalink_type );
	*/
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

	$Request->param_integer_range( 'user_minpwdlen', 1, 32, T_('Minimun password length must be between %d and %d.') );
	$Settings->set( 'user_minpwdlen', $user_minpwdlen );

	$Request->param_integer_range( 'reloadpage_timeout', 0, 99999, T_('Reload-page timeout must be between %d and %d.') );
	$Settings->set( 'reloadpage_timeout', $reloadpage_timeout );

	if( ! $Messages->count('error') )
	{
		if( $Settings->updateDB() )
		{
			$Messages->add( T_('General settings updated.'), 'success' );
		}
	}

}


/**
 * Display page header:
 */
require dirname(__FILE__).'/_menutop.php';


// Check permission:
$current_User->check_perm( 'options', 'view', true );

// Begin payload block:
$AdminUI->dispPayloadBegin();

require dirname(__FILE__).'/_set_general.form.php';

// End payload block:
$AdminUI->dispPayloadEnd();

require dirname(__FILE__).'/_footer.php';

/*
 * $Log$
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