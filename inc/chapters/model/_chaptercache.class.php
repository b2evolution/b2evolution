<?php
/**
 * This file implements the ChapterCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'chapters/model/_chapter.class.php', 'Chapter' );

/**
 * ChapterCache Class
 *
 * @package evocore
 */
class ChapterCache extends DataObjectCache
{
	var $subset_cache = array();

	/**
	 * Which property of the objects defines the subset
	 */
	var $subset_property;

	/**
	 * Which subsets have been loaded
	 */
	var $loaded_subsets = array();

	/**
	 * These are the level 0 categories (which have no parent)
	 */
	var $root_cats = array();

	/**
	 * These are the level 0 categories (which have no parent) for each subset
	 */
	var $subset_root_cats = array();

	/**
	 * Have the children been revealed for all subsets yet?
	 */
	var $revealed_all_children = false;
	/**
	 * Have the children been revealed for all subsets yet?
	 */
	var $revealed_subsets = array();

	/**
	 * Flags about which arrays are already sorted
	 */
	var $sorted_flags = array();

	/**
	 * Lazy filled index of url titles
	 */
	var $urlname_index = array();


	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct( 'Chapter', false, 'T_categories', 'cat_', 'cat_ID', 'cat_name' );

		// This is the property by which we will filter out subsets, for exampel 'blog_ID' if we want to only load a specific collection:
		$this->subset_property = 'blog_ID';
	}


	/**
	 * Empty/reset the cache
	 */
	function clear( $keep_shadow = false )
	{
		$this->subset_cache = array();
		$this->loaded_subsets = array();
		$this->root_cats = array();
		$this->subset_root_cats = array();
		$this->revealed_all_children = false;
		$this->revealed_subsets = array();
		$this->sorted_flags = array();
		$this->urlname_index = array();
		parent::clear( $keep_shadow );
	}


	/**
	 * Add a dataobject to the cache
	 *
	 * @param object Chapter object to add in cache
	 * @return boolean TRUE on adding, FALSE on wrong object or if it is already in cache
	 */
	function add( $Chapter )
	{
		if( parent::add( $Chapter ) )
		{	// Successfully added

			if( !empty( $this->subset_property ) )
			{	// Also add to subset cache:
				$this->subset_cache[ $Chapter->{$this->subset_property} ][ $Chapter->ID ] = $Chapter;
			}
			return true;
		}

		return false;
	}


	/**
	 * Get an object from cache by ID
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param integer ID of object to load
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
 	 * @param integer|NULL NULL for all subsets
	 * @return Chapter Reference on cached object or false.
	 */
	function & get_by_ID( $req_ID, $halt_on_error = true, $halt_on_empty = true, $subset_ID = NULL )
	{
		global $DB, $Debuglog;

		if( empty($req_ID) )
		{
			if($halt_on_empty)
			{
				debug_die( "Requested Chapter from $this->dbtablename without ID!" );
			}
			$r = NULL;
			return $r;
		}

		if( !empty( $this->cache[ $req_ID ] ) )
		{ // Already in cache
			$Debuglog->add( "Accessing Chapter($req_ID) from cache", 'dataobjects' );
			return $this->cache[ $req_ID ];
		}
		elseif( !$this->all_loaded )
		{ // Not in cache, but not everything is loaded yet
			if( $this->load_all || is_null($subset_ID) )
			{ // It's ok to just load everything:
				$this->load_all();
			}
			else
			{ // Load just the requested object:
				$Debuglog->add( "Loading <strong>$this->objtype($req_ID)</strong> into cache", 'dataobjects' );
				// Note: $req_ID MUST be an unsigned integer. This is how DataObject works.
				$sql = "SELECT *
				          FROM T_categories
				         WHERE cat_ID = $req_ID
				           AND cat_blog_ID = ".$subset_ID;

				if( $row = $DB->get_row( $sql, OBJECT, 0, 'ChapterCache::get_by_ID()' ) )
				{
					if( ! $this->instantiate( $row ) )
					{
						$Debuglog->add( 'Could not add() object to cache!', 'dataobjects' );
					}
				}
				else
				{
					$Debuglog->add( 'Could not get DataObject by ID. Query: '.$sql, 'dataobjects' );
				}
			}
		}

		if( empty( $this->cache[ $req_ID ] ) )
		{ // Requested object does not exist
			// $Debuglog->add( 'failure', 'dataobjects' );
			if( $halt_on_error )
			{
				debug_die( "Requested $this->objtype does not exist!" );
			}
			$r = false;
			return $r;
		}

		return $this->cache[ $req_ID ];
	}


	/**
	 * Get an array with chapters ID that are located in the given blog from root to the given chapter
	 *
	 * @param integer Blog ID
	 * @param integer Chapter ID
	 * @return array Chapters ID
	 */
	function get_chapter_path( $blog_ID, $chapter_ID )
	{
		$this->load_subset( $blog_ID );

		$chapter_path = array( $chapter_ID );
		if( isset( $this->subset_cache[ $blog_ID ] ) )
		{
			$chapters = $this->subset_cache[ $blog_ID ];
			if( isset( $chapters[ $chapter_ID ] ) )
			{
				$Chapter = $chapters[ $chapter_ID ];
				while( $Chapter->get( 'parent_ID' ) > 0 )
				{
					$chapter_path[] = $Chapter->get( 'parent_ID' );
					// Select a parent chapter
					$Chapter = $chapters[ $Chapter->get( 'parent_ID' ) ];
				}
			}
		}

		return $chapter_path;
	}


	/**
	 * Get an object from cache by urlname
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param string ID of object to load
	 * @param boolean true if function should die on error
	 * @return reference on cached object
	 */
	function & get_by_urlname( $req_urlname, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		if( !isset( $this->urlname_index[$req_urlname] ) )
		{ // not yet in cache:
			// Load just the requested object:
			$Debuglog->add( "Loading <strong>$this->objtype($req_urlname)</strong> into cache", 'dataobjects' );
			$sql = "SELECT *
			          FROM $this->dbtablename
			         WHERE cat_urlname = ".$DB->quote($req_urlname);
			$row = $DB->get_row( $sql );
			if( empty( $row ) )
			{	// Requested object does not exist
				if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );
				// put into index:
				$this->urlname_index[$req_urlname] = false;

				return $this->urlname_index[$req_urlname];
			}

			$this->instantiate( $row );

			// put into index:
			$this->urlname_index[$req_urlname] = & $this->cache[ $row->cat_ID ];
		}
		else
		{
			$Debuglog->add( "Retrieving <strong>$this->objtype($req_urlname)</strong> from cache" );
		}

		return $this->urlname_index[$req_urlname];
	}


	/**
	 * Load a list of chapter referenced by their urlname into the cache
	 *
	 * @param array of urlnames of Chapters to load
	 */
	function load_urlname_array( $req_array )
	{
		global $DB, $Debuglog;

		$req_list = $DB->quote( $req_array );
		$Debuglog->add( "Loading <strong>$this->objtype($req_list)</strong> into cache", 'dataobjects' );
		$sql = "SELECT * FROM $this->dbtablename WHERE cat_urlname IN ( $req_list )";
		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		foreach( $DB->get_results( $sql ) as $row )
		{
			$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!

			// put into index:
			$this->urlname_index[$row->cat_urlname] = & $this->cache[ $row->$dbIDname ];

			$Debuglog->add( "Cached <strong>$this->objtype($row->cat_urlname)</strong>" );
		}
	}



	/**
	 * Load a keyed subset of the cache
	 *
 	 * @param integer|NULL NULL for all subsets
 	 * @param string Force 'order by' setting ('manual' = 'ORDER BY cat_order')
	 */
	function load_subset( $subset_ID, $force_order_by = '' )
	{
		global $DB, $Debuglog, $Settings;

		if( $this->all_loaded || isset( $this->loaded_subsets[$subset_ID] ) )
		{ // Already loaded
			return false;
		}

		// fp> TODO: This kills other subsets. BAD if we want to handle multiple subsets independently
		$this->clear( true );

		$Debuglog->add( 'ChapterCache - Loading <strong>chapters('.$subset_ID.')</strong> into cache', 'dataobjects' );
		$sql = 'SELECT *
					FROM T_categories
					WHERE cat_blog_ID = '.$subset_ID;

		foreach( $DB->get_results( $sql, OBJECT, 'Loading chapters('.$subset_ID.') into cache' ) as $row )
		{
			// Instantiate a custom object
			$this->instantiate( $row );
		}

		$this->loaded_subsets[$subset_ID] = true;

		return true;
	}


	/**
	 * Get chapters in the given subset
	 * Note: This is not ordered and it returns all of chpaters and subchapters
	 *
	 * @param integer subset ID
	 * @return array of Chapters
	 */
	function get_chapters_by_subset( $subset_ID )
	{
		if( ! isset( $this->loaded_subsets[$subset_ID] ) )
		{ // Load subset
			$this->load_subset( $subset_ID );
		}

		return $this->subset_cache[$subset_ID];
	}


	/**
	 * Get chapters by collection ID and parent ID
	 *
	 * @param integer collection ID
	 * @param integer parent chapter ID
	 * @param boolean set to true to sort the result, leave it on false otherwise
	 *
	 * @return array set of chapters
	 */
	function get_chapters( $coll_ID, $parent_ID = 0, $sorted = false )
	{
		$this->reveal_children( $coll_ID, $sorted );

		if( $parent_ID == 0 )
		{
			return $this->subset_root_cats[$coll_ID];
		}

		$parent_Chapter = & $this->get_by_ID( $parent_ID );
		if( $sorted )
		{ // Sort chapter
			$parent_Chapter->sort_children();
		}

		return $parent_Chapter->children;
	}


	/**
	 * Check if there are categories in the given subset
	 *
	 * @param integer subset ID
	 * @return boolean true if subset has chapters, false otherwise
	 */
	function has_chapters_in_subset( $subset_ID )
	{
		$this->load_subset( $subset_ID );
		return ! empty( $this->subset_cache[$subset_ID] );
	}


	/**
	 * Move a chapter and its descendants to a different collection
	 *
	 * @param integer
	 * @param integer
	 * @param integer
	 */
	function move_Chapter_subtree( $chapter_ID, $src_collection_ID, $dest_collection_ID )
	{
		/**
		 * @var DB
		 */
		global $DB;

		// Make sure children have been revealed for specific subset:
		$this->reveal_children( $src_collection_ID );

		// fp>We get the Chapter AFTER reveal_children, because something is wrong with reveal_children or get_by_ID
		// I don't know what right now, but if we get Chapter earlier, we'll be stuck with an old copy of it that does NOT have the children
		// TODO: find out what's freakin wrong
		$Chapter = $this->get_by_ID($chapter_ID);

		$chapters_to_move = array();
		// Get $chapters_to_move:
		$this->recurse_move_subtree( $Chapter, $chapters_to_move );
		// pre_dump( $chapters_to_move );

		$DB->begin();

		// Move to root:
		if( $parent_Chapter = $Chapter->get_parent_Chapter() )
		{	// Was not already at root, cut it and move it:
			// echo 'Move to root';
			$Chapter->set( 'parent_ID', NULL );
			$Chapter->dbupdate();
		}

		// Move Chapters to new Blog:
		$sql = 'UPDATE T_categories
							 SET cat_blog_ID = '.$dest_collection_ID.'
						 WHERE cat_blog_ID = '.$src_collection_ID /* extra security */ .'
						 	 AND cat_ID IN ('.implode( ',', $chapters_to_move ).')';
		$DB->query( $sql );

		$DB->commit();

		// Now the cache is badly screwed. Reseting it is fair enough, because this won't happen very often.
		$this->clear();
	}


	/**
	 * Support function for move_Chapter_subtree
	 *
	 * @param Chapter
	 * @param array
	 */
	function recurse_move_subtree( & $Chapter, & $list_array )
	{
		// Add this to the list:
		$list_array[] = $Chapter->ID;

		foreach( $Chapter->children as $child_Chapter )
		{
			$this->recurse_move_subtree( $child_Chapter, $list_array );
		}
	}


	/**
	 * Instanciate a new object within this cache
 	 *
 	 * @param object|NULL
 	 * @param integer|NULL subset to use for new object
	 */
	function & new_obj( $row = NULL, $subset_ID = NULL )
	{
		// Instantiate a custom object
		$Chapter = new Chapter( $row, $subset_ID ); // Copy

		return $Chapter;
	}


	/**
	 * Reveal children
	 *
	 * After executing this, each Chapter will have an array pointing to its direct children
	 *
	 * @param integer|NULL NULL for all subsets
	 * @param boolean Set true to sort root categories or leave it on false otherwise
	 */
	function reveal_children( $subset_ID = NULL, $sort_root_cats = false )
	{
		global $Debuglog;
		global $Timer;

		if( $this->revealed_all_children )
		{	// All children have already been revealed: (can happen even if we require a subset *now*)
			if( $sort_root_cats )
			{
				$this->sort_root_cats( $subset_ID );
			}
			return;
		}

		if( empty($this->subset_property) )
		{	// We are not handling a subset but everything instead:

			echo 'DEBUG - PARTIAL IMPLEMENTATION - Revealing all children -- this is not yet handling all edge cases that the subset version can handle';

			// Make sure everything has been loaded:
			$this->load_all();

			// Reveal children:
			if( !empty( $this->cache ) )
			{	// There are loaded categories, so loop on all loaded categories to set their children list if it has:
				foreach( $this->cache as $cat_ID => $Chapter )
				{
					// echo $Chapter->name;
					if( ! is_null( $Chapter->parent_ID ) )
					{	// This category has a parent, so add it to its parent children list:
						$this->cache[$Chapter->parent_ID]->add_child_category( $this->cache[$cat_ID] );
					}
					else
					{	// This category has no parent, so add it to the parent categories list
						$this->root_cats[] = & $this->cache[$cat_ID];
					}
				}
			}

			if( $sort_root_cats )
			{ // Sort root categories as it was requested
				$this->sort_root_cats( NULL );
			}

			$this->revealed_all_children = true;
		}
		else
		{	// We are handling a subset (for example Chapers of a specific blog_ID only):

			if( is_null( $subset_ID ) )
			{	// No specific subset requested, we are going to reveal all subsets in a row:

				// Make sure everything has been loaded:
				$this->load_all();

				// Commented out, because it is needed in case of cross posting through blogs
				// echo 'DEBUG - PARTIAL IMPLEMENTATION - REVEALING ALL SUBSETS in a row. Is this needed?';

				foreach( $this->subset_cache as $subset_ID => $dummy )
				{
					$this->reveal_children( $subset_ID, $sort_root_cats );
				}

				$this->revealed_all_children = true;
			}
			else
			{	// We're interested in a specific subset:
				// *** THIS IS THE CASE USED IN B2EVOLUTION ***

				if( !empty( $this->revealed_subsets[$subset_ID] ) )
				{	// Children have already been revealed:
					if( $sort_root_cats )
					{
						$this->sort_root_cats( $subset_ID );
					}
					return;
				}

				$Timer->resume('reveal_children', false );

				$Debuglog->add( 'Revealing subset of children', 'CategoryCache' );

				// Make sure the requested subset has been loaded:
				$this->load_subset($subset_ID);

				// Reveal children:
				if( !empty( $this->subset_cache[$subset_ID] ) )
				{	// There are loaded categories

					// Now check that each category has a path to the root:
					foreach( $this->subset_cache[$subset_ID] as $cat_ID => $dummy )	// "as" would give a freakin COPY of the object :(((
					{
						$this->check_path_to_root( $subset_ID, $cat_ID );
					}

					// loop on all loaded categories to set their children list if they have some:
					foreach( $this->subset_cache[$subset_ID] as $cat_ID => $dummy )	// "as" would give a freakin COPY of the object :(((
					{
						$Chapter = & $this->subset_cache[$subset_ID][$cat_ID];
						// echo '<br>'.$cat_ID;
						// echo $Chapter->name;
						if( ! is_null( $Chapter->parent_ID ) )
						{	// This category has a parent, so add it to its parent children list:
							$Debuglog->add( 'adding child with ID='.$cat_ID.' to parent ID='.$Chapter->parent_ID, 'CategoryCache' );
							$this->cache[$Chapter->parent_ID]->add_child_category( $this->cache[$cat_ID] );
						}
						else
						{	// This category has no parent, so add it to the parent categories list
							$Debuglog->add( 'adding parent with ID='.$cat_ID, 'CategoryCache' );
							$this->root_cats[] = & $Chapter; // $this->cache[$cat_ID];
							$this->subset_root_cats[$this->cache[$cat_ID]->{$this->subset_property}][] = & $Chapter; // $this->cache[$cat_ID];
						}
					}

					if( $sort_root_cats )
					{ // Sort subset root categories as it was requested
						$this->sort_root_cats( $subset_ID );
					}

					$Timer->pause('reveal_children', false );

				}

				// Children have been revealed.
				$this->revealed_subsets[$subset_ID] = true;
			}
		}

	}


	/**
	 * Support function for reveal_children()
	 *
	 * @param integer
	 * @param integer
	 * @return true if root parent
	 */
	function check_path_to_root( $subset_ID, $cat_ID, $path_array = array() )
	{
		global $Debuglog;
		$padding = str_pad('',count($path_array),'*');
		$Debuglog->add( $padding.'Checking path to root for cat ID='.$cat_ID.' with path:'.implode(',',$path_array), 'CategoryCache' );
		$Chapter = & $this->subset_cache[$subset_ID][$cat_ID];
		if( is_null( $Chapter->parent_ID ) )
		{	// Found root parent
			$Debuglog->add( $padding.'*OK', 'CategoryCache' );
			return true;
		}
		// This is not the last parent...
		// Record path...
		$path_array[] = $Chapter->ID;

		if( in_array( $Chapter->parent_ID, $path_array ) )
		{	// We are about to enter an infinite loop
			$Debuglog->add( $padding.'*LOOP! -> assign to root', 'CategoryCache' );
			$Chapter->parent_ID = NULL;
			return false;
		}
		elseif( ! isset($this->cache[$Chapter->parent_ID] ) )
		{
			$Debuglog->add( $padding.'*Missing parent ID('.$Chapter->parent_ID.')! -> assign to root', 'CategoryCache' );
			$Chapter->parent_ID = NULL;
			return false;
		}

		// Recurse!
		return $this->check_path_to_root( $subset_ID, $Chapter->parent_ID, $path_array );
	}


	/**
	 * Compare an Item with a Chapter based on the given order type
	 * TODO: asimo> Move this function to a different class ( E.g. to Chapter or to ChapterCache )
	 *
	 * @param Object Item
	 * @param Object Chapter
	 * @param string order type: 'alpha' or 'manual'
	 * @return @return number -1 if Item < Chapter, 1 if Item > Chapter, 0 if Item == Chapter
	 */
	function compare_item_with_chapter( $Item, $Chapter, $order_type )
	{
		if( $order_type == 'alpha' )
		{
			return strcmp( $Item->title, $Chapter->name );
		}

		if( $Item->order == NULL )
		{
			return $Chapter->order == NULL ? 0 : 1;
		}
		elseif( $Chapter->order == NULL )
		{
			return -1;
		}

		return ( $Item->order < $Chapter->order ) ? -1 : ( ( $Item->order > $Chapter->order ) ? 1 : 0 );
	}


	/**
	 * Sort root categories or root categories in a subset if they are not sorted yet
	 *
	 * @param integer subset_ID, leave it on null to sort in all subsets
	 */
	function sort_root_cats( $subset_ID = NULL )
	{
		if( $subset_ID == NULL && empty($this->subset_property) )
		{
			if( !$this->sorted_flags['root_cats'] )
			{ // Root cats were not sorted yet
				usort( $this->root_cats, array( 'Chapter','compare_chapters' ) );
				$this->sorted_flags['root_cats'] = true;
			}
			return;
		}

		if( $subset_ID == NULL )
		{
			foreach( array_keys( $this->subset_root_cats ) as $key )
			{
				if( $this->sorted_flags[$key] )
				{ // This subset was already sorted
					continue;
				}

				usort( $this->subset_root_cats[$key], array( 'Chapter','compare_chapters' ) );
				$this->sorted_flags[$key] = true;
			}
			return;
		}

		if( empty($this->sorted_flags[$subset_ID]) )
		{ // This subset was not sorted yet
			usort( $this->subset_root_cats[$subset_ID], array( 'Chapter','compare_chapters' ) );
			$this->sorted_flags[$subset_ID] = true;
		}
	}


	/**
	 * Return recursive display of loaded categories
	 *
	 * @param array callback functions (to format the display)
	 * @param integer|NULL NULL for all subsets
	 * @param array categories list to display
	 * @param integer depth of categories list
	 * @param integer Max depth of categories list, 0/empty - is infinite
	 * @param array Other params regarding the recursive display:
	 *   boolean sorted, sort categories or not - default value is false
	 *   string chapter_path, array of chapter ids from root category to the selected category
	 *   boolean expand_all, set true to expand all categories and not only the one in the chapter path
	 *   etc.
	 *
	 * @return string recursive list of all loaded categories
	 */
	function recurse( $callbacks, $subset_ID = NULL, $cat_array = NULL, $level = 0, $max_level = 0, $params = array() )
	{
		$params = array_merge( array(
				'sorted' => false,
				'chapter_path' => array(),
				'expand_all' => true,
				'posts_are_loaded' => false,
				'subset_ID' => $subset_ID,
				'max_level' => $max_level
			), $params );
		$sorted = $params['sorted'];
		$display_posts = ! empty( $callbacks['posts'] );

		// Make sure children have been (loaded and) revealed for specific subset:
		$this->reveal_children( $subset_ID, $sorted );

		if( $display_posts && !$params['posts_are_loaded'] && ( $params['expand_all'] || !empty( $params['chapter_path'] ) ) )
		{ // Posts needs to be loaded
			$ItemCache = & get_ItemCache();
			$ItemCache->load_by_categories( $params['chapter_path'], $subset_ID );
			$params['posts_are_loaded'] = true;
		}

		if( is_null( $cat_array ) )
		{	// Get all parent categories:
			if( is_null( $subset_ID ) )
			{
				$cat_array = $this->root_cats;
			}
			elseif( isset( $this->subset_root_cats[$subset_ID] ) )
			{ // We have root cats for the requested subset:
				$cat_array = $this->subset_root_cats[$subset_ID];
			}
			else
			{
				$cat_array = array();
			}
		}

		$r = '';

		// Go through all categories at this level:
		foreach( $cat_array as $cat )
		{
			// Check if category is expended
			$params['is_selected'] = in_array( $cat->ID, $params['chapter_path'] );
			$params['is_opened'] = $params['expand_all'] || $params['is_selected'];

			// Display a category:
			if( is_array( $callbacks['line'] ) )
			{ // object callback:
				$r .= $callbacks['line'][0]->{$callbacks['line'][1]}( $cat, $level, $params ); // <li> Category  - or - <tr><td>Category</td></tr> ...
			}
			else
			{
				$r .= $callbacks['line']( $cat, $level, $params ); // <li> Category  - or - <tr><td>Category</td></tr> ...
			}

			if( ( empty( $max_level ) || $max_level > $level + 1 ) && $params['is_opened'] )
			{ // Iterate through sub categories recursively
				$params['level'] = $level;
				$r .= $this->iterate_through_category_children( $cat, $callbacks, true, $params );
			}
		}

		if( ! empty( $r ) )
		{ // We have something to display at this level, wrap in in before/after:
			$r_before = '';
			if( is_array( $callbacks['before_level'] ) )
			{ // object callback:
				$r_before .= $callbacks['before_level'][0]->{$callbacks['before_level'][1]}( $level ); // <ul>
			}
			elseif( isset( $callbacks['before_level'] ) )
			{
				$r_before .= $callbacks['before_level']( $level ); // <ul>
			}

			$r = $r_before.$r;

			if( is_array( $callbacks['after_level'] ) )
			{ // object callback:
				$r .= $callbacks['after_level'][0]->{$callbacks['after_level'][1]}( $level ); // </ul>
			}
			elseif( isset( $callbacks['after_level'] ) )
			{
				$r .= $callbacks['after_level']( $level ); // </ul>
			}
		}

		return $r;
	}


	/**
	 * Iterate through the given Chapter sub cats and items
	 *
	 * @param Object Chapter
	 * @param array  Callback functions to display a Chapter and to display an Item
	 * @param boolean Set true to iterate sub categories recursively, false otherwise
	 * @param array Any additional params ( e.g. 'sorted', 'level', 'list_subs_start', etc. )
	 * @return string the concatenated callbacks result
	 */
	function iterate_through_category_children( $Chapter, $callbacks, $recurse = false, $params = array() )
	{
		$r = "";
		$cat_items = array();
		$has_sub_cats = ! empty( $Chapter->children );
		$params = array_merge( array(
				'sorted'    => false,
				'level'     => 0,
				'max_level' => 0,
				'subset_ID' => $Chapter->blog_ID
			), $params );

		if( $params['sorted'] && $has_sub_cats )
		{
			$Chapter->sort_children();
		}
		if( ! empty( $callbacks['posts'] ) )
		{
			$ItemCache = & get_ItemCache();
			$cat_items = $ItemCache->get_by_cat_ID( $Chapter->ID, $params['sorted'] );
		}

		if( $has_sub_cats || !empty( $cat_items ) )
		{ // Display category or posts
			$cat_index = 0;
			$item_index = 0;
			$subcats_to_display = array();
			$chapter_children_ids = array_keys( $Chapter->children );
			$has_more_children = isset( $chapter_children_ids[$cat_index] );
			$has_more_items = isset( $cat_items[$item_index] );
			$cat_order = $Chapter->get_subcat_ordering();
			// Set post params for post display
			$params['chapter_ID'] = $Chapter->ID;
			$params['cat_order'] = $cat_order;

			if( ( $has_more_children || $has_more_items ) && isset( $params['list_subs_start'] ) )
			{
				$r .= $params['list_subs_start'];
			}

			while( $has_more_children || $has_more_items )
			{
				$current_sub_Chapter = $has_more_children ? $Chapter->children[$chapter_children_ids[$cat_index]] : NULL;
				$current_Item = $has_more_items ? $cat_items[$item_index] : NULL;
				if( $current_Item != NULL && ( $current_sub_Chapter == NULL || ( $this->compare_item_with_chapter( $current_Item, $current_sub_Chapter, $cat_order ) <= 0 ) ) )
				{
					if( ! empty( $subcats_to_display ) )
					{
						if( $recurse )
						{
							$r .= $this->recurse( $callbacks, $params['subset_ID'], $subcats_to_display, $params['level'] + 1, $params['max_level'], $params );
						}
						else
						{ // Display each category without recursion
							foreach( $subcats_to_display as $sub_Chapter )
							{ // Display each category:
								if( is_array( $callbacks['line'] ) )
								{ // object callback:
									$r .= $callbacks['line'][0]->{$callbacks['line'][1]}( $sub_Chapter, 0, $params ); // <li> Category  - or - <tr><td>Category</td></tr> ...
								}
								else
								{
									$r .= $callbacks['line']( $sub_Chapter, 0, $params ); // <li> Category  - or - <tr><td>Category</td></tr> ...
								}
							}
						}
						$subcats_to_display = array();
					}
					if( is_array( $callbacks['posts'] ) )
					{ // object callback:
						$r .= $callbacks['posts'][0]->{$callbacks['posts'][1]}( $current_Item, $params['level'] + 1, $params );
					}
					else
					{
						$r .= $callbacks['posts']( $current_Item, $params['level'] + 1, $params );
					}
					$has_more_items = isset( $cat_items[++$item_index] );
				}
				elseif( $current_sub_Chapter != NULL )
				{
					$subcats_to_display[] = $current_sub_Chapter;
					$has_more_children = isset( $chapter_children_ids[++$cat_index] );
				}
			}

			if( ! empty( $subcats_to_display ) )
			{ // Display all subcats which were not displayed yet
				if( $recurse )
				{
					$r .= $this->recurse( $callbacks, $params['subset_ID'], $subcats_to_display, $params['level'] + 1, $params['max_level'], $params );
				}
				else
				{ // Display each category without recursion
					foreach( $subcats_to_display as $sub_Chapter )
					{ // Display each category:
						if( is_array( $callbacks['line'] ) )
						{ // object callback:
							$r .= $callbacks['line'][0]->{$callbacks['line'][1]}( $sub_Chapter, 0, $params ); // <li> Category  - or - <tr><td>Category</td></tr> ...
						}
						else
						{
							$r .= $callbacks['line']( $sub_Chapter, 0, $params ); // <li> Category  - or - <tr><td>Category</td></tr> ...
						}
					}
				}
			}

			if( ( $cat_index > 0 || $item_index > 0 ) && isset( $params['list_subs_end'] ) )
			{
				$r .= $params['list_subs_end'];
			}
		}
		elseif( isset( $callbacks['no_children'] ) )
		{ // Display message when no children
			if( is_array( $callbacks['no_children'] ) )
			{ // object callback:
				$r .= $callbacks['no_children'][0]->{$callbacks['no_children'][1]}( $Chapter, $params['level'] + 1 ); // </li>
			} else {
				$r .= $callbacks['no_children']( $Chapter, $params['level'] + 1 ); // </li>
			}
		}

		return $r;
	}


	/**
	 * Return recursive select options list of all loaded categories
	 *
	 * @param integer selected category in the select input
	 * @param integer NULL for all subsets
	 * @param boolean Include the root element?
	 * @param array GenercCategory objects to display (will recurse from those starting points)
	 * @param integer depth of categories list
	 * @param array IDs of categories to exclude (their children will be ignored to)
	 *
	 * @return string select options list of all loaded categories
	 */
	function recurse_select( $selected = NULL, $subset_ID = NULL, $include_root = false, $Cat_array = NULL,
							$level = 0, $exclude_array = array() )
	{
		// pre_dump( $exclude_array );

		// Make sure children have been revealed for specific subset:
		$this->reveal_children( $subset_ID );

		if( is_null( $Cat_array ) )
		{	// Get all parent categorie:
			$Cat_array = $this->root_cats;
		}

		$r = '';

		if( $include_root )
		{
			$r .= '<option value="">'.T_('Root').'</option>';
			$level++;
		}

		foreach( $Cat_array as $Chapter )
		{
			if( in_array( $Chapter->ID, $exclude_array ) )
			{	// We want to exclude that cat.
				continue;
			}

			// Set category indentation in the select:
			$indent = '';
			for($i = 0; $i < $level; $i++)
			{
				$indent .='&nbsp;&nbsp;';
			}
			// Set category option:
			$r .= '<option value="'.$Chapter->ID.'" ';
			if( $Chapter->ID == $selected ) $r .= ' selected="selected"';
			$r .= ' >'.$indent.$Chapter->name.'</option>';

			if( !empty( $Chapter->children ) )
			{	// Add children categories:
				$r .= $this->recurse_select( $selected, $subset_ID, false, $Chapter->children, $level+1, $exclude_array );
			}
		}

		return $r;
	}
}

?>