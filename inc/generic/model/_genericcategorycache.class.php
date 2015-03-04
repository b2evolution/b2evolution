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
	 */
	function reveal_children( $subset_ID = NULL )
	{
		global $Debuglog;
		global $Timer;

		if( $this->revealed_all_children )
		{	// All children have already been revealed: (can happen even if we require a subset *now*)
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

			$this->revealed_all_children = true;
		}
		else
		{	// We are handling a subset (for example Chapers of a specific blog_ID only):

			if( is_null( $subset_ID ) )
			{	// No specific subset requested, we are going to reveal all subsets in a row:

				// Make sure everything has been loaded:
				$this->load_all();

				echo 'DEBUG - PARTIAL IMPLEMENTATION - REVEALING ALL SUBSETS in a row. Is this needed?';

				foreach( $this->subset_cache as $subset_ID => $dummy )
				{
					$this->reveal_children( $subset_ID );
				}

				$this->revealed_all_children = true;
			}
			else
			{	// We're interested in a specific subset:
				// *** THIS IS THE CASE USED IN B2EVOLUTION ***

				if( !empty( $this->revealed_subsets[$subset_ID] ) )
				{	// Children have already been revealed:
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

					// TODO: One option would be to pre-sort everything here...
					// BUT it's probably better to sort at display time, only if we need to!
					// 
					// In case we wanted to presort here, we would od it approx like this:
					// 1. Get sort order of requested collection (blog_ID) : either alphabetic or specifc order
					// 2. Sort root categories against each other  --   sort( $this->subset_root_cats[$this->cache[$cat_ID]->{$this->subset_property}] )
					// 3. Loop through all categories:
					//   4. If this category has no children, continue to next cat
					//   5. Get desired sort order for this categorie's children: either alphabetic or specific order or inherit from parent
					//     6. If inhereited from parent, ask parent for sort order (which may trigger a recursion)
					//   7. Sort children of this category against each other

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
	 * Return recursive display of loaded categories
	 *
	 * @param array callback funtions (to format the display)
	 * @param integer|NULL NULL for all subsets
	 * @param array categories list to display
	 * @param integer depth of categories list
	 * @param integer Max depth of categories list, 0/empty - is infinite
	 * TODO: add a param to request sorting if we do want sorting (in some cases we don't need to sort, e-g: producing an XML sitemap)
	 * The new param should probably be "$sorted = false" with possible other values being true or the sort order of the parent if we are in a recusion: 'alpha' or 'manual' (this will be used in case of inherit)
	 *
	 * @return string recursive list of all loaded categories
	 */
	function recurse( $callbacks, $subset_ID = NULL, $cat_array = NULL, $level = 0, $max_level = 0 )
	{
		// Make sure children have been (loaded and) revealed for specific subset:
		$this->reveal_children( $subset_ID );

		if( is_null( $cat_array ) )
		{	// Get all parent categories:
			if( is_null( $subset_ID ) )
			{
				$cat_array = $this->root_cats;
			}
			elseif( isset( $this->subset_root_cats[$subset_ID] ) )
			{	// We have root cats for the requested subset:
				$cat_array = $this->subset_root_cats[$subset_ID];
			}
			else
			{
				$cat_array = array();
			}
		}

		$r = '';

		// TODO: if we want to display a $sorted list:
		// 1. Check if this level of categories has been sorted before (don't do it twice)
		// 2. Get sorting order for the current cat:
		//    - If this is a root category, get if from the collection( blog_ID ): alphabetic or manual order
		//    - If this is a child catgegory, get if from the category itself (new property): alphabetic or manual order or inherit from parent
		//       - if inherited form parent, use the value received througg $order param
		// 3. Sort level alphabetically or by order field

		// Go through all categories at this level:
		foreach( $cat_array as $cat )
		{
			// Display a category:
			if( is_array( $callbacks['line'] ) )
			{ // object callback:
				$r .= $callbacks['line'][0]->{$callbacks['line'][1]}( $cat, $level ); // <li> Category  - or - <tr><td>Category</td></tr> ...
			}
			else
			{
				$r .= $callbacks['line']( $cat, $level ); // <li> Category  - or - <tr><td>Category</td></tr> ...
			}

			if( ! empty( $cat->children ) && ( empty( $max_level ) || $max_level > $level + 1 ) )
			{ // Add children categories if they exist and no restriction by level depth:
				$r .= $this->recurse( $callbacks, $subset_ID, $cat->children, $level + 1, $max_level );
				// TODO: add false or sort order of parent to params above
			}
			elseif( is_array( $callbacks['no_children'] ) )
			{ // object callback:
				$r .= $callbacks['no_children'][0]->{$callbacks['no_children'][1]}( $cat, $level ); // </li>
			}
			else
			{
				$r .= $callbacks['no_children']( $cat, $level ); // </li>
			}
		}


		if( !empty( $cat->parent_ID ) && !empty( $callbacks['posts'] ) )
		{	// Callback to display the posts under subchapters
			if( is_array( $callbacks['posts'] ) )
			{	// object callback:
				$r .= $callbacks['posts'][0]->{$callbacks['posts'][1]}( $cat->parent_ID );
			}
			else
			{
				$r .= $callbacks['posts']( $cat->parent_ID );
			}
		}


		if( ! empty( $r ) )
		{ // We have something to display at this level, wrap in in before/after:
			$r_before = '';
			if( is_array( $callbacks['before_level'] ) )
			{ // object callback:
				$r_before .= $callbacks['before_level'][0]->{$callbacks['before_level'][1]}( $level ); // <ul>
			}
			else
			{
				$r_before .= $callbacks['before_level']( $level ); // <ul>
			}

			$r = $r_before.$r;

			if( is_array( $callbacks['after_level'] ) )
			{ // object callback:
				$r .= $callbacks['after_level'][0]->{$callbacks['after_level'][1]}( $level ); // </ul>
			}
			else
			{
				$r .= $callbacks['after_level']( $level ); // </ul>
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