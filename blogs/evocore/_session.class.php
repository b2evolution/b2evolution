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
 *
 * Matt FOLLETT grants François PLANQUE the right to license
 * Matt FOLLETT's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jeffbearer: Jeff BEARER - {@link http://www.jeffbearer.com/}.
 * @author mfollett:  Matt Follett - {@link http://www.mfollett.com/}.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 *
 * @package evocore
 */
class Session
{
	/**
	 * The ID of the session.
	 * @var integer
	 */
	var $ID;

	/**
	 * The session key (to be used in URLs).
	 * @var string
	 */
	var $key;

	/**
	 * The user of the session
	 * @var integer
	 */
	 var $userID;


	/**
	 * Keep the session active for the current user.
	 * // QUESTION: what to use for ID? T_sessions.sess_ID is BIGINT()..
	 */
	function Session()
	{
		global $DB, $Debuglog, $current_User, $servertimenow;
		global $Hit;
		global $cookie_session, $cookie_expires, $cookie_path, $cookie_domain, $cookie_key;

		/**
		 * @todo move to $Settings - use only for display of online user, not to prune sessions!
		 */
		global $online_session_timeout;

		if( !empty( $_COOKIE[$cookie_session] ) )
		{ // session ID sent by cookie
			$session_id_by_cookie = remove_magic_quotes($_COOKIE[$cookie_session]);
			$session_key_by_cookie = remove_magic_quotes($_COOKIE[$cookie_key]);
			$Debuglog->add( 'ID (from cookie): '.$session_id_by_cookie, 'session' );
			if( $row = $DB->get_row( 'SELECT sess_ID, sess_data, sess_user_ID, sess_key FROM T_sessions
																	WHERE sess_ID = '.$DB->quote($session_id_by_cookie) . ' AND sess_key = ' . $DB->quote($session_key_by_cookie) ) )
			{
				$Debuglog->add( 'Session data loaded.', 'session' );
				$this->ID = $row->sess_ID;
				$this->key = $row->sess_key;
				$this->data = $row->sess_data;
				$this->userID = $row->sess_user_ID;
			}
			else
			{ // No session data in the table
				$this->key = false;

				$Debuglog->add( 'ID not valid!', 'session' );
			}
		}

		if( !$this->key )
		{ // start new session
			$this->key = $this->generate_key();

			// fplanque>> I'm changing INSERT into REPLACE because this fails all the time on duplicate entry! :(((
			$DB->query( 'REPLACE INTO T_sessions
										(sess_key, sess_lastseen, sess_ipaddress, sess_user_ID)
										VALUES (
											"'.$this->key.'",
											"'.date( 'Y-m-d H:i:s', $servertimenow ).'",
											"'.getIpList( true ).'",
											'.( $current_User ? '"'.$current_User->ID.'"' : 'NULL' )
										.')' );

			$this->ID = $DB->insert_id;

			$Debuglog->add( 'ID (generated): '.$this->ID, 'session' );
		}
		else
		{ // update "Last seen" info
			$DB->query( 'UPDATE T_sessions
										SET sess_lastseen = "'.date( 'Y-m-d H:i:s', $servertimenow ).'"
										WHERE sess_ID = "'.$this->ID.'"' );
		}

		// Send session ID cookie
		setcookie( $cookie_session, $this->ID, $cookie_expires, $cookie_path, $cookie_domain );
		// Send the session key cookie
		setcookie( $cookie_key, $this->key, $cookie_expires, $cookie_path, $cookie_domain );


		/*
		TODO: - use a new $Setting for this and delete not always (like hitlog autopruning).
					- respect session timeout setting, instead of $online_session_timeout.

		*/
		// mafolle:  I left this in for now till the other method is written or else session tables could get clogged.
		// Delete deprecated session info:
		$DB->query( 'DELETE FROM T_sessions
									WHERE sess_lastseen < "'.date( 'Y-m-d H:i:s', ($servertimenow - $online_session_timeout) ).'"
										OR ( sess_ipaddress = "'.getIpList( true ).'"
													AND sess_user_ID is NULL )' );
	}


	/**
	 * Is the session validated by a key?
	 *
	 * @return boolean
	 */
	function is_valid_by_key()
	{
		return !empty($this->key);
	}

	/**
	 * Generate a valid key of size $size
	 *
	 * @param integer length of key
	 * @return string key
	 */
	function generate_key( $size = 32 )
	{
		$key = "";
		if( !is_int($key) )
		{
			$size = 32;
		}
		while( strlen( $key ) < $size )
		{
			$choice = mt_rand( 1, 3);
			/**
			 * To fit the specifications of the security enhancements
			 * the code had to do [a-zA-Z0-9].
			 * This does that by randomly picking a-z, A-Z, or 0-9
			 * and then randomly picking a character in that sequence.
			 */
			switch($choice)
			{
				case '1':
					$key .= chr( mt_rand( 48, 57 ) );
					break;
				case '2':
					$key .= chr( mt_rand( 65, 90 ) );
					break;
				default:
					$key .= chr( mt_rand( 97, 122 ) );
					break;
			}
		}
		return $key;
	}

	/*
	*
	* Set the user of the session by the user
	* @param $User is the User class variable relating to the current person
	* @return boolean whether the action was successfully performed
	*/
	function set_user( $User = 0)
	{
		global $DB, $Debuglog;
		// if the variable is a User object get the user's ID`
		if( is_a( $User, 'User' ) )
		{
			$ID = $User->get('ID');
		}
		// else, if the object is an integer, it must be the ID
		elseif( is_int( $User ) )
		{
			$ID = $User;
		}
		// else I don't know or care what it is so just leave
		else
		{
			return false;
		}

		// Set the entry in the database 
		if(!$DB->query( 'UPDATE T_sessions SET sess_user_ID = "' . $ID . '" ' .
											'where sess_key = "' . $this->key . 
											'" AND sess_ID = ' . $this->ID . ';' ))
		{
			$Debuglog->add( 'Setting user of session failed!', 'session' );
			return false;
		}
									
		return true;
	}
	
	/*
	*
	* Remove the user from the current session (for logout or timeout)
	* @return boolean whether this was successfully executed
	*/
	function remove_user()
	{
		global $Debuglog;
		// this will set the sess_user_ID field in T_sessions to 0
		// which will log them out
		if($this->set_user( 0 ) )
		{
			$Debuglog->add( 'Removing user from session failed!', 'session');
			return false;
		}

		$self->userID='NULL';
		return true;
		
	}

	/*
	*
	* Check if session has a user
	* @return boolean
	*/
	function session_has_user()
	{
		//  this has so many possibilities because when MySQL first sets it
		//  it sets it to null, when I attempt to set it to null though it sets
		//  it to 0 so I just set it to 0, then I checked '' just because
		return( $this->userID != 0 && $this->userID != 'NULL' && $this->userID != '' );
	}
}

?>
