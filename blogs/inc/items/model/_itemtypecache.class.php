<?php
/**
 * This file implements the item type cache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/model/dataobjects/_dataobjectcache.class.php');

/**
 * ItemTypeCache Class
 *
 * @package evocore
 */
class ItemTypeCache extends DataObjectCache
{
	/**
	 * Item type cache for each collection
	 */
	var $col_cache = array();

	/**
	 * Default item type for each collection
	 */
	var $col_default = array();


	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function ItemTypeCache()
	{
		// Call parent constructor:
		parent::DataObjectCache( 'ItemType', true, 'T_items__type', 'ptyp_', 'ptyp_ID', 'ptyp_name', 'ptyp_ID' );
	}


	/**
	 * Load a list of item types for a given collection and store them into the collection cache
	 *
	 * Note: object will also get stored into the global cache.
	 */
	function load_col( $col_ID )
	{
		global $DB;

		$rows = $DB->get_results( 'SELECT *
																 FROM T_items__type
													 INNER JOIN T_ityp_col ON ityp_ID = itco_ityp_ID
																WHERE itco_col_ID = '.$col_ID.'
																ORDER BY ityp_name' );

		foreach( $rows as $row )
		{
			// Instantiate the item type to the global cache and add it to the collections cache
			$this->col_cache[$col_ID][$row->ityp_ID] = & $this->instantiate( $row );

			if( $row->itco_coldefault <> 0 )
			{	// Item type is selected by default, so update the default item types collection
				$this->col_default[$col_ID] = $row->ityp_ID;
			}
		}
	}


	/**
	 * Return the default item type ID for a given collection
	 *
	 * fp> will be used in b2evo 2.0
	 *
	 * @param integer collection ID
	 */
	function get_col_default_type_ID( $col_ID )
	{
		if( !isset( $this->col_default[$col_ID] ) )
		{	// Collection is not in cache yet:
			$this->load_col( $col_ID );
		}

		return $this->col_default[$col_ID];
	}


	/**
	 * Returns form option list with cache contents restricted to a collection
	 *
	 * Load the item types collection cache if necessary
	 *
	 * fp> will be used in b2evo 2.0
	 *
	 * @param integer selected ID
	 * @param integer collection ID
	 * @return string
	 */
	function get_option_list_by_col_ID( $default, $col_ID )
	{
		if( !isset( $this->col_cache[$col_ID] ) )
		{ // Collection cache for this collection ID is not set yet, so we load all item types in collection cache for this collection
			$this->load_col( $col_ID );
		}

		// TODO: move this away
		if( empty( $default ) )
		{	// No default param, so we set it to the collection item type by default if exist else to 0
			$default = isset( $this->col_default[$col_ID] ) ? $this->col_default[$col_ID] : 0 ;
		}

		$r = '';

		// Loop on all item types from the collection cache
		foreach( $this->col_cache[ $col_ID ] as $loop_Obj )
		{
			$r .=  '<option value="'.$loop_Obj->ID.'"';
			if( $loop_Obj->ID == $default ) $r .= ' selected="selected"';
			$r .= '>';
			$r .= format_to_output( $loop_Obj->name, 'htmlbody' );
			$r .=  '</option>'."\n";
		}

		return $r;
	}

	/**
	 * Returns a form option list which only contains unreserved post types.
	 *
	 * @see $posttypes_reserved_IDs
	 *
	 * @param integer The selected ID.
	 * @param boolean Provide a choice for "none" with ID ''
	 * @param string  Callback method name.
	 * @return string
	 */
	function get_option_list_unreserved_only( $default = 0, $allow_none = false, $method = 'get_name' )
	{
		global $posttypes_reserved_IDs;

		return $this->get_option_list( $default, $allow_none, $method, $posttypes_reserved_IDs );
	}

	/**
	 * For use by Universal Item List widget
	 */
	function get_option_array()
	{
		global $posttypes_reserved_IDs;

		return parent::get_option_array( 'get_name', $posttypes_reserved_IDs );
	}
}

/*
 * $Log$
 * Revision 1.5  2009/03/15 20:35:18  fplanque
 * Universal Item List proof of concept
 *
 * Revision 1.4  2009/03/08 23:57:44  fplanque
 * 2009
 *
 * Revision 1.3  2009/01/23 22:08:12  tblue246
 * - Filter reserved post types from dropdown box on the post form (expert tab).
 * - Indent/doc fixes
 * - Do not check whether a post title is required when only e. g. switching tabs.
 *
 * Revision 1.2  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:28  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.14  2007/05/14 02:43:05  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.13  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.12  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.11  2006/11/20 19:51:28  blueyed
 * doc: package gsbcore => evocore
 */
?>