<?php
/**
 * This is the login screen
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
require_once( dirname(__FILE__).'/'.$htsrv_dirout.$core_subdir.'_main.php' );

param( 'action', 'string', '' );
param( 'mode', 'string', '' );
// bookmarklet stuff:
param( 'text', 'html', '' );
param( 'popupurl', 'string', '' );
param( 'popuptitle', 'string', '' );

switch($action)
{
	case 'logout':
		/*
		 * Logout:
		 */
		// Do the log out!
		logout();

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate"); // for HTTP/1.1
		header("Pragma: no-cache");

		param( 'redirect_to', 'string', $_SERVER['HTTP_REFERER'] );
		$location = empty($redirect_to) ? $baseurl : $redirect_to;
		header('Refresh:0;url='.str_replace('&amp;', '&', $location));
		exit();
		break; // case 'logout'


	case 'lostpassword':
		/*
		 * Lost password:
		 */
		param( 'redirect_to', 'string', $admin_url.'b2edit.php' );
		// Display retrieval form:
		require( dirname(__FILE__).'/_lostpass_form.php' );
		exit();
		break; // case 'lostpassword'



	case 'retrievepassword':
		/*
		 * Retrieve lost password:
		 */
		param( 'log', 'string', true );
		param( 'redirect_to', 'string', $admin_url );
		// echo 'login: ', $log;

		if( $demo_mode && ($log == 'demouser' || $log == 'admin') )
		{
			$notes = T_('You cannot reset this account in demo mode.')."<br />\n";
		}
		else
		{
			$user_data	= get_userdatabylogin($log);
			$user_email	= $user_data['user_email'];

			locale_temp_switch( $user_data['user_locale'] );

			// echo 'email: ', $user_email;
			// echo 'locale: '.$user_data['locale'];

			if( empty($user_email) )
			{	// pretend that the email is sent for avoiding guessing user_login
				$notes = T_('An email with the new password was sent successfully to your email address.')."<br />\n";
			}
			else
			{
				$random_password = substr(md5(uniqid(microtime())),0,6);

				$message  = T_('Login:')." $log\r\n";
				$message .= T_('New Password:')." $random_password\r\n";
				$message .= "\r\n".T_('You can login here:')."\r\n".$admin_url."\r\n";

				// DEBUG!
				// echo $message.' (password not set yet, only when sending email does not fail);

				if( !send_mail( $user_email, T_('your weblog\'s login/password'), $message, $notify_from ) )
				{
					$notes = T_('The email could not be sent.')."<br />\n"
									.T_('Possible reason: your host may have disabled the mail() function...');
				}
				else
				{
					$DB->query( "UPDATE T_users
											SET user_pass = '" . md5($random_password) . "'
											WHERE user_login = '$log'" );
					$notes = T_('An email with the new password was sent successfully to your email address.')."<br />\n";
				}

			}

			locale_restore_previous();
		}

	default:
		/*
		 * Default: login form:
		 */
		if( is_logged_in() )
		{	// The user is already logged in...
			// TODO: use $login_error to be clear
			
			$error = is_string($error) ? $error.'<br />' : '';
			$error .= T_('Note: You are already logged in!');

			// Note: if $redirect_to is already set, param() will not touch it.
			param( 'redirect_to', 'string', $ReqURI );
			if( preg_match( '/login.php([&?].*)?$/', $redirect_to ) )
			{ // avoid "endless loops"
				$redirect_to = $admin_url;
			}
			$error .= ' <a href="'.$redirect_to.'">'.T_('Continue...').'</a>';
		}

		// Display login form:
		require( dirname(__FILE__).'/_login_form.php' );
		debug_info();
		exit();

} // switch

?>