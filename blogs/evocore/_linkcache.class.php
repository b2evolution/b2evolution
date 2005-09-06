<?php
/**
 * This file implements the ItemCache class.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobjectcache.class.php';

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
	 *
	 * {@internal LinkCache::LinkCache(-) }}
	 */
	function LinkCache()
	{
		parent::DataObjectCache( 'Link', false, 'T_links', 'link_', 'link_ID' );
	}


	/**
	 * Add a dataobject to the cache
	 *
	 * {@internal LinkCache::add(-) }}
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
	 * {@internal LinkCache::get_by_item_ID(-) }}
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
	 * {@internal LinkCache::load_blogmembers(-) }}
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

			foreach( $DB->get_results( 'SELECT *
																		FROM T_links
																	 WHERE link_item_ID = '.$item_ID ) as $row )
			{	// Cache each matching object:
				$this->add( new Link( $row ) );
			}
		}

		return true;
	}


  /**
	 * Load links for a given Item list
	 *
	 * {@internal LinkCache::load_by_item_list(-) }}
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
																 WHERE link_item_ID IN ('.$item_list.')' ) as $row )
		{	// Cache each matching object:
			$this->add( new Link( $row ) );
		}

		return true;
	}


}
?>