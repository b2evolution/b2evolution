<?php
/**
 * This file implements the ItemCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _itemcache.class.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

load_class( 'items/model/_item.class.php', 'Item' );

/**
 * Item Cache Class
 *
 * @package evocore
 */
class ItemCache extends DataObjectCache
{
	/**
	 * Lazy filled index of url titles
	 */
	var $urltitle_index = array();

	/**
	 * Constructor
	 *
	 * @param string object type of elements in Cache
	 * @param string Name of the DB table
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function ItemCache( $objType = 'Item', $dbtablename = 'T_items__item', $dbprefix = 'post_', $dbIDname = 'post_ID' )
	{
		parent::DataObjectCache( $objType, false, $dbtablename, $dbprefix, $dbIDname );
	}


	/**
	 * Get an object from cache by its urltitle
	 *
	 * Load into cache if necessary
	 *
	 * @param string stub of object to load
	 * @param boolean false if you want to return false on error
	 * @param boolean true if function should die on empty/null
	 */
	function & get_by_urltitle( $req_urltitle, $halt_on_error = true, $halt_on_empty = true )
	{
		global $DB, $Debuglog;

		if( !isset( $this->urltitle_index[$req_urltitle] ) )
		{ // not yet in cache:
	    // Get from SlugCache
			$SlugCache = & get_SlugCache();
			$req_Slug =  $SlugCache->get_by_name( $req_urltitle, $halt_on_error, $halt_on_empty );

			if( $req_Slug && $req_Slug->get( 'type' ) == 'item' )
			{	// It is in SlugCache
				$itm_ID = $req_Slug->get( 'itm_ID' );
				if( $Item = $this->get_by_ID( $itm_ID, $halt_on_error, $halt_on_empty ) )
				{
					$this->urltitle_index[$req_urltitle] = $Item;
				}
				else
				{	// Item does not exist
					if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );
					$this->urltitle_index[$req_urltitle] = false;
				}
			}
			else
			{	// not in the slugCache
				if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );
				$this->urltitle_index[$req_urltitle] = false;
			}
		}
		else
		{
			$Debuglog->add( "Retrieving <strong>$this->objtype($req_urltitle)</strong> from cache" );
		}

		return $this->urltitle_index[$req_urltitle];
	}


	/**
	 * Load a list of item referenced by their urltitle into the cache
	 *
	 * @param array of urltitles of Items to load
	 */
	function load_urltitle_array( $req_array )
	{
		global $DB, $Debuglog;

		$req_list = "'".implode( "','", $req_array)."'";
		$Debuglog->add( "Loading <strong>$this->objtype($req_list)</strong> into cache", 'dataobjects' );
		$sql = "SELECT * FROM $this->dbtablename WHERE post_urltitle IN ( $req_list )";
		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		foreach( $DB->get_results( $sql ) as $row )
		{
			$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!
			// $obj = $this->cache[ $row->$dbIDname ];
			// $obj->disp( 'name' );

			// put into index:
			$this->urltitle_index[$row->post_urltitle] = & $this->cache[ $row->$dbIDname ];

			$Debuglog->add( "Cached <strong>$this->objtype($row->post_urltitle)</strong>" );
		}

		// Set cache from Slug table:
		foreach( $req_array as $urltitle )
		{
			if( !isset( $this->urltitle_index[$urltitle] ) )
			{ // not yet in cache:
				$SlugCache = & get_SlugCache();
				if( $req_Slug = $SlugCache->get_by_name( $urltitle, false, false ) )
				{
					if( $req_Slug->get( 'type' ) == 'item' )
					{	// Is item slug
						if( $Item = $this->get_by_ID( $req_Slug->get( 'itm_ID' ), false ) )
						{	// Set cache 
							$this->urltitle_index[$urltitle] = $Item;
							$Debuglog->add( "Cached <strong>$this->objtype($urltitle)</strong>" );
							continue;
						}
					}
				}
				// Set cache for non found objects:
				$this->urltitle_index[$urltitle] = false; // Remember it doesn't exist in DB either
				$Debuglog->add( "Cached <strong>$this->objtype($urltitle)</strong> as NON EXISTENT" );
			}
		}
	}

}

?>