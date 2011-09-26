<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-bogdan: Evo Factory / Bogdan.
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'users', 'view', true );

$AdminUI->set_path( 'users', 'usersettings', 'registration' );

param_action();

switch ( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'registration' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// UPDATE general settings:
		param( 'newusers_canregister', 'integer', 0 );

		param( 'newusers_grp_ID', 'integer', true );

		param_integer_range( 'newusers_level', 0, 9, T_('User level must be between %d and %d.') );

		param( 'newusers_mustvalidate', 'integer', 0 );

		param( 'newusers_revalidate_emailchg', 'integer', 0 );

		param_integer_range( 'user_minpwdlen', 1, 32, T_('Minimum password length must be between %d and %d.') );

		param( 'js_passwd_hashing', 'integer', 0 );

		param( 'passwd_special', 'integer', 0 );

		param( 'registration_require_country', 'integer', 0 );

		param( 'registration_ask_locale', 'integer', 0 );

		param( 'registration_require_gender', 'string', '' );

		param( 'gender_colored', 'integer', 0 );

		$Settings->set_array( array(
									 array( 'newusers_canregister', $newusers_canregister),

									 array( 'newusers_grp_ID', $newusers_grp_ID),

									 array( 'newusers_level', $newusers_level),

									 array( 'newusers_mustvalidate', $newusers_mustvalidate),

		                             array( 'newusers_revalidate_emailchg', $newusers_revalidate_emailchg),

									 array( 'user_minpwdlen', $user_minpwdlen),

									 array( 'js_passwd_hashing', $js_passwd_hashing),

									 array( 'passwd_special', $passwd_special),

									 array( 'registration_require_country', $registration_require_country),

									 array( 'registration_ask_locale', $registration_ask_locale),

									 array( 'registration_require_gender', $registration_require_gender),

									 array( 'gender_colored', $gender_colored) ) );

		if( ! $Messages->has_errors() )
		{
			if( $Settings->dbupdate() )
			{
				$Messages->add( T_('General settings updated.'), 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=registration', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=usersettings' );
$AdminUI->breadcrumbpath_add( T_('Registration'), '?ctrl=registration' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'users/views/_registration.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.14  2011/09/26 14:49:58  efy-yurybakh
 * colored usernames
 *
 * Revision 1.13  2011/09/06 16:25:18  efy-james
 * Require special chars in password
 *
 * Revision 1.12  2011/06/14 13:33:56  efy-asimo
 * in-skin register
 *
 * Revision 1.11  2010/11/25 15:16:35  efy-asimo
 * refactor $Messages
 *
 * Revision 1.10  2010/11/24 14:55:30  efy-asimo
 * Add user gender
 *
 * Revision 1.9  2010/05/07 08:07:14  efy-asimo
 * Permissions check update (User tab, Global Settings tab) - bugfix
 *
 * Revision 1.8  2010/01/09 13:30:12  efy-yury
 * added redirect 303 for prevent dublicate sql executions
 *
 * Revision 1.7  2010/01/03 17:45:21  fplanque
 * crumbs & stuff
 *
 * Revision 1.6  2009/12/06 22:55:19  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.5  2009/09/16 05:35:49  efy-bogdan
 * Require country checkbox added
 *
 * Revision 1.4  2009/09/15 12:11:23  efy-bogdan
 * Clean structure
 *
 * Revision 1.3  2009/09/15 09:20:49  efy-bogdan
 * Moved the "email validation" and the "security options" blocks to the Users -> Registration tab
 *
 * Revision 1.2  2009/09/15 02:43:35  fplanque
 * doc
 *
 * Revision 1.1  2009/09/14 12:01:00  efy-bogdan
 * User Registration tab
 *
 */
?>
