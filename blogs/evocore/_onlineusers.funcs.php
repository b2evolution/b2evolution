<?php
/**
 * This file implements functions to track who's online.
 *
 * Functions to maintain online sessions and
 * displaying who is currently active on the site.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jeffbearer: Jeff BEARER - {@link http://www.jeffbearer.com/}.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * Keep the session active for the current user.
 *
 * {@internal online_user_update(-)}}
 */
function online_user_update()
{
	global $DB, $Debuglog, $current_User, $servertimenow, $online_session_timeout;

	$Debuglog->add( 'Updating the active session for the current user' );

	// Delete deprecated session info:
	// Note: we also delete any anonymous user from the current IP address since it will be
	// recreated below (REPLACE won't work properly when a column is NULL)
	$DB->query( 'DELETE FROM T_sessions
		WHERE sess_lastseen < "'.date( 'Y-m-d H:i:s', ($servertimenow - $online_session_timeout) ).'"
									OR ( sess_ipaddress = "'.getIpList( true ).'"
												AND sess_user_ID is NULL )' );

	// Record current session info
	$DB->query( 'REPLACE INTO T_sessions( sess_lastseen, sess_ipaddress, sess_user_ID )
		VALUES( "'.date( 'Y-m-d H:i:s', $servertimenow ).'",
												"'.getIpList( true ).'",
												'.( $current_User ? 'NULL' : '"'.$current_User->ID.'"' ).')' );
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
	global $DB, $online_session_timeout, $Blog, $UserCache;

	$users = array( 'guests' => 0,
									'registered' => 0 );

	foreach( $DB->get_results( 'SELECT sess_user_ID FROM T_sessions', ARRAY_A ) as $row )
	{ // Loop through active sessions
		if( !empty( $row['sess_user_ID'] ) )
		{ // This session is logged in:
			$User = & $UserCache->get_by_ID( $row['sess_user_ID'] );
			if( $User->showonline )
			{
				echo $before;
				echo $User->get('preferedname');
				if( isset($Blog) ) $User->msgform_link( $Blog->get('msgformurl') );
				echo $after;
				$users['registered']++;
			}
			else
			{ // Wants to remain anonymous
				// echo 'anonymous user!';
				$users['guests']++;
			}
		}
		else
		{ // Not logged in:
			$users['guests']++;
		}
	}

	// Return the number of registered users and the number of guests
	return $users;
}

/*
 * $Log$
 * Revision 1.8  2005/02/23 22:48:09  blueyed
 * T_sessions refactoring
 *
 * Revision 1.7  2005/02/21 00:34:34  blueyed
 * check for defined DB_USER!
 *
 * Revision 1.6  2005/02/09 21:43:32  blueyed
 * introduced getIpList()
 *
 * Revision 1.5  2005/02/08 23:57:20  blueyed
 * moved Debugmessage, ..
 *
 * Revision 1.4  2005/02/08 20:17:57  blueyed
 * removed obsolete $User_ID global
 *
 * Revision 1.3  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.18  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 */
?>