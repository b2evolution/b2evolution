<?php
/* <Register> */

require('../conf/b2evo_config.php');
require( dirname(__FILE__).'/'.$b2inc.'/_functions.php' );

function add_magic_quotes($array)
{
	foreach ($array as $k => $v)
	{
		if (is_array($v))
		{
			$array[$k] = add_magic_quotes($v);
		}
		else
		{
			$array[$k] = addslashes($v);
		}
	}
	return $array;
}

if (!get_magic_quotes_gpc())
{
	$HTTP_GET_VARS    = add_magic_quotes($HTTP_GET_VARS);
	$HTTP_POST_VARS   = add_magic_quotes($HTTP_POST_VARS);
	$HTTP_COOKIE_VARS = add_magic_quotes($HTTP_COOKIE_VARS);
}

$b2varstoreset = array('action');
for ($i=0; $i<count($b2varstoreset); $i++)
{
	$b2var = $b2varstoreset[$i];
	if (!isset($$b2var))
	{
		if (empty($_POST[$b2var]))
		{
			if (empty($_GET[$b2var]))
			{
				$$b2var = '';
			}
			else
			{
				$$b2var = $_GET[$b2var];
			}
		}
		else
		{
			$$b2var = $_POST[$b2var];
		}
	}
}

if (!$users_can_register)
{
	$action = 'disabled';
}

switch($action)
{
	case 'register':
		/*
		 * Do the registration:
		 */
		param( 'redirect_to', 'string', $pathserver.'b2edit.php' );

		function filter($value)	{
			return ereg("^[a-zA-Z0-9\_-\|]+$",$value);
		}

		param( 'user_login', 'string', '' );
		param( 'pass1', 'string', '' );
		param( 'pass2', 'string', '' );
		param( 'user_email', 'string', '' );


		/* checking login has been typed */
		if($user_login == '')
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('please enter a Login'));
		}

		if( preg_match( '/[^A-Za-z0-9]/', $user_login ) )
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('please enter a valid Login (only letters and numbers are allowed)'));
		} 

		/* checking the password has been typed twice */
		if($pass1 == '' || $pass2 == '')
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('please enter your password twice'));
		}

		/* checking the password has been typed twice the same */
		if($pass1 != $pass2)
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('please type the same password in the two password fields'));
		}
		$user_nickname = $user_login;

		/* checking e-mail address */
		if($user_email == '')
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('please type your e-mail address'));
		}
		elseif (!is_email($user_email))
		{
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('the email address is invalid'));
		}

		// Connecting to the db:
		dbconnect();

		/* checking the login isn't already used by another user */
		$request =  "SELECT user_login FROM $tableusers WHERE user_login = '$user_login'";
		$result = mysql_query($request) or mysql_oops( $request );
		$lines = mysql_num_rows($result);

		mysql_free_result($result);

		if ($lines >= 1) {
			die ('<strong>'. T_('ERROR'). "</strong>: ". T_('this login is already registered, please choose another one'). "");
		}

		$user_ip			= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$user_domain	= isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '';
		$user_browser	= isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

		$user_login	= addslashes($user_login);
		$pass1 = addslashes($pass1);
		$user_nickname = addslashes($user_nickname);

		$query = "INSERT INTO $tableusers " .
					"(user_login, user_pass, user_nickname, user_email, user_ip, user_domain, user_browser, dateYMDhour, user_level, user_idmode) " .
					"VALUES ('$user_login', '" . md5($pass1) . "', '$user_nickname', '$user_email', '$user_ip', '$user_domain', '$user_browser', NOW(), '$new_users_can_blog', 'nickname')";
		$result = mysql_query($query) or mysql_oops( $query );

		$stars='';
		for ($i = 0; $i < strlen($pass1); $i++)
		{
			$stars .= '*';
		}

		$message  = T_('new user registration on your blog', $default_locale). ":\n\n";
		$message .= T_('Login', $default_locale). ": $user_login\n\n". T_('Email', $default_locale). ": $user_email\n\n";
		$message .= T_('Manage users', $default_locale). ": $pathserver/b2team.php\n\n";

		@mail( $admin_email, T_('new user registration on your blog', $default_locale), $message, "From: $notify_from\nX-Mailer: b2evolution $b2_version - PHP/".phpversion());

	?><html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo T_('b2evo') ?> &gt; <?php echo T_('Registration complete') ?></title>
<link rel="stylesheet" href="b2.css" type="text/css">
<style type="text/css">
<!--
<?php
		if(strpos($HTTP_USER_AGENT, 'Nav') === false) // if(!preg_match("/Nav/",$HTTP_USER_AGENT))
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
<td class="b2menutop" align="center">
<?php echo T_('Registration complete') ?>
</td>
</tr>

<tr height="150"><td align="right" valign="bottom" height="150" colspan="2">

<table width="180">
<tr><td align="right" colspan="2"><?php echo T_('Login') ?>: <strong><?php echo $user_login ?>&nbsp;</strong></td></tr>
<tr><td align="right" colspan="2"><?php echo T_('Password') ?>: <strong><?php echo $stars ?>&nbsp;</strong></td></tr>
<tr><td align="right" colspan="2"><?php echo T_('Email') ?>: <strong><?php echo $user_email ?>&nbsp;</strong></td></tr>
<tr><td width="90">&nbsp;</td>
<td><form name="login" action="b2login.php" method="post">
<input type="hidden" name="log" value="<?php echo $user_login ?>" />
<input type="hidden" name="redirect_to" value="<?php echo $redirect_to ?>" />
<input type="submit" class="search" value="Login" name="submit" /></form></td></tr>
</table>
</td>
</tr>
</table>

</td>
</tr>
</table>

</div>
</body>
</html>

<?php
	break; // case 'register'

	case 'disabled':
		/*
		 * Registration disabled:
		 */
	?><html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo T_('b2evo') ?> &gt; <?php echo T_('Registration Currently Disabled') ?></title>
	<link rel="stylesheet" href="b2.css" type="text/css">
<style type="text/css">
<!--
<?php
		if(strpos($HTTP_USER_AGENT, 'Nav') === false) // if(!preg_match("/Nav/",$HTTP_USER_AGENT))
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
<td class="b2menutop" align="center">
registration disabled<br />
</td>
</tr>

<tr height="150">
<td align="center" valign="center" height="150" colspan="2">
<table width="80%" height="100%">
<tr><td class="b2menutop">
<?php echo T_('User registration is currently not allowed.') ?><br />
<a href="<?php echo $baseurl ?>" ><?php echo T_('Home') ?></a>
</td></tr></table>
</td>
</tr>
</table>

</td>
</tr>
</table>

</body>
</html>

<?php
	break; // case 'disabled'

	default:
		/*
		 * Default: registration form:
		 */
		param( 'redirect_to', 'string', $pathserver.'b2edit.php' );

	?><html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
<title><?php echo T_('b2evo') ?> &gt; <?php echo T_('Register form') ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
<link rel="stylesheet" href="b2.css" type="text/css">
<style type="text/css">
<!--
<?php
		if(strpos($HTTP_USER_AGENT, 'Nav') === false) // if(!preg_match("/Nav/",$HTTP_USER_AGENT))
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
<td class="b2menutop" align="center">
registration<br />
</td>
</tr>

<tr height="150"><td align="right" valign="bottom" height="150" colspan="2">

<form method="post" action="b2register.php">
<input type="hidden" name="action" value="register" />
<input type="hidden" name="redirect_to" value="<?php echo $redirect_to ?>" />
<table border="0" width="180" class="menutop" style="background-color: #ffffff">
<tr>
<td width="150" align="right"><?php echo T_('Login:') ?></td>
<td>
<input type="text" name="user_login" size="8" maxlength="20" />
</td>
</tr>
<tr>
<td align="right"><?php echo T_('Password:') ?><br /><?php echo T_('(twice)') ?></td>
<td>
<input type="password" name="pass1" size="8" maxlength="100" />
<br />
<input type="password" name="pass2" size="8" maxlength="100" />
</td>
</tr>
<tr>
<td align="right"><?php echo T_('Email') ?></td>
<td>
<input type="text" name="user_email" size="8" maxlength="100" />
</td>
</tr>
<tr>
<td>&nbsp;</td>
<td><input type="submit" value="OK" class="search" name="submit">
</td>
</tr>
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
