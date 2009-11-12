<?php
/**
 * This file implements the Session class and holds the
 * {@link session_unserialize_callback()} function used by it.
 *
 * A session can be bound to a user and provides functions to store data in its
 * context.
 * All Hitlogs are also bound to a Session.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
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
 * A session also stores data for the length of the session.
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
	 * The user ID for the user of the session (NULL for anonymous (not logged in) user).
	 *
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
	 *
	 * This holds an array( expire, value ) for each data item key.
	 *
	 * @access protected
	 * @var array
	 */
	var $_data;

	var $_session_needs_save = false;


	/**
	 * Constructor
	 *
	 * If valid session cookie received: pull session from DB
	 * Otherwise, INSERT a session into DB
	 */
	function Session()
	{
		global $DB, $Debuglog, $current_User, $localtimenow, $Messages, $Settings, $UserSettings;
		global $Hit;
		global $cookie_session, $cookie_expires, $cookie_path, $cookie_domain;

		$Debuglog->add( 'cookie_domain='.$cookie_domain, 'session' );
		$Debuglog->add( 'cookie_path='.$cookie_path, 'session' );

		$session_cookie = param_cookie( $cookie_session, 'string', '' );
		if( empty( $session_cookie ) )
		{
			$Debuglog->add( 'No session cookie received.', 'session' );
		}
		else
		{ // session ID sent by cookie
			if( ! preg_match( '~^(\d+)_(\w+)$~', $session_cookie, $match ) )
			{
				$Debuglog->add( 'Invalid session cookie format!', 'session' );
			}
			else
			{	// We have a valid session cookie:
				$session_id_by_cookie = $match[1];
				$session_key_by_cookie = $match[2];

				$Debuglog->add( 'Session ID received from cookie: '.$session_id_by_cookie, 'session' );

				$timeout_sessions = NULL;
				if( $this->user_ID != NULL )
				{	// User is not anonymous, get custom session timeout (may return NULL):
					$timeout_sessions = $UserSettings->get( 'timeout_sessions', $this->user_ID );
				}

				if( empty( $timeout_sessions ) )
				{	// User is anonymous or has no custom session timeout. So, we use default session timeout:
					$timeout_sessions = $Settings->get('timeout_sessions');
				}

				$row = $DB->get_row( '
					SELECT sess_ID, sess_key, sess_data, sess_user_ID
					  FROM T_sessions
					 WHERE sess_ID  = '.$DB->quote($session_id_by_cookie).'
					   AND sess_key = '.$DB->quote($session_key_by_cookie).'
					   AND UNIX_TIMESTAMP(sess_lastseen) > '.( $localtimenow - $timeout_sessions ) );
				if( empty( $row ) )
				{
					$Debuglog->add( 'Session ID/key combination is invalid!', 'session' );
				}
				else
				{ // ID + key are valid: load data
					$Debuglog->add( 'Session ID is valid.', 'session' );
					$this->ID = $row->sess_ID;
					$this->key = $row->sess_key;
					$this->user_ID = $row->sess_user_ID;
					$this->is_validated = true;

					$Debuglog->add( 'Session user_ID: '.var_export($this->user_ID, true), 'session' );

					if( empty( $row->sess_data ) )
					{
						$Debuglog->add( 'No session data available.', 'session' );
						$this->_data = array();
					}
					else
					{ // Some session data has been previsouly stored:

						// Unserialize session data (using an own callback that should provide class definitions):
						$old_callback = ini_set( 'unserialize_callback_func', 'session_unserialize_callback' );
						if( $old_callback === false || is_null($old_callback) /* disabled, reported with PHP 5.2.5 */ )
						{	// NULL if ini_set has been disabled for security reasons
							// Brutally load all classes that we might need:
 							session_unserialize_load_all_classes();
						}
						// TODO: dh> This can fail, if there are special chars in sess_data:
						//       It will be encoded in $evo_charset _after_ "SET NAMES", but
						//       get retrieved here, _before_ any "SET NAMES" (if $db_config['connection_charset'] is not set (default))!
						$this->_data = @unserialize($row->sess_data);

						if( $old_callback !== false )
						{	// Restore the old callback if we changed it:
							ini_set( 'unserialize_callback_func', $old_callback );
						}

						if( ! is_array($this->_data) )
						{
							$Debuglog->add( 'Session data corrupted!<br />
								connection_charset: '.var_export($DB->connection_charset, true).'<br />
								Serialized data was: --['.var_export($row->sess_data, true).']--', array('session','error') );
							$this->_data = array();
						}
						else
						{
							$Debuglog->add( 'Session data loaded.', 'session' );

							// Load a Messages object from session data, if available:
							if( ($sess_Messages = $this->get('Messages')) && is_a( $sess_Messages, 'log' ) )
							{
								// dh> TODO: "old" messages should rather get prepended to any existing ones from the current request, rather than appended
								$Messages->add_messages( $sess_Messages->messages );
								$Debuglog->add( 'Added Messages from session data.', 'session' );
								$this->delete( 'Messages' );
							}
						}
					}
				}
			}
		}


		if( $this->ID )
		{ // there was a valid session before; data needs to be updated at page exit (lastseen)
			$this->_session_needs_save = true;
		}
		else
		{ // create a new session! :
			$this->key = generate_random_key(32);

			// We need to INSERT now because we need an ID now! (for the cookie)
			$DB->query( "
				INSERT INTO T_sessions( sess_key, sess_lastseen, sess_ipaddress )
				VALUES (
					'".$this->key."',
					'".date( 'Y-m-d H:i:s', $localtimenow )."',
					".$DB->quote($Hit->IP)."
				)" );

			$this->ID = $DB->insert_id;

			// Set a cookie valid for ~ 10 years:
			setcookie( $cookie_session, $this->ID.'_'.$this->key, time()+315360000, $cookie_path, $cookie_domain );

			$Debuglog->add( 'ID (generated): '.$this->ID, 'session' );
			$Debuglog->add( 'Cookie sent.', 'session' );
		}
	}


	/**
	 * Attach a User object to the session.
	 *
	 * @param User The user to attach
	 */
	function set_User( $User )
	{
		return $this->set_user_ID( $User->ID );
	}


	/**
	 * Attach a user ID to the session.
	 *
	 * NOTE: ID gets saved to DB on shutdown. This may be a "problem" when querying T_sessions for sess_user_ID.
	 *
	 * @param integer The ID of the user to attach
	 */
	function set_user_ID( $user_ID )
	{
		if( $user_ID != $this->user_ID )
		{
			global $Settings, $UserSettings, $DB;

			$multiple_sessions = $Settings->get( 'multiple_sessions' );

			if( $multiple_sessions != 'always' && ( $multiple_sessions == 'never' || !$UserSettings->get('login_multiple_sessions', $user_ID) ) )
			{ // The user does not want/is not allowed to have multiple sessions open at the same time:
				// Invalidate previous sessions:
				global $Debuglog;
				$Debuglog->add( 'Invalidating all previous user sessions, because login_multiple_sessions=0', 'session' );
				$DB->query( '
					UPDATE T_sessions
					   SET sess_key = NULL
					 WHERE sess_user_ID = '.$DB->quote($user_ID).'
					   AND sess_ID != '.$this->ID );
			}

			$this->user_ID = $user_ID;
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
		$this->_data = array(); // We don't need to keep old data
		$this->_session_needs_save = true;
		$this->dbsave();

		$this->user_ID = NULL; // Unset user_ID after invalidating/saving the session above, to keep the user info attached to the old session.

		// clean up the session cookie:
		setcookie( $cookie_session, '', 200000000, $cookie_path, $cookie_domain );
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
		if( !empty($this->user_ID) )
		{
			$UserCache = & get_UserCache();
			return $UserCache->get_by_ID( $this->user_ID );
		}

		$r = false;
		return $r;
	}


	/**
	 * Get a data value for the session. This checks for the data to be expired and unsets it then.
	 *
	 * @param string Name of the data's key.
	 * @param mixed Default value to use if key is not set or has expired. (since 1.10.0)
	 * @return mixed The value, if set; otherwise $default
	 */
	function get( $param, $default = NULL )
	{
		global $Debuglog, $localtimenow;

		if( isset( $this->_data[$param] ) )
		{
			if( array_key_exists(1, $this->_data[$param]) // can be NULL!
			  && ( is_null( $this->_data[$param][0] ) || $this->_data[$param][0] > $localtimenow ) ) // check for expired data
			{
				return $this->_data[$param][1];
			}
			else
			{ // expired or old format (without 'value' key)
				unset( $this->_data[$param] );
				$this->_session_needs_save = true;
				$Debuglog->add( 'Session data['.$param.'] expired.', 'session' );
			}
		}

		return $default;
	}


	/**
	 * Set a data value for the session.
	 *
	 * Updated values get saved to the DB automatically on shutdown, in {@link shutdown()}.
	 *
	 * @param string Name of the data's key.
	 * @param mixed The value
	 * @param integer Time in seconds for data to expire (0 to disable).
	 */
	function set( $param, $value, $expire = 0 )
	{
		global $Debuglog, $localtimenow;

		if( ! isset($this->_data[$param])
		 || ! is_array($this->_data[$param]) // deprecated: check to transform 1.6 session data to 1.7
		 || $this->_data[$param][1] != $value
		 || $expire != 0 )
		{	// There is something to update:
			$this->_data[$param] = array( ( $expire ? ($localtimenow + $expire) : NULL ), $value );

			if( $param == 'Messages' )
			{ // also set boolean to not call CachePageContent plugin event on next request:
				$this->set( 'core.no_CachePageContent', 1 );
			}

			$Debuglog->add( 'Session data['.$param.'] updated. Expire in: '.( $expire ? $expire.'s' : '-' ).'.', 'session' );

			$this->_session_needs_save = true;
		}
	}


	/**
	 * Delete a value from the session data.
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
	 * NOTE: Debuglog additions will may not be displayed since the debuglog may alreayd have been displayed (shutdown function)
	 */
	function dbsave()
	{
		global $DB, $Debuglog, $Hit, $localtimenow;

		if( ! $this->_session_needs_save )
		{	// There have been no changes since the last save.
			$Debuglog->add( 'Session is up to date and does not need to be saved.', 'session' );
			return false;
		}

		$sess_data = empty($this->_data) ? NULL : serialize($this->_data);

	 	// Note: The key actually only needs to be updated on a logout.
	 	// Note: we increase the hitcoutn every time. That assumes that there will be no 2 calls for a single hit.
	 	//       Anyway it is not a big problem if this number is approximate.
		$sql = "UPDATE T_sessions SET
				sess_hitcount = sess_hitcount + 1,
				sess_lastseen = '".date( 'Y-m-d H:i:s', $localtimenow )."',
				sess_data = ".$DB->quote( $sess_data ).",
				sess_ipaddress = '".$Hit->IP."',
				sess_key = ".$DB->quote( $this->key );
		if( !is_null($this->user_ID) )
		{	// We do NOT erase existing IDs at logout. We only want to set IDs at login:
				$sql .= ", sess_user_ID = ".$this->user_ID;
		}
		$sql .= "	WHERE sess_ID = ".$this->ID;

		$DB->query( $sql, 'Session::dbsave()' );

		$Debuglog->add( 'Session data saved!', 'session' );

		$this->_session_needs_save = false;
	}


	/**
	 * Reload session data.
	 *
	 * This is needed if the running process waits for a child process to write data
	 * into the Session, e.g. the captcha plugin in test mode waiting for the Debuglog
	 * output from the process that created the image (included through an IMG tag).
	 */
	function reload_data()
	{
		global $Debuglog, $DB;

		if( empty($this->ID) )
		{
			return false;
		}

		$sess_data = $DB->get_var( '
			SELECT SQL_NO_CACHE sess_data FROM T_sessions
			 WHERE sess_ID = '.$this->ID );

		$sess_data = @unserialize( $sess_data );
		if( $sess_data === false )
		{
			$this->_data = array();
		}
		else
		{
			$this->_data = $sess_data;
		}

		$Debuglog->add( 'Reloaded session data.' );
	}
}


/**
 * This gets used as a {@link unserialize()} callback function, which is
 * responsible for loading the requested class.
 *
 * IMPORTANT: when modifying this, modify the following also:
 * @see session_unserialize_load_all_classes()
 *
 * @todo Once we require PHP5, we should think about using this as __autoload function.
 *
 * @return boolean True, if the required class could be loaded; false, if not
 */
function session_unserialize_callback( $classname )
{
	switch( strtolower($classname) )
	{
		case 'blog':
			load_class( 'collections/model/_blog.class.php', 'Blog' );
			return true;

		case 'collectionsettings':
			load_class( 'collections/model/_collsettings.class.php', 'CollectionSettings' );
			return true;

		case 'comment':
			load_class( 'comments/model/_comment.class.php', 'Comment' );
			return true;

		case 'item':
			load_class( 'items/model/_item.class.php', 'Item' );
			return true;

		case 'group':
			load_class( 'users/model/_group.class.php', 'Group' );
			return true;

		case 'user':
			load_class( 'users/model/_user.class.php', 'User' );
			return true;
	}

	return false;
}


/**
 * When session_unserialize_callback() cannot be registered to do some smart loading,
 * then we fall back to this function and load everything with brute force...
 *
 * IMPORTANT: when modifying this, modify the following also:
 * @see session_unserialize_callback()
 */
function session_unserialize_load_all_classes()
{
	load_class( 'collections/model/_blog.class.php', 'Blog' );
	load_class( 'collections/model/_collsettings.class.php', 'CollectionSettings' );
	load_class( 'comments/model/_comment.class.php', 'Comment' );
	load_class( 'items/model/_item.class.php', 'Item' );
	load_class( 'users/model/_group.class.php', 'Group' );
	load_class( 'users/model/_user.class.php', 'User' );
}


/*
 * $Log$
 * Revision 1.21  2009/11/12 03:54:17  fplanque
 * wording/doc/cleanup
 *
 * Revision 1.20  2009/11/12 00:46:31  fplanque
 * doc/minor/handle demo mode
 *
 * Revision 1.19  2009/10/27 16:43:33  efy-maxim
 * custom session timeout
 *
 * Revision 1.18  2009/10/25 22:02:43  efy-maxim
 * 1. multiple sessions check
 * 2. user form deleted
 *
 * Revision 1.17  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.16  2009/09/25 19:12:39  blueyed
 * Allow caching for the load-session-data query. After all, a user might click somewhere before the next one arrives (re query cache).
 *
 * Revision 1.15  2009/09/25 07:33:14  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.14  2009/09/14 14:36:16  tblue246
 * Fixing broken commits by efy-arrin
 *
 * Revision 1.13  2009/09/14 13:38:10  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.12  2009/09/13 21:27:20  blueyed
 * SQL_NO_CACHE for SELECT queries using T_sessions
 *
 * Revision 1.11  2009/05/28 22:46:14  blueyed
 * doc
 *
 * Revision 1.10  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.9  2008/12/27 21:09:28  fplanque
 * minor
 *
 * Revision 1.8  2008/12/22 01:56:54  fplanque
 * minor
 *
 * Revision 1.7  2008/11/20 23:30:57  blueyed
 * Quote IP when creating new session
 *
 * Revision 1.6  2008/11/20 23:27:07  blueyed
 * minor indenting
 *
 * Revision 1.5  2008/09/13 11:07:43  fplanque
 * speed up display of dashboard on first login of the day
 *
 * Revision 1.4  2008/03/18 00:31:40  blueyed
 * Fix loading of required classes for unserialize, if ini_set() is disabled (ref: http://forums.b2evolution.net//viewtopic.php?p=71333#71333)
 *
 * Revision 1.3  2008/02/19 11:11:18  fplanque
 * no message
 *
 * Revision 1.2  2008/01/21 09:35:33  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:01:00  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.43  2007/06/19 22:50:15  blueyed
 * cleanup
 *
 * Revision 1.42  2007/05/13 22:02:09  fplanque
 * removed bloated $object_def
 *
 * Revision 1.41  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.40  2007/04/23 15:08:39  blueyed
 * TODO
 *
 * Revision 1.39  2007/04/05 21:53:51  fplanque
 * fix for OVH
 *
 * Revision 1.38  2007/03/11 18:29:50  blueyed
 * Use is_array for session data check
 *
 * Revision 1.37  2007/02/25 01:39:05  fplanque
 * wording
 *
 * Revision 1.36  2007/02/21 22:21:30  blueyed
 * "Multiple sessions" user setting
 *
 * Revision 1.35  2007/02/15 16:37:53  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.34  2007/02/14 14:38:04  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.33  2007/01/27 15:18:23  blueyed
 * doc
 *
 * Revision 1.32  2007/01/27 01:02:49  blueyed
 * debug_die() if ini_set() fails on Session data restore
 *
 * Revision 1.31  2007/01/16 00:08:44  blueyed
 * Implemented $default param for Session::get()
 *
 * Revision 1.30  2006/12/28 15:43:31  fplanque
 * minor
 *
 * Revision 1.29  2006/12/17 23:44:35  fplanque
 * minor cleanup
 *
 * Revision 1.28  2006/12/07 23:13:11  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.27  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.26  2006/11/14 21:13:58  blueyed
 * I've spent > 2 hours debugging this charset nightmare and all I've got are those lousy TODOs..
 */
?>
