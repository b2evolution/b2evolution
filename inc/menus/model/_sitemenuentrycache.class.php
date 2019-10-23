<?php
/**
 * This file implements the SiteMenuCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'menus/model/_sitemenuentry.class.php', 'SiteMenuEntry' );

/**
 * SiteMenuEntryCache Class
 *
 * @package evocore
 */
class SiteMenuEntryCache extends DataObjectCache
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
		parent::__construct( 'SiteMenuEntry', false, 'T_menus__entry', 'ment_', 'ment_ID', 'ment_text' );

		// This is the property by which we will filter out subsets, for exampel 'blog_ID' if we want to only load a specific collection:
		$this->subset_property = 'menu_ID';
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
	 * @param object SiteMenuEntry object to add in cache
	 * @return boolean TRUE on adding, FALSE on wrong object or if it is already in cache
	 */
	function add( $SiteMenuEntry )
	{
		if( parent::add( $SiteMenuEntry ) )
		{	// Successfully added

			if( ! empty( $this->subset_property ) )
			{	// Also add to subset cache:
				$this->subset_cache[ $SiteMenuEntry->{$this->subset_property} ][ $SiteMenuEntry->ID ] = $SiteMenuEntry;
			}
			return true;
		}

		return false;
	}


	/**
	 * Get an array with chapters ID that are located in the given blog from root to the given chapter
	 *
	 * @param integer Blog ID
	 * @param integer Chapter ID
	 * @return array Chapters ID
	 */
	function get_menu_entry_path( $menu_ID, $menu_entry_ID )
	{
		$this->load_subset( $menu_ID );

		$menu_entry_path = array( $menu_entry_ID );
		if( isset( $this->subset_cache[ $menu_ID ] ) )
		{
			$menu_entries = $this->subset_cache[ $menu_ID ];
			if( isset( $menu_entries[ $menu_entry_ID ] ) )
			{
				$SiteMenuEntry = $menu_entries[ $menu_entry_ID ];
				while( $SiteMenuEntry->get( 'parent_ID' ) > 0 )
				{
					$menu_entry_path[] = $SiteMenuEntry->get( 'parent_ID' );
					// Select a parent chapter
					$SiteMenuEntry = $chapters[ $SiteMenuEntry->get( 'parent_ID' ) ];
				}
			}
		}

		return $menu_entry_path;
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

		$Debuglog->add( 'SiteMenuEntryCache - Loading <strong>menu entries('.$subset_ID.')</strong> into cache', 'dataobjects' );
		$sql = 'SELECT *
					FROM T_menus__entry
					WHERE ment_menu_ID = '.$subset_ID;

		foreach( $DB->get_results( $sql, OBJECT, 'Loading menu entries('.$subset_ID.') into cache' ) as $row )
		{
			// Instantiate a custom object
			$this->instantiate( $row );
		}

		$this->loaded_subsets[$subset_ID] = true;

		return true;
	}


	/**
	 * Support function for move_SiteMenuEntry_subtree
	 *
	 * @param SiteMenuEntry
	 * @param array
	 */
	function recurse_move_subtree( & $SiteMenuEntry, & $list_array )
	{
		// Add this to the list:
		$list_array[] = $SiteMenuEntry->ID;

		foreach( $SiteMenuEntry->children as $child_SiteMenuEntry )
		{
			$this->recurse_move_subtree( $child_SiteMenuEntry, $list_array );
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
		$SiteMenuEntry = new SiteMenuEntry( $row, $subset_ID ); // Copy

		return $SiteMenuEntry;
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
				foreach( $this->cache as $ment_ID => $SiteMenuEntry )
				{
					if( ! is_null( $SiteMenuEntry->parent_ID ) )
					{	// This category has a parent, so add it to its parent children list:
						$this->cache[$SiteMenuEntry->parent_ID]->add_child_entry( $this->cache[$ment_ID] );
					}
					else
					{	// This category has no parent, so add it to the parent categories list
						$this->root_cats[] = & $this->cache[$ment_ID];
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
					foreach( $this->subset_cache[$subset_ID] as $ment_ID => $dummy )	// "as" would give a freakin COPY of the object :(((
					{
						$this->check_path_to_root( $subset_ID, $ment_ID );
					}

					// loop on all loaded categories to set their children list if they have some:
					foreach( $this->subset_cache[$subset_ID] as $ment_ID => $dummy )	// "as" would give a freakin COPY of the object :(((
					{
						$SiteMenuEntry = & $this->subset_cache[$subset_ID][$ment_ID];
						if( ! is_null( $SiteMenuEntry->parent_ID ) )
						{	// This category has a parent, so add it to its parent children list:
							$Debuglog->add( 'adding child with ID='.$ment_ID.' to parent ID='.$SiteMenuEntry->parent_ID, 'CategoryCache' );
							$this->cache[$SiteMenuEntry->parent_ID]->add_child_entry( $this->cache[$ment_ID] );
						}
						else
						{	// This category has no parent, so add it to the parent categories list
							$Debuglog->add( 'adding parent with ID='.$ment_ID, 'CategoryCache' );
							$this->root_cats[] = & $SiteMenuEntry; // $this->cache[$ment_ID];
							$this->subset_root_cats[$this->cache[$ment_ID]->{$this->subset_property}][] = & $SiteMenuEntry;
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
	function check_path_to_root( $subset_ID, $ment_ID, $path_array = array() )
	{
		global $Debuglog;
		$padding = str_pad('',count($path_array),'*');
		$Debuglog->add( $padding.'Checking path to root for cat ID='.$ment_ID.' with path:'.implode(',',$path_array), 'CategoryCache' );
		$SiteMenuEntry = & $this->subset_cache[$subset_ID][$ment_ID];
		if( is_null( $SiteMenuEntry->parent_ID ) )
		{	// Found root parent
			$Debuglog->add( $padding.'*OK', 'CategoryCache' );
			return true;
		}
		// This is not the last parent...
		// Record path...
		$path_array[] = $SiteMenuEntry->ID;

		if( in_array( $SiteMenuEntry->parent_ID, $path_array ) )
		{	// We are about to enter an infinite loop
			$Debuglog->add( $padding.'*LOOP! -> assign to root', 'CategoryCache' );
			$SiteMenuEntry->parent_ID = NULL;
			return false;
		}
		elseif( ! isset($this->cache[$SiteMenuEntry->parent_ID] ) )
		{
			$Debuglog->add( $padding.'*Missing parent ID('.$SiteMenuEntry->parent_ID.')! -> assign to root', 'CategoryCache' );
			$SiteMenuEntry->parent_ID = NULL;
			return false;
		}

		// Recurse!
		return $this->check_path_to_root( $subset_ID, $SiteMenuEntry->parent_ID, $path_array );
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
				usort( $this->root_cats, array( $this,'compare_site_menu_entries' ) );
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

				usort( $this->subset_root_cats[$key], array( $this,'compare_site_menu_entries' ) );
				$this->sorted_flags[$key] = true;
			}
			return;
		}

		if( empty($this->sorted_flags[$subset_ID]) )
		{ // This subset was not sorted yet
			usort( $this->subset_root_cats[$subset_ID], array( $this,'compare_site_menu_entries' ) );
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
				'highlight_current' => true,
				'sorted' => false,
				'chapter_path' => array(),
				'expand_all' => true,
				'subset_ID' => $subset_ID,
				'max_level' => $max_level
			), $params );

		// Make sure children have been (loaded and) revealed for specific subset:
		$this->reveal_children( $subset_ID, $params['sorted'] );

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
			$params['is_selected'] = $params['highlight_current'] && in_array( $cat->ID, $params['chapter_path'] );
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

			if( $params['is_opened'] || ( $max_level > $level + 1 ) )
			{	// Iterate through sub categories recursively:
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
	 * Iterate through the given Site Menu Entry sub entries
	 *
	 * @param Object Chapter
	 * @param array  Callback functions to display a Site Menu Entry
	 * @param boolean Set true to iterate sub categories recursively, false otherwise
	 * @param array Any additional params ( e.g. 'sorted', 'level', 'list_subs_start', etc. )
	 * @return string the concatenated callbacks result
	 */
	function iterate_through_category_children( $SiteMenuEntry, $callbacks, $recurse = false, $params = array() )
	{
		$r = "";
		$has_sub_cats = ! empty( $SiteMenuEntry->children );
		$params = array_merge( array(
				'sorted'    => false,
				'level'     => 0,
				'max_level' => 0,
				'subset_ID' => $SiteMenuEntry->menu_ID,
			), $params );

		if( $params['sorted'] && $has_sub_cats )
		{
			$SiteMenuEntry->sort_children();
		}

		if( $has_sub_cats )
		{ // Display category
			$cat_index = 0;
			$subcats_to_display = array();
			$chapter_children_ids = array_keys( $SiteMenuEntry->children );
			$has_more_children = isset( $chapter_children_ids[$cat_index] );
			// Set post params for post display
			$params['chapter_ID'] = $SiteMenuEntry->ID;

			if( ( $has_more_children ) && isset( $params['list_subs_start'] ) )
			{
				$r .= $params['list_subs_start'];
			}

			while( $has_more_children )
			{
				$current_sub_SiteMenuEntry = $has_more_children ? $SiteMenuEntry->children[$chapter_children_ids[$cat_index]] : NULL;
				if( $current_sub_SiteMenuEntry != NULL )
				{
					$subcats_to_display[] = $current_sub_SiteMenuEntry;
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
					foreach( $subcats_to_display as $sub_SiteMenuEntry )
					{ // Display each category:
						if( is_array( $callbacks['line'] ) )
						{ // object callback:
							$r .= $callbacks['line'][0]->{$callbacks['line'][1]}( $sub_SiteMenuEntry, 0, $params ); // <li> Category  - or - <tr><td>Category</td></tr> ...
						}
						else
						{
							$r .= $callbacks['line']( $sub_SiteMenuEntry, 0, $params ); // <li> Category  - or - <tr><td>Category</td></tr> ...
						}
					}
				}
			}

			if( ( $cat_index > 0 ) && isset( $params['list_subs_end'] ) )
			{
				$r .= $params['list_subs_end'];
			}
		}
		elseif( isset( $callbacks['no_children'] ) )
		{ // Display message when no children
			if( is_array( $callbacks['no_children'] ) )
			{ // object callback:
				$r .= $callbacks['no_children'][0]->{$callbacks['no_children'][1]}( $SiteMenuEntry, $params['level'] + 1 ); // </li>
			} else {
				$r .= $callbacks['no_children']( $SiteMenuEntry, $params['level'] + 1 ); // </li>
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

		// Sort categories alphabetically or manually depending on settings:
		usort( $Cat_array, array( $this, 'compare_site_menu_entries' ) );

		$r = '';

		if( $include_root )
		{
			$r .= '<option value="">'.T_('Root').'</option>';
			$level++;
		}

		foreach( $Cat_array as $SiteMenuEntry )
		{
			if( in_array( $SiteMenuEntry->ID, $exclude_array ) )
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
			$r .= '<option value="'.$SiteMenuEntry->ID.'" ';
			if( $SiteMenuEntry->ID == $selected ) $r .= ' selected="selected"';
			$r .= ' >'.$indent.$SiteMenuEntry->text.'</option>';

			if( !empty( $SiteMenuEntry->children ) )
			{	// Add children categories:
				$r .= $this->recurse_select( $selected, $subset_ID, false, $SiteMenuEntry->children, $level+1, $exclude_array );
			}
		}

		return $r;
	}


	/**
	 * Compare two Site Menu Entries based on the parent/blog sort category setting
	 *
	 * @param Chapter A
	 * @param Chapter B
	 * @return number -1 if A < B, 1 if A > B, 0 if A == B
	 */
	static function compare_site_menu_entries( $a_SiteMenuEntry, $b_SiteMenuEntry )
	{
		if( $a_SiteMenuEntry == NULL || $b_SiteMenuEntry == NULL ) {
			debug_die('Invalid category objects received to compare.');
		}

		if( $a_SiteMenuEntry->ID == $b_SiteMenuEntry->ID )
		{ // The two chapters are the same
			return 0;
		}

		if( $a_SiteMenuEntry->parent_ID != $b_SiteMenuEntry->parent_ID )
		{ // Two chapters from the same blog, but different parrents
			// Compare those parents of these chapters which have a common parent Chapter or they are both root Chapters.
			$path_to_root_a = array_reverse( $this->get_menu_entry_path( $a_SiteMenuEntry->menu_ID, $a_SiteMenuEntry->ID ) );
			$path_to_root_b = array_reverse( $this->get_menu_entry_path( $b_SiteMenuEntry->menu_ID, $b_SiteMenuEntry->ID ) );
			$index = 0;
			while( isset( $path_to_root_a[$index] ) && isset( $path_to_root_b[$index] ) )
			{
				if( $path_to_root_a[$index] != $path_to_root_b[$index] )
				{ // The first different parent on the same level was found, compare parent objects
					$parent_a_SiteMenuEntry = $this->get_by_ID( $path_to_root_a[$index] );
					$parent_b_SiteMenuEntry = $this->get_by_ID( $path_to_root_b[$index] );
					return $this->compare_site_menu_entries( $parent_a_SiteMenuEntry, $parent_b_SiteMenuEntry );
				}
				$index++;
			}

			// One of the chapters is a parent of the other, the parent is considered greater than the other
			return isset( $path_to_root_a[$index] ) ? 1 : -1;
		}

		if( $a_SiteMenuEntry->order === NULL )
		{ // NULL values are greater than any number
			$result = ( $b_SiteMenuEntry->order !== NULL ) ? 1 : 0;
		}
		elseif( $b_SiteMenuEntry->order === NULL )
		{ // NULL values are greater than any number, so a is lower than b
			$result = -1;
		}
		else
		{
			$result = ( $a_SiteMenuEntry->order > $b_SiteMenuEntry->order ) ? 1 : ( ( $a_SiteMenuEntry->order < $b_SiteMenuEntry->order ) ? -1 : 0 );
		}

		if( $result == 0 )
		{ // In case if the order fields are equal order by ID
			$result = $a_SiteMenuEntry->ID > $b_SiteMenuEntry->ID ? 1 : -1;
		}

		return $result;
	}
}

?>