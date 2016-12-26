<?php
/**
 * This file implements the UserCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
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
	function __construct()
	{
		parent::__construct( 'User', false, 'T_users', 'user_', 'user_ID', NULL, '',
			/* TRANS: "None" select option */ NT_('No user') );
	}


	/**
	 * Load the cache **extensively**
	 */
	function load_all()
	{
		if( $this->all_loaded )
		{ // Already loaded
			return false;
		}

		debug_die( 'Load all is not allowed for UserCache!' );
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
		$login = utf8_strtolower( $login );

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
				$new_user = new User( $row );
				$this->add( $new_user );
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
	 * @param boolean Is the password parameter already hashed?
	 * @return false|User
	 */
	function & get_by_loginAndPwd( $login, $pass, $pass_is_hashed = true )
	{
		if( !( $User = & $this->get_by_login( $login ) ) )
		{
			return false;
		}

		if( ! $User->check_password( $pass, $pass_is_hashed ) )
		{	// The password doesn't match
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
	 * @param string password
	 * @param array hashed password - If this is set, it means we need to check the hasshed password instead of the md5 password
	 * @param string current session password salt
	 * @return false|array false if user with this email not exists, array( $User, $exists_more ) pair otherwise
	 */
	function get_by_emailAndPwd( $email, $pass, $pwd_hashed = NULL, $pepper = NULL )
	{
		global $DB;

		// Get all users with matching email address
		$result = $DB->get_results('SELECT * FROM T_users
					WHERE user_email = '.$DB->quote( utf8_strtolower( $email ) ).'
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
				$user_PasswordDriver = get_PasswordDriver( $row->user_pass_driver );
				if( ! $user_PasswordDriver || ! $user_PasswordDriver->check( $pass, $row->user_salt, $row->user_pass ) )
				{ // password doesn't match
					continue;
				}
			}
			else
			{
				$pwd_matched = false;
				foreach( $pwd_hashed as $encrypted_password )
				{
					$pwd_matched = ( sha1( $row->user_pass.$pepper ) == $encrypted_password );
					if( $pwd_matched )
					{ // The corresponding user was found
						break;
					}
				}
				if( ! $pwd_matched )
				{ // If the user with matched login credentials was not fount continue with the next row
					continue;
				}
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
	 * @param object User object to add in cache
	 * @return boolean TRUE on adding, FALSE on wrong object or if it is already in cache
	 */
	function add( $User )
	{
		if( parent::add( $User ) )
		{
			$this->cache_login[ utf8_strtolower( $User->login ) ] = $User;

			return true;
		}

		return false;
	}


	/**
	 * Load members of a given blog
	 *
	 * @todo make a UNION query when we upgrade to MySQL 4
	 * @param integer Blog ID to load members for
	 * @param integer Limit, 0 - for unlimit
	 * @param boolean TRUE - to load only members, FALSE - to load the users which can be assigned to items if it is allowed by blog's settings
	 */
	function load_blogmembers( $blog_ID, $limit = 0, $load_members = true )
	{
		global $DB, $Debuglog;

		$BlogCache = & get_BlogCache();
		if( ! ( $Collection = $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) ) )
		{	// Wrong request:
			$Debuglog->add( "Collection #$blog_ID doesn't exist in DB on <strong>$this->objtype(Blog #$blog_ID members)</strong> into cache", 'dataobjects' );
			return false;
		}

		$load_assignees = false;
		if( ! $load_members && ! empty( $blog_ID ) )
		{	// Load assignees only when advanced perms are available and workflow is used:
			$load_assignees = $Blog->get( 'advanced_perms' ) && $Blog->get_setting( 'use_workflow' );
		}
		if( $load_assignees )
		{ // Load users which can be assigneed to items of the blog
			$cache_name = 'blogassignees';
			$db_field = 'can_be_assignee';
		}
		else
		{ // Load members of the blog
			$cache_name = 'blogmembers';
			$db_field = 'ismember';
		}

		if( isset( $this->alreadyCached[ $cache_name ] ) && isset( $this->alreadyCached[ $cache_name ][ $blog_ID ] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(Blog #$blog_ID members)</strong> into cache", 'dataobjects' );
			return false;
		}

		$this->none_option_text = NT_('No user');

		// Clear previous users to get only the members of this blog
		$this->clear();

		// Remember this special load:
		$this->alreadyCached[ $cache_name ][ $blog_ID ] = true;

		$Debuglog->add( "Loading <strong>$this->objtype(Blog #$blog_ID members)</strong> into cache[$cache_name]", 'dataobjects' );

		// The collection's owner is member and can be assignee by default, Load it together with other members/assignees:
		if( $owner_User = & $Blog->get_owner_User() )
		{	// Add collection owner to the cache:
			$this->add( $owner_User );
		}

		$users_sql = array();
		if( $Blog->get( 'advanced_perms' ) )
		{	// Load group and user permissions ONLY if collection advanced permissions are enabled:

			// Get users which are members or can be assignees of the collection:
			$user_perms_SQL = new SQL();
			$user_perms_SQL->SELECT( 'T_users.*' );
			$user_perms_SQL->FROM( 'T_users' );
			$user_perms_SQL->FROM_add( 'LEFT JOIN T_coll_user_perms ON user_ID = bloguser_user_ID' );
			$user_perms_SQL->WHERE( 'bloguser_blog_ID = '.$DB->quote( $blog_ID ) );
			$user_perms_SQL->WHERE_and( 'bloguser_'.$db_field.' <> 0' );
			$users_sql[] = $user_perms_SQL->get();

			// Get users which primary groups are members or can be assignees of the collection:
			$group_perms_SQL = new SQL();
			$group_perms_SQL->SELECT( 'T_users.*' );
			$group_perms_SQL->FROM( 'T_users' );
			$group_perms_SQL->FROM_add( 'INNER JOIN T_coll_group_perms ON user_grp_ID = bloggroup_group_ID' );
			$group_perms_SQL->WHERE( 'bloggroup_blog_ID = '.$DB->quote( $blog_ID ) );
			$group_perms_SQL->WHERE_and( 'bloggroup_'.$db_field.' <> 0' );
			$users_sql[] = $group_perms_SQL->get();

			// Get users which secondary groups are members or can be assignees of the collection:
			$secondary_group_perms_SQL = new SQL();
			$secondary_group_perms_SQL->SELECT( 'T_users.*' );
			$secondary_group_perms_SQL->FROM( 'T_users' );
			$secondary_group_perms_SQL->FROM_add( 'INNER JOIN T_users__secondary_user_groups ON sug_user_ID = user_ID' );
			$secondary_group_perms_SQL->FROM_add( 'INNER JOIN T_coll_group_perms ON sug_grp_ID = bloggroup_group_ID' );
			$secondary_group_perms_SQL->WHERE( 'bloggroup_blog_ID = '.$DB->quote( $blog_ID ) );
			$secondary_group_perms_SQL->WHERE_and( 'bloggroup_'.$db_field.' <> 0' );
			$users_sql[] = $secondary_group_perms_SQL->get();
		}

		// Get members or assignees which primary groups have a setting to VIEW/EDIT ALL collections:
		$group_setting_SQL = new SQL();
		$group_setting_SQL->SELECT( 'T_users.*' );
		$group_setting_SQL->FROM( 'T_users' );
		$group_setting_SQL->FROM_add( 'INNER JOIN T_groups ON user_grp_ID = grp_ID' );
		if( $load_members )
		{	// Get members which primary groups have a setting to VIEW/EDIT ALL collections:
			$group_setting_SQL->WHERE( 'grp_perm_blogs = "viewall" OR grp_perm_blogs = "editall"' );
		}
		else
		{	// Get assignees which primary groups have a setting to EDIT ALL collections:
			$group_setting_SQL->WHERE( 'grp_perm_blogs = "editall"' );
		}
		$users_sql[] = $group_setting_SQL->get();

		// Union sql queries to execute one query and save an order as one list:
		$users_sql = '( '.implode( ' ) UNION ( ', $users_sql ).' )';
		$users_sql .= ' ORDER BY user_login';
		if( $limit > 0 )
		{	// Limit the users:
			$users_sql .= ' LIMIT '.$limit;
		}

		$users = $DB->get_results( $users_sql, OBJECT, 'Load all members of collection #'.$blog_ID.' into cache['.$cache_name.']' );
		foreach( $users as $row )
		{
			if( ! isset( $this->cache[ $row->user_ID ] ) )
			{ // Save reinstatiating User if it's already been added
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
			$this->load_blogmembers( $blog_ID, 0, false );

			// Make sure current user is in list:
			if( $default && $always_load_default )
			{
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
			unset( $this->cache_login[ utf8_strtolower($Obj->login) ] );
		}
		parent::remove_by_ID($req_ID);
	}
}

?>