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

		// checking login has been typed:
		if( $login == '' )
		{
			$error = '<strong>'.T_('ERROR').'</strong>: '.T_('please enter a Login');
			break;
		}

		// checking the password has been typed twice
		if( $pass1 == '' || $pass2 == '' )
		{
			$error = '<strong>'.T_('ERROR').'</strong>: '.T_('please enter your password twice');
			break;
		}

		// checking the password has been typed twice the same:
		if( $pass1 != $pass2 )
		{
			$error = '<strong>'.T_('ERROR').'</strong>: '.T_('please type the same password in the two password fields');
			break;
		}

		// checking password length
		if( strlen($pass1) < $Settings->get('user_minpwdlen') )
		{
			$error = sprintf( T_('The mimimum password length is %d characters.'), $Settings->get('user_minpwdlen'));
			break;
		}

		// checking e-mail address:
		if($email == '')
		{
			$error = '<strong>'.T_('ERROR').'</strong>: '.T_('please type your e-mail address');
			break;
		}
		elseif (!is_email($email))
		{
			$error = '<strong>'.T_('ERROR').'</strong>: '.T_('the email address is invalid');
			break;
		}

		// TODO: START TRANSACTION !!

		// checking the login isn't already used by another user:
		if( $DB->get_var( "SELECT count(*)
												FROM T_users
												WHERE user_login = '".$DB->escape($login)."'" ) )
		{
			$error = '<strong>'. T_('ERROR'). "</strong>: ". T_('this login is already registered, please choose another one');
			break;
		}

		$new_User = & new User();
		$new_User->set( 'login', $login );
		$new_User->set( 'pass', md5($pass1) ); // encrypted
		$new_User->set( 'nickname', $login );
		$new_User->set( 'email', $email );
		$new_User->set( 'ip', '127.0.0.1' );
		$new_User->set( 'domain', 'localhost' );
		$new_User->set( 'ip', isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '' );
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

		// TODO: Optionally auto create a blog

		// TODO: Optionally auto assign rights

		// TODO: END TRANSACTION !!

		// switch to admins locale
		$admin_data = get_userdata(1);
		locale_temp_switch( $admin_data['user_locale'] );

		$message  = T_('New user registration on your blog'). ":\n\n";
		$message .= T_('Login:'). " $login\n\n". T_('Email'). ": $email\n\n";
		$message .= T_('Manage users').': '.$admin_url."b2users.php\n\n";

		send_mail( $admin_email, T_('new user registration on your blog'), $message, $notify_from );

		locale_restore_previous();

		// Display confirmation screen:
		require( dirname(__FILE__).'/_reg_complete.php' );
		exit();
		break; // case 'register'


	case 'disabled':
		/*
		 * Registration disabled:
		 */
		require( dirname(__FILE__).'/_reg_disabled.php' );
		exit();
		break; // case 'disabled'


} // switch

/*
 * Default: registration form:
 */
param( 'redirect_to', 'string', $admin_url.'b2edit.php' );
// Display reg form:
require( dirname(__FILE__).'/_reg_form.php' );

?>
