<?php
/**
 * This file implements the Whosonline plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
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
 * }}
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author jeffbearer: Jeff BEARER - {@link http://www.jeffbearer.com/}.
 *
 * @version $Id$
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
	var $version = '2.0';
	var $author = 'The b2evo Group';
	var $group = 'widget';


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
		global $generating_static, $Plugins;

		if( ! empty($generating_static) || $Plugins->trigger_event_first_true('CacheIsCollectingContent') )
		{ // We're not generating static pages nor is a caching plugin collecting the content, so we can display this block
			return false;
		}

		echo $params['block_start'];

		echo $params['block_title_start'];
		echo T_('Who\'s Online?');
		echo $params['block_title_end'];

		$OnlineSessions = & new OnlineSessions( $params['timeout_online_user'] );
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
		global $DB, $localtimenow;

		$this->_count_guests = 0;
		$this->_registered_Users = array();

		$timeout_YMD = date( 'Y-m-d H:i:s', ($localtimenow - $this->_timeout_online_user) );

		$UserCache = & get_UserCache();

		// We get all sessions that have been seen in $timeout_YMD and that have a session key.
		// NOTE: we do not use DISTINCT here, because guest users are all "NULL".
		foreach( $DB->get_results( "
			SELECT SQL_NO_CACHE sess_user_ID
			  FROM T_sessions INNER JOIN T_hitlog ON sess_ID = hit_sess_ID
			 WHERE sess_lastseen > '".$timeout_YMD."'
			   AND sess_key IS NOT NULL
			   AND hit_agent_type = 'browser'
			 GROUP BY sess_ID", OBJECT, 'Sessions: get list of relevant users.' ) as $row )
		{
			if( !empty( $row->sess_user_ID )
					&& ( $User = & $UserCache->get_by_ID( $row->sess_user_ID ) ) )
			{
				// assign by ID so that each user is only counted once (he could use multiple user agents at the same time)
				$this->_registered_Users[ $User->ID ] = & $User;

				if( !$User->showonline )
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
		global $DB, $Blog;
		global $generating_static;
		if( isset($generating_static) ) { return; }

		if( !isset($this->_registered_Users) )
		{
			$this->init();
		}

		// Note: not all users want to appear online, so we might have an empty list.
		$r = '';

		foreach( $this->_registered_Users as $User )
		{
			if( $User->showonline )
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
		global $generating_static;
		if( isset($generating_static) ) { return; }

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


/*
 * $Log$
 * Revision 1.11  2009/12/20 22:12:19  fplanque
 * doc
 *
 * Revision 1.10  2009/12/18 23:49:47  blueyed
 * Who is online plugin: only select browser agent types
 *
 * Revision 1.9  2009/09/26 12:00:44  tblue246
 * Minor/coding style
 *
 * Revision 1.8  2009/09/25 07:33:31  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.7  2009/09/13 21:27:20  blueyed
 * SQL_NO_CACHE for SELECT queries using T_sessions
 *
 * Revision 1.6  2009/07/04 17:43:13  tblue246
 * Made $timeout_online_user a widget setting
 *
 * Revision 1.5  2009/06/24 18:47:54  tblue246
 * Make widget plugin names translatable
 *
 * Revision 1.4  2009/03/08 23:57:48  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:41  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/06/20 23:36:06  fplanque
 * cleanup
 *
 * Revision 1.1  2007/06/20 23:12:51  fplanque
 * "Who's online" moved to a plugin
 *
 * Revision 1.13  2007/06/11 22:01:53  blueyed
 * doc fixes
 *
 * Revision 1.12  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.11  2007/02/05 13:29:09  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.10  2006/12/17 23:44:35  fplanque
 * minor cleanup
 *
 * Revision 1.9  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>
