<?php
/**
 * Who's Online - functions to maintiain online sessions and displaying who is currently active on the site.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Jeff Bearer - {@link http://www.jeffbearer.com/}
 *
 * @package evocore
 * @author This file built upon code from original b2 - http://cafelog.com/
 */

/*
 * online_user_update(-)
 *
 * Keep the session active for the current user.
 *
 */
function online_user_update()
{
	global $DB, $user_ID, $online_session_timeout;

	// Prepare the statement to remove old session info
	if( is_logged_in() )
	{
		$sql = "DELETE FROM T_sessions
	                WHERE sess_user_ID='$user_ID'
			OR  sess_time < ".( time() - $online_session_timeout );
	}
	else
	{
		$sql = "DELETE FROM T_sessions
			WHERE sess_time < ".( time() - $online_session_timeout )."
			OR ( 
				sess_ipaddress='$_SERVER[REMOTE_ADDR]'
				AND sess_user_ID is NULL
			)";
	}
	$DB->query( $sql );

	// Prepare the statement to insert the new session info
	$sql = "INSERT INTO T_sessions (sess_time,sess_ipaddress,sess_user_ID)
		VALUES ('".time()."','$_SERVER[REMOTE_ADDR]',";
	$sql .= (empty($user_ID)) ? "NULL" : "'$user_ID'";
	$sql .= ")";
	$DB->query( $sql );
}


/*
 * online_user_display(-)
 *
 * Display the registered users who are online
 * Values can be supplied for before and after each user name
 * The number of registered users and guests are returned.
 *
 * @param string
 * @param string
 */
function online_user_display( $before = '', $after = '' )
{
	global $DB, $online_session_timeout, $Blog;
	$users = array();

	$sql = 'SELECT sess_user_ID
		FROM T_sessions
		WHERE sess_user_ID IS NOT NULL
		AND T_sessions.sess_time > "'.( time() - $online_session_timeout ).'"';

	$rows = $DB->get_results( $sql, ARRAY_A );
	$users['guests'] = 0;
	$users['registered'] = 0;
	if( count( $rows ) ) foreach( $rows as $row )
	{
		$user = get_userdata( $row['sess_user_ID'] );
		$User = new User($user);
		if( $user->showonline )
		{
			echo $before;
			echo $User->get('preferedname');
			$User->msgform_link( $Blog->get('msgformurl') );
			echo $after;
			$users['registered']++;
		}
		else
		{
			$users['guests']++;
		}
	}

	$users['guests'] += $DB->get_var( "SELECT count(*)
						FROM T_sessions
						WHERE sess_user_ID IS NULL");

	// Return the number of registered users and the number of guests
	return $users;
}
?>