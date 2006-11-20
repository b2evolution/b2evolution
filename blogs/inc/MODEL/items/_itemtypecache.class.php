<?php
/**
 * This file implements the item type cache class.
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobjectcache.class.php';

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
		parent::DataObjectCache( 'ItemType', true, 'T_itemtypes', 'ptyp_', 'ptyp_ID', 'ptyp_name', 'ptyp_ID' );
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
																 FROM T_itemtypes
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

}

/*
 * $Log$
 * Revision 1.11  2006/11/20 19:51:28  blueyed
 * doc: package gsbcore => evocore
 *
 * Revision 1.10  2006/09/11 22:29:19  fplanque
 * chapter cleanup
 *
 * Revision 1.9  2006/09/11 22:06:08  blueyed
 * Cleaned up option_list callback handling
 *
 * Revision 1.8  2006/08/09 21:30:56  fplanque
 * doc
 *
 */
?>