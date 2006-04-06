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
 *
 * {@internal Open Source relicensing agreement:
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
require_once $inc_path.'_main.inc.php';


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
		// Call plugin event to allow catching input in general and validating own things from DisplayRegisterFormFieldset event
		$Plugins->trigger_event( 'RegisterFormSent' );

		if( $Messages->count( 'error' ) )
		{ // a Plugin has added an error
			break;
		}

		/*
		 * Do the registration:
		 */
		param( 'redirect_to', 'string', $admin_url );
		param( 'pass1', 'string', '' );
		param( 'pass2', 'string', '' );

		// Check profile params:
		profile_check_params( array( 'login' => $login, 'pass1' => $pass1, 'pass2' => $pass2, 'email' => $email, 'pass_required' => true ) );

		// We want all logins to be lowercase to guarantee uniqueness regardless of the database case handling for UNIQUE indexes:
		$login = strtolower( $login );

		if( $UserCache->get_by_login( $login ) )
		{ // The login is already registered
			$Messages->add( sprintf( T_('The login &laquo;%s&raquo; is already registered, please choose another one.'), $login ), 'error' );
			break;
		}

		if( ! $Messages->count( 'error' ) )
		{
			// TODO: START TRANSACTION !!

			$new_User = & new User();
			$new_User->set( 'login', $login );
			$new_User->set( 'pass', md5($pass1) ); // encrypted
			$new_User->set( 'nickname', $login );
			$new_User->set( 'email', $email );
			$new_User->set( 'ip', $Hit->IP );
			$new_User->set( 'domain', $Hit->get_remote_host( true ) );
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

			// Actions to be appended to the user registration transaction:
			$Plugins->trigger_event( 'AppendUserRegistrTransact', array( 'User' => & $new_User ) );

			// TODO: END TRANSACTION !!

			// switch to admins locale
			$AdminUser = & $UserCache->get_by_ID( 1 );
			locale_temp_switch( $AdminUser->get( 'locale' ) );

			$message  = T_('New user registration on your blog').":\n"
			          ."\n"
			          .T_('Login:')." $login\n"
			          .T_('Email').": $email\n"
			          ."\n"
			          .T_('Manage users').': '.$admin_url.'?ctrl=users&user_ID='.$new_User->ID."\n";

			send_mail( $admin_email, T_('New user registration on your blog'), $message, $notify_from );

			locale_restore_previous();

			// Display confirmation screen:
			require $view_path.'login/_reg_complete.php';

			exit();
		}
		break;


	case 'disabled':
		/*
		 * Registration disabled:
		 */
		require $view_path.'login/_reg_disabled.php';

		exit();
}


/*
 * Default: registration form:
 */
param( 'redirect_to', 'string', $admin_url );
// Display reg form:
require $view_path.'login/_reg_form.php';

?>