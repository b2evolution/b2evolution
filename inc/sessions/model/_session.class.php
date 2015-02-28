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
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
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
	 * Session start timestamp
	 * @var string
	 */
	var $start_ts;

	/**
	 * Session last seen timestamp which was logged
	 * Value may be off by up to 60 seconds
	 * @var string
	 */
	var $lastseen_ts;

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
	 * The user device from where this session was created
	 *
	 * @var string
	 */
	var $sess_device;

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

		$Debuglog->add( 'Session: cookie_domain='.$cookie_domain, 'request' );
		$Debuglog->add( 'Session: cookie_path='.$cookie_path, 'request' );

		$session_cookie = param_cookie( $cookie_session, 'string', '' );
		if( empty( $session_cookie ) )
		{
			$Debuglog->add( 'Session: No session cookie received.', 'request' );
		}
		else
		{ // session ID sent by cookie
			if( ! preg_match( '~^(\d+)_(\w+)$~', $session_cookie, $match ) )
			{
				$Debuglog->add( 'Session: Invalid session cookie format!', 'request' );
			}
			else
			{	// We have a valid session cookie:
				$session_id_by_cookie = $match[1];
				$session_key_by_cookie = $match[2];

				$Debuglog->add( 'Session: Session ID received from cookie: '.$session_id_by_cookie, 'request' );

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
					SELECT sess_ID, sess_key, sess_data, sess_user_ID, sess_start_ts, sess_lastseen_ts, sess_device
					  FROM T_sessions
					 WHERE sess_ID  = '.$DB->quote($session_id_by_cookie).'
					   AND sess_key = '.$DB->quote($session_key_by_cookie).'
					   AND UNIX_TIMESTAMP(sess_lastseen_ts) > '.( $localtimenow - $timeout_sessions ) );
				if( empty( $row ) )
				{
					$Debuglog->add( 'Session: Session ID/key combination is invalid!', 'request' );
				}
				else
				{ // ID + key are valid: load data
					$Debuglog->add( 'Session: Session ID is valid.', 'request' );
					$this->ID = $row->sess_ID;
					$this->key = $row->sess_key;
					$this->user_ID = $row->sess_user_ID;
					$this->start_ts = mysql2timestamp( $row->sess_start_ts );
					$this->lastseen_ts = mysql2timestamp( $row->sess_lastseen_ts );
					$this->is_validated = true;
					$this->sess_device = $row->sess_device;

					$Debuglog->add( 'Session: Session user_ID: '.var_export($this->user_ID, true), 'request' );

					if( empty( $row->sess_data ) )
					{
						$Debuglog->add( 'Session: No session data available.', 'request' );
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
							$Debuglog->add( 'Session: Session data corrupted!<br />
								connection_charset: '.var_export($DB->connection_charset, true).'<br />
								Serialized data was: --['.var_export($row->sess_data, true).']--', array('session','error') );
							$this->_data = array();
						}
						else
						{
							$Debuglog->add( 'Session: Session data loaded.', 'request' );

							// Load a Messages object from session data, if available:
							if( ($sess_Messages = $this->get('Messages')) && is_a( $sess_Messages, 'Messages' ) )
							{
								// dh> TODO: "old" messages should rather get prepended to any existing ones from the current request, rather than appended
								$Messages->add_messages( $sess_Messages );
								$Debuglog->add( 'Session: Added Messages from session data.', 'request' );
								$this->delete( 'Messages' );
							}
						}
					}
				}
			}
		}


		if( $this->ID )
		{ // there was a valid session before
			if( $this->lastseen_ts < $localtimenow - 60 )
			{ // lastseen timestamp is older then a minute, it needs to be updated at page exit
				$this->session_needs_save( true );
			}
		}
		else
		{ // create a new session! :
			$this->key = generate_random_key(32);

			// Detect user device
			global $user_devices;
			$this->sess_device = '';

			if( !empty($_SERVER['HTTP_USER_AGENT']) )
			{
				foreach( $user_devices as $device_name => $device_regexp )
				{
					if( preg_match( '~'.$device_regexp.'~i', $_SERVER['HTTP_USER_AGENT'] ) )
					{
						$this->sess_device = $device_name;
						break;
					}
				}
			}

			// We need to INSERT now because we need an ID now! (for the cookie)
			$DB->query( "
				INSERT INTO T_sessions( sess_key, sess_start_ts, sess_lastseen_ts, sess_ipaddress, sess_device )
				VALUES (
					'".$this->key."',
					'".date( 'Y-m-d H:i:s', $localtimenow )."',
					'".date( 'Y-m-d H:i:s', $localtimenow )."',
					".$DB->quote( $Hit->IP ).",
					".$DB->quote( $this->sess_device )."
				)" );

			$this->ID = $DB->insert_id;

			// Set a cookie valid for ~ 10 years:
			evo_setcookie( $cookie_session, $this->ID.'_'.$this->key, time()+315360000, $cookie_path, $cookie_domain, false, true );

			$Debuglog->add( 'Session: ID (generated): '.$this->ID, 'request' );
			$Debuglog->add( 'Session: Cookie sent.', 'request' );
		}
	}


	/**
	 * Get this class db table config params
	 *
	 * @return array
	 */
	static function get_class_db_config()
	{
		static $session_db_config;

		if( !isset( $session_db_config ) )
		{
			$session_db_config = array(
				'dbtablename'        => 'T_sessions',
				'dbprefix'           => 'sess_',
				'dbIDname'           => 'sess_ID',
			);
		}

		return $session_db_config;
	}


	/**
	 * Delete sessions from database based on where condition or by object ids
	 *
	 * @return array
	 */
	static function db_delete_where( $class_name, $sql_where, $object_ids = NULL, $params = NULL )
	{
		global $DB;

		$DB->begin();

		if( ! empty( $sql_where ) )
		{
			$object_ids = $DB->get_col( 'SELECT sess_ID FROM T_sessions WHERE '.$sql_where );
		}

		if( ! $object_ids )
		{ // There is no session to delete
			$DB->commit();
			return;
		}

		$session_ids_to_delete = implode( ', ', $object_ids );

		$result = $DB->query( 'DELETE FROM T_hitlog WHERE hit_sess_ID IN ( '.$session_ids_to_delete.' )' );

		$result = ( $result !== false ) ? $DB->query( 'DELETE FROM T_sessions WHERE sess_ID IN ( '.$session_ids_to_delete.')' ) : $result;

		( $result !== false ) ? $DB->commit() : $DB->rollback();

		return $result;
	}


	function session_needs_save( $session_needs_save )
	{
		// pre_dump( 'SETTING session needs save to', $session_needs_save );
		$this->_session_needs_save = $session_needs_save;
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
				$Debuglog->add( 'Session: Invalidating all previous user sessions, because login_multiple_sessions=0', 'request' );
				$DB->query( '
					UPDATE T_sessions
					   SET sess_key = NULL
					 WHERE sess_user_ID = '.$DB->quote($user_ID).'
					   AND sess_ID != '.$this->ID );
			}

			$this->user_ID = $user_ID;
			$this->session_needs_save( true );
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
		$this->session_needs_save( true );
		$this->dbsave();

		$this->user_ID = NULL; // Unset user_ID after invalidating/saving the session above, to keep the user info attached to the old session.

		// clean up the session cookie:
		evo_setcookie( $cookie_session, '', 200000000, $cookie_path, $cookie_domain, false, true );
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
				$this->session_needs_save( true );
				$Debuglog->add( 'Session: Session data['.$param.'] expired.', 'request' );
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

			$Debuglog->add( 'Session: Session data['.$param.'] updated. Expire in: '.( $expire ? $expire.'s' : '-' ).'.', 'request' );

			$this->session_needs_save( true );
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

			$Debuglog->add( 'Session: Session data['.$param.'] deleted!', 'request' );

			$this->session_needs_save( true );
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
			$Debuglog->add( 'Session: Session is up to date and does not need to be saved.', 'request' );
			return false;
		}

		$sess_data = empty($this->_data) ? NULL : serialize($this->_data);

	 	// Note: The key actually only needs to be updated on a logout.
	 	// Note: we increase the hitcoutn every time. That assumes that there will be no 2 calls for a single hit.
	 	//       Anyway it is not a big problem if this number is approximate.
		$sql = "UPDATE T_sessions SET
				sess_lastseen_ts = '".date( 'Y-m-d H:i:s', $localtimenow )."',
				sess_data = ".$DB->quote( $sess_data ).",
				sess_ipaddress = '".$Hit->IP."',
				sess_key = ".$DB->quote( $this->key );
		if( !is_null($this->user_ID) )
		{	// We do NOT erase existing IDs at logout. We only want to set IDs at login:
				$sql .= ", sess_user_ID = ".$this->user_ID;
		}
		$sql .= "	WHERE sess_ID = ".$this->ID;

		$DB->query( $sql, 'Session::dbsave()' );

		$Debuglog->add( 'Session: Session data saved!', 'request' );

		$this->session_needs_save( false );
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

		$Debuglog->add( 'Session: Reloaded session data.' );
	}


	/**
	 * Create a crumb that will be saved into the Session and returned to the caller for inclusion in Form or action url.
	 *
	 * For any action, a new crumb is generated every hour and the previous one is saved. (2 hours are valid)
	 *
	 * @param string crumb name
	 * @return string crumb value
	 */
	function create_crumb( $crumb_name )
	{
		global $servertimenow, $crumb_expires;

		// Retrieve latest saved crumb:
		$crumb_recalled = $this->get( 'crumb_latest_'.$crumb_name, '-0' );
		list( $crumb_value, $crumb_time ) = explode( '-', $crumb_recalled );

		if( $servertimenow - $crumb_time > ($crumb_expires/2) )
		{	// The crumb we already had is older than 1 hour...
			// We'll need to generate a new value:
			$crumb_value = '';
			if( $servertimenow - $crumb_time < ($crumb_expires - 200) ) // Leave some margin here to make sure we do no overwrite a newer 1-2 hr crumb
			{	// Not too old either, save as previous crumb:
				$this->set( 'crumb_prev_'.$crumb_name, $crumb_recalled );
			}
		}

		if( empty($crumb_value) )
		{	// We need to generate a new crumb:
			$crumb_value = generate_random_key( 32 );

			// Save crumb into session so we can later compare it to what get got back from the user request:
			$this->set( 'crumb_latest_'.$crumb_name, $crumb_value.'-'.$servertimenow );
		}
		return $crumb_value;
	}


	/**
	 * Assert that we received a valid crumb for the object we want to act on.
	 *
	 * This will DIE if we have not received a valid crumb.
	 *
	 * The received crumb must match a crumb we previously saved less than 2 hours ago.
	 *
	 * @param string crumb name
	 * @param boolean true if the script should die on error
	 */
	function assert_received_crumb( $crumb_name, $die = true )
	{
		global $servertimenow, $crumb_expires, $debug;

		if( ! $crumb_received = param( 'crumb_'.$crumb_name, 'string', NULL ) )
		{ // We did not receive a crumb!
			if( $die )
			{
				bad_request_die( 'Missing crumb ['.$crumb_name.'] -- It looks like this request is not legit.' );
			}
			return false;
		}

		// Retrieve latest saved crumb:
		$crumb_recalled = $this->get( 'crumb_latest_'.$crumb_name, '-0' );
		list( $crumb_value, $crumb_time ) = explode( '-', $crumb_recalled );
		if( $crumb_received == $crumb_value && $servertimenow - $crumb_time <= $crumb_expires )
		{	// Crumb is valid
			// echo '<p>-<p>-<p>A';
			return true;
		}

		$crumb_valid_latest = $crumb_value;

		// Retrieve previous saved crumb:
		$crumb_recalled = $this->get( 'crumb_prev_'.$crumb_name, '-0' );
		list( $crumb_value, $crumb_time ) = explode( '-', $crumb_recalled );
		if( $crumb_received == $crumb_value && $servertimenow - $crumb_time <= $crumb_expires )
		{	// Crumb is valid
			// echo '<p>-<p>-<p>B';
			return true;
		}

		if( ! $die )
		{
			return false;
		}

		// ERROR MESSAGE, with form/button to bypass and enough warning hopefully.
		// TODO: dh> please review carefully!
		echo '<div style="background-color: #fdd; padding: 1ex; margin-bottom: 1ex;">';
		echo '<h3 style="color:#f00;">'.T_('Incorrect crumb received!').' ['.$crumb_name.']</h3>';
		echo '<p>'.T_('Your request was stopped for security reasons.').'</p>';
		echo '<p>'.sprintf( T_('Have you waited more than %d minutes before submitting your request?'), floor($crumb_expires/60) ).'</p>';
		echo '<p>'.T_('Please go back to the previous page and refresh it before submitting the form again.').'</p>';
		echo '</div>';

		if( $debug > 0 )
		{
			echo '<div>';
			echo '<p>Received crumb:'.$crumb_received.'</p>';
			echo '<p>Latest saved crumb:'.$crumb_valid_latest.'</p>';
			echo '<p>Previous saved crumb:'.$crumb_value.'</p>';
			echo '</div>';
		}

		echo '<div>';
		echo '<p class="warning">'.T_('Alternatively, you can try to resubmit your request with a refreshed crumb:').'</p>';
		$Form = new Form( '', 'evo_session_crumb_resend', $_SERVER['REQUEST_METHOD'] );
		$Form->begin_form( 'inline' );
		$Form->add_crumb( $crumb_name );
		$Form->hiddens_by_key( remove_magic_quotes($_REQUEST) );
		$Form->button( array( 'submit', '', T_('Resubmit now!'), 'ActionButton' ) );
		$Form->end_form();
		echo '</div>';

		die();
	}


	/**
	 * Was this session created from a mobile device
	 */
	function is_mobile_session()
	{
		global $mobile_user_devices;

		return array_key_exists( $this->sess_device, $mobile_user_devices );
	}


	/**
	 * Was this session created from a mobile device
	 */
	function is_tablet_session()
	{
		global $tablet_user_devices;

		return array_key_exists( $this->sess_device, $tablet_user_devices );
	}


	/**
	 * Was this session created from a desktop device
	 */
	function is_desktop_session()
	{
		global $pc_user_devices;

		return array_key_exists( $this->sess_device, $pc_user_devices );
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

		case 'itemsettings':
			load_class( 'items/model/_itemsettings.class.php', 'ItemSettings' );
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
	load_class( 'items/model/_itemsettings.class.php', 'ItemSettings' );
	load_class( 'users/model/_group.class.php', 'Group' );
	load_class( 'users/model/_user.class.php', 'User' );
}

?>