<?php
/**
 * This file implements the UserCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
	 * @param string user login
	 * @param boolean force to run db query to get user by the given login.
	 *        !IMPORTANT! Set this to false only if it's sure that this user was already loaded if it exists on DB!
	 *
	 * @return false|User Reference to the user object or false if not found
	 */
	function & get_by_login( $login, $force_db_check = true )
	{
		// Make sure we have a lowercase login:
		// We want all logins to be lowercase to guarantee uniqueness regardless of the database case handling for UNIQUE indexes.
		$login = evo_strtolower( $login );

		if( !( $force_db_check || isset( $this->cache_login[$login] ) ) )
		{ // force db check is false and this login is not set in the cache it means that user with the given login doesn't exists
			$this->cache_login[$login] = false;
		}

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
	 * Get a user object by email and password
	 * If multiple accounts match, give priority to:
	 *  -accounts that are activated over non activated accounts
	 *  -accounts that were used more recently than others
	 *
	 * @param string email address
	 * @param string md5 hashed password
	 * @param string hashed password - If this is set, it means we need to check the hasshed password instead of the md5 password
	 * @param string password salt
	 * @return false|array false if user with this email not exists, array( $User, $exists_more ) pair otherwise
	 */
	function get_by_emailAndPwd( $email, $pass_md5, $pwd_hashed = NULL, $pwd_salt = NULL )
	{
		global $DB;

		// Get all users with matching email address
		$result = $DB->get_results('SELECT * FROM T_users
					WHERE LOWER(user_email) = '.$DB->quote( evo_strtolower($email) ).'
					ORDER BY user_lastseen_ts DESC, user_status ASC');

		if( empty( $result ) )
		{ // user was not found with the given email address
			return false;
		}

		// check if exists more user with the same email address
		$exists_more = ( count( $result ) > 1 );
		$index = -1;
		$first_matched_index = false;
		// iterate through the result list
		foreach( $result as $row )
		{
			$index++;
			if( empty( $pwd_hashed ) )
			{
				if( $row->user_pass != $pass_md5 )
				{ // password doesn't match
					continue;
				}
			}
			elseif( sha1($row->user_pass.$pwd_salt) != $pwd_hashed )
			{ // password doesn't match
				continue;
			}
			// a user with matched password was found
			$first_matched_index = $index;
			if( ( $row->user_status == 'activated' ) || ( $row->user_status == 'autoactivated' ) )
			{ // an activated user was found, break from the iteration
				$User = new User( $row );
				break;
			}
			if( ( !isset( $first_notclosed_User ) ) && ( $row->user_status != 'closed' ) )
			{
				$first_notclosed_User = new User( $row );
			}
		}

		if( !isset( $User ) )
		{ // There is no activated user with the given email and password
			if( isset( $first_notclosed_User ) )
			{ // Get first not closed user with the given email and password
				$User = $first_notclosed_User;
			}
			elseif( $first_matched_index !== false )
			{ // There is only closed user with the given email and password
				$User = new User( $result[$first_matched_index] );
			}
			else
			{ // No matched user was found
				return false;
			}
		}

		// Add user to the cache and return result
		$this->add( $User );
		return array( & $User, $exists_more );
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
			$this->cache_login[ evo_strtolower($Obj->login) ] = & $Obj;

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
	function remove_by_ID( $req_ID )
	{
		if( isset($this->cache[$req_ID]) )
		{
			$Obj = & $this->cache[$req_ID];
			unset( $this->cache_login[ evo_strtolower($Obj->login) ] );
		}
		parent::remove_by_ID($req_ID);
	}
}


/*
 * $Log$
 * Revision 1.14  2013/11/06 08:05:03  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>