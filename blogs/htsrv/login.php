<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 */
require_once(dirname(__FILE__).'/../conf/_config.php');
require_once(dirname(__FILE__)."/$htsrv_dirout/$core_subdir/_main.php");

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
		$location = isset($redirect_to) ? $redirect_to : $baseurl.'/' ;
		header("Refresh:0;url=$location");
		exit();
		break; // case 'logout'


	case 'lostpassword':
		/*
		 * Lost password:
		 */
		param( 'redirect_to', 'string', $htsrv_url.'/login.php' );
		// Display retrieval form:
		require( dirname(__FILE__).'/_lostpass_form.php' );
		exit();
		break; // case 'lostpassword'



	case 'retrievepassword':
		/*
		 * Retrieve password:
		 */
		param( 'user_login', 'string', true );
		$user_data	= get_userdatabylogin($user_login);
		$user_email	= $user_data['user_email'];

		if (empty($user_email))
		{	// pretend that the email is sent for avoiding guessing user_login
			echo '<p>', T_('The email was sent successfully to your email address.'), "<br />\n";
			echo '<a href="', $htsrv_url, '/login.php">', T_('Click here to login !'), '</a></p>';
			die();
		}

		$random_password = substr(md5(uniqid(microtime())),0,6);
		$query = "UPDATE $tableusers SET user_pass = '" . md5($random_password) . "' WHERE user_login = '$user_login'";
		$result = mysql_query($query) or mysql_oops( $query );

		$message  = T_('Login:')." $user_login\r\n";
		$message .= T_('New Password:')." $random_password\r\n";
		
		// DEBUG!
		// echo $message;

		if( ! mail($user_email, T_('your weblog\'s login/password'), $message, "From: $notify_from\nX-Mailer: b2evolution $b2_version - PHP/".phpversion()))
		{
			echo '<p>', T_('The email could not be sent.'), "<br />\n";
			echo T_('Possible reason: your host may have disabled the mail() function...</p>');
			die();
		}
		
		echo '<p>', T_('The email was sent successfully to your email address.'), "<br />\n";
		echo '<a href="', $htsrv_url, '/login.php">', T_('Click here to login !'), '</a></p>';

		break; // case 'retrievepassword'


	default:
		/*
		 * Default: login form:
		 */
		if( is_logged_in() )
		{	// The user is already logged in...
			$error = T_('Note: You are already logged in!');
		}
		
		// Display login form:
		require( dirname(__FILE__).'/_login_form.php' );
		exit();

} // switch

?>
