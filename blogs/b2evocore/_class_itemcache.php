<?php
/**
 * Item Cache Class
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_class_dataobjectcache.php';

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
			debug_log( "Loading <strong>$this->objtype($req_urltitle)</strong> into cache" );
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
			debug_log( "Retrieving <strong>$this->objtype($req_urltitle)</strong> from cache" );
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
		debug_log( "Loading <strong>$this->objtype($req_list)</strong> into cache" );
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

			debug_log( "Cached <strong>$this->objtype($row->post_urltitle)</strong>" );
		}
		
		// Set cache for non found objects:
		foreach( $req_array as $urltitle )
		{
			if( !isset( $this->urltitle_index[$urltitle] ) )
			{ // not yet in cache:
				$this->urltitle_index[$urltitle] = false; // Remember it doesn't exist in DB either
				debug_log( "Cached <strong>$this->objtype($urltitle)</strong> as NON EXISTENT" );
			}
		}
	}

}
?>
