<?php
/**
 * This file implements the ItemCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id$
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
				if( $Item = $this->get_by_ID( $req_Slug->get( 'itm_ID' ), $halt_on_error ) !== false )
				{
					$Item = $this->get_by_ID( $req_Slug->get( 'itm_ID' ), $halt_on_error );
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
				$req_Slug =  $SlugCache->get_by_name( $urltitle, false, false );
				if( $req_Slug->get( 'type' ) == 'item' )
				{	// Is item slug
					if( $Item = $this->get_by_ID( $req_Slug->get( 'itm_ID' ), false ) )
					{	// Set cahce 
						$this->urltitle_index[$urltitle] = $Item;
						$Debuglog->add( "Cached <strong>$this->objtype($urltitle)</strong>" );
						continue;
					}
				}
				// Set cache for non found objects:
				$this->urltitle_index[$urltitle] = false; // Remember it doesn't exist in DB either
				$Debuglog->add( "Cached <strong>$this->objtype($urltitle)</strong> as NON EXISTENT" );
			}
		}
	}

}

/*
 * $Log$
 * Revision 1.11  2011/09/04 22:13:17  fplanque
 * copyright 2011
 *
 * Revision 1.10  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.9  2010/04/22 18:59:09  blueyed
 * Add halt_on_empty param to get_by_urltitle and use it. Bug: did when looking up single char bad URLs.
 *
 * Revision 1.8  2010/03/29 12:25:31  efy-asimo
 * allow multiple slugs per post
 *
 * Revision 1.7  2010/02/08 17:53:14  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.6  2009/09/14 18:37:07  fplanque
 * doc/cleanup/minor
 *
 * Revision 1.5  2009/09/14 13:17:28  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.4  2009/03/08 23:57:44  fplanque
 * 2009
 *
 * Revision 1.3  2008/09/27 07:54:33  fplanque
 * minor
 *
 * Revision 1.2  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:25  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.10  2007/05/14 02:43:05  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.9  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.8  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>
