<?php
/**
 * This file implements the UserCache class.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobjectcache.class.php';

/**
 * Blog Cache Class
 *
 * @package evocore
 */
class UserCache extends DataObjectCache
{
	/**
	 * Cache for login -> User object reference
	 * @access private
	 * @var array
	 */
	var $cache_login = array();


	/**
	 * Remember special cache loads.
	 * @access protected
	 */
	var $alreadyCached = array();


	/**
	 * Constructor
	 *
	 * {@internal UserCache::UserCache(-) }}
	 */
	function UserCache()
	{
		parent::DataObjectCache( 'User', false, 'T_users', 'user_', 'ID' );
	}

	/* this is for debugging only:
	function & get_by_ID( $req_ID, $halt_on_error = true )
	{
		$obj = parent::get_by_ID( $req_ID, $halt_on_error );
			pre_dump($obj);
		return $obj;
	}
	*/


	/**
	 * Get a user object by login.
	 *
	 * Does not halt on error.
	 *
	 * @return false|User Reference to the user object or false if not found
	 */
	function & get_by_login( $login )
	{
		$login = strtolower( $login );
		if( !isset( $this->cache_login[$login] ) )
		{
			global $DB;
			if( $row = $DB->get_row( 'SELECT *
																	FROM T_users
																 WHERE user_login = "'.$DB->escape($login).'"', 0, 0, 'Get User login' ) )
			{
				$this->add( new User( $row ) );
			}
			else
			{
				$this->cache_login[$login] = false;
			}
		}

		return $this->cache_login[$login];
	}


	/**
	 * Get a user object by login, only if password matches.
	 *
	 * @param string Login
	 * @param string Password
	 * @param boolean Password is MD5()'ed
	 * @return false|User
	 */
	function & get_by_loginAndPwd( $login, $pass, $pass_is_md5 = true )
	{
		if( !($User =& $this->get_by_login( $login )) )
		{
			return false;
		}

		if( !$pass_is_md5 )
		{
			$pass = md5($pass);
		}

		if( $User->pass != $pass )
		{
			return false;
		}

		return $User;
	}


	/**
	 * Overload parent's function to also maintain the login cache.
	 *
	 * {@internal UserCache::add(-) }}
	 * @param User
	 * @return boolean
	 */
	function add( & $Obj )
	{
		if( parent::add( $Obj ) )
		{
			$this->cache_login[$Obj->login] = & $Obj;

			return true;
		}

		return false;
	}


	/**
	 * Load members of a given blog
	 *
	 * {@internal UserCache::load_blogmembers(-) }}
	 *
	 * @param integer blog ID to load members for
	 */
	function load_blogmembers( $blog_ID )
	{
		global $DB, $Debuglog;

		if( isset( $this->alreadyCached['blogmembers'] ) && isset( $this->alreadyCached['blogmembers'][$blog_ID] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(Blog #$blog_ID members)</strong> into cache" );
			return false;
		}

		// Remember this special load:
		$this->alreadyCached['blogmembers'][$blog_ID] = true;

		$Debuglog->add( "Loading <strong>$this->objtype(Blog #$blog_ID members)</strong> into cache" );

		foreach( $DB->get_results( 'SELECT *
																	FROM T_users INNER JOIN T_blogusers ON ID = bloguser_user_ID
																 WHERE bloguser_blog_ID = '.$blog_ID.'
																	 AND bloguser_ismember <> 0' ) as $row )
		{
			$this->add( new User( $row ) );
		}

		return true;
	}


	/**
	 * Loads cache with blog memeber, then display form option list with cache contents
	 *
	 * Optionally, also adds default choice to the cache.
	 *
	 * {@internal UserCache::blog_member_list(-) }}
	 *
	 * @param integer blog ID
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 */
	function blog_member_list( $blog_ID, $default = 0, $allow_none = false, $always_load_default = false, $disp = true )
	{
		if( $blog_ID )
		{ // Load requested blog members:
			$this->load_blogmembers( $blog_ID );

			// Make sure current user is in list:
			if( $default && $always_load_default )
			{
				// echo '<option>getting default';
				$this->get_by_ID( $default );
			}
		}
		else
		{ // No blog specified: load ALL members:
			$this->load_all();
		}

		if( $disp )
		{
			parent::option_list( $default, $allow_none, 'prefered_name' );
		}
		else
		{
			return parent::option_list_return( $default, $allow_none, 'prefered_name_return' );
		}
	}


	/**
	 * Display form option list with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * {@internal UserCache::option_list(-) }}
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 */
	function option_list( $default = 0, $allow_none = false )
	{
		parent::option_list( $default, $allow_none, 'prefered_name' );
	}

}

/*
 * $Log$
 * Revision 1.16  2005/03/14 20:22:20  fplanque
 * refactoring, some cacheing optimization
 *
 * Revision 1.15  2005/02/28 09:06:34  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.14  2005/02/20 23:08:41  blueyed
 * get_by_loginAndPwd() added
 *
 * Revision 1.13  2005/02/18 00:36:08  blueyed
 * $alreadyCached class member
 *
 * Revision 1.12  2005/02/16 15:48:06  fplanque
 * merged with work app :p
 *
 * Revision 1.11  2005/02/15 20:05:52  fplanque
 * no message
 *
 * Revision 1.10  2005/02/14 21:17:54  blueyed
 * optimized cache handling
 *
 * Revision 1.9  2005/02/14 14:33:35  fplanque
 * todo..
 *
 * Revision 1.8  2005/02/09 00:27:13  blueyed
 * Removed deprecated globals / userdata handling
 *
 * Revision 1.7  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.6  2005/02/08 04:07:47  blueyed
 * fixed results from DB::get_var()
 *
 * Revision 1.5  2005/01/20 20:38:58  fplanque
 * refactoring
 *
 * Revision 1.4  2004/12/30 16:45:40  fplanque
 * minor changes on file manager user interface
 *
 * Revision 1.3  2004/12/29 03:15:38  blueyed
 * added get_by_login()
 *
 * Revision 1.2  2004/12/23 21:19:41  fplanque
 * no message
 *
 * Revision 1.1  2004/12/21 21:18:38  fplanque
 * Finished handling of assigning posts/items to users
 *
 */
?>