<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 */
require_once(dirname(__FILE__).'/../conf/b2evo_config.php');
require_once(dirname(__FILE__)."/$b2inc/_main.php");

param( 'action', 'string', '' );

if(!$users_can_register)
{
	$action = 'disabled';
}

switch($action)
{
	case 'register':
		/*
		 * Do the registration:
		 */
		param( 'redirect_to', 'string', $pathserver.'/b2edit.php' );

		param( 'login', 'string', '' );
		param( 'pass1', 'string', '' );
		param( 'pass2', 'string', '' );
		param( 'email', 'string', '' );

		// checking login has been typed:
		if($login == '')
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('please enter a Login'));
		}

		// checking the password has been typed twice
		if($pass1 == '' || $pass2 == '')
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('please enter your password twice'));
		}

		// checking the password has been typed twice the same:
		if($pass1 != $pass2)
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('please type the same password in the two password fields'));
		}
		$user_nickname = $login;

		// checking e-mail address:
		if($email == '')
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('please type your e-mail address'));
		}
		elseif (!is_email($email))
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('the email address is invalid'));
		}

		// TODO: START TRANSACTION !!

		// checking the login isn't already used by another user:
		$request =  "SELECT user_login FROM $tableusers WHERE user_login = '$login'";
		$result = mysql_query($request) or mysql_oops( $request );
		$lines = mysql_num_rows($result);

		mysql_free_result($result);

		if ($lines >= 1) 
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('this login is already registered, please choose another one'). "");
		}

		$user_ip			= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$user_domain	= isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '';
		$user_browser	= isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

		$user_login	= addslashes($login);
		$pass1 = md5($pass1);
		$user_nickname = addslashes($user_nickname);

		$query = "INSERT INTO $tableusers " .
					"(user_login, user_pass, user_nickname, user_email, user_ip, user_domain, user_browser, dateYMDhour, user_level, user_idmode) " .
					"VALUES ('$user_login', '$pass1', '$user_nickname', '$email', '$user_ip', '$user_domain', '$user_browser', NOW(), '$new_users_can_blog', 'nickname')";
		$result = mysql_query($query) or mysql_oops( $query );

		// TODO: END TRANSACTION !!

		$message  = T_('new user registration on your blog', $default_locale). ":\n\n";
		$message .= T_('Login', $default_locale). ": $user_login\n\n". T_('Email', $default_locale). ": $user_email\n\n";
		$message .= T_('Manage users', $default_locale). ": $pathserver/b2team.php\n\n";

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


	default:
		/*
		 * Default: registration form:
		 */
		param( 'redirect_to', 'string', $pathserver.'/b2edit.php' );
		// Display reg form:
		require( dirname(__FILE__).'/_reg_form.php' );
		exit();

} // switch

?>
