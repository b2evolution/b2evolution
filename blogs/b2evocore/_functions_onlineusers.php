<?php
/**
 * Who's Online? - functions to maintain online sessions and
 * displaying who is currently active on the site.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 * @author Jeff Bearer - {@link http://www.jeffbearer.com/} + blueyed, fplanque
 */

/**
 * Keep the session active for the current user.
 *
 * {@internal online_user_update(-)}}
 */
function online_user_update()
{
	global $DB, $user_ID, $servertimenow, $online_session_timeout;

	// Delete deprecated session info:
	// Note: we also delete any anonymous user from the current IP address since it will be
	// recreated below (REPLACE won't work properly when a column is NULL)
	$DB->query( "DELETE FROM T_sessions
             		WHERE sess_time < ".( $servertimenow - $online_session_timeout )."
									 OR (	sess_ipaddress='$_SERVER[REMOTE_ADDR]'
												AND sess_user_ID is NULL )" );

	// Record current sesssion info
	$DB->query("REPLACE INTO T_sessions( sess_time, sess_ipaddress, sess_user_ID )
							VALUES( '".$servertimenow."',
											'$_SERVER[REMOTE_ADDR]',
											".( empty($user_ID) ? "NULL" : "'$user_ID'" ).")" );
}


/**
 * Display the registered users who are online
 *
 * {@internal online_user_display(-)}}
 *
 * @param string to display before each user
 * @param string to display after each user
 * @return array containing number of registered users and guests
 */
function online_user_display( $before = '', $after = '' )
{
	global $DB, $online_session_timeout, $Blog;

	$users = array( 'guests' => 0,
									'registered' => 0 );

	$rows = $DB->get_results( 'SELECT sess_user_ID	FROM T_sessions', ARRAY_A );
	if( count( $rows ) ) foreach( $rows as $row )
	{	// Loop through active sessions
		if( !empty( $row['sess_user_ID'] ) )
		{	// This session is logged in:
			$user_ID = get_userdata( $row['sess_user_ID'] );
			$User = new User($user_ID);
			if( $User->showonline )
			{
				echo $before;
				echo $User->get('preferedname');
				if( isset($Blog) ) $User->msgform_link( $Blog->get('msgformurl') );
				echo $after;
				$users['registered']++;
			}
			else
			{	// Wants to remain anonymous
				// echo 'anonymous user!';
				$users['guests']++;
			}
		}
		else
		{	// Not logged in:
			$users['guests']++;
		}
	}

	// Return the number of registered users and the number of guests
	return $users;
}
?>