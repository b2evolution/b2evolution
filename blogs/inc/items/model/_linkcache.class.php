<?php
/**
 * This file implements the LinkCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/model/dataobjects/_dataobjectcache.class.php');

/**
 * LinkCache Class
 *
 * @package evocore
 */
class LinkCache extends DataObjectCache
{
	/**
	 * Cache for item -> array of object references
	 * @access private
	 * @var array
	 */
	var $cache_item = array();

	/**
	 * Constructor
	 */
	function LinkCache()
	{
		parent::DataObjectCache( 'Link', false, 'T_links', 'link_', 'link_ID' );
	}


	/**
	 * Add a dataobject to the cache
	 */
	function add( & $Obj )
	{
		if( isset($Obj->ID) && $Obj->ID != 0 )
		{	// If the object wasn't already cached and is valid:
			$this->cache[$Obj->ID] = & $Obj;
			// Also cache indexed by Item ID:
			$this->cache_item[$Obj->Item->ID][$Obj->ID] = & $Obj;
			return true;
		}
		return false;
	}


	/**
	 * Returns links for a given Item
	 *
	 * Loads if necessary
	 *
	 * @todo dh> does not get used anywhere (yet)?
	 *
	 * @param integer item ID to load links for
	 * @return array of refs to Link objects
	 */
	function & get_by_item_ID( $item_ID )
	{
		// Make sure links are loaded:
		$this->load_by_item_ID( $item_ID );

		return $this->cache_item[$item_ID];
	}


	/**
	 * Load links for a given Item
	 *
	 * Optimization: If the Item happens to be in the current MainList, Links for the whole MainList will be cached.
	 *
	 * @todo cache Link targets before letting the Link constructor handle it
	 *
	 * @param integer item ID to load links for
	 */
	function load_by_item_ID( $item_ID )
	{
		global $DB, $Debuglog, $MainList;

		if( isset( $this->cache_item[$item_ID] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(Item #$item_ID)</strong> into cache", 'dataobjects' );
			return false;
		}

		// Check if this Item is part of the MainList
		if( isset( $MainList ) && in_array( $item_ID, $MainList->postIDarray ) )
		{ // YES! We found the current Item in the MainList, let's load/cache the links for the WholeMainList
			$Debuglog->add( "Loading <strong>$this->objtype(Item #$item_ID)</strong> into cache as part of MainList...");
			$this->load_by_item_list( $MainList->postIDarray );
		}
		else
		{	// NO, load Links for this single Item:

			// Remember this special load:
			$this->cache_item[$item_ID] = array();

			$Debuglog->add( "Loading <strong>$this->objtype(Item #$item_ID)</strong> into cache", 'dataobjects' );

			$sql = 'SELECT *
								FROM T_links
							 WHERE link_itm_ID = '.$item_ID.'
							 ORDER BY link_ltype_ID, link_dest_itm_ID, link_file_ID';
			foreach( $DB->get_results( $sql ) as $row )
			{	// Cache each matching object:
				$this->add( new Link( $row ) );
			}
		}

		return true;
	}


	/**
	 * Load links for a given Item list
	 *
	 * @todo cache Link targets before letting the Link constructor handle it
	 *
	 * @param array of of item IDs to load links for
	 */
	function load_by_item_list( $itemIDarray )
	{
		global $DB, $Debuglog;

		$item_list = implode( ',', $itemIDarray );

		$Debuglog->add( "Loading <strong>$this->objtype(Items #$item_list)</strong> into cache", 'dataobjects' );

		// For each item in list...
		foreach( $itemIDarray as $item_ID )
		{ // Remember this special load:
			$this->cache_item[$item_ID] = array();
		}

		foreach( $DB->get_results( 'SELECT *
																	FROM T_links
																 WHERE link_itm_ID IN ('.$item_list.')' ) as $row )
		{	// Cache each matching object:
			$this->add( new Link( $row ) );
		}

		return true;
	}

}

/*
 * $Log$
 * Revision 1.4  2009/02/27 19:57:17  blueyed
 * TODO
 *
 * Revision 1.3  2008/09/27 07:54:34  fplanque
 * minor
 *
 * Revision 1.2  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:29  fplanque
 * MODULES (refactored MVC)
 *
 */
?>