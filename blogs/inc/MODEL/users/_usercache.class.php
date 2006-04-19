<?php
/**
 * This file implements the UserCache class.
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
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobjectcache.class.php';

/**
 * Blog Cache Class
 *
 * @package evocore
 */
class UserCache extends DataObjectCache
{
	/**
	 * Cache for login -> User object reference. "login" is transformed to lowercase.
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
	 */
	function UserCache()
	{
		parent::DataObjectCache( 'User', false, 'T_users', 'user_', 'user_ID' );
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
		// Make sure we have a lowercase login:
		// We want all logins to be lowercase to guarantee uniqueness regardless of the database case handling for UNIQUE indexes.
		$login = strtolower( $login );

		if( !isset( $this->cache_login[$login] ) )
		{
			global $DB;
			if( $row = $DB->get_row( '
					SELECT *
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
	 * @param User
	 * @return boolean
	 */
	function add( & $Obj )
	{
		if( parent::add( $Obj ) )
		{
			$this->cache_login[ strtolower($Obj->login) ] = & $Obj;

			return true;
		}

		return false;
	}


	/**
	 * Load members of a given blog
	 *
	 * @todo make a UNION query when we upgrade to MySQL 4
	 * @param integer blog ID to load members for
	 */
	function load_blogmembers( $blog_ID )
	{
		global $DB, $Debuglog;

		if( isset( $this->alreadyCached['blogmembers'] ) && isset( $this->alreadyCached['blogmembers'][$blog_ID] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(Blog #$blog_ID members)</strong> into cache", 'dataobjects' );
			return false;
		}

		// Remember this special load:
		$this->alreadyCached['blogmembers'][$blog_ID] = true;

		$Debuglog->add( "Loading <strong>$this->objtype(Blog #$blog_ID members)</strong> into cache", 'dataobjects' );

		// User perms:
		$sql = 'SELECT T_users.*
					    FROM T_users INNER JOIN T_coll_user_perms ON user_ID = bloguser_user_ID
				     WHERE bloguser_blog_ID = '.$blog_ID.'
					     AND bloguser_ismember <> 0';
		foreach( $DB->get_results( $sql ) as $row )
		{
			if( !isset($this->cache[$row->user_ID]) )
			{	// Save reinstatiating User if it's already been added
				$this->add( new User( $row ) );
			}
		}

		// Group perms:
		$sql = 'SELECT T_users.*
					    FROM T_users LEFT JOIN T_coll_group_perms ON user_grp_ID = bloggroup_group_ID
					   WHERE bloggroup_blog_ID = '.$blog_ID.'
					     AND bloggroup_ismember  <> 0';
		foreach( $DB->get_results( $sql ) as $row )
		{
			if( !isset($this->cache[$row->user_ID]) )
			{	// Save reinstatiating User if it's already been added
				$this->add( new User( $row ) );
			}
		}

		return true;
	}


	/**
	 * Loads cache with blog memeber, then display form option list with cache contents
	 *
	 * Optionally, also adds default choice to the cache.
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
			parent::option_list( $default, $allow_none, 'preferred_name' );
		}
		else
		{
			return parent::option_list_return( $default, $allow_none, 'preferred_name_return' );
		}
	}


	/**
	 * Display form option list with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 */
	function option_list( $default = 0, $allow_none = false )
	{
		parent::option_list( $default, $allow_none, 'preferred_name' );
	}

}

/*
 * $Log$
 * Revision 1.3  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.2  2006/03/12 23:09:00  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.25  2006/02/10 22:33:19  fplanque
 * logins should be lowercase
 *
 * Revision 1.24  2006/02/09 00:53:10  blueyed
 * add(): Cache logins lowercase!
 *
 * Revision 1.23  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.22  2005/11/23 17:28:00  fplanque
 * also check group permissions
 *
 * Revision 1.21  2005/10/03 17:26:44  fplanque
 * synched upgrade with fresh DB;
 * renamed user_ID field
 *
 * Revision 1.20  2005/09/29 15:07:30  fplanque
 * spelling
 *
 * Revision 1.19  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.18  2005/05/25 17:13:34  fplanque
 * implemented email notifications on new comments/trackbacks
 *
 * Revision 1.17  2005/05/16 15:17:13  fplanque
 * minor
 *
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