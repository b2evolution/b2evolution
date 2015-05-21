<?php
/**
 * This file implements the GenericCategoryCache class.
 *
 * This is the object handling genreric category lists.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( 'generic/model/_genericcache.class.php', 'GenericCache' );


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_CategoryCache'] = false;


/**
 * GenericCategoryCache class
 *
 *
 * @package evocore
 */
class GenericCategoryCache extends GenericCache
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
	 * Constructor
	 */
	function GenericCategoryCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname = 'ID', $name_field = NULL, $subset_property = NULL, $order_by = '', $allow_none_text = NULL, $allow_none_value = '', $select = '' )
	{
		parent::GenericCache( $objtype, $load_all, $tablename, $prefix, $dbIDname, $name_field, $order_by, $allow_none_text, $allow_none_value, $select );

		// This is the property by which we will filter out subsets, for exampel 'blog_ID' if we want to only load a specific collection:
		$this->subset_property = $subset_property;
	}


	/**
	 * Empty/reset the cache
	 */
	function clear()
	{
		$this->subset_cache = array();
		$this->loaded_subsets = array();
		$this->root_cats = array();
		$this->subset_root_cats = array();
		$this->revealed_all_children = false;
		$this->revealed_subsets = array();
		$this->sorted_flags = array();
		parent::clear();
 	}


	/**
	 * Add a dataobject to the cache
	 */
	function add( & $Obj )
	{
		global $Debuglog;

		if( parent::add( $Obj ) )
		{	// Successfully added

			if( !empty($this->subset_property) )
			{	// Also add to subset cache:
				$this->subset_cache[$Obj->{$this->subset_property}][$Obj->ID] = & $Obj;
			}
			return true;
		}

		return false;
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
				foreach( $this->cache as $cat_ID => $GenericCategory )
				{
					// echo $GenericCategory->name;
					if( ! is_null( $GenericCategory->parent_ID ) )
					{	// This category has a parent, so add it to its parent children list:
						$this->cache[$GenericCategory->parent_ID]->add_child_category( $this->cache[$cat_ID] );
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
						$GenericCategory = & $this->subset_cache[$subset_ID][$cat_ID];
						// echo '<br>'.$cat_ID;
						// echo $GenericCategory->name;
						if( ! is_null( $GenericCategory->parent_ID ) )
						{	// This category has a parent, so add it to its parent children list:
							$Debuglog->add( 'adding child with ID='.$cat_ID.' to parent ID='.$GenericCategory->parent_ID, 'CategoryCache' );
							$this->cache[$GenericCategory->parent_ID]->add_child_category( $this->cache[$cat_ID] );
						}
						else
						{	// This category has no parent, so add it to the parent categories list
							$Debuglog->add( 'adding parent with ID='.$cat_ID, 'CategoryCache' );
							$this->root_cats[] = & $GenericCategory; // $this->cache[$cat_ID];
							$this->subset_root_cats[$this->cache[$cat_ID]->{$this->subset_property}][] = & $GenericCategory; // $this->cache[$cat_ID];
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
		$GenericCategory = & $this->subset_cache[$subset_ID][$cat_ID];
		if( is_null( $GenericCategory->parent_ID ) )
		{	// Found root parent
			$Debuglog->add( $padding.'*OK', 'CategoryCache' );
			return true;
		}
		// This is not the last parent...
		// Record path...
		$path_array[] = $GenericCategory->ID;

		if( in_array( $GenericCategory->parent_ID, $path_array ) )
		{	// We are about to enter an infinite loop
			$Debuglog->add( $padding.'*LOOP! -> assign to root', 'CategoryCache' );
			$GenericCategory->parent_ID = NULL;
			return false;
		}
		elseif( ! isset($this->cache[$GenericCategory->parent_ID] ) )
		{
			$Debuglog->add( $padding.'*Missing parent ID('.$GenericCategory->parent_ID.')! -> assign to root', 'CategoryCache' );
			$GenericCategory->parent_ID = NULL;
			return false;
		}

		// Recurse!
		return $this->check_path_to_root( $subset_ID, $GenericCategory->parent_ID, $path_array );
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
	 * @param array callback funtions (to format the display)
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

		foreach( $Cat_array as $GenericCategory )
		{
			if( in_array( $GenericCategory->ID, $exclude_array ) )
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
			$r .= '<option value="'.$GenericCategory->ID.'" ';
			if( $GenericCategory->ID == $selected ) $r .= ' selected="selected"';
			$r .= ' >'.$indent.$GenericCategory->name.'</option>';

			if( !empty( $GenericCategory->children ) )
			{	// Add children categories:
				$r .= $this->recurse_select( $selected, $subset_ID, false, $GenericCategory->children, $level+1, $exclude_array );
			}
		}

		return $r;
	}

}

?>