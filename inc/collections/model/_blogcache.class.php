<?php
/**
 * This file implements the BlogCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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
class BlogCache extends DataObjectCache
{
	/**
	 * Cache by absolute siteurl
	 * @var array
	 */
	var $cache_siteurl_abs = array();

	/**
	 * Cache by urlname
	 * @var array
	 */
	var $cache_urlname = array();

	/**
	 * Constructor
	 *
	 * @param string Name of the order field or NULL to use name field
	 */
	function BlogCache( $order_by = 'blog_order' )
	{
		parent::DataObjectCache( 'Blog', false, 'T_blogs', 'blog_', 'blog_ID', NULL, $order_by,
			/* TRANS: "None" select option */ NT_('No blog'), 0 );
	}


	/**
	 * Add object to cache, handling our own indices.
	 *
	 * @param Blog
	 * @return boolean True on add, false if already existing.
	 */
	function add( & $Blog )
	{
		if( ! empty($Blog->siteurl) && preg_match( '~^https?://~', $Blog->siteurl ) )
		{ // absolute siteurl
			$this->cache_siteurl_abs[ $Blog->siteurl ] = & $Blog;
		}

		$this->cache_urlname[ $Blog->urlname ] = & $Blog;

		return parent::add( $Blog );
	}


	/**
	 * Get an object from cache by its url ("siteurl")
	 *
	 * Load the cache if necessary
	 *
	 * This gets used in /index_multi.php to detect blogs according to the requested HostWithPath
	 *
	 * @todo fp> de-factorize. cleanup. make efficient. split access types.
	 *
	 * @param string URL of blog to load (should be the whole requested URL/path, e.g. "http://mr.example.com/permalink")
	 * @param boolean false if you want to return false on error
	 * @return Blog A Blog object on success, false on failure (may also halt!)
	 */
	function & get_by_url( $req_url, $halt_on_error = true )
	{
		global $DB, $Debuglog, $baseurl, $basedomain;

		foreach( array_keys($this->cache_siteurl_abs) as $siteurl_abs )
		{
			if( strpos( $req_url, $siteurl ) === 0 )
			{ // found in cache
				return $this->cache_siteurl_abs[$siteurl_abs];
			}
		}

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_url)</strong> into cache", 'dataobjects' );

		$req_url_wo_proto = substr( $req_url, strpos( $req_url, '://' ) ); // req_url without protocol, so it matches http and https below

		$sql = 'SELECT *
			  FROM T_blogs
			 WHERE ( blog_access_type = "absolute"
			         AND ( '.$DB->quote('http'.$req_url_wo_proto).' LIKE CONCAT( blog_siteurl, "%" )
		                 OR '.$DB->quote('https'.$req_url_wo_proto).' LIKE CONCAT( blog_siteurl, "%" ) ) )
			    OR ( blog_access_type = "subdom"
			         AND '.$DB->quote($req_url_wo_proto).' LIKE CONCAT( "://", blog_urlname, ".'.$basedomain.'/%" ) )';

		// Match stubs like "http://base/url/STUB?param=1" on $baseurl
		/*
		if( preg_match( "#^$baseurl([^/?]+)#", $req_url, $match ) )
		{
			$sql .= "\n OR ( blog_access_type = 'stub' AND blog_stub = ".$DB->quote($match[1])." )";
		}
		*/

		$row = $DB->get_row( $sql, OBJECT, 0, 'Blog::get_by_url()' );

		if( empty( $row ) )
		{ // Requested object does not exist
			if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );

			$r = false;
			return $r; // we return by reference!
		}

		$Blog = new Blog( $row );
		$this->add( $Blog );

		return $Blog;
	}


	/**
	 * Get a blog from cache by its URL name.
	 *
	 * Load the object into cache, if necessary.
	 *
	 * @param string URL name of object to load
	 * @param boolean false if you want to return false on error
	 * @return Blog|false A Blog object on success, false on failure (may also halt!)
	 */
	function & get_by_urlname( $req_urlname, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		if( isset($this->cache_urlname[$req_urlname]) )
		{
			return $this->cache_urlname[$req_urlname];
		}

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_urlname)</strong> into cache", 'dataobjects' );
		$sql = "
				SELECT *
				  FROM $this->dbtablename
				 WHERE blog_urlname = ".$DB->quote($req_urlname);
		$row = $DB->get_row( $sql );

		if( empty( $row ) )
		{ // Requested object does not exist
			if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );
			$r = false;
			return $r;
		}

		$Blog = new Blog( $row );
		$this->add( $Blog );

		return $Blog;
	}


	/**
	 * Load the cache **extensively**
	 */
	function load_all( $order_by = '', $order_dir = '' )
	{
		global $Settings;

		if( $order_by == '' )
		{	// Use default value from settings
			$order_by = $Settings->get('blogs_order_by');
		}

		if( $order_dir == '' )
		{	// Use default value from settings
			$order_dir = $Settings->get('blogs_order_dir');
		}

		// Save order
		$saved_order = $this->order_by;

		$this->order_by = gen_order_clause( $order_by, $order_dir, $this->dbprefix, $this->dbIDname );

		parent::load_all();

		// Restore
		$this->order_by = $saved_order;
	}


	/**
	 * Load a list of public blogs into the cache
	 *
	 * @param string
	 * @return array of IDs
	 */
	function load_public( $order_by = '', $order_dir = '' )
	{
		global $DB, $Settings, $Debuglog;

		$Debuglog->add( "Loading <strong>$this->objtype(public)</strong> into cache", 'dataobjects' );

		if( $order_by == '' )
		{	// Use default value from settings
			$order_by = $Settings->get('blogs_order_by');
		}

		if( $order_dir == '' )
		{	// Use default value from settings
			$order_dir = $Settings->get('blogs_order_dir');
		}

		$SQL = new SQL();
		$SQL->SELECT( '*' );
		$SQL->FROM( $this->dbtablename );
		$sql_where = 'blog_in_bloglist = "public"';
		if( is_logged_in() )
		{ // Allow the blogs that available for logged in users
			$sql_where .= ' OR blog_in_bloglist = "logged"';
			// Allow the blogs that available for members
			global $current_User;
			$sql_where .= ' OR ( blog_in_bloglist = "member" AND (
					( SELECT bloguser_user_ID FROM T_coll_user_perms WHERE bloguser_blog_ID = blog_ID AND bloguser_ismember = 1 AND bloguser_user_ID = '.$current_User->ID.' ) OR
					( SELECT bloggroup_group_ID FROM T_coll_group_perms WHERE bloggroup_blog_ID = blog_ID AND bloggroup_ismember = 1 AND bloggroup_group_ID = '.$current_User->grp_ID.' )
				) )';
		}
		$SQL->WHERE( '( '.$sql_where.' )' );
		$SQL->ORDER_BY( gen_order_clause( $order_by, $order_dir, 'blog_', 'blog_ID' ) );

		foreach( $DB->get_results( $SQL->get(), OBJECT, 'Load public blog list' ) as $row )
		{	// Instantiate a custom object
			$this->instantiate( $row );
		}

		return $DB->get_col( NULL, 0 );
	}


	/**
	 * Load a list of blogs owner by specific ID into the cache
	 *
	 * @param integer
	 * @param string
	 * @return array of IDs
	 */
	function load_owner_blogs( $owner_ID, $order_by = '', $order_dir = '' )
	{
		global $DB, $Settings, $Debuglog;

		$Debuglog->add( "Loading <strong>$this->objtype(owner={$owner_ID})</strong> into cache", 'dataobjects' );

		if( $order_by == '' )
		{	// Use default value from settings
			$order_by = $Settings->get('blogs_order_by');
		}

		if( $order_dir == '' )
		{	// Use default value from settings
			$order_dir = $Settings->get('blogs_order_dir');
		}

		$SQL = new SQL();
		$SQL->SELECT( '*' );
		$SQL->FROM( $this->dbtablename );
		$SQL->WHERE( 'blog_owner_user_ID = '.$DB->quote($owner_ID) );
		$SQL->ORDER_BY( gen_order_clause( $order_by, $order_dir, 'blog_', 'blog_ID' ) );

		foreach( $DB->get_results( $SQL->get(), OBJECT, 'Load owner blog list' ) as $row )
		{	// Instantiate a custom object
			$this->instantiate( $row );
		}

		return $DB->get_col( NULL, 0 );
	}


	/**
	 * Load blogs a user has permissions for.
	 *
	 * @param string permission: 'member' (default), 'browse' (files)
	 * @param string
	 * @param integer user ID
	 * @return array The blog IDs
	 */
	function load_user_blogs( $permname = 'blog_ismember', $permlevel = 'view', $user_ID = NULL, $order_by = '', $order_dir = '', $limit = NULL )
	{
		global $DB, $Settings, $Debuglog;

		$Debuglog->add( "Loading <strong>$this->objtype(permission: $permname)</strong> into cache", 'dataobjects' );

		if( $order_by == '' )
		{	// Use default value from settings
			$order_by = $Settings->get('blogs_order_by');
		}

		if( $order_dir == '' )
		{	// Use default value from settings
			$order_dir = $Settings->get('blogs_order_dir');
		}

		if( is_null($user_ID) )
		{
			global $current_User;
			$user_ID = $current_User->ID;
			$for_User = $current_User;
		}
		else
		{
			$UserCache = & get_UserCache();
			$for_User = & $UserCache->get_by_ID( $user_ID );
		}
		$for_User->get_Group();// ensure Group is set

		$Group = $for_User->Group;
		// First check if we have a global access perm:
 		if( $Group->check_perm( 'blogs', $permlevel ) )
		{ // If group grants a global permission:
			$this->load_all( $order_by, $order_dir );
			return $this->get_ID_array();
		}

		// Note: We only JOIN in the advanced perms if any given blog has them enabled,
		// otherwise they are ignored!
		$sql = "SELECT DISTINCT T_blogs.*
		          FROM T_blogs LEFT JOIN T_coll_user_perms ON (blog_advanced_perms <> 0
		          																				AND blog_ID = bloguser_blog_ID
		          																				AND bloguser_user_ID = {$user_ID} )
		          		 LEFT JOIN T_coll_group_perms ON (blog_advanced_perms <> 0
		          																	AND blog_ID = bloggroup_blog_ID
		          																	AND bloggroup_group_ID = {$Group->ID} )
		         WHERE ";

		if( $permname != 'blog_admin' )
		{	// Only the admin perm is not convered by being the owner of the blog:
			$sql .= "blog_owner_user_ID = {$user_ID} ";
		}

		switch( $permname )
		{
			case 'blog_ismember':
				$sql .= "OR bloguser_ismember <> 0
								 OR bloggroup_ismember <> 0";
				break;

			case 'blog_post_statuses':
				$sql .= "OR bloguser_perm_poststatuses <> ''
							   OR bloggroup_perm_poststatuses <> ''";
				break;

			case 'blog_comments':
				// user needs to have permission for at least one kind of comments
				$sql .= "OR bloguser_perm_cmtstatuses <> ''
						OR bloggroup_perm_cmtstatuses <> ''";
				break;

			case 'stats':
				$permname = 'blog_properties';	// TEMP
			case 'blog_cats':
			case 'blog_properties':
			case 'blog_admin':
			case 'blog_media_browse':
				$short_permname = substr( $permname, 5 );
				$sql .= "OR bloguser_perm_{$short_permname} <> 0
								 OR bloggroup_perm_{$short_permname} <> 0";
				break;

			default:
				debug_die( 'BlogCache::load_user_blogs() : Unsupported perm ['.$permname.']!' );
		}

		$sql .= " ORDER BY ".gen_order_clause( $order_by, $order_dir, $this->dbprefix, $this->dbIDname );

		if( $limit )
		{
			$sql .= " LIMIT {$limit}";
		}

		foreach( $DB->get_results( $sql, OBJECT, 'Load user blog list' ) as $row )
		{
			// Instantiate a custom object
			$this->instantiate( $row );
		}

		return $DB->get_col( NULL, 0 );
	}


	/**
	 * Returns form option list with cache contents
	 *
	 * Loads the whole cache!
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 * @param string Callback method name
	 * @return string HTML tags <option>
	 */
	function get_option_list( $default = 0, $allow_none = false, $method = 'get_name' )
	{
		// We force a full load!
		$this->load_all();

		return parent::get_option_list( $default, $allow_none, $method );
	}


	/**
	 * Returns form option list with cache contents
	 *
	 * Loads the blogs with forums type!
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 * @param string Callback method name
	 * @return string HTML tags <option>
	 */
	function get_option_list_forums( $default = 0, $allow_none = false, $method = 'get_name' )
	{
		// Clear previous cache list
		$this->clear();
		// Load only blogs with type 'forum'
		$this->load_where( 'blog_type = "forum"' );

		return parent::get_option_list( $default, $allow_none, $method );
	}


	/**
	 * Returns form option list with cache contents
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 * @param string Callback method name
	 * @return string HTML tags <option>
	 */
	function get_option_list_parent( $default = 0, $allow_none = false, $method = 'get_name' )
	{
		return parent::get_option_list( $default, $allow_none, $method );
	}
}

?>