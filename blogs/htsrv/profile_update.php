<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file update the current user's profile!
 */

// Initialize everything:
require_once (dirname(__FILE__).'/../b2evocore/_main.php');

// Getting GET or POST parameters:
param( 'checkuser_id', 'integer', '' );
param( 'newuser_firstname', 'string', '' );
param( 'newuser_lastname', 'string', '' );
param( 'newuser_nickname', 'string', '' );
param( 'newuser_idmode', 'string', '' );
param( 'newuser_icq', 'string', '' );
param( 'newuser_aim', 'string', '' );
param( 'newuser_msn', 'string', '' );
param( 'newuser_yim', 'string', '' );
param( 'newuser_url', 'string', '' );
param( 'newuser_email', 'string', '' );
param( 'pass1', 'string', '' );
param( 'pass2', 'string', '' );

if( ! is_loggued_in() )
{	// must be loggued in!
	die( T_('You are not loggued in.') );
}

if( $checkuser_id != $user_ID )
{	// Can only edit your own profile
	die( T_('You are not loggued in under the same account you are trying to modify.') );
}

if( $demo_mode && ($user_login == 'demouser'))
{
	die( 'Demo mode: you can\'t edit the demouser profile!'.'<br />[<a href="javascript:history.go(-1)">'. T_('Back to profile'). '</a>]' );
}

// checking the nickname has been typed
if (empty($newuser_nickname))
{
	die ('<strong>'.T_('ERROR').'</strong>: '.T_('please enter your nickname (can be the same as your login)').'<br />[<a href="javascript:history.go(-1)">'. T_('Back to profile'). '</a>]' );
	return false;
}
	
// if the ICQ UIN has been entered, check to see if it has only numbers 
if (!empty($newuser_icq))
{
	if (!ereg("^[0-9]+$", $newuser_icq))
	{
		die ('<strong>'. T_('ERROR'). '</strong>: '. T_('your ICQ UIN can only be a number, no letters allowed').'<br />[<a href="javascript:history.go(-1)">'. T_('Back to profile'). '</a>]' );
		return false;
	}
}
	
// checking e-mail address
if (empty($newuser_email))
{
	die ('<strong>'. T_('ERROR'). '</strong>: '. T_('please type your e-mail address').'<br />[<a href="javascript:history.go(-1)">'. T_('Back to profile'). '</a>]' );
	return false;
}
elseif (!is_email($newuser_email))
{
	die ('<strong>'. T_('ERROR'). '</strong>: '. T_('the email address isn\'t correct').'<br />[<a href="javascript:history.go(-1)">'. T_('Back to profile'). '</a>]' );
	return false;
}
	
if ($pass1 == '')
{
	if ($pass2 != '')
	{
		die ('<strong>'. T_('ERROR'). '</strong>: '. T_('you typed your new password only once. Go back to type it twice.'));
	}
	$updatepassword = '';
}
else
{
	if ($pass2 == '')
	{
		die ('<strong>'. T_('ERROR'). '</strong>: '. T_('you typed your new password only once. Go back to type it twice.') );
	}
	if ($pass1 != $pass2)
	{
		die ('<strong>'. T_('ERROR'). '</strong>: '. T_('you typed two different passwords. Go back to correct that.') );
	}
	$newuser_pass = md5($pass1);
	$updatepassword = "user_pass = '$newuser_pass', ";
	if( !setcookie( $cookie_pass, $newuser_pass, $cookie_expires, $cookie_path, $cookie_domain) )
	{
		printf( T_('setcookie %s failed!'), $cookie_pass );
	}

	echo '<br />';
}


$query = "UPDATE $tableusers ".
	"SET " . 	$updatepassword.
	"user_firstname = '$newuser_firstname', ".
	"user_lastname='$newuser_lastname', ".
	"user_nickname='$newuser_nickname', ".
	"user_icq='$newuser_icq', ".
	"user_email='$newuser_email', ".
	"user_url='$newuser_url', ".
	"user_aim='$newuser_aim', ".
	"user_msn='$newuser_msn', ".
	"user_yim='$newuser_yim', ".
	"user_idmode='$newuser_idmode' ".
	"WHERE ID = $user_ID";
$querycount++;
$result = mysql_query($query) or mysql_oops( $query );

//echo $query;
//exit();

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$location = (!empty($_POST['redirect_to'])) ? $_POST['redirect_to'] : $_SERVER['HTTP_REFERER'];
header("Refresh:0;url=$location");

?>
