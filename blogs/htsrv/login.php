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
		param( 'redirect_to', 'string', $htsrvurl.'/login.php' );

		setcookie( 'cafeloguser' );		// OLD
		setcookie( 'cafeloguser', '', $cookie_expired, $cookie_path, $cookie_domain); // OLD
		setcookie( $cookie_user, '', $cookie_expired, $cookie_path, $cookie_domain);

		setcookie( 'cafelogpass');			// OLD
		setcookie( 'cafelogpass', '', $cookie_expired, $cookie_path, $cookie_domain);	// OLD
		setcookie( $cookie_pass, '', $cookie_expired, $cookie_path, $cookie_domain);

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate"); // for HTTP/1.1
		header("Pragma: no-cache");

		header("Refresh:0;url=$redirect_to");
		exit();
		break; // case 'logout'


	case 'login':
		/*
		 * Logout:
		 */
		param( 'redirect_to', 'string', $pathserver.'/b2edit.php' );

		$log = trim(strip_tags(get_magic_quotes_gpc() ? stripslashes($_POST['log']) : $_POST['log']));
		$pwd = md5(trim(strip_tags(get_magic_quotes_gpc() ? stripslashes($_POST['pwd']) : $_POST['pwd'])));
		unset($_POST['pwd']); // password is hashed from now on

		function login()
		{
			global $dbhost, $dbusername, $dbpassword, $dbname, $log, $pwd, $error, $user_ID;
			global $tableusers;
			$user_login = $log;
			if (!$user_login) 
			{
				$error='<strong>'. T_('ERROR'). '</strong>: '. T_('The login field is empty');
				return false;
			}

			if (!$pwd) 
			{
				$error='<strong>'. T_('ERROR'). '<\\1strong>: '. T_('the password field is empty');
				return false;
			}

			$query =  "SELECT ID, user_login, user_pass FROM $tableusers WHERE user_login = '$user_login' AND user_pass = '$pwd'";
			$result = mysql_query($query) or mysql_oops( $query );

			$lines = mysql_num_rows($result);
			if ($lines >= 1)
			{
				return true;
			}
			else {
				$error = '<strong>' . T_('ERROR') . '</strong>: ' . T_('Wrong login or password');
				$pwd = '';
				return false;
			}
		}

		if (!login())
		{	// Login failed
			// echo 'login failed!!';
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-cache, must-revalidate");
			header("Pragma: no-cache");

			header('Refresh:0;url='.$htsrvurl.'/login.php?error='.urlencode( $error ) );

			exit();
		}
		else
		{
			//echo $user_login, $pass_is_md5, $user_pass,  $cookie_domain;
			if( !setcookie( $cookie_user, $log, $cookie_expires, $cookie_path, $cookie_domain ) )
				printf( T_('setcookie %s failed!').'<br />', $cookie_user );
			if( !setcookie( $cookie_pass, $pwd, $cookie_expires, $cookie_path, $cookie_domain) )
				printf( T_('setcookie %s failed!').'<br />', $cookie_user );

			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-cache, must-revalidate");
			header("Pragma: no-cache");

			switch($mode)
			{
				case 'bookmarklet':
					// Caution: any ; (like in &amp;) in the $location will break the refresh!
					$location = $redirect_to.'?popuptitle='.urlencode($popuptitle).'&popupurl='.urlencode($popupurl).'&text='.urlencode($text);
					break;

				case 'sidebar':
					// Caution: any ; (like in &amp;) in the $location will break the refresh!
					$location = $redirect_to.'b2sidebar.php?popuptitle='.urlencode($popuptitle).'&popupurl='.urlencode($popupurl).'&text='.urlencode($text);
					break;

				default:
					$location = $redirect_to;
					break;
			}

			header("Refresh:1;url=$location");
			?>
			<html>
				<head></head>
				<body>
					<p>Your are being redirected.</p>
					<p>If nothing happens, <a href="<?php echo $location ?>">click here</a>.</p>
				</body>
			</html>
			<?php
			
		}

		break; // case 'login'


	case 'lostpassword':
		/*
		 * Lost password:
		 */
		param( 'redirect_to', 'string', $htsrvurl.'/login.php' );
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
			echo '<a href="', $htsrvurl, '/login.php">', T_('Click here to login !'), '</a></p>';
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
		echo '<a href="', $htsrvurl, '/login.php">', T_('Click here to login !'), '</a></p>';

		break; // case 'retrievepassword'


	default:
		/*
		 * Default: login form:
		 */
		param( 'redirect_to', 'string', $pathserver.'/b2edit.php' );
		param( 'log', 'string', '' );

		if( is_loggued_in() )
		{	// The user is already loggued in, no need to go thru this...
		
			header("Expires: Wed, 5 Jun 1979 23:41:00 GMT"); /* private joke: this is Michel's birthdate - though officially it's on the 6th, since he's on GMT+1 :) */
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); /* different all the time */
			header("Cache-Control: no-cache, must-revalidate"); /* to cope with HTTP/1.1 */
			header("Pragma: no-cache");

			header("Location: $redirect_to");
			exit();
		}
		// Display login form:
		require( dirname(__FILE__).'/_login_form.php' );
		exit();
		break; // case default

} // switch

?>
