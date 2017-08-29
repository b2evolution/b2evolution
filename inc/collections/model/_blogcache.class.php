<?php
/**
 * This file implements the BlogCache class.
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
	function __construct( $order_by = 'blog_order' )
	{
		parent::__construct( 'Blog', false, 'T_blogs', 'blog_', 'blog_ID', NULL, $order_by,
			/* TRANS: "None" select option */ NT_('No blog'), 0 );
	}


	/**
	 * Add object to cache, handling our own indices.
	 *
	 * @param object Collection object
	 * @return boolean True on add, false if already existing.
	 */
	function add( $Blog )
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
		global $DB, $Debuglog, $baseurl, $basehost, $baseport;

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
			         AND ( '.$DB->quote( 'http'.$req_url_wo_proto ).' LIKE CONCAT( blog_siteurl, "%" )
		                 OR '.$DB->quote( 'https'.$req_url_wo_proto ).' LIKE CONCAT( blog_siteurl, "%" ) ) )
			    OR ( blog_access_type = "subdom"
			         AND '.$DB->quote( $req_url_wo_proto ).' LIKE CONCAT( "://", blog_urlname, ".'.$basehost.$baseport.'/%" ) )';

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

		$Collection = $Blog = new Blog( $row );
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

		$Collection = $Blog = new Blog( $row );
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
	 * @param string Order By
	 * @param string Order Direction
	 * @return array of IDs
	 */
	function load_public( $order_by = '', $order_dir = '' )
	{
		global $DB, $Debuglog;

		$Debuglog->add( "Loading <strong>$this->objtype(public)</strong> into cache", 'dataobjects' );

		$SQL = $this->get_public_colls_SQL( $order_by, $order_dir );

		foreach( $DB->get_results( $SQL->get(), OBJECT, 'Load public blog list' ) as $row )
		{	// Instantiate a custom object
			$this->instantiate( $row );
		}

		return $DB->get_col( NULL, 0 );
	}


	/**
	 * Get SQL to load a list of public collections
	 *
	 * @param string Order By
	 * @param string Order Direction
	 * @return object SQL
	 */
	function get_public_colls_SQL( $order_by = '', $order_dir = '' )
	{
		global $Settings;

		if( $order_by == '' )
		{	// Use default value from settings:
			$order_by = $Settings->get( 'blogs_order_by' );
		}

		if( $order_dir == '' )
		{	// Use default value from settings:
			$order_dir = $Settings->get( 'blogs_order_dir' );
		}

		$SQL = new SQL();
		$SQL->SELECT( '*' );
		$SQL->FROM( $this->dbtablename );
		$sql_where = 'blog_in_bloglist = "public"';
		if( is_logged_in() )
		{	// Allow the collections that available for logged in users:
			$sql_where .= ' OR blog_in_bloglist = "logged"';
			// Allow the collections that available for members:
			global $current_User;
			$sql_where .= ' OR ( blog_in_bloglist = "member" AND (
					( SELECT grp_ID
					    FROM T_groups
					   WHERE grp_ID = '.$current_User->grp_ID.'
					     AND grp_perm_blogs IN ( "viewall", "editall" ) ) OR
					( SELECT bloguser_user_ID
					    FROM T_coll_user_perms
					   WHERE bloguser_blog_ID = blog_ID
					     AND bloguser_ismember = 1
					     AND bloguser_user_ID = '.$current_User->ID.' ) OR
					( SELECT bloggroup_group_ID
					    FROM T_coll_group_perms
					   WHERE bloggroup_blog_ID = blog_ID
					     AND bloggroup_ismember = 1
					     AND ( bloggroup_group_ID = '.$current_User->grp_ID.'
					           OR bloggroup_group_ID IN ( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = '.$current_User->ID.' ) )
					  LIMIT 1
					)
				) )';
		}
		$SQL->WHERE( '( '.$sql_where.' )' );
		$SQL->ORDER_BY( gen_order_clause( $order_by, $order_dir, 'blog_', 'blog_ID' ) );

		return $SQL;
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
	 * @param string Permission name: 'member' (default), 'browse' (files)
	 * @param string Permission level: 'view', 'edit'
	 * @param integer User ID
	 * @param string Order by
	 * @param string Order direction
	 * @param integer Limit
	 * @param string Filter: 'favorite' - to get only favorite collections for current user
	 * @return array The blog IDs
	 */
	function load_user_blogs( $permname = 'blog_ismember', $permlevel = 'view', $user_ID = NULL, $order_by = '', $order_dir = '', $limit = NULL, $filter = NULL )
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

		if( $filter == 'favorite' )
		{	// Get only favorite collections of the user:
			$sql_filter = 'INNER JOIN T_coll_user_favs
				 ON cufv_blog_ID = blog_ID
				AND cufv_user_ID = '.$DB->quote( $user_ID );
		}

		$Group = $for_User->Group;
		// First check if we have a global access perm:
		if( $Group->check_perm( 'blogs', $permlevel ) ||
		    ( $permname == 'blog_media_browse' && $Group->check_perm( 'files', 'edit' ) ) ||
		    ( $permname == 'stats' && $Group->check_perm( 'stats', 'view' ) ) )
		{ // If group grants a global permission:
			$this->clear();
			if( isset( $sql_filter ) )
			{	// Filter collections:
				$blog_SQL = $this->get_SQL_object();
				$blog_SQL->FROM_add( $sql_filter );
				$this->load_by_sql( $blog_SQL );
			}
			else
			{	// Get all collections:
				$this->load_all( $order_by, $order_dir );
			}
			return $this->get_ID_array();
		}

		// Note: We only JOIN in the advanced perms if any given blog has them enabled,
		// otherwise they are ignored!
		$sql = 'SELECT DISTINCT T_blogs.*
			FROM T_blogs
			LEFT JOIN T_coll_user_perms ON ( blog_advanced_perms <> 0
			      AND blog_ID = bloguser_blog_ID
			      AND bloguser_user_ID = '.$user_ID.' )
			LEFT JOIN T_coll_group_perms ON ( blog_advanced_perms <> 0
			      AND blog_ID = bloggroup_blog_ID
			      AND ( bloggroup_group_ID = '.$Group->ID.'
			            OR bloggroup_group_ID IN ( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = '.$user_ID.' ) ) )';

		if( isset( $sql_filter ) )
		{	// Filter collections:
			$sql .= $sql_filter;
		}

		$sql .= ' WHERE ';

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
						OR bloggroup_perm_cmtstatuses <> ''
						OR bloguser_perm_meta_comment = 1
						OR bloggroup_perm_meta_comment = 1";
				break;

			case 'stats':
				$permname = 'blog_analytics';
			case 'blog_analytics':
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
	 * Load into the cache a list of collections which have the enabled settings to subscribe on new posts or comments
	 *
	 * @param object User, Restrict public collections which available only for the User
	 * @param array IDs of collections which should be exluded from list
	 * @param string Order By
	 * @param string Order Direction
	 * @return array of IDs
	 */
	function load_subscription_colls( $User, $exclude_coll_IDs = NULL, $order_by = '', $order_dir = '' )
	{
		global $DB, $Settings, $Debuglog;

		$Debuglog->add( 'Loading <strong>'.$this->objtype.'(subscription collections)</strong> into cache', 'dataobjects' );

		if( $order_by == '' )
		{	// Use default value from settings:
			$order_by = $Settings->get( 'blogs_order_by' );
		}

		if( $order_dir == '' )
		{	// Use default value from settings:
			$order_dir = $Settings->get( 'blogs_order_dir' );
		}

		load_class( 'collections/model/_collsettings.class.php', 'CollectionSettings' );
		$CollectionSettings = new CollectionSettings();

		$blog_cache_SQL = $this->get_SQL_object();

		$blog_cache_SQL->title = 'Get the '.$this->objtype.'(subscription collections) rows to load the objects into the cache by '.get_class().'->'.__FUNCTION__.'()';

		// Initialize subquery to get all collections which allow subscription for new ITEMS/POSTS:
		if( $CollectionSettings->get_default( 'allow_subscriptions' ) == 0 )
		{	// If default setting disables to subscribe for new ITEMS/POSTS, we should get only the collections which allow the subsriptions:
			$sql_operator = 'IN';
			$sql_value = '1';
		}
		else
		{	// If default setting enables to subscribe for new ITEMS/POSTS, we should exclude the collections which don't allow the subsriptions:
			$sql_operator = 'NOT IN';
			$sql_value = '0';
		}
		$allow_item_subscriptions_sql = 'blog_ID '.$sql_operator.' (
					SELECT cset_coll_ID
					  FROM T_coll_settings
					 WHERE cset_name = "allow_subscriptions"
					   AND cset_value = '.$sql_value.'
				)';
		// Initialize subquery to get all collections which allow subscription for new COMMENTS:
		if( $CollectionSettings->get_default( 'allow_comment_subscriptions' ) == 0 )
		{	// If default setting disables to subscribe for new COMMENTS, we should get only the collections which allow the subsriptions:
			$sql_operator = 'IN';
			$sql_value = '1';
		}
		else
		{	// If default setting enables to subscribe for new COMMENTS, we should exclude the collections which don't allow the subsriptions:
			$sql_operator = 'NOT IN';
			$sql_value = '0';
		}
		$allow_comment_subscriptions_sql = 'blog_ID '.$sql_operator.' (
				SELECT cset_coll_ID
				  FROM T_coll_settings
				 WHERE cset_name = "allow_comment_subscriptions"
				   AND cset_value = '.$sql_value.'
			)';

		// Get collections which which allow subscription for new items/posts OR comments:
		$blog_cache_SQL->WHERE_and( $allow_item_subscriptions_sql.' OR '.$allow_comment_subscriptions_sql );

		if( $Settings->get( 'subscribe_new_blogs' ) == 'public' )
		{	// If a subscribing is available only for the public collections:
			$blog_cache_SQL->WHERE_and( '( blog_ID NOT IN ( SELECT cset_coll_ID FROM T_coll_settings WHERE cset_name = "allow_access" AND cset_value = "members" ) ) OR
				( blog_ID IN ( SELECT cset_coll_ID FROM T_coll_settings WHERE cset_name = "allow_access" AND cset_value = "members" ) AND (
					( SELECT bloguser_user_ID
					    FROM T_coll_user_perms
					   WHERE bloguser_blog_ID = blog_ID
					     AND bloguser_ismember = 1
					     AND bloguser_user_ID = '.$User->ID.' ) OR
					( SELECT bloggroup_group_ID
					    FROM T_coll_group_perms
					   WHERE bloggroup_blog_ID = blog_ID
					     AND bloggroup_ismember = 1
					     AND ( bloggroup_group_ID = '.$User->grp_ID.'
					           OR bloggroup_group_ID IN ( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = '.$User->ID.' ) )
					  LIMIT 1
					)
				) )' );
		}

		if( ! empty( $exclude_coll_IDs ) )
		{	// Exclude the collections from the list (for example, if user already is subscribed on them):
			$blog_cache_SQL->WHERE_and( 'blog_ID NOT IN ( '.$DB->quote( $exclude_coll_IDs ).' )' );
		}

		$blog_cache_SQL->ORDER_BY( gen_order_clause( $order_by, $order_dir, $this->dbprefix, $this->dbIDname ) );

		$this->load_by_sql( $blog_cache_SQL );

		return array_keys( $this->cache );
	}


	/**
	 * Returns form option list with cache contents
	 *
	 * Loads the whole cache!
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 * @param string Callback method name
	 * @param array IDs to ignore.
	 * @return string HTML tags <option>
	 */
	function get_option_list( $default = 0, $allow_none = false, $method = 'get_name', $ignore_IDs = array() )
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