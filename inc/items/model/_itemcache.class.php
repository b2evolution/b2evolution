<?php
/**
 * This file implements the ItemCache class.
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

load_class( 'items/model/_item.class.php', 'Item' );

/**
 * Item Cache Class
 *
 * @package evocore
 */
class ItemCache extends DataObjectCache
{
	/**
	 * Lazy filled index of url titles
	 */
	var $urltitle_index = array();

	/**
	 * Lazy filled map of items by category
	 */
	var $items_by_cat_map = array();

	/**
	 * @var array
	 */
	var $cache_slug = array();

	/**
	 * Constructor
	 *
	 * @param string object type of elements in Cache
	 * @param string Name of the DB table
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function __construct( $objType = 'Item', $dbtablename = 'T_items__item', $dbprefix = 'post_', $dbIDname = 'post_ID' )
	{
		parent::__construct( $objType, false, $dbtablename, $dbprefix, $dbIDname );
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

		debug_die( 'Load all is not allowed for ItemCache!' );
	}


	function get_by_cat_ID( $cat_ID, $sorted = false )
	{
		$ChapterCache = & get_ChapterCache();
		$Chapter = $ChapterCache->get_by_ID( $cat_ID );

		if( ! isset( $this->items_by_cat_map[$cat_ID] ) )
		{ // Load items if not loaded yet
			$this->load_by_categories( array( $cat_ID ), $Chapter->blog_ID );
		}

		if( ! ( isset( $this->items_by_cat_map[$cat_ID]['sorted'] ) && $this->items_by_cat_map[$cat_ID]['sorted'] ) )
		{ // Not sorted yet
			$compare_method = $Chapter->get_subcat_ordering() == 'alpha' ? 'compare_items_by_title' : 'compare_items_by_order';
			usort( $this->items_by_cat_map[$cat_ID]['items'], array( 'Item', $compare_method ) );
			$this->items_by_cat_map[$cat_ID]['sorted'] = true;
		}

		return $this->items_by_cat_map[$cat_ID]['items'];
	}


	/**
	 * Load items by the given categories or collection ID
	 * After the Items are loaded create a map of loaded items by categories
	 *
	 * @param array of category ids
	 * @param integer collection ID
	 * @return boolean true if load items was required and it was loaded successfully, false otherwise
	 */
	function load_by_categories( $cat_array, $coll_ID )
	{
		global $DB;

		if( empty( $cat_array ) && empty( $coll_ID ) )
		{ // Nothing to load
			return false;
		}

		// In case of an empty cat_array param, use categoriesfrom the given collection
		if( empty( $cat_array ) )
		{ // Get all categories from the given subset
			$ChapterCache = & get_ChapterCache();
			$subset_chapters = $ChapterCache->get_chapters_by_subset( $coll_ID );
			$cat_array = array();
			foreach( $subset_chapters as $Chapter )
			{
				$cat_array[] = $Chapter->ID;
			}
		}

		// Check which category is not loaded
		$not_loaded_cat_ids = array();
		foreach( $cat_array as $cat_ID )
		{
			if( ! isset( $this->items_by_cat_map[$cat_ID] ) )
			{ // This category is not loaded
				$not_loaded_cat_ids[] = $cat_ID;
				// Initialize items_by_cat_map for this cat_ID
				$this->items_by_cat_map[$cat_ID] = array( 'items' => array(), 'sorted' => false );
			}
		}

		if( empty( $not_loaded_cat_ids ) )
		{ // Requested categories items are all loaded
			return false;
		}

		// Query to load all Items from the given categories
		$sql = 'SELECT postcat_cat_ID as cat_ID, postcat_post_ID as post_ID FROM T_postcats
					WHERE postcat_cat_ID IN ( '.implode( ', ', $not_loaded_cat_ids ).' )
					ORDER BY postcat_post_ID';

		$cat_posts = $DB->get_results( $sql, ARRAY_A, 'Get all category post ids pair by category' );

		// Initialize $Blog from coll_ID
		$BlogCache = & get_BlogCache();
		$Collection = $Blog = $BlogCache->get_by_ID( $coll_ID );

		$visibility_statuses = is_admin_page() ? get_visibility_statuses( 'keys', array('trash') ) : get_inskin_statuses( $coll_ID, 'post' );

		// Create ItemQuery for loading visible items
		$ItemQuery = new ItemQuery( $this->dbtablename, $this->dbprefix, $this->dbIDname );

		// Set filters what to select
		$ItemQuery->SELECT( $this->dbtablename.'.*' );
		$ItemQuery->where_chapter2( $Blog, $not_loaded_cat_ids, "" );
		$ItemQuery->where_visibility( $visibility_statuses );
		$ItemQuery->where_datestart( NULL, NULL, NULL, NULL, $Blog->get_timestamp_min(), $Blog->get_timestamp_max() );
		$ItemQuery->where_itemtype_usage( 'post' );

		// Clear previous items from the cache and load by the defined SQL
		$this->clear( true );
		$this->load_by_sql( $ItemQuery );

		foreach( $cat_posts as $row )
		{ // Iterate through the post - cat pairs and fill the map
			if( empty( $this->cache[ $row['post_ID'] ] ) )
			{ // The Item was not loaded because it does not correspond to the defined filters
				continue;
			}

			// Add to the map
			$this->items_by_cat_map[$row['cat_ID']]['items'][] = $this->get_by_ID( $row['post_ID'] );
		}
	}


	/**
	 * Get an object from cache by its urltitle
	 *
	 * Load into cache if necessary
	 *
	 * @param string Item slug title to get by
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return object|NULL|boolean Reference on cached object, NULL - if request with empty ID, FALSE - if requested object does not exist
	 */
	function & get_by_urltitle( $req_urltitle, $halt_on_error = true, $halt_on_empty = true )
	{
		if( ! isset( $this->urltitle_index[ $req_urltitle ] ) )
		{	// Get Item by SlugCache if it is not in cache yet:
			$SlugCache = & get_SlugCache();
			$slug_Item = & $SlugCache->get_object_by_slug( $req_urltitle, 'item', $halt_on_error, $halt_on_empty );
			$this->urltitle_index[ $req_urltitle ] = $slug_Item;
		}
		else
		{	// Use Item from cache:
			global $Debuglog;
			$Debuglog->add( 'Retrieving <strong>'.$this->objtype.'( '.$req_urltitle.' )</strong> from cache' );
		}

		return $this->urltitle_index[ $req_urltitle ];
	}


	/**
	 * Load a list of item referenced by their urltitle into the cache
	 *
	 * @deprecated DEPRECATED - To be removed in b2evolution 8.0
	 *
	 * @param array of urltitles of Items to load
	 */
	function load_urltitle_array( $req_array )
	{
		$SlugCache = & get_SlugCache();
		$SlugCache->load_objects_by_slugs( $req_array, 'item' );
	}


}

?>