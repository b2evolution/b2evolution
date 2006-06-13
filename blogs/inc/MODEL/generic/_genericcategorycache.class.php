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
	// List of categories object loaded
	var $cats = array();
	
	var $parent_cats = array();
	
	// TO DO
	var $revealed_children = false;

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
		if( $this->revealed_children )
		{	// Children have already been revealed:
			return;
			/* RETURN */
		}
		
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
		$this->revealed_children = true;
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
		if( !$this->revealed_children )
		{
			$this->Reveal_children();
		}
		
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
		if( !$this->revealed_children )
		{
			$this->Reveal_children();
		}
	
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
	
	
	/**
	 * Returns form option list with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID ''
	 */
	function parent_option_list_return( $default = 0, $allow_none = false, $method = 'name_return' )
	{
		if( (! $this->all_loaded) && $this->load_all )
		{ // We have not loaded all items so far, but we're allowed to... so let's go:
			$this->load_all();
		}
		
		if( !$this->revealed_children )
		{
			$this->Reveal_children();
		}

		$r = '';

		if( $allow_none )
		{
			$r .= '<option value=""';
			if( empty($default) ) $r .= ' selected="selected"';
			$r .= '>'.T_('None').'</option>'."\n";
		}

		foreach( $this->parent_cats as $loop_Obj )
		{
			$r .=  '<option value="'.$loop_Obj->ID.'"';
			if( $loop_Obj->ID == $default ) $r .= ' selected="selected"';
			$r .= '>';
			$r .= $loop_Obj->$method();
			$r .=  '</option>'."\n";
		}

		return $r;
	}
	
}
?>