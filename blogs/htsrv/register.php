<?php
/**
 * Register a new user
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 */

/**
 * Includes:
 */
require_once( dirname(__FILE__).'/../conf/_config.php' );
require_once( dirname(__FILE__).'/'.$htsrv_dirout.$core_subdir.'_main.inc.php' );


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
																	'email' => $email,) );

		if( $UserCache->get_by_login( $login ) )
		{ // The login is already registered
			$Messages->add( sprintf( T_('The login &laquo;%s&raquo; is already registered, please choose another one.'), $login ) );
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
			$new_User->set( 'ip', '127.0.0.1' );
			$new_User->set( 'domain', 'localhost' );
			$new_User->set( 'ip', getIpList( true ) );
			$new_User->set( 'domain', isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '' );
			$new_User->set( 'browser', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '' );
			$new_User->set_datecreated( $localtimenow );
			$new_User->set( 'level', $Settings->get('newusers_level') );
			$new_User->set( 'locale', $locale );
			$newusers_grp_ID = $Settings->get('newusers_grp_ID');
			// echo $newusers_grp_ID;
			$new_user_Group = $GroupCache->get_by_ID( $newusers_grp_ID );
			// echo $new_user_Group->disp('name');
			$new_User->setGroup( $new_user_Group );
			$new_User->dbinsert();

			$UserCache->add( $new_User );

			// TODO: Optionally auto create a blog

			// TODO: Optionally auto assign rights

			// TODO: END TRANSACTION !!

			// switch to admins locale
			$AdminUser =& $UserCache->get_by_ID( 1 );
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
