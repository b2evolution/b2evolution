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
 *
 */
class Session
{
	/**
	 * The ID of the session.
	 * @var integer
	 */
	var $ID;


	/**
	 * Keep the session active for the current user.
	 * // QUESTION: what to use for ID? T_session.sess_ID is BIGINT()..
	 */
	function Session()
	{
		global $DB, $Debuglog, $current_User, $localtimenow;
		global $Hit;
		global $cookie_session;

		/**
		 * @todo move to $Settings - use only for display of online user, not to prune sessions!
		 */
		global $online_session_timeout;

		if( $sessionByCookie = param( $cookie_session, 'string', '' ) )
		{ // session sent by cookie
			$this->ID = $sessionByCookie;
		}
		else
		{ // start new session
			global $cookie_expires, $cookie_path, $cookie_domain;
			$this->ID = md5( $Hit->IP.$Hit->getUseragent() );

			setcookie( $cookie_session, $this->ID, $cookie_expires, $cookie_path, $cookie_domain );
		}

		$Debuglog->add( 'Updating the active session for the current user.' );

		// Delete deprecated session info:
		// Note: we also delete any anonymous user from the current IP address since it will be
		// recreated below (REPLACE won't work properly when a column is NULL)
		$DB->query( 'DELETE FROM T_sessions
									WHERE sess_lastseen < "'.date( 'Y-m-d H:i:s', ($localtimenow - $online_session_timeout) ).'"
										OR ( sess_ipaddress = "'.getIpList( true ).'"
													AND sess_user_ID is NULL )' );

		// Record current session info
		$DB->query( 'REPLACE INTO T_sessions( sess_ID, sess_lastseen, sess_ipaddress, sess_user_ID )
									VALUES( "'.$this->ID.'",
													"'.date( 'Y-m-d H:i:s', $localtimenow ).'",
													"'.getIpList( true ).'",
													'.( $current_User ? '"'.$current_User->ID.'"' : 'NULL' ).')' );

	}


	/**
	 *
	 *
	 * @return integer
	 */
	function getID()
	{
		return $this->ID;
	}
}

?>
