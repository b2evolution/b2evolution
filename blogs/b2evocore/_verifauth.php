<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */

require_once(dirname(__FILE__).'/../conf/b2evo_config.php');
require_once (dirname(__FILE__).'/_functions.php');

// Connecting to the db:
dbconnect();

/* checking login & pass in the database */
function veriflog()
{
	global $tableusers, $tablesettings, $tablecategories, $tablecomments, $cookie_user, $cookie_user, $cookie_pass;
	global $error;

	if (!empty($_COOKIE[$cookie_user])) 
	{
		$user_login = $_COOKIE[$cookie_user];
		$user_pass_md5 = $_COOKIE[$cookie_pass];
		// echo 'pass=', $user_pass_md5;
	}
	else
	{
		$error = T_('You must log in!');
		return false;
	}

	if ($user_login == '')
	{
		return false;
	}
	elseif($user_pass_md5 == '')
	{
		return false;
	}

	$query = "SELECT user_login, user_pass FROM $tableusers WHERE user_login = '$user_login' AND user_pass = '" . $user_pass_md5 . "'";
	$result = @mysql_query($query) or die("Query: $query<br /><br />Error: ".mysql_error());

	$lines = mysql_num_rows($result);
	if ($lines < 1)
	{
		$error='<strong>'. T_('ERROR'). "</strong>: ". T_('login no longer exists');
		return false;
	} 
	else
	{
		$res = mysql_fetch_assoc($result);
		if ($res['user_login'] == $user_login && $res['user_pass'] == $user_pass_md5)
		{
			return true;
		} else {
			$error='<strong>'. T_('ERROR'). "</strong>: ". T_('login/password no longer valid');
			return false;
		}
	}
}
/*
if ( $user_login!="" && $user_pass!="" && $id_session!="" && $adresse_ip==$REMOTE_ADDR) {
	if ( !(veriflog()) AND !(verifcookielog()) ) {
*/
	if(!veriflog())
	{
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		require(dirname(__FILE__).'/'.$pathcore_out.'/'.$backoffice_subdir."/b2login.php");
		exit();
	}
/* } */
?>
