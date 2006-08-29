<?php
/**
 * This file implements the Sessions class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 * @todo Move to a "who's online" plugin
 * @todo dh> I wanted to add a MySQL INDEX on the sess_lastseen field, but this "plugin"
 *       is the only real user of this. So, when making this a plugin, this should
 *       add the index perhaps.
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
		global $DB, $localtimenow, $timeout_online_user;

		$this->_count_guests = 0;
		$this->_registered_Users = array();

		$timeout_YMD = date( 'Y-m-d H:i:s', ($localtimenow - $timeout_online_user) );

		$UserCache = & get_Cache( 'UserCache' );

		// We get all sessions that have been seen in $timeout_YMD and that have a session key.
		// NOTE: we do not use DISTINCT here, because guest users are all "NULL".
		foreach( $DB->get_results( '
			SELECT sess_user_ID
			  FROM T_sessions
			 WHERE sess_lastseen > "'.$timeout_YMD.'"
			   AND sess_key IS NOT NULL', OBJECT, 'Sessions: get list of relevant users.' ) as $row )
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
	 * @todo get class="" out of here (put it into skins)
	 *
	 * @param string To be displayed before all users
	 * @param string To be displayed after all users
	 * @param string Template to display for each user, see {@link replace_callback()}
	 * @return array containing number of registered users and guests
	 */
	function display_online_users( $beforeAll = '<ul class="onlineUsers">', $afterAll = '</ul>', $templateEach = '<li class="onlineUser">$user_preferredname$ $user_msgformlink$</li>' )
	{
		global $DB, $Blog;
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
	 *
	 * @todo get class="" out of here (put it into skins)
	 */
	function display_onliners()
	{
		$this->display_online_users();

		// Wrap in the same <ul> class as the online users:
		$this->display_online_guests( '<ul class="onlineUsers"><li>'.T_('Guest Users:').' ', '</li></ul>' );
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
		if( isset($this->params['User']) && substr($matches[1], 0, 5) == 'user_' )
		{ // user properties
			$prop = substr($matches[1], 5);
			if( $prop == 'msgformlink' )
			{
				return $this->params['User']->get_msgform_link();
			}
			elseif( $prop = $this->params['User']->get( $prop ) )
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
 * Revision 1.8  2006/08/29 22:59:09  blueyed
 * doc
 *
 * Revision 1.7  2006/08/29 00:26:11  fplanque
 * Massive changes rolling in ItemList2.
 * This is somehow the meat of version 2.0.
 * This branch has gone officially unstable at this point! :>
 *
 * Revision 1.6  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.5  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.4  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 * Revision 1.3  2006/04/06 09:46:37  blueyed
 * display_onliners(): Wrap "online guests" in same "<ul>" as "online users" (because of left padding/margin)
 *
 * Revision 1.2  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
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