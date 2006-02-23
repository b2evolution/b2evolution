<?php
/**
 * This file implements the Sessions class.
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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @todo Move to a "who's online" plugin, maybe...
 */ 
class Sessions extends Widget
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


	var $_initialized = false;


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
		global $DB, $UserCache, $localtimenow, $timeout_online_user;

		$this->_count_guests = 0;
		$this->_registered_Users = array();

		$timeout_YMD = date( 'Y-m-d H:i:s', ($localtimenow - $timeout_online_user) );

		// We get all sessions that have been seen in $timeout_YMD and that have a session key.
		// NOTE: we do not use DISTINCT here, because guest users are all "NULL".
		foreach( $DB->get_results( '
			SELECT sess_user_ID
			  FROM T_sessions
			 WHERE sess_lastseen > "'.$timeout_YMD.'"
			   AND sess_key IS NOT NULL' ) as $row )
		{
			if( !empty( $row->sess_user_ID )
					&& ( $User = & $UserCache->get_by_ID( $row->sess_user_ID ) ) )
			{
				// assign by ID so that each user is only counted once (he could use multiple user agents at the same time)
				$this->_registered_Users[ $User->get('ID') ] = & $User;

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
	 * Template function: Display the registered users who are online
	 *
	 * @param string To be displayed before all users
	 * @param string To be displayed after all users
	 * @param string Template to display for each user, see {@link replace_callback()}
	 * @return array containing number of registered users and guests
	 */
	function display_online_users( $beforeAll = '<ul class="onlineUsers">', $afterAll = '</ul>', $templateEach = '<li class="onlineUser">$user_preferredname$ $user_msgformlink$</li>' )
	{
		global $DB, $Blog, $UserCache;
		global $generating_static;
		if( isset($generating_static) ) { return; }

		if( !isset($this->_registered_Users) )
		{
			$this->init();
		}

		// Note: not all users want to get displayed, so we might have an empty list.
		$r = '';

		foreach( $this->_registered_Users as $User )
		{
			if( $User->showonline )
			{
				if( empty($r) )
				{ // first user
					$r .= $beforeAll;
				}

				$r .= $this->replace_vars( $templateEach, array( 'User' => &$User ) );
			}
		}
		if( !empty($r) )
		{ // we need to close the list
			$r .= $afterAll;
		}

		echo $r;
	}


	/**
	 * Template function: Display number of online guests.
	 *
	 * @return string
	 */
	function display_online_guests( $before = '', $after = '' )
	{
		global $generating_static;
		if( isset($generating_static) ) { return; }

		if( !isset($this->_count_guests) )
		{
			$this->init();
		}

		if( empty($before) )
		{
			$before = T_('Guest Users:').' ';
		}

		$r = $before.$this->_count_guests.$after;

		echo $r;
	}


	/**
	 * Template function: Display onliners, both registered users and guests.
	 */
	function display_onliners()
	{
		$this->display_online_users();

		$this->display_online_guests();
	}


	/**
	 * Widget callback for template vars.
	 *
	 * This replaces user properties if set through $user_xxx$ and especially $user_msgformlink$.
	 *
	 * This allows to replace template vars, see {@link Widget::replace_callback()}.
	 *
	 * @param array
	 * @param User an optional User object
	 * @return string
	 */
	function replace_callback( $matches, $User = NULL )
	{
		if( isset($this->replace_params['User']) && substr($matches[1], 0, 5) == 'user_' )
		{ // user properties
			$prop = substr($matches[1], 5);
			if( $prop == 'msgformlink' )
			{
				return $this->replace_params['User']->get_msgform_link();
			}
			elseif( $prop = $this->replace_params['User']->get( $prop ) )
			{
				return $prop;
			}

			return false;
		}

		switch( $matches[1] )
		{
			default:
				return parent::replace_callback( $matches );
		}
	}

}

/*
 * $Log$
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.22  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.21  2005/11/25 14:07:40  fplanque
 * no message
 *
 * Revision 1.19  2005/11/18 03:37:55  blueyed
 * doc
 *
 * Revision 1.18  2005/11/17 19:35:26  fplanque
 * no message
 *
 */
?>