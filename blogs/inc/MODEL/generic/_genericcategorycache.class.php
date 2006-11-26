<?php
/**
 * This file implements the GenericCategoryCache class.
 *
 * This is the object handling genreric category lists.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( 'MODEL/generic/_genericcache.class.php' );


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
	function GenericCategoryCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname = 'ID', $name_field = NULL, $subset_property = NULL )
	{
		parent::GenericCache( $objtype, $load_all, $tablename, $prefix, $dbIDname, $name_field );

		$this->subset_property = $subset_property;
	}


	/**
	 * Add a dataobject to the cache
	 */
	function add( & $Obj )
	{
		global $Debuglog;

		if( parent::add( $Obj ) )
		{	// Successfuly added

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
	 * After this each Category will have an array pointing to its direct children
	 *
	 * @param integer|NULL NULL for all subsets
	 */
	function reveal_children( $subset_ID = NULL )
	{
		if( $this->revealed_all_children )
		{	// ALL Children have already been revealed: (can happen even if we require a subset *now*)
			return;
			/* RETURN */
		}

		if( empty($this->subset_property) )
		{	// We are not handling subsets

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
						$this->cache[$GenericCategory->parent_ID]->add_children( $this->cache[$cat_ID] );
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
		{	// We are handling subsets

			if( is_null( $subset_ID ) )
			{	// No specific subset requested, we are going to reveal all subsets

				// Make sure everything has been loaded:
    		$this->load_all();

    		echo 'REVEALING ALL SUBSETS in a row. Is this needed?';

				foreach( $this->subset_cache as $subset_ID => $dummy )
				{
					$this->reveal_children( $subset_ID );
				}

				$this->revealed_all_children = true;
			}
			else
			{	// We're interested in a specific subset
				if( !empty( $this->revealed_subsets[$subset_ID] ) )
				{	// Children have already been revealed:
					return;
					/* RETURN */
				}

				// Make sure the requested subset has been loaded:
    		$this->load_subset($subset_ID);


				// Reveal children:
				if( !empty( $this->subset_cache[$subset_ID] ) )
				{	// There are loaded categories, so loop on all loaded categories to set their children list if it has:
					foreach( $this->subset_cache[$subset_ID] as $cat_ID => $GenericCategory )
					{
						// echo $GenericCategory->name;
						if( ! is_null( $GenericCategory->parent_ID ) )
						{	// This category has a parent, so add it to its parent children list:
							$this->cache[$GenericCategory->parent_ID]->add_children( $this->cache[$cat_ID] );
						}
						else
						{	// This category has no parent, so add it to the parent categories list
							$this->root_cats[] = & $this->cache[$cat_ID];
							$this->subset_root_cats[$this->cache[$cat_ID]->{$this->subset_property}][] = & $this->cache[$cat_ID];
						}
					}
				}

				// Children have been revealed.
				$this->revealed_subsets[$subset_ID] = true;
			}
		}
	}


	/**
	 * Return recursive display of loaded categories
	 *
	 * @param array callback funtions (to format the display)
	 * @param integer|NULL NULL for all subsets
	 * @param array categories list to display
	 * @param int depth of  categories list
	 *
	 * @return string recursive list of all loaded categories
	 */
	function recurse( $callbacks, $subset_ID = NULL, $cat_array = NULL, $level = 0 )
	{
		// Make sure children have been revealed for specific subset:
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

		if( is_array( $callbacks['before_level'] ) )
		{ // object callback:
			$r .= $callbacks['before_level'][0]->{$callbacks['before_level'][1]}( $level ); // <ul>
		}
		else
		{
			$r .= $callbacks['before_level']( $level ); // <ul>
		}

		foreach( $cat_array as $cat )
		{
			if( is_array( $callbacks['line'] ) )
			{ // object callback:
				$r .= $callbacks['line'][0]->{$callbacks['line'][1]}( $cat, $level ); // <li> Category  - or - <tr><td>Category</td></tr> ...
			}
			else
			{
				$r .= $callbacks['line']( $cat, $level ); // <li> Category  - or - <tr><td>Category</td></tr> ...
			}

			if( !empty( $cat->children ) )
			{	// Add children categories:
				$r .= $this->recurse( $callbacks, $subset_ID, $cat->children, $level+1 );
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

		if( is_array( $callbacks['after_level'] ) )
		{ // object callback:
			$r .= $callbacks['after_level'][0]->{$callbacks['after_level'][1]}( $level ); // </ul>
		}
		else
		{
			$r .= $callbacks['after_level']( $level ); // </ul>
		}

		return $r;
	}


	/**
	 * Return recursive select options list of all loaded categories
	 *
	 * @param integer selected category in the select input
	 * @param integer|NULL NULL for all subsets
	 * @param boolean
	 * @param array categories list to display
	 * @param int depth of  categories list
	 *
	 * @return string select options list of all loaded categories
	 */
	function recurse_select( $selected = NULL, $subset_ID = NULL, $include_root = false, $cat_array = NULL, $level = 0 )
	{
		// Make sure children have been revealed for specific subset:
		$this->reveal_children( $subset_ID );

		if( is_null( $cat_array ) )
		{	// Get all parent categorie:
			$cat_array = $this->root_cats;
		}

		$r = '';

		if( $include_root )
		{
			$r .= '<option value="">'.T_('Root').'</option>';
			$level++;
		}

		foreach ($cat_array as $cat )
		{
			// Set category indentation in the select:
			$indent = '';
			for($i = 0; $i < $level; $i++)
			{
				$indent .='&nbsp;&nbsp;';
			}
			// Set category option:
			$r .= '<option value="'.$cat->ID.'" ';
			if( $cat->ID == $selected ) $r .= ' selected="selected"';
			$r .= ' >'.$indent.$cat->name.'</option>';

			if( !empty( $cat->children ) )
			{	// Add children categories:
				$r .= $this->recurse_select( $selected, $subset_ID, false, $cat->children, $level+1 );
			}
		}

		return $r;
	}


}

/*
 * $Log$
 * Revision 1.11  2006/11/26 01:42:09  fplanque
 * doc
 *
 */
?>