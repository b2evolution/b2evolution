<?php
/**
 * This file implements the UserCache class.
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

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

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
		parent::DataObjectCache( 'User', false, 'T_users', 'user_', 'user_ID', NULL, '',
			/* TRANS: "None" select option */ T_('No user') );
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

			if( $row = $DB->get_row( "
					SELECT *
					  FROM T_users
					 WHERE user_login = '".$DB->escape($login)."'", 0, 0, 'Get User login' ) )
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
	 * Extract list of contacts of current user from his message threads
	 *
	 * @todo fp> Although this deals with user IDs, this function should go to the Messaging module (see todo in next func below)
	 *
	 * @param current user ID
	 */
	function load_messaging_threads_recipients( $user_ID )
	{
		global $DB;

		$SQL = & new SQL();

		$SQL->SELECT( 'DISTINCT u.*' );

		$SQL->FROM( 'T_messaging__threadstatus ts
						LEFT OUTER JOIN T_messaging__threadstatus tsr
							ON ts.tsta_thread_ID = tsr.tsta_thread_ID
						LEFT OUTER JOIN T_users u
							ON tsr.tsta_user_ID = u.user_ID' );

		$SQL->WHERE( 'ts.tsta_user_ID = '.$user_ID );

		foreach( $DB->get_results( $SQL->get() ) as $row )
		{
			if( !isset($this->cache[$row->user_ID]) )
			{
				$this->add( new User( $row ) );
			}
		}
	}


	/**
	 * Load all of the recipients of current thread
	 *
	 * @todo fp> I think this should dbe a method of Thread (Ideally the app can handle Users without the Messaging module enabled - reverse can never be true)
	 *
	 * @param current thread ID
	 */
	function load_messaging_thread_recipients( $thrd_ID )
	{
		global $DB;

		$SQL = & new SQL();

		$SQL->SELECT( 'u.*' );

		$SQL->FROM( 'T_messaging__threadstatus ts
						LEFT OUTER JOIN T_users u
							ON ts.tsta_user_ID = u.user_ID' );

		$SQL->WHERE( 'ts.tsta_thread_ID = '.$thrd_ID );

		foreach( $DB->get_results( $SQL->get() ) as $row )
		{
			if( !isset($this->cache[$row->user_ID]) )
			{
				$this->add( new User( $row ) );
			}
		}
	}

	/**
	 * Loads cache with blog memeber, then display form option list with cache contents
	 *
	 * Optionally, also adds default choice to the cache.
	 *
	 * @param integer blog ID or 0 for ALL
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 * @param boolean make sur the current default user is part of the choices
	 */
	function get_blog_member_option_list( $blog_ID, $default = 0, $allow_none = false, $always_load_default = false )
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

		return parent::get_option_list( $default, $allow_none, 'get_preferred_name' );
	}


	/**
	 * Clear our caches.
	 */
	function clear( $keep_shadow = false )
	{
		$this->alreadyCached = array();
		$this->cache_login = array();

		return parent::clear($keep_shadow);
	}

	/**
	 * Handle our login cache.
	 */
	function remove_by_ID( $reg_ID )
	{
		if( isset($this->cache[$req_ID]) )
		{
			unset( $this->cache_login[ $this->cache[$req_ID] ] );
		}
		parent::remove_by_ID($req_ID);
	}
}


/*
 * $Log$
 * Revision 1.8  2009/09/18 15:47:11  fplanque
 * doc/cleanup
 *
 * Revision 1.7  2009/09/18 10:38:31  efy-maxim
 * 15x15 icons next to login in messagin module
 *
 * Revision 1.6  2009/09/14 13:46:11  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.5  2009/09/12 11:15:43  efy-arrin
 * Included the ClassName in loadclass with proper UpperCase
 *
 * Revision 1.4  2009/08/30 00:42:11  fplanque
 * fixed user form
 *
 * Revision 1.3  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:36  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:01:47  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.10  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.9  2007/02/08 03:48:22  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.8  2006/12/05 01:35:27  blueyed
 * Hooray for less complexity and the 8th param for DataObjectCache()
 *
 * Revision 1.7  2006/12/05 00:34:39  blueyed
 * Implemented custom "None" option text in DataObjectCache; Added for $ItemStatusCache, $GroupCache, UserCache and BlogCache; Added custom text for Item::priority_options()
 *
 * Revision 1.6  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.5  2006/10/13 10:01:07  blueyed
 * Fixed clear() and remove_by_ID() for UserCache and its own caches + test
 */
?>