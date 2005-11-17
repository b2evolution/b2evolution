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
 * A session stores data for a given user (not necessarily logged in).
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


	/**
	 * Keep the session active for the current user.
	 */
	function Session()
	{
		global $DB, $Debuglog, $current_User, $localtimenow, $Messages, $Settings;
		global $Hit;
		global $cookie_session, $cookie_expires, $cookie_path, $cookie_domain;

		if( !empty( $_COOKIE[$cookie_session] ) )
		{ // session ID sent by cookie
			if( preg_match( '~^(\d+)_(\w+)$~', remove_magic_quotes($_COOKIE[$cookie_session]), $match ) )
			{
				$session_id_by_cookie = $match[1];
				$session_key_by_cookie = $match[2];

				$Debuglog->add( 'ID (from cookie): '.$session_id_by_cookie, 'session' );

				if( $row = $DB->get_row( '
					SELECT sess_ID, sess_key, sess_data, sess_user_ID
					  FROM T_sessions
					 WHERE sess_ID  = '.$DB->quote($session_id_by_cookie).'
					   AND sess_key = '.$DB->quote($session_key_by_cookie).'
					   AND sess_lastseen > '.($localtimenow - $DB->quote($Settings->get('timeout_sessions'))) ) )
				{ // ID + key are valid: load data
					$Debuglog->add( 'ID is valid.', 'session' );
					$this->ID = $row->sess_ID;
					$this->key = $row->sess_key;
					$this->user_ID = $row->sess_user_ID;
					$this->is_validated = true;

					$Debuglog->add( 'user_ID: '.var_export($this->user_ID, true), 'session' );

					if( $row->sess_data )
					{
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
								$this->dbsave();
								$Debuglog->add( 'Added Messages from session data.', 'session' );
							}
						}
					}
					else
					{
						$Debuglog->add( 'No session data available.', 'session' );
						$this->_data = NULL;
					}
				}
				else
				{
					$Debuglog->add( 'Session ID/key combination is invalid!', 'session' );
				}
			}
			else
			{
				$Debuglog->add( 'Invalid cookie data format!', 'session' );
			}
		}


		if( $this->ID )
		{ // there was a valid session before; update data
			$DB->query( '
				UPDATE T_sessions SET
					sess_lastseen = "'.date( 'Y-m-d H:i:s', $localtimenow ).'",
					sess_ipaddress = "'.$Hit->IP.'"
					WHERE sess_ID = '.$this->ID );
		}
		else
		{ // create a new session
			$this->key = generate_random_key(32);

			$DB->query( '
				INSERT INTO T_sessions
				( sess_key, sess_lastseen, sess_ipaddress, sess_agnt_ID )
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

		/*
		TODO: (post-phoenix)
		$Cron->add_task( array(&$this, 'dbsave'), 'always' ); // always save data (no need to call it manually)!
		*/
	}


	/**
	 * Attach a User object to the session.
	 *
	 * @param User The user to attach
	 * @return boolean true on success, false on failure
	 */
	function set_User( $User )
	{
		return $this->set_user_ID( $User->get('ID') );
	}


	/**
	 * Attach a user ID to the session.
	 *
	 * @param integer The ID of the user to attach
	 * @return boolean true on success, false on failure
	 */
	function set_user_ID( $ID )
	{
		global $DB, $Debuglog;

		// Set the entry in the database
		$q = $DB->query( '
			UPDATE T_sessions SET sess_user_ID = "'.$ID.'"
			 WHERE sess_ID = "'.$this->ID.'"' );
		if( $q !== false )
		{ // No DB error - query() might return 0 for "0 rows affected"
			$this->user_ID = $ID;

			$Debuglog->add( 'Set user_ID to '.$this->user_ID, 'session' );

			return true;
		}
		else
		{
			$Debuglog->add( 'Setting user of session failed!', 'session' );

			return false;
		}
	}


	/**
	 * Logout the user, by invalidating the session key and unsetting {@link $user_ID}.
	 *
	 * We want to keep the user in the session log, but we're unsetting {@link $user_ID}, which refers
	 * to the current session.
		// What happens on the next page? The session key will be broken -> will it start a new session? It should! 
		// Note: we MIGHT want to link subsequent sessions together if we want to keep track...
	 */
	function logout()
	{
		global $Debuglog, $cookie_session, $cookie_path, $cookie_domain;

		// Invalidate the session key (no one will be able to use this session again)
		$this->key = NULL;
		// How is this going to stay in the log if we set it to null? $this->user_ID = NULL;
		$this->_data = NULL; // We don't need to keep old data
		$this->dbsave();

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
	 * Get the probability that this is spam.
	 *
	 * @todo (php5) Move to interface
	 * @return integer|boolean
	 */
	function get_spam_probability()
	{
		if( $this->has_User() )
		{
			return -50;
		}

		return 0;
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
			return & $UserCache->get_by_ID( $this->user_ID );
		}

		$r = false;
		return $r;
	}


	/**
	 * Get a data value for the session.
	 *
	 * @param string Name of the data's key.
	 * @return mixed|false The value, if set; otherwise false
	 */
	function get( $param )
	{
		if( isset( $this->_data[$param] ) )
		{
			return $this->_data[$param];
		}

		return false;
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
		$this->_data[$param] = $value;
	}


	/**
	 * Delete a value from the session data.
	 *
	 * You'll have to call {@link $dbsave()} to commit it!
	 *
	 * @param string Name of the data's key.
	 */
	function delete( $key )
	{
		unset( $this->_data[$key] );
	}


	/**
	 * Updates {@link $_data} and {@link $key} into the database.
	 */
	function dbsave()
	{
		global $DB;

		$DB->query( '
			UPDATE T_sessions SET
				sess_data = '.$DB->quote( serialize($this->_data) ).',
				sess_key = '.$DB->quote( $this->key ).'
			WHERE sess_ID = '.$this->ID, 'Session::dbsave' );
	}
}

?>
