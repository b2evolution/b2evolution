<?php
/**
 * This file implements the ItemCache class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobjectcache.class.php';

/**
 * Item Cache Class
 *
 * @package evocore
 */
class ItemCache extends DataObjectCache
{
	var $urltitle_index = array();

	/**
	 * Constructor
	 *
	 * {@internal ItemCache::ItemCache(-) }}
	 */
	function ItemCache()
	{
		global $tableposts;

		parent::DataObjectCache( 'Item', false, $tableposts, 'post_', 'ID' );
	}

	/**
	 * Get an object from cache by its urltitle
	 *
	 * Load into cache if necessary
	 *
	 * {@internal ItemCache::get_by_urltitle(-) }}
	 *
	 * @param string stub of object to load
	 * @param boolean false if you want to return false on error
	 */
	function get_by_urltitle( $req_urltitle, $halt_on_error = true )
	{
		global $DB;

		if( !isset( $this->urltitle_index[$req_urltitle] ) )
		{ // not yet in cache:
			// Load just the requested object:
			$Debuglog->add( "Loading <strong>$this->objtype($req_urltitle)</strong> into cache" );
			$sql = "SELECT *
							FROM $this->dbtablename
							WHERE post_urltitle = ".$DB->quote($req_urltitle);
			$row = $DB->get_row( $sql );
			if( empty( $row ) )
			{	// Requested object does not exist
				if( $halt_on_error ) die( "Requested $this->objtype does not exist!" );
				// put into index:
				$this->urltitle_index[$req_urltitle] = false;
				return false;
			}

			$dbIDname = $this->dbIDname;
			$objtype = $this->objtype;
			$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!

			// put into index:
			$this->urltitle_index[$req_urltitle] = & $this->cache[ $row->$dbIDname ];
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
	 * {@internal DataObjectCache::load_urltitle_array(-) }}
	 *
	 * @param array of urltitles of Items to load
	 */
	function load_urltitle_array( $req_array )
	{
		global $DB;

		$req_list = "'".implode( "','", $req_array)."'";
		$Debuglog->add( "Loading <strong>$this->objtype($req_list)</strong> into cache" );
		$sql = "SELECT * FROM $this->dbtablename WHERE post_urltitle IN ( $req_list )";
		$rows = $DB->get_results( $sql );
		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		if( count($rows) ) foreach( $rows as $row )
		{
			$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!
			// $obj = $this->cache[ $row->$dbIDname ];
			// $obj->disp( 'name' );

			// put into index:
			$this->urltitle_index[$row->post_urltitle] = & $this->cache[ $row->$dbIDname ];

			$Debuglog->add( "Cached <strong>$this->objtype($row->post_urltitle)</strong>" );
		}

		// Set cache for non found objects:
		foreach( $req_array as $urltitle )
		{
			if( !isset( $this->urltitle_index[$urltitle] ) )
			{ // not yet in cache:
				$this->urltitle_index[$urltitle] = false; // Remember it doesn't exist in DB either
				$Debuglog->add( "Cached <strong>$this->objtype($urltitle)</strong> as NON EXISTENT" );
			}
		}
	}

}

/*
 * $Log$
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.7  2004/10/12 16:12:18  fplanque
 * Edited code documentation.
 *
 */
?>