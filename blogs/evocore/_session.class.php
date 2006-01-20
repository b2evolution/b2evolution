<?php
/**
 * This file implements the Session class.
 *
 * A session can be bound to a user and provides functions to store data in its
 * context.
 * All Hitlogs are also bound to a Session.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Matt FOLLETT grants Francois PLANQUE the right to license
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
 * @author mfollett:  Matt FOLLETT - {@link http://www.mfollett.com/}.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * A session tracks a given user (not necessarily logged in) while he's navigating the site.
 * A sessions also stores data for the length of the session.
 *
 * Sessions are tracked with a cookie containing the session ID.
 * The cookie also contains a random key to prevent sessions hacking.
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
	 * The user of the session.
	 * @var integer
	 */
	var $user_ID;

	/**
	 * Is the session validated?
	 * This means that it was created from a received cookie.
	 * @var boolean
	 */
	var $is_validated = false;

	/**
	 * Data stored for the session.
	 * @access protected
	 * @var object
	 */
	var $_data;

	var $_session_needs_save = false;


	/**
	 * Constructor
	 */
	function Session()
	{
		global $DB, $Debuglog, $current_User, $localtimenow, $Messages, $Settings;
		global $Hit;
		global $cookie_session, $cookie_expires, $cookie_path, $cookie_domain;

		if( !empty( $_COOKIE[$cookie_session] ) )
		{ // session ID sent by cookie
			if( ! preg_match( '~^(\d+)_(\w+)$~', remove_magic_quotes($_COOKIE[$cookie_session]), $match ) )
			{
				$Debuglog->add( 'Invalid session cookie format!', 'session' );
			}
			else
			{	// We have a valid session cookie:
				$session_id_by_cookie = $match[1];
				$session_key_by_cookie = $match[2];

				$Debuglog->add( 'ID (from cookie): '.$session_id_by_cookie, 'session' );

				$row = $DB->get_row( '
					SELECT sess_ID, sess_key, sess_data, sess_user_ID
					  FROM T_sessions
					 WHERE sess_ID  = '.$DB->quote($session_id_by_cookie).'
					   AND sess_key = '.$DB->quote($session_key_by_cookie).'
					   AND sess_lastseen > '.($localtimenow - $DB->quote($Settings->get('timeout_sessions'))) );
				if( empty( $row ) )
				{
					$Debuglog->add( 'Session ID/key combination is invalid!', 'session' );
				}
				else
				{ // ID + key are valid: load data
					$Debuglog->add( 'ID is valid.', 'session' );
					$this->ID = $row->sess_ID;
					$this->key = $row->sess_key;
					$this->user_ID = $row->sess_user_ID;
					$this->is_validated = true;

					$Debuglog->add( 'user_ID: '.var_export($this->user_ID, true), 'session' );

					if( empty( $row->sess_data ) )
					{
						$Debuglog->add( 'No session data available.', 'session' );
						$this->_data = NULL;
					}
					else
					{ // Some session data has been previsouly stored:

						$this->_data = @unserialize($row->sess_data);

						if( $this->_data === false )
						{
							$Debuglog->add( 'Session data corrupted!', 'session' );
							$this->_data = NULL;
						}
						else
						{
							$Debuglog->add( 'Session data loaded.', 'session' );

							// Load a Messages object from session data, if available:
							if( isset($this->_data['Messages']) && is_a( $this->_data['Messages'], 'log' ) )
							{
								$Messages->add_messages( $this->_data['Messages']->messages );
								$this->delete( 'Messages' );
								$Debuglog->add( 'Added Messages from session data.', 'session' );
							}
						}
					}
				}
			}
		}


		if( $this->ID )
		{ // there was a valid session before; update data
			$this->_session_needs_save = true;
		}
		else
		{ // create a new session
			$this->key = generate_random_key(32);

			// We need to INSERT now because we need an ID now! (for the cookie)
			$DB->query( '
				INSERT INTO T_sessions( sess_key, sess_lastseen, sess_ipaddress, sess_agnt_ID )
				VALUES (
					"'.$this->key.'",
					"'.date( 'Y-m-d H:i:s', $localtimenow ).'",
					"'.$Hit->IP.'",
					"'.$Hit->agent_ID.'"
				)' );

			$this->ID = $DB->insert_id;

			// Set a cookie valid for ~ 10 years:
			setcookie( $cookie_session, $this->ID.'_'.$this->key, time()+315360000, $cookie_path, $cookie_domain );

			$Debuglog->add( 'ID (generated): '.$this->ID, 'session' );
			$Debuglog->add( 'Cookie sent.', 'session' );
		}

		register_shutdown_function( array( &$this, 'dbsave' ) );
	}


	/**
	 * Attach a User object to the session.
	 *
	 * @param User The user to attach
	 */
	function set_User( $User )
	{
		return $this->set_user_ID( $User->get('ID') );
	}


	/**
	 * Attach a user ID to the session.
	 *
	 * NOTE: ID gets saved to DB on shutdown. This may be a "problem" when querying T_sessions for sess_user_ID.
	 *
	 * @param integer The ID of the user to attach
	 */
	function set_user_ID( $ID )
	{
		if( $ID != $this->user_ID )
		{
			$this->user_ID = $ID;
			$this->_session_needs_save = true;
		}
	}


	/**
	 * Logout the user, by invalidating the session key and unsetting {@link $user_ID}.
	 *
	 * We want to keep the user in the session log, but we're unsetting {@link $user_ID}, which refers
	 * to the current session.
	 *
	 * Because the session key is invalid/broken, on the next request a new session will be started.
	 *
	 * NOTE: we MIGHT want to link subsequent sessions together if we want to keep track...
	 */
	function logout()
	{
		global $Debuglog, $cookie_session, $cookie_path, $cookie_domain;

		// Invalidate the session key (no one will be able to use this session again)
		$this->key = NULL;
		$this->_data = NULL; // We don't need to keep old data
		$this->_session_needs_save = true;
		$this->dbsave();

		$this->user_ID = NULL; // Unset user_ID after invalidating/saving the session above, to keep the user info attached to the old session.

		// clean up the session cookie:
		setcookie( $cookie_session, '', 272851261, $cookie_path, $cookie_domain ); // 272851261 being the birthday of a lovely person
	}


	/**
	 * Check if session has a user attached.
	 *
	 * @return boolean
	 */
	function has_User()
	{
		return !empty( $this->user_ID );
	}


	/**
	 * Get the attached User.
	 *
	 * @return false|User
	 */
	function & get_User()
	{
		global $UserCache;

		if( !empty($this->user_ID) )
		{
			return $UserCache->get_by_ID( $this->user_ID );
		}

		$r = false;
		return $r;
	}


	/**
	 * Get a data value for the session.
	 *
	 * @param string Name of the data's key.
	 * @return mixed|NULL The value, if set; otherwise NULL
	 */
	function get( $param )
	{
		if( isset( $this->_data[$param] ) )
		{
			return $this->_data[$param];
		}

		return NULL;
	}


	/**
	 * Set a data value for the session.
	 *
	 * You'll have to call {@link $dbsave()} to commit it!
	 *
	 * @param string Name of the data's key.
	 * @param mixed The value
	 */
	function set( $param, $value )
	{
		global $Debuglog;

		if( ! isset($this->_data[$param]) || ($this->_data[$param] != $value) )
		{	// There is something to update:
			$this->_data[$param] = $value;

			$Debuglog->add( 'Session data['.$param.'] updated!', 'session' );

			$this->_session_needs_save = true;
		}
	}


	/**
	 * Delete a value from the session data.
	 *
	 * You'll have to call {@link $dbsave()} to commit it!
	 *
	 * @param string Name of the data's key.
	 */
	function delete( $param )
	{
		global $Debuglog;

		if( isset($this->_data[$param]) )
		{
			unset( $this->_data[$param] );

			$Debuglog->add( 'Session data['.$param.'] deleted!', 'session' );

			$this->_session_needs_save = true;
		}
	}


	/**
	 * Updates session data in database.
	 *
	 * Note: The key actually only needs to be updated on a logout.
	 */
	function dbsave()
	{
		global $DB, $Debuglog, $Hit, $localtimenow;

		if( $this->_session_needs_save )
		{	// There have been changes since the last save.
			$sess_data = is_null($this->_data) ? $this->_data : serialize($this->_data);
			$DB->query( '
				UPDATE T_sessions SET
					sess_agnt_ID = "'.$Hit->agent_ID.'",
					sess_data = '.$DB->quote( $sess_data ).',
					sess_ipaddress = "'.$Hit->IP.'",
					sess_key = '.$DB->quote( $this->key ).',
					sess_lastseen = "'.date( 'Y-m-d H:i:s', $localtimenow ).'",
					sess_user_ID = '.$DB->null( $this->user_ID ).'
				WHERE sess_ID = '.$this->ID, 'Session::dbsave()' );

			$Debuglog->add( 'Session data saved!', 'session' );

			$this->_session_needs_save = false;
		}
	}
}

/*
 * $Log$
 * Revision 1.42  2006/01/20 17:08:13  blueyed
 * Save sess_data as NULL (unserialized) if NULL.
 *
 * Revision 1.41  2006/01/20 16:40:56  blueyed
 * Cleanup
 *
 * Revision 1.40  2006/01/14 14:23:07  blueyed
 * "Out of range" fix in dbsave()
 *
 * Revision 1.39  2006/01/12 21:55:13  blueyed
 * Fix
 *
 * Revision 1.38  2006/01/11 18:23:04  blueyed
 * Also update sess_user_ID in DB on shutdown with set_User() and set_user_ID().
 *
 * Revision 1.36  2006/01/11 01:06:37  blueyed
 * Save session data once at shutdown into DB
 *
 * Revision 1.35  2005/12/21 20:38:18  fplanque
 * Session refactoring/doc
 *
 * Revision 1.34  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.33  2005/11/17 19:35:26  fplanque
 * no message
 *
 */
?>