<?php
/**
 * This file implements the DataObjectCache class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Data Object Cache Class
 *
 * @package evocore
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
		global $DB, $Debuglog;

		if( $this->all_loaded )
			return	false;	// Already loaded;

		$Debuglog->add( "Loading <strong>$this->objtype(ALL)</strong> into cache" );
		$sql = "SELECT * FROM $this->dbtablename";
		$rows = $DB->get_results( $sql );
		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		if( count($rows) ) foreach( $rows as $row )
		{
			if( $objtype == 'Element' )
			{ // Instanciate a dataobject with its params:
				$this->cache[ $row->$dbIDname ] = new Element( $this->dbtablename, $this->dbprefix, $this->dbIDname, $row ); // COPY!
			}
			else
			{	// Instantiate a custom object
				$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!
			}
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
		global $DB, $Debuglog;

		$Debuglog->add( "Loading <strong>$this->objtype($req_list)</strong> into cache" );

		if( empty( $req_list ) )
		{
			return false;
		}

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
	 * @return reference on cached object
	 */
	function & get_by_ID( $req_ID, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		if( !empty( $this->cache[ $req_ID ] ) )
		{	// Already in cache
			// $Debuglog->add( "Accessing $this->objtype($req_ID) from cache" );
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
				$Debuglog->add( "Loading <strong>$this->objtype($req_ID)</strong> into cache" );
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
	 * Display form option list with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * {@internal DataObjectCache::get_by_ID(-) }}
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID 0
	 */
	function option_list( $default = 0, $allow_none = false, $method ='name' )
	{
		if( (! $this->all_loaded) && $this->load_all )
		{	// We have not loaded all items so far, but we're allowed to... so let's go:
			$this->load_all();
		}

		if( $allow_none )
		{
			echo '<option value="0"';
			if( 0 == $default ) echo ' selected="selected"';
			echo '>', T_('None') ,'</option>'."\n";
		}

		foreach( $this->cache as $loop_Obj )
		{
			echo '<option value="'.$loop_Obj->ID.'"';
			if( $loop_Obj->ID == $default ) echo ' selected="selected"';
			echo '>';
			$loop_Obj->$method();
			echo '</option>'."\n";
		}
	}
}

/*
 * $Log$
 * Revision 1.7  2004/12/27 18:37:58  fplanque
 * changed class inheritence
 *
 * Revision 1.5  2004/12/21 21:18:38  fplanque
 * Finished handling of assigning posts/items to users
 *
 * Revision 1.4  2004/12/17 20:38:52  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 * Revision 1.3  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.2  2004/10/14 16:28:40  fplanque
 * minor changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.18  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>