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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

class Sessions extends Widget
{
	/**
	 * Number of guests (and users that want to be anonymous)
	 *
	 * @access protected
	 */
	var $_countGuests = array();


	var $_initialized = false;


	var $_registeredUsers = array();


	/**
	 * Constructor
	 */
	function Sessions()
	{
	}


	/**
	 * Get an array of registered users and guests.
	 *
	 * @todo this is not really a method of a single Session
	 * @todo Cache!
	 *
	 * @return array containing number of registered users and guests ('registered' and 'guests')
	 */
	function init()
	{
		if( $this->_initialized )
		{
			return true;
		}

		global $DB, $UserCache, $localtimenow, $online_session_timeout;

		$this->_countGuests = 0;

		foreach( $DB->get_results( 'SELECT sess_user_ID FROM T_sessions
																WHERE sess_lastseen > "'.date( 'Y-m-d H:i:s', ($localtimenow - $online_session_timeout) ).'"' )
							as $row )
		{
			if( !empty( $row->sess_user_ID )
					&& ( $User = & $UserCache->get_by_ID( $row->sess_user_ID ) ) )
			{
				$this->_registeredUsers[] =& $User;

				if( !$User->showonline )
				{
					$this->_countGuests++;
				}
			}
			else
			{
				$this->_countGuests++;
			}
		}

		$this->_initialized = true;
	}


	/**
	 * Get the number of guests.
	 *
	 * @param boolean display?
	 */
	function numberOfGuests( $display = true )
	{
		$this->init();

		if( $display )
		{
			echo $this->_countGuests;
		}
		return $this->_countGuests;
	}



	/**
	 * Display the registered users who are online
	 *
	 * @param string Template to display each user (the first %s gets the user's prefered name,
	 *               the second the link to his mail form - if he has an email address)
	 * @return array containing number of registered users and guests
	 */
	function displayOnlineUsers( $beforeEach = '<li class="onlineUser">', $afterEach = '</li>', $beforeAll = '<ul class="onlineUsers">', $afterAll = '</ul>' )
	{
		global $DB, $Blog, $UserCache;

		$this->init();

		foreach( $this->_registeredUsers as $User )
		{
			if( $User->showonline )
			{
				echo $beforeAll;
				$beforeAll = '';


				echo $beforeEach;
				echo $User->get('preferedname');

				if( isset($Blog) )
				{
					$User->msgform_link( $Blog->get('msgformurl') );
				}
				echo $afterEach;

				echo $afterAll;
				$afterAll = '';
			}
		}
	}


	/**
	 *
	 *
	 * @return
	 */
	function displayOnlineGuests( $before = NULL, $after = NULL)
	{
		$this->init();

		if( is_null($before) )
		{
			$before = T_('Guest Users:').' ';
		}

		$r = $before.$this->_countGuests.$after;

		echo $r;
	}


	function displayOnliners()
	{
		$this->displayOnlineUsers();

		$this->displayOnlineGuests();
	}


	/**
	 * Widget callback for template vars.
	 *
	 * @return string
	 */
	function callback( $matches )
	{
		switch( $matches[1] )
		{
			case '':
				break;

			default:
				return parent::callback( $matches );
		}
	}

}
