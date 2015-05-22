<?php
/**
 * This file implements the Whosonline plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Calendar Plugin
 *
 * This plugin displays
 */
class whosonline_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	var $name;
	var $code = 'evo_WhosOnline';
	var $priority = 96;
	var $version = '5.0.0';
	var $author = 'The b2evo Group';
	var $group = 'widget';
	var $subgroup = 'other';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_( 'Who\'s online Widget' );
		$this->short_desc = T_('This skin tag displays a list of whos is online.');
		$this->long_desc = T_('All logged in users and guest users who have requested a page in the last 5 minutes are listed.');
	}


  /**
   * Get definitions for widget specific editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		$r = array(
			'contacticons' => array(
				'label' => T_('Contact icons'),
				'note' => T_('Display contact icons allowing to send private messages to logged in users.'),
				'type' => 'checkbox',
				'defaultvalue' => true,
			),
			'timeout_online_user' => array(
				'label' => T_( 'Online session timeout' ),
				'note' => T_( 'seconds. After how much time of inactivity an user is considered to be offline? 300 seconds are 5 minutes.' ),
				'type' => 'integer',
				'defaultvalue' => 300,
				'valid_range' => array(
					'min' => 1, // 0 would not make sense.
				),
			),
		);
		return $r;
	}


	/**
	 * Event handler: SkinTag (widget)
	 *
	 * @param array Associative array of parameters.
	 * @return boolean did we display?
	 */
	function SkinTag( $params )
	{
		global $Plugins;

		if( $Plugins->trigger_event_first_true('CacheIsCollectingContent') )
		{ // A caching plugin collecting the content
			return false;
		}

		echo $params['block_start'];

		echo $params['block_title_start'];
		echo T_('Who\'s Online?');
		echo $params['block_title_end'];

		$OnlineSessions = new OnlineSessions( $params['timeout_online_user'] );
		$OnlineSessions->display_onliners( $params );

		echo $params['block_end'];

		return true;
	}
}


/**
 * This tracks who is online
 *
 * @todo dh> I wanted to add a MySQL INDEX on the sess_lastseen field, but this "plugin"
 *       is the only real user of this. So, when making this a plugin, this should
 *       add the index perhaps.
 * @package evocore
 */
class OnlineSessions
{
	/**
	 * Number of guests (and users that want to be anonymous)
	 *
	 * Gets lazy-filled when needed, through {@link init()}.
	 *
	 * @access protected
	 */
	var $_count_guests;


	/**
	 * List of registered users.
	 *
	 * Gets lazy-filled when needed, through {@link init()}.
	 *
	 * @access protected
	 */
	var $_registered_Users;

	/**
	 * Online session timeout in seconds.
	 *
	 * Default value: 300 (5 minutes). Set by {@link OnlineSessions::OnlineSessions()}.
	 *
	 * @access protected
	 */
	var $_timeout_online_user;

	var $_initialized = false;


	/**
	 * Constructor.
	 *
	 * @param integer Online session timeout in seconds.
	 */
	function OnlineSessions( $timeout_online_user = 300 )
	{
		$this->_timeout_online_user = $timeout_online_user;
	}


	/**
	 * Get an array of registered users and guests.
	 *
	 * @return array containing number of registered users and guests ('registered' and 'guests')
	 */
	function init()
	{
		if( $this->_initialized )
		{
			return true;
		}
		global $DB, $UserSettings, $localtimenow;

		$this->_count_guests = 0;
		$this->_registered_Users = array();

		$timeout_YMD = date( 'Y-m-d H:i:s', ($localtimenow - $this->_timeout_online_user) );

		$UserCache = & get_UserCache();

		// We get all sessions that have been seen in $timeout_YMD and that have a session key.
		// NOTE: we do not use DISTINCT here, because guest users are all "NULL".
		$online_user_ids = $DB->get_col( "
			SELECT SQL_NO_CACHE sess_user_ID
			  FROM T_sessions
			 WHERE sess_lastseen_ts > '".$timeout_YMD."'
			   AND sess_key IS NOT NULL
			 GROUP BY sess_ID", 0, 'Sessions: get list of relevant users.' );
		$registered_online_user_ids = array_diff( $online_user_ids, array( NULL ) );
		// load all online users into the cache because we need information ( login, avatar ) about them
		$UserCache->load_list( $registered_online_user_ids );
		foreach( $online_user_ids as $user_ID )
		{
			if( !empty( $user_ID ) && ( $User = & $UserCache->get_by_ID( $user_ID, false ) ) )
			{
				// assign by ID so that each user is only counted once (he could use multiple user agents at the same time)
				$this->_registered_Users[ $user_ID ] = & $User;

				if( $UserSettings->get( 'show_online', $User->ID ) )
				{
					$this->_count_guests++;
				}
			}
			else
			{
				$this->_count_guests++;
			}
		}

		$this->_initialized = true;
	}


	/**
	 * Get the number of guests.
	 *
	 * @param boolean display?
	 */
	function number_of_guests( $display = true )
	{
		if( !isset($this->_count_guests) )
		{
			$this->init();
		}

		if( $display )
		{
			echo $this->_count_guests;
		}
		return $this->_count_guests;
	}


	/**
	 * Template function: Display onliners, both registered users and guests.
	 *
	 * @todo get class="" out of here (put it into skins)
	 */
	function display_onliners( $params )
	{
		$this->display_online_users( $params );
		$this->display_online_guests( $params );
	}

	/**
	 * Template function: Display the registered users who are online
	 *
	 * @todo get class="" out of here (put it into skins)
	 *
	 * @param array
	 */
	function display_online_users( $params )
	{
		global $DB, $Blog, $UserSettings;

		if( !isset($this->_registered_Users) )
		{
			$this->init();
		}

		// Note: not all users want to appear online, so we might have an empty list.
		$r = '';

		foreach( $this->_registered_Users as $User )
		{
			if( $UserSettings->get( 'show_online', $User->ID ) )
			{
				if( empty($r) )
				{ // first user
					$r .= $params['list_start'];
				}

				$r .= $params['item_start'];
				$r .= $User->dget('preferredname');
				if( $params['contacticons'] )
				{	// We want contact icons:
					$r .= $User->get_msgform_link();
				}
				$r .= $params['item_end'];
			}
		}

		if( !empty($r) )
		{ // we need to close the list
			$r .= $params['list_end'];;
		}

		echo $r;
	}


	/**
	 * Template function: Display number of online guests.
	 */
	function display_online_guests( $params )
	{
		if( !isset($this->_count_guests) )
		{
			$this->init();
		}

		$r = $params['list_start'];
		$r .= $params['item_start'];
		$r .= T_('Guest Users:').' ';
		$r .= $this->_count_guests;
		$r .= $params['item_end'];
		$r .= $params['list_end'];;

		echo $r;
	}
}

?>