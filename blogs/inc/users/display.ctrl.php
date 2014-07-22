<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
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
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @version $Id: display.ctrl.php 7172 2014-07-22 08:07:56Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'users', 'view', true );

$AdminUI->set_path( 'users', 'usersettings', 'display' );

param_action();

switch ( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'display' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// UPDATE display settings:
		param( 'use_gravatar', 'integer', 0 );
		param( 'default_gravatar', 'string', 'b2evo' );
		param( 'gender_colored', 'integer', 0 );
		param( 'bubbletip', 'integer', 0 );
		param( 'bubbletip_size_admin', 'string', '' );
		param( 'bubbletip_size_front', 'string', '' );
		param( 'bubbletip_anonymous', 'integer', 0 );
		param( 'bubbletip_size_anonymous', 'string', '' );
		param( 'bubbletip_overlay' );
		param( 'allow_anonymous_user_list', 'integer', 0 );
		param( 'allow_anonymous_user_profiles', 'integer', 0 );
		param( 'user_url_loggedin', 'string', '' );
		param( 'user_url_anonymous', 'string', '' );

		$Settings->set_array( array(
			array( 'use_gravatar', $use_gravatar ),
			array( 'default_gravatar', $default_gravatar ),
			array( 'gender_colored', $gender_colored ),
			array( 'bubbletip', $bubbletip ),
			array( 'bubbletip_size_admin', $bubbletip_size_admin ),
			array( 'bubbletip_size_front', $bubbletip_size_front ),
			array( 'bubbletip_anonymous', $bubbletip_anonymous ),
			array( 'bubbletip_size_anonymous', $bubbletip_size_anonymous ),
			array( 'bubbletip_overlay', $bubbletip_overlay ),
			array( 'allow_anonymous_user_list', $allow_anonymous_user_list ),
			array( 'allow_anonymous_user_profiles', $allow_anonymous_user_profiles ),
			array( 'user_url_loggedin', $user_url_loggedin ),
			array( 'user_url_anonymous', $user_url_anonymous ) ) );

		if( ! $Messages->has_errors() )
		{
			if( $Settings->dbupdate() )
			{
				// invalidate all PageCaches
				invalidate_pagecaches();

				$Messages->add( T_('Display settings updated.'), 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=display', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=usersettings' );
$AdminUI->breadcrumbpath_add( T_('Display'), '?ctrl=display' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'users/views/_display.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>