<?php
/**
 * file
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evolution
 */
require_once(dirname(__FILE__).'/../conf/_config.php');
require_once(dirname(__FILE__)."/$htsrv_dirout/$core_subdir/_main.php");

param( 'action', 'string', '' );
param( 'login', 'string', '' );
param( 'email', 'string', '' );

if(!get_settings('pref_newusers_canregister'))
{
	$action = 'disabled';
}

switch($action)
{
	case 'register':
		/*
		 * Do the registration:
		 */
		param( 'redirect_to', 'string', $admin_url.'/b2edit.php' );
		param( 'pass1', 'string', '' );
		param( 'pass2', 'string', '' );

		// checking login has been typed:
		if($login == '')
		{
			$error = '<strong>'. T_('ERROR'). "</strong>: ". T_('please enter a Login');
			break;
		}

		// checking the password has been typed twice
		if($pass1 == '' || $pass2 == '')
		{
			$error = '<strong>'. T_('ERROR'). "</strong>: ". T_('please enter your password twice');
			break;
		}

		// checking the password has been typed twice the same:
		if($pass1 != $pass2)
		{
			$error = '<strong>'. T_('ERROR'). "</strong>: ". T_('please type the same password in the two password fields');
			break;
		}
		$user_nickname = $login;

		// checking e-mail address:
		if($email == '')
		{
			$error = '<strong>'. T_('ERROR'). "</strong>: ". T_('please type your e-mail address');
			break;
		}
		elseif (!is_email($email))
		{
			$error = '<strong>'. T_('ERROR'). "</strong>: ". T_('the email address is invalid');
			break;
		}

		// TODO: START TRANSACTION !!

		// checking the login isn't already used by another user:
		$request =  "SELECT user_login FROM $tableusers WHERE user_login = '".addslashes($login)."'";
		$result = mysql_query($request) or mysql_oops( $request );
		$lines = mysql_num_rows($result);

		mysql_free_result($result);

		if ($lines >= 1) 
		{
			$error = '<strong>'. T_('ERROR'). "</strong>: ". T_('this login is already registered, please choose another one');
			break;
		}

		$new_User = & new User();
		$new_User->set( 'login', $login );
		$new_User->set( 'pass', md5($pass1) ); // encrypted
		$new_User->set( 'nickname', $user_nickname );
		$new_User->set( 'email', $email );
		$new_User->set( 'ip', '127.0.0.1' );
		$new_User->set( 'domain', 'localhost' );
		$new_User->set( 'ip', isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '' );
		$new_User->set( 'domain', isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '' );
		$new_User->set( 'browser', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '' );
		$new_User->set_datecreated( $localtimenow );
		$new_User->set( 'level', get_settings('pref_newusers_level') );
		$pref_newusers_grp_ID = get_settings('pref_newusers_grp_ID');
		// echo $pref_newusers_grp_ID;
		$new_user_Group = Group_get_by_ID( $pref_newusers_grp_ID );
		// echo $new_user_Group->disp('name');
		$new_User->setGroup( $new_user_Group );
		$new_User->dbinsert();

		// TODO: END TRANSACTION !!

		$message  = T_('new user registration on your blog', $default_locale). ":\n\n";
		$message .= T_('Login:', $default_locale). " $login\n\n". T_('Email', $default_locale). ": $user_email\n\n";
		$message .= T_('Manage users', $default_locale). ": $admin_url/b2users.php\n\n";

		@mail( $admin_email, T_('new user registration on your blog', $default_locale), $message, "From: $notify_from\nX-Mailer: b2evolution $b2_version - PHP/".phpversion());

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
param( 'redirect_to', 'string', $admin_url.'/b2edit.php' );
// Display reg form:
require( dirname(__FILE__).'/_reg_form.php' );

?>
