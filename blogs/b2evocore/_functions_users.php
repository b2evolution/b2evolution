<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */

/*
 * user_create(-)
 *
 * Create a new user
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function user_create()
{
}


/*
 * user_update(-)
 *
 * Update a user
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function user_update( $post_id )
{

}


/*
 * user_delete(-)
 *
 * Delete a user
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function user_delete( $post_id )
{

}



function user_pass_ok($user_login,$user_pass) {
	global $cache_userdata,$use_cache;
	if ((empty($cache_userdata[$user_login])) OR (!$use_cache)) {
		$userdata = get_userdatabylogin($user_login);
	} else {
		$userdata = $cache_userdata[$user_login];
	}
	return ($user_pass == $userdata['user_pass']);
}

function get_userdata($userid) 
{
	global $tableusers,$querycount,$cache_userdata,$use_cache;
	if ((empty($cache_userdata[$userid])) OR (!$use_cache)) {
	/*	$sql = "SELECT * FROM $tableusers WHERE ID = '$userid'";
		$result = mysql_query($sql) or die("Your SQL query: <br />$sql<br /><br />MySQL said:<br />".mysql_error());
		$myrow = mysql_fetch_array($result);
		$querycount++;
		$cache_userdata[$userid] = $myrow;
		*
		* Optimized by: R. U. Serious
		*/
		$sql = "SELECT * FROM $tableusers"; 
		$result = mysql_query($sql) or mysql_oops( $sql );
		$querycount++; 
		while ($myrow = mysql_fetch_array($result)) 
		{ 
			 $cache_userdata[$myrow['ID']] = $myrow; 
		} 
		$myrow = $cache_userdata[$userid]; 
	} else {
		$myrow = $cache_userdata[$userid];
	}
	return($myrow);
}

function get_userdata2($userid) 
{ // for team-listing
	global $tableusers,$row;
	$user_data['ID'] = $userid;
	$user_data['user_login'] = $row->user_login;
	$user_data['user_firstname'] = $row->user_firstname;
	$user_data['user_lastname'] = $row->user_lastname;
	$user_data['user_nickname'] = $row->user_nickname;
	$user_data['user_level'] = $row->user_level;
	$user_data['user_email'] = $row->user_email;
	$user_data['user_url'] = $row->user_url;
	return($user_data);
}

function get_userdatabylogin($user_login) 
{
	global $tableusers,$querycount,$cache_userdata,$use_cache;
	if ((empty($cache_userdata["$user_login"])) OR (!$use_cache)) 
	{
		$sql = "SELECT * FROM $tableusers WHERE user_login = '$user_login'";
		$result = mysql_query($sql) or mysql_oops( $sql );
		$myrow = mysql_fetch_array($result);
		$querycount++;
		$cache_userdata["$user_login"] = $myrow;
	} else {
		$myrow = $cache_userdata["$user_login"];
	}
	return($myrow);
}

function get_userid($user_login) {
	global $tableusers,$querycount,$cache_userdata,$use_cache;
	if ((empty($cache_userdata["$user_login"])) OR (!$use_cache)) {
	/*	$sql = "SELECT ID FROM $tableusers WHERE user_login = '$user_login'";
		$result = mysql_query($sql) or die("No user with the login <i>$user_login</i>");
		$myrow = mysql_fetch_array($result);
		$querycount++;
		$cache_userdata["$user_login"] = $myrow;
	 * 
	 * Optimized by R. U. Serious
	 */
		$sql = "SELECT user_login, ID FROM $tableusers"; 
		$result = mysql_query($sql) or mysql_oops( $sql ); 
		$querycount++; 
		while ($myrow = mysql_fetch_array($result)) 
		{ 
			 $cache_userdata[$myrow['user_login']] = $myrow['ID']; 
		} 
		$myrow = $cache_userdata["$user_login"]; 
	}
	return($myrow[0]);
}

function get_usernumposts($userid) 
{
	global $tableusers,$tablesettings,$tablecategories,$tableposts,$tablecomments,$querycount;
	$sql = "SELECT * FROM $tableposts WHERE post_author = $userid";
	$result = mysql_query($sql) or mysql_oops( $sql );
	$querycount++;
	return mysql_num_rows($result);
}

/*
 * get_currentuserinfo(-) 
 */
function get_currentuserinfo() 
{ // a bit like get_userdata(), on steroids
	global $user_login, $userdata, $user_level, $user_ID, $user_nickname, $user_email, $user_url, $user_pass_md5, $cookie_user;
	// *** retrieving user's data from cookies and db - no spoofing
	$user_login		= $_COOKIE[$cookie_user];
	$userdata		= get_userdatabylogin($user_login);
	$user_level		= $userdata['user_level'];
	$user_ID			= $userdata['ID'];
	$user_nickname = $userdata["user_nickname"];
	$user_email		= $userdata["user_email"];
	$user_url		= $userdata["user_url"];
	$user_pass_md5	= $userdata["user_pass"];
}




function profile($user_login) 
{
	global $user_data;
	echo "<a href=\"#\" OnClick=\"javascript:window.open('b2profile.php?user=".$user_data["user_login"]."','Profile','toolbar=0,status=1,location=0,directories=0,menuBar=0,scrollbars=1,resizable=1,width=480,height=320,left=100,top=100');\">$user_login</a>";
}
?>
