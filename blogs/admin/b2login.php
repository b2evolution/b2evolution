<?php

require_once(dirname(__FILE__)."/../conf/b2evo_config.php");
require_once(dirname(__FILE__)."/$b2inc/_functions_template.php");
require_once(dirname(__FILE__)."/$b2inc/_functions.php");
require_once(dirname(__FILE__)."/$b2inc/_vars.php");

if (!function_exists('add_magic_quotes')) 
{
	function add_magic_quotes($array) {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				$array[$k] = add_magic_quotes($v);
			} else {
				$array[$k] = addslashes($v);
			}
		}
		return $array;
	}
}

if (!get_magic_quotes_gpc()) {
	$HTTP_GET_VARS    = add_magic_quotes($HTTP_GET_VARS);
	$HTTP_POST_VARS   = add_magic_quotes($HTTP_POST_VARS);
	$HTTP_COOKIE_VARS = add_magic_quotes($HTTP_COOKIE_VARS);
}

$b2varstoreset = array('action', 'mode', 'error', 'text', 'popupurl', 'popuptitle');

for ($i = 0; $i < count($b2varstoreset); $i = $i + 1)
{
	$b2var = $b2varstoreset[$i];
	if (!isset($$b2var))
	{
		if (empty($HTTP_POST_VARS[$b2var]))
		{
			if (empty($HTTP_GET_VARS[$b2var]))
			{
				$$b2var = '';
			}
			else
			{
				$$b2var = $HTTP_GET_VARS[$b2var];
			}
		}
		else
		{
			$$b2var = $HTTP_POST_VARS[$b2var];
		}
	}
}

// Connecting to the db:
dbconnect();


switch($action) 
{
	case 'logout':

		setcookie( "cafeloguser");		// OLD
		setcookie( "cafeloguser", '', $cookie_expired, $cookie_path, $cookie_domain); // OLD
		setcookie( $cookie_user, '', $cookie_expired, $cookie_path, $cookie_domain);

		setcookie( "cafelogpass");			// OLD
		setcookie( "cafelogpass", '', $cookie_expired, $cookie_path, $cookie_domain);	// OLD
		setcookie( $cookie_pass, '', $cookie_expired, $cookie_path, $cookie_domain);

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate"); // for HTTP/1.1
		header("Pragma: no-cache");
	//if ($is_IIS) {
			header("Refresh:1;url=b2login.php");
	//} else {
	//	header("Location: b2login.php");
	//}
		exit();

	break; // case 'logout'


	case 'login':

		$log = $_POST['log'];
		$pwd = md5($_POST['pwd']);
		unset($_POST['pwd']); // password is hashed from now on
		$redirect_to = $_POST['redirect_to'];

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
			if ($is_IIS)
			{
				header('Refresh: 0;url=b2login.php?error='.urlencode( $error ) );
			} else {
				header('Location: b2login.php?error='.urlencode( $error ) );
			}
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
				case "bookmarklet":
					$location = "b2bookmarklet.php?text=$text&popupurl=$popupurl&popuptitle=$popuptitle";
					break;
				case "sidebar":
					$location = "sidebar.php?text=$text&popupurl=$popupurl&popuptitle=$popuptitle";
					break;
				case "profile":
					$location = "profile.php?text=$text&popupurl=$popupurl&popuptitle=$popuptitle";
					break;
				default:
					$location = $redirect_to;
					break;
			}

			// if ($is_IIS) {
				header("Refresh:1;url=$location");
			// } else {
			// 	header("Location: $location");
			//}
		}

	break; // case 'login'


	case 'lostpassword':

	?><html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo T_('b2evo') ?> &gt; <?php echo T_('Lost password ?') ?></title>
	<link rel="stylesheet" href="b2.css" type="text/css">
<style type="text/css">
<!--
<?php
		if(strpos($HTTP_USER_AGENT, 'Nav') === false) // if (!preg_match("/Nav/",$HTTP_USER_AGENT))
		{
?>
textarea,input,select {
	background-color: #f0f0f0;
	border-width: 1px;
	border-color: #cccccc;
	border-style: solid;
	padding: 2px;
	margin: 1px;
}
<?php
		}
?>
-->
</style>
</head>
<body bgcolor="#ffffff" text="#000000" link="#cccccc" vlink="#cccccc" alink="#ff0000">

<table width="100%" height="100%">
<td align="center" valign="middle">

<table width="200" height="200" style="border: 1px solid #cccccc;" cellpadding="0" cellspacing="0">

<tr height="50">
<td height="50" width="50">
<a href="http://b2evolution.net/" target="_blank"><img src="img/b2minilogo.png" border="0" alt="visit b2's homepage" /></a>
</td>
<td align="right" valign="top">&nbsp;</td>
</tr>

<tr height="150"><td align="right" valign="bottom" height="150" colspan="2">

<p align="center" style="color: #b0b0b0"><?php echo T_('Type your login here and click OK. You will receive an email with your password.') ?></p>
<?php
		if ($error)
		{
			echo "<div align=\"right\" style=\"padding:4px;\"><font color=\"#FF0000\">$error</font><br />&nbsp;</div>";
		}
?>

<form name="" action="b2login.php" method="post">
<input type="hidden" name="action" value="retrievepassword" />
<table width="100" style="background-color: #ffffff">
<tr><td align="right"><?php echo T_('Login') ?></td>
	<td><input type="text" name="user_login" value="" size="8" />&nbsp;&nbsp;&nbsp;</td></tr>
<tr><td>&nbsp;</td>
	<td><input type="submit" name="Submit2" value="<?php echo T_('OK') ?>" class="search">&nbsp;&nbsp;&nbsp;</td></tr>
</table>

</form>

</td>
</tr>
</table>
</td>
</tr>
</table>

</body>
</html>
	<?php

	break; // case 'lostpassword'

	case 'retrievepassword':

		$user_login	= $_POST['user_login'];
		$user_data	= get_userdatabylogin($user_login);
		$user_email	= $user_data['user_email'];

		if (empty($user_email))
		{
			// pretend that the email is sent for avoiding guessing user_login
			echo '<p>', T_('The email was sent successfully to your email address.'), "<br />\n";
			echo '<a href="b2login.php">', T_('Click here to login !'), '</a></p>';
			die();
		}
		else
		{
			$random_password = substr(md5(uniqid(microtime())),0,6);
			$query = "UPDATE $tableusers SET user_pass = '" . md5($random_password) . "' WHERE user_login = '$user_login'";
			$result = mysql_query($query) or mysql_oops( $query );

			$message  = T_('Login:')." $user_login\r\n";
			$message .= T_('New Password:')." $random_password\r\n";

		// DEBUG!
		// echo $message;

		if(mail($user_email, T_('your weblog\'s login/password'), $message, "From: $notify_from\nX-Mailer: b2evolution $b2_version - PHP/".phpversion()))
		{
			echo '<p>', T_('The email was sent successfully to your email address.'), "<br />\n";
			echo '<a href="b2login.php">', T_('Click here to login !'), '</a></p>';
			die();
		}
		else
		{
			echo '<p>', T_('The email could not be sent.'), "<br />\n";
			echo T_('Possible reason: your host may have disabled the mail() function...</p>');
			die();
		}

	break; // case 'retrievepassword'


	default:

		if(!empty($_COOKIE[$cookie_user]) && !empty($_COOKIE[$cookie_pass]))
		{
			$user_login		= $_COOKIE[$cookie_user];
			$user_pass_md5 = $_COOKIE[$cookie_pass];
		}

		function checklogin()
		{
			global $dbhost, $dbusername, $dbpassword, $dbname;
			global $user_login, $user_pass_md5, $user_ID;

			// echo $user_login; exit;
			$userdata = get_userdatabylogin($user_login);

			if ($user_pass_md5 != '' && ($user_pass_md5 == $userdata['user_pass']))
			{
				return true;
			} else {
				return false;
			}
		}

		if (checklogin())
		{
			header("Expires: Wed, 5 Jun 1979 23:41:00 GMT"); /* private joke: this is Michel's birthdate - though officially it's on the 6th, since he's on GMT+1 :) */
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); /* different all the time */
			header("Cache-Control: no-cache, must-revalidate"); /* to cope with HTTP/1.1 */
			header("Pragma: no-cache");
			header("Location: b2edit.php");
			exit();
		}
		elseif (!empty($_COOKIE[$cookie_user]))
		{
			$error = '<strong>'. T_('ERROR'). '</strong>: '. T_('Wrong login or password');
		}

	?><html xml:lang="<?php echo locale_lang ?>" lang="<?php echo locale_lang ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo T_('b2evo') ?> &gt; <?php echo T_('Login form') ?></title>
	<link rel="stylesheet" href="b2.css" type="text/css">
<style type="text/css">
<!--
<?php
		if (!preg_match("/Nav/",$HTTP_USER_AGENT))
		{
?>
textarea,input,select {
	background-color: #f0f0f0;
	border-width: 1px;
	border-color: #cccccc;
	border-style: solid;
	padding: 2px;
	margin: 1px;
}
<?php
		}
?>
-->
</style>
</head>
<body bgcolor="#ffffff" text="#000000" link="#cccccc" vlink="#cccccc" alink="#ff0000">

<table width="100%" height="100%">
<td align="center" valign="middle">

<table width="200" height="200" style="border: 1px solid #cccccc;" cellpadding="0" cellspacing="0">

<tr height="50">
<td height="50" width="50">
<a href="http://cafelog.com" target="_blank"><img src="img/b2minilogo.png" border="0" alt="visit b2's homepage" /></a>
</td>
<td align="right" valign="top">
<a href="b2register.php" class="b2menutop"><?php echo T_('register ?') ?></a><br />
<a href="b2login.php?action=lostpassword" class="b2menutop"><?php echo T_('lost your password ?') ?></a>
</td>
</tr>

<tr height="150"><td align="right" valign="bottom" height="150" colspan="2">

<?php
		if ($error)
		{
			echo "<div align=\"right\" style=\"padding:4px;\"><font color=\"#FF0000\">$error</font><br />&nbsp;</div>";
		}
?>

<form name="" action="b2login.php" method="post">
<?php
		if ($mode=="bookmarklet")
			{
?>
<input type="hidden" name="mode" value="<?php echo $mode ?>" />
<input type="hidden" name="text" value="<?php echo $text ?>" />
<input type="hidden" name="popupurl" value="<?php echo $popupurl ?>" />
<input type="hidden" name="popuptitle" value="<?php echo $popuptitle ?>" />
<?php
			}
?>
<input type="hidden" name="redirect_to" value="b2edit.php" />
<input type="hidden" name="action" value="login" />
<table width="100" style="background-color: #ffffff">
<tr><td align="right"><?php echo T_('Login:') ?></td>
	<td><input type="text" name="log" value="" size="8" />&nbsp;&nbsp;&nbsp;</td></tr>
<tr><td align="right"><?php echo T_('Password:') ?></td>
	<td><input type="password" name="pwd" value="" size="8" />&nbsp;&nbsp;&nbsp;</td></tr>
<tr><td>&nbsp;</td>
	<td><input type="submit" name="Submit2" value="OK" class="search">&nbsp;&nbsp;&nbsp;</td></tr>
</table>

</form>

</td>
</tr>
</table>
</td>
</tr>
</table>

</body>
</html>
	<?php

	break; // case default
} // switch

?>
