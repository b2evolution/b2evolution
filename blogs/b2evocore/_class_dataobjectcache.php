<?php
/**
 * Data Object Cache Class
 * 
 * "data objects by fplanque" :P
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
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
	var $all_loaded = false;
	/**#@-*/

	/** 
	 * Constructor
	 *
	 * {@internal DataObjectCache::DataObjectCache(-) }}
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function DataObjectCache( $objtype, $tablename, $prefix = '', $dbIDname = 'ID' )
	{
		$this->objtype = $objtype;
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
		global $querycount;

		if( !empty( $this->cache ) )
			return	false;	// Already loaded;
		
		$sql = "SELECT * FROM $this->dbtablename";
		$result = mysql_query($sql) or mysql_oops( $sql );
		$querycount++;
		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		while( $row = mysql_fetch_object($result) )
		{
			$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!
			// $obj = $this->cache[ $row->$dbIDname ];
			// $obj->disp( 'name' );
		}
	
		$this->all_loaded = true;

		return true;
	}
	

	/** 
	 * Add a dataobject to the cache
	 *
	 * {@internal DataObjectCache::add(-) }}
	 */
	function add( $Obj )
	{
		if( isset($Obj->ID) && $Obj->ID != 0 )
		{
			$this->cache[$Obj->ID] = $Obj;
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
	 */
	function get_by_ID( $req_ID ) 
	{

		if( empty( $this->cache ) )
			$this->load_all();
	
		if( empty( $this->cache[ $req_ID ] ) ) 
			die( "Requested $this->objtype does not exist!" );
	
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
