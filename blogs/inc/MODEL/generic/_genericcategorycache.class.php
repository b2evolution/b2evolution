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
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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


/**
 * GenericCategoryCache class
 *
 *
 * @package evocore
 */
class GenericCategoryCache extends GenericCache
{
	/**
	 * Which suibsets have been loaded
	 */
	var $loaded_subsets = array();

	/**
	 * List of category objects loaded
	 */
	var $cats = array();

	/**
	 * These are the level 0 categories (which have no parent)
	 */
	var $parent_cats = array();

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
	function GenericCategoryCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname = 'ID', $name_field = NULL ) 		
	{
		parent::GenericCache( $objtype, $load_all, $tablename, $prefix, $dbIDname, $name_field );
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
		if( is_null( $subset_ID ) )
		{	// No specific subset
			if( $this->revealed_all_children )
			{	// Children have already been revealed:
				return;
				/* RETURN */
			}

			// Make sure everything has been loaded:
    	$this->load_all();

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
		}


		// Reveal children:
		if( !empty( $this->cache ) )
		{	// There are loaded categories, so loop on all loaded categories to set their children list if it has:
			foreach( $this->cache as $cat_ID => $GenericCategory )
			{
				if( ! is_null( $GenericCategory->parent_ID ) )
				{	// This category has a parent, so add it to its parent children list:

					$this->cache[$GenericCategory->parent_ID]->add_children( $this->cache[$cat_ID] );
				}		
				else 
				{	// This category has no parent, so add it to the parent categories list  
					$this->parent_cats[] = & $this->cache[$cat_ID];
				}	
			}	
		}

		// Children have been revealed.
		if( is_null( $subset_ID ) )
		{	// No specific subset
			$this->revealed_children = true;
		}
		else
		{	// We're interested in a specific subset
			$this->revealed_subsets[$subset_ID] = true;
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
			$cat_array = $this->parent_cats;
		}
	
		$r = '';
		
		$r .= $callbacks['before_level']( $level ); // <ul>

		foreach ($cat_array as $cat )
		{ 
			$r .= $callbacks['line']( $cat, $level ); // <li> Category  - or - <tr><td>Category</td></tr> ... 
			
			if( !empty( $cat->children ) )
			{	// Add children categories:
				$r .= $this->recurse( $callbacks, $subset_ID, $cat->children, $level+1 );
			}
			else 
			{
				$r .=$callbacks['no_children']( $cat, $level ); // </li>
			}
			
		}

		$r .= $callbacks['after_level']( $level ); // </ul>

		return $r;
	}
	
	
	/**
	 * Return recursive select options list of all loaded categories
	 *
	 * @param integer selected category in the select input
	 * @param integer|NULL NULL for all subsets
	 * @param array categories list to display
	 * @param int depth of  categories list
	 *
	 * @return string select options list of all loaded categories
	 */
	function recurse_select( $selected = NULL, $subset_ID = NULL, $cat_array = NULL, $level = 0 )
	{
		// Make sure children have been revealed for specific subset:
		$this->reveal_children( $subset_ID );

		if( is_null( $cat_array ) )
		{	// Get all parent categorie:
			$cat_array = $this->parent_cats;
		}
	
		$r ='';
		
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
				$r .= $this->recurse_select( $selected, $subset_ID, $cat->children, $level+1 );
			}
		}

		return $r;
	}
	
	
}
?>