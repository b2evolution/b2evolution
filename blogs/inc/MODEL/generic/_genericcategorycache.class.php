<?php
/**
 * This file implements the GenericCategoryCache class.
 *
 * This is the object handling genreric category lists.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author mbruneau: MArc BRUNEAU.
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
	// List of categories object loaded
	var $cats = array();
	
	var $parent_cats = array();

	/**
	 * Constructor

	 */
	function GenericCategoryCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname = 'ID', $name_field = NULL ) 		
	{
		parent::GenericCache( $objtype, $load_all, $tablename, $prefix, $dbIDname, $name_field );
	}	

		
	/**
	 * Reveal_children
	 */
	function Reveal_children()
	{	
		if( !$this->all_loaded )
		{
			$this->load_all();
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
	}
	
	
	/**
	 * Return recursive display of loaded categories
	 * 
	 * @param array callback funtions (to format the display)
	 * @param array categories list to display
	 * @param int depth of  categories list
	 * 
	 * @return string recursive list of all loaded categories 
	 */
	function recurse( $callbacks, $cat_array = NULL, $level = 0 )
	{
		if( is_null( $cat_array ) )
		{	// Get all parent categories:
			$cat_array = $this->parent_cats;
		}
	
		$r ='';
		
		$r .= $callbacks['before_level']( $level ); // <ul>

		foreach ($cat_array as $cat )
		{ 
			$r .= $callbacks['line']( $cat, $level ); // <li> Category  - or - <tr><td>Category</td></tr> ... 
			
			if( !empty( $cat->children ) )
			{	// Add children categories:
				$r .= $this->recurse( $callbacks, $cat->children, $level+1 ); 
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
	 * @param array categories list to display
	 * @param int depth of  categories list
	 * 
	 * @return string select options list of all loaded categories
	 */
	function recurse_select( $selected = NULL, $cat_array = NULL, $level = 0 )
	{
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
				$r .= $this->recurse_select( $selected, $cat->children, $level+1 );
			}
		}

		return $r;
	}
	
}
?>