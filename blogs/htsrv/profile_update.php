<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file update the current user's profile!
 */

// Initialize everything:
require_once( dirname(__FILE__) . '/../b2evocore/_main.php' );

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
param( 'newuser_notify', 'integer', 0 );
param( 'pass1', 'string', '' );
param( 'pass2', 'string', '' );

if( ! is_logged_in() )
{	// must be logged in!
	die( T_('You are not logged in.') );
}

if( $checkuser_id != $user_ID )
{	// Can only edit your own profile
	die( T_('You are not logged in under the same account you are trying to modify.') );
}

if( $demo_mode && ($user_login == 'demouser'))
{
	die( 'Demo mode: you can\'t edit the demouser profile!<br />[<a href="javascript:history.go(-1)">' 
				. T_('Back to profile') . '</a>]' );
}

// checking the nickname has been typed
if (empty($newuser_nickname))
{
	die ('<strong>' . T_('ERROR') . '</strong>: ' . T_('please enter your nickname (can be the same as your login)')
				. '<br />[<a href="javascript:history.go(-1)">' . T_('Back to profile') . '</a>]' );
	return false;
}

// if the ICQ UIN has been entered, check to see if it has only numbers
if (!empty($newuser_icq))
{
	if (!ereg("^[0-9]+$", $newuser_icq))
	{
		die ('<strong>' . T_('ERROR') . '</strong>: ' . T_('your ICQ UIN can only be a number, no letters allowed')
					. '<br />[<a href="javascript:history.go(-1)">' . T_('Back to profile') . '</a>]' );
		return false;
	}
}

// checking e-mail address
if (empty($newuser_email))
{
	die ('<strong>' . T_('ERROR') . '</strong>: ' . T_('please type your e-mail address')
				. '<br />[<a href="javascript:history.go(-1)">' . T_('Back to profile') . '</a>]' );
	return false;
}
elseif (!is_email($newuser_email))
{
	die ('<strong>' . T_('ERROR') . '</strong>: ' . T_('the email address is invalid')
				. '<br />[<a href="javascript:history.go(-1)">' . T_('Back to profile') . '</a>]' );
	return false;
}

if ($pass1 == '')
{
	if ($pass2 != '')
	{
		die ('<strong>' . T_('ERROR') . '</strong>: ' . T_('you typed your new password only once. Go back to type it twice.'));
	}
	$updatepassword = '';
}
else
{
	if ($pass2 == '')
	{
		die ('<strong>' . T_('ERROR') . '</strong>: ' . T_('you typed your new password only once. Go back to type it twice.') );
	}
	if ($pass1 != $pass2)
	{
		die ('<strong>' . T_('ERROR') . '</strong>: ' . T_('you typed two different passwords. Go back to correct that.') );
	}
	$newuser_pass = md5($pass1);
	$updatepassword = "user_pass = '$newuser_pass', ";
	if( !setcookie( $cookie_pass, $newuser_pass, $cookie_expires, $cookie_path, $cookie_domain) )
	{
		printf( T_('setcookie %s failed!'), $cookie_pass );
	}
}


$DB->query( "UPDATE $tableusers 
								SET $updatepassword
										user_firstname= '".$DB->escape($newuser_firstname)."',
										user_lastname= '".$DB->escape($newuser_lastname)."',
										user_nickname= '".$DB->escape($newuser_nickname)."',
										user_icq= '".$DB->escape($newuser_icq)."',
										user_email= '".$DB->escape($newuser_email)."',
										user_url= '".$DB->escape($newuser_url)."',
										user_aim= '".$DB->escape($newuser_aim)."',
										user_msn= '".$DB->escape($newuser_msn)."',
										user_yim= '".$DB->escape($newuser_yim)."',
										user_idmode= '".$DB->escape($newuser_idmode)."',
										user_notify= $newuser_notify
							WHERE ID = $user_ID" );

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

param( 'redirect_to', 'string' );
$location = (!empty($redirect_to)) ? $redirect_to : $_SERVER['HTTP_REFERER'];
header('Refresh:0;url=' . $location);

?>
