<?php
/**
 * This file implements the ItemCache class.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobjectcache.class.php';

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
			$Debuglog->add( "Already loaded <strong>$this->objtype(Item #$item_ID)</strong> into cache" );
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

			$Debuglog->add( "Loading <strong>$this->objtype(Item #$item_ID)</strong> into cache" );

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

		$Debuglog->add( "Loading <strong>$this->objtype(Items #$item_list)</strong> into cache" );

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
?>