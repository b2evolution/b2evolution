<?php
/**
 * This file implements the LinkCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

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
	 * @access private
	 * @var array Remember full loads
	 */
	var $loaded_cache_item = array();

	/**
	 * Cache for item -> array of object references
	 * @access private
	 * @var array
	 */
	var $cache_comment = array();

	/**
	 * @access private
	 * @var array Remember full loads
	 */
	var $loaded_cache_comment = array();

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
			if( $Obj->Item != NULL )
			{ // Also cache indexed by Item ID:
				$this->cache_item[$Obj->Item->ID][$Obj->ID] = & $Obj;
			}
			elseif( $Obj->Comment != NULL )
			{ // Also cache indexed by Comment ID:
				$this->cache_comment[$Obj->Comment->ID][$Obj->ID] = & $Obj;
			}
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
	 * Returns links for a given Comment
	 *
	 * Loads if necessary
	 *
	 * @param integer comment ID to load links for
	 * @return array of refs to Link objects
	 */
	function & get_by_comment_ID( $comment_ID )
	{
		// Make sure links are loaded:
		$this->load_by_comment_ID( $comment_ID );

		return $this->cache_comment[$comment_ID];
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
		global $DB, $Debuglog, $MainList, $ItemList;

		if( isset( $this->loaded_cache_item[$item_ID] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(Item #$item_ID)</strong> into cache", 'dataobjects' );
			return false;
		}
		// Check if this Item is part of the MainList
		if( $MainList || $ItemList )
		{
			$prefetch_IDs = array();
			if( $MainList )
			{
				$prefetch_IDs = array_merge($prefetch_IDs, $MainList->get_page_ID_array());
			}
			if( $ItemList )
			{
				$prefetch_IDs = array_merge($prefetch_IDs, $ItemList->get_page_ID_array());
			}
			$Debuglog->add( "Loading <strong>$this->objtype(Item #$item_ID)</strong> into cache as part of MainList/ItemList...");
			$this->load_by_item_list( $prefetch_IDs );
		}
		else
		{	// NO, load Links for this single Item:

			// Remember this special load:
			$this->cache_item[$item_ID] = array();
			$this->loaded_cache_item[$item_ID] = true;

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
	 * Load links for a given Comment
	 *
	 * @param integer comment ID to load links for
	 */
	function load_by_comment_ID( $comment_ID )
	{
		global $DB, $Debuglog;

		if( isset( $this->loaded_cache_comment[$comment_ID] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(Comment #$comment_ID)</strong> into cache", 'dataobjects' );
			return false;
		}

		// Remember this special load:
		$this->cache_comment[$comment_ID] = array();
		$this->loaded_cache_comment[$comment_ID] = true;

		$Debuglog->add( "Loading <strong>$this->objtype(Comment #$comment_ID)</strong> into cache", 'dataobjects' );

		$sql = 'SELECT *
							FROM T_links
						 WHERE link_cmt_ID = '.$comment_ID.'
						 ORDER BY link_file_ID';
		foreach( $DB->get_results( $sql ) as $row )
		{	// Cache each matching object:
			$this->add( new Link( $row ) );
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
			$this->loaded_cache_item[$item_ID] = true;
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
 * Revision 1.11  2011/09/04 22:13:17  fplanque
 * copyright 2011
 *
 * Revision 1.10  2011/03/03 12:47:29  efy-asimo
 * comments attachments
 *
 * Revision 1.9  2010/02/08 17:53:16  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.8  2009/10/11 02:37:03  blueyed
 * Nasty bugfix for LinkCache: if an item got added, the list for the corresponding item would not get loaded anymore. Please verify and backport.
 *
 * Revision 1.7  2009/09/14 13:17:28  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.6  2009/03/08 23:57:44  fplanque
 * 2009
 *
 * Revision 1.5  2009/02/27 19:59:26  blueyed
 * Implement cache prefetching in LinkCache::load_by_item_ID - although it does not get used currently. Untested.
 *
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