<?php
/**
 * Register a new user.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once dirname(__FILE__).'/'.$htsrv_dirout.$core_subdir.'_main.inc.php';


param( 'action', 'string', '' );
param( 'login', 'string', '' );
param( 'email', 'string', '' );
param( 'locale', 'string', $Settings->get('default_locale') );

locale_activate( $locale );

if(!$Settings->get('newusers_canregister'))
{
	$action = 'disabled';
}

switch( $action )
{
	case 'register':
		/*
		 * Do the registration:
		 */
		param( 'redirect_to', 'string', $admin_url.'b2edit.php' );
		param( 'pass1', 'string', '' );
		param( 'pass2', 'string', '' );

		profile_check_params( array( 'login' => $login,
																	'pass1' => $pass1,
																	'pass2' => $pass2,
																	'email' => $email,
																	'pass_required' => true ) );

		if( $UserCache->get_by_login( $login ) )
		{ // The login is already registered
			$Messages->add( sprintf( T_('The login &laquo;%s&raquo; is already registered, please choose another one.'), $login ), 'error' );
			break;
		}

		if( !$Messages->count( 'error' ) )
		{
			// TODO: START TRANSACTION !!

			$new_User = & new User();
			$new_User->set( 'login', $login );
			$new_User->set( 'pass', md5($pass1) ); // encrypted
			$new_User->set( 'nickname', $login );
			$new_User->set( 'email', $email );
			$new_User->set( 'ip', $Hit->IP );
			$new_User->set( 'domain', $Hit->get_remote_host() );
			$new_User->set( 'browser', $Hit->user_agent );
			$new_User->set_datecreated( $localtimenow );
			$new_User->set( 'locale', $locale );
			$newusers_grp_ID = $Settings->get('newusers_grp_ID');
			// echo $newusers_grp_ID;
			$new_user_Group = & $GroupCache->get_by_ID( $newusers_grp_ID );
			// echo $new_user_Group->disp('name');
			$new_User->setGroup( $new_user_Group );
			$new_User->dbinsert();

			$UserCache->add( $new_User );

			// TODO: Optionally auto create a blog (handle this together with the LDAP plugin)

			// TODO: Optionally auto assign rights

			// TODO: END TRANSACTION !!

			// switch to admins locale
			$AdminUser = & $UserCache->get_by_ID( 1 );
			locale_temp_switch( $AdminUser->get( 'locale' ) );

			$message  = T_('New user registration on your blog').":\n"
									."\n"
									.T_('Login:')." $login\n"
									.T_('Email').": $email\n"
									."\n"
									.T_('Manage users').': '.$admin_url."b2users.php\n";

			send_mail( $admin_email, T_('New user registration on your blog'), $message, $notify_from );

			locale_restore_previous();

			// Display confirmation screen:
			require( dirname(__FILE__).'/_reg_complete.php' );

			exit();
		}
		break;


	case 'disabled':
		/*
		 * Registration disabled:
		 */
		require( dirname(__FILE__).'/_reg_disabled.php' );

		exit();
}


/*
 * Default: registration form:
 */
param( 'redirect_to', 'string', $admin_url.'b2edit.php' );
// Display reg form:
require( dirname(__FILE__).'/_reg_form.php' );

?>
