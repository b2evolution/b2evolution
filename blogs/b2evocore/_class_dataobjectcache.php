<?php
/**
 * Data Object Cache Class
 * 
 * "data objects by fplanque" :P
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 */

/**
 * Data Object Cache Class
 *
 * @version beta
 */
class DataObjectCache
{
	/**#@+
	 * @access private
	 */
	var	$objtype;
	var	$dbtablename;
	var $dbprefix;
	var $dbIDname;
	var $cache = array();
	var $load_add = false;
	var $all_loaded = false;
	/**#@-*/

	/** 
	 * Constructor
	 *
	 * {@internal DataObjectCache::DataObjectCache(-) }}
	 *
	 * @param string Name of DataObject class we are cacheing
	 * @param boolean true if it's OK to just load all items!
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function DataObjectCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname = 'ID' )
	{
		$this->objtype = $objtype;
		$this->load_all = $load_all;
		$this->dbtablename = $tablename;
		$this->dbprefix = $prefix;
		$this->dbIDname = $dbIDname;
	}	
	

	/** 
	 * Load the cache **extensively**
	 *
	 * {@internal DataObjectCache::load_all(-) }}
	 */
	function load_all()
	{
		global $DB;

		if( $this->all_loaded )
			return	false;	// Already loaded;
		
		debug_log( "Loading <strong>$this->objtype(ALL)</strong> into cache" );
		$sql = "SELECT * FROM $this->dbtablename";
		$rows = $DB->get_results( $sql );
		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		if( count($rows) ) foreach( $rows as $row )
		{
			$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!
			// $obj = $this->cache[ $row->$dbIDname ];
			// $obj->disp( 'name' );
		}
	
		$this->all_loaded = true;

		return true;
	}


	/** 
	 * Load a list of objects into the cache 
	 *
	 * {@internal DataObjectCache::load_list(-) }}
	 *
	 * @param string list of IDs of objects to load
	 */
	function load_list( $req_list )
	{
		global $DB;

		debug_log( "Loading <strong>$this->objtype($req_list)</strong> into cache" );
		$sql = "SELECT * FROM $this->dbtablename WHERE $this->dbIDname IN ($req_list)";
		$rows = $DB->get_results( $sql );
		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		if( count($rows) ) foreach( $rows as $row )
		{
			$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!
			// $obj = $this->cache[ $row->$dbIDname ];
			// $obj->disp( 'name' );
		}
	}
	

	/** 
	 * Add a dataobject to the cache
	 *
	 * {@internal DataObjectCache::add(-) }}
	 */
	function add( & $Obj )
	{
		if( isset($Obj->ID) && $Obj->ID != 0 )
		{
			$this->cache[$Obj->ID] = & $Obj;
			return true;
		}
		return false;
	}

	
	/** 
	 * Clear the cache **extensively**
	 *
	 * {@internal DataObjectCache::clear(-) }}
	 */
	function clear()
	{
		$this->cache = array();
		$this->all_loaded = false;
	}


	/** 
	 * Get an object from cache by ID
	 *
	 * Load the cache if necessary
	 *
	 * {@internal DataObjectCache::get_by_ID(-) }}
	 *
	 * @param integer ID of object to load
	 * @param boolean false if you want to return false on error
	 */
	function get_by_ID( $req_ID, $halt_on_error = true ) 
	{
		global $DB;

		if( !empty( $this->cache[ $req_ID ] ) )
		{	// Already in cache
			// debug_log( "Accessing $this->objtype($req_ID) from cache" );
			return $this->cache[ $req_ID ];
		}
		elseif( !$this->all_loaded )
		{	// Not in cache, but not everything is loaded yet
			if( $this->load_all )
			{	// It's ok to just load everything:
				$this->load_all();
			}
			else
			{ // Load just the requested object:
				debug_log( "Loading <strong>$this->objtype($req_ID)</strong> into cache" );
				$sql = "SELECT * FROM $this->dbtablename WHERE $this->dbIDname = $req_ID";
				$row = $DB->get_row( $sql );
				$dbIDname = $this->dbIDname;
				$objtype = $this->objtype;
				$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!
			}
		}
	
		if( empty( $this->cache[ $req_ID ] ) ) 
		{	// Requested object does not exist
			if( $halt_on_error ) die( "Requested $this->objtype does not exist!" );
			return false;
		}
	
		return $this->cache[ $req_ID ];
	}


	/** 
	 * Display option list with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * {@internal DataObjectCache::get_by_ID(-) }}
	 */
	function option_list( $default = 0 )
	{
		global $cache_Groups;
	
		if( ! $this->all_loaded )
			$this->load_all();
		
		foreach( $this->cache as $loop_Obj )
		{
			echo '<option value="'.$loop_Obj->ID.'"';
			if( $loop_Obj->ID == $default ) echo ' selected="selected"';
			echo '>';
			$loop_Obj->disp( 'name' );
			echo '</option>';
		}
	}
	
}
?>
