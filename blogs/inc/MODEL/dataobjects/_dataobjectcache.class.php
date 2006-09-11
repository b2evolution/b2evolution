<?php
/**
 * This file implements the DataObjectCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Data Object Cache Class
 *
 * @package evocore
 * @version beta
 */
class DataObjectCache
{
	var $dbtablename;
	var $dbprefix;
	var $dbIDname;

	/**
	 * Class name of objects in this cache:
	 */
	var $objtype;

	/**
	 * Object array
	 */
	var $cache = array();

	/**
	 * Copy of previous object array
	 * @see DataObjectCache::clear()
	 */
	var $shadow_cache = NULL;

	var $load_add = false;
	var $all_loaded = false;
	var $name_field;
	var $order_by;


	/**
	 * Constructor
	 *
	 * @param string Name of DataObject class we are cacheing
	 * @param boolean true if it's OK to just load all items!
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function DataObjectCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname, $name_field = NULL, $order_by = '' )
	{
		$this->objtype = $objtype;
		$this->load_all = $load_all;
		$this->dbtablename = $tablename;
		$this->dbprefix = $prefix;
		$this->dbIDname = $dbIDname;
		$this->name_field = $name_field;

		if( empty( $order_by ) )
		{
			if( empty( $name_field ) )
			{
				$this->order_by = $dbIDname;
			}
			else
			{
				$this->order_by = $name_field;
			}
		}
		else
		{
			$this->order_by = $order_by;
		}
	}


	/**
	 * Instanciate a new object within this cache
	 */
	function & new_obj( $row = NULL )
	{
		$objtype = $this->objtype;

		// Instantiate a custom object
		$obj = new $objtype( $row ); // COPY !!

		return $obj;
	}


	/**
	 * Load the cache **extensively**
	 */
	function load_all()
	{
		/**
		 * @var DB
		 */
		global $DB;
		global $Debuglog;

		if( $this->all_loaded )
		{ // Already loaded
			return false;
		}

		$this->clear( true );

		$Debuglog->add( get_class($this).' - Loading <strong>'.$this->objtype.'(ALL)</strong> into cache', 'dataobjects' );
		$sql = 'SELECT *
							FROM '.$this->dbtablename.'
						 ORDER BY '.$this->order_by;

		foreach( $DB->get_results( $sql, OBJECT, 'Loading '.$this->objtype.'(ALL) into cache' ) as $row )
		{
			// Instantiate a custom object
			$this->instantiate( $row );
		}

		$this->all_loaded = true;

		return true;
	}


	/**
	 * Load a list of objects into the cache
	 *
	 * @param string list of IDs of objects to load
	 */
	function load_list( $req_list )
	{
		global $DB, $Debuglog;

		$Debuglog->add( "Loading <strong>$this->objtype($req_list)</strong> into cache", 'dataobjects' );

		if( empty( $req_list ) )
		{
			return false;
		}

		$sql = "SELECT *
		          FROM $this->dbtablename
		         WHERE $this->dbIDname IN ($req_list)";
		$objtype = $this->objtype;
		foreach( $DB->get_results( $sql ) as $row )
		{
			$this->add( new $objtype( $row ) );
			// TODO: use instantiate()
		}
	}


	/**
	 * Get an array of all (loaded) IDs.
	 *
	 * @return array
	 */
	function get_ID_array()
	{
		$IDs = array();

		foreach( $this->cache as $obj )
		{
			$IDs[] = $obj->ID;
		}

		return $IDs;
	}


	/**
	 * Add a dataobject to the cache
	 */
	function add( & $Obj )
	{
		global $Debuglog;

		if( is_null($Obj->ID) )	// value 0 is used by item preview
		{
			$Debuglog->add( 'No object to add!', 'dataobjects' );
			return false;
		}

		if( isset($this->cache[$Obj->ID]) )
		// fplanque: I don't want an extra (and expensive) comparison here. $this->cache[$Obj->ID] === $Obj. If you need this you're probably misusing the cache.
		{
			$Debuglog->add( $this->objtype.': Object with ID '.$Obj->ID.' is already cached', 'dataobjects' );
			return false;
		}

		// If the object is valid and not already cached:
		$this->cache[$Obj->ID] = & $Obj;

		return true;
	}


	/**
	 * Instantiate a DataObject from a table row and then cache it.
	 *
	 * @param Object Database row
	 * @return Object
	 */
	function & instantiate( & $db_row )
	{
		// Get ID of the object we'ere preparing to instantiate...
		$obj_ID = $db_row->{$this->dbIDname};

		if( is_null($obj_ID) )	// value 0 is used for item preview
		{
			$Obj = NULL;
			return $Obj;
		}

		if( isset( $this->cache[$obj_ID] ) )
		{ // Already in cache, do nothing!
		}
		elseif( isset( $this->shadow_cache[$obj_ID] ) )
		{	// Already in shadow, recycle object:
			$this->add( $this->shadow_cache[$obj_ID] );
		}
		else
		{ // Not already cached, add new object:
			$this->add( $this->new_obj( $db_row ) );
		}

		return $this->cache[$obj_ID];
	}


	/**
	 * Clear the cache **extensively**
	 *
	 */
	function clear( $keep_shadow = false )
	{
		if( $keep_shadow )
		{	// Keep copy of cache in case we try to re instantiate previous object:
			$this->shadow_cache = $this->cache;
		}
		else
		{
			$this->shadow_cache = NULL;
		}

		$this->cache = array();
		$this->all_loaded = false;
	}


	/**
	 * Get an object from cache by ID
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param integer ID of object to load
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return reference on cached object
	 */
	function & get_by_ID( $req_ID, $halt_on_error = true, $halt_on_empty = true )
	{
		global $DB, $Debuglog;

		if( empty($req_ID) )
		{
			if($halt_on_empty)
			{
				debug_die( "Requested $this->objtype from $this->dbtablename without ID!" );
			}
			$r = NULL;
			return $r;
		}

		if( !empty( $this->cache[ $req_ID ] ) )
		{ // Already in cache
			// $Debuglog->add( "Accessing $this->objtype($req_ID) from cache", 'dataobjects' );
			return $this->cache[ $req_ID ];
		}
		elseif( !$this->all_loaded )
		{ // Not in cache, but not everything is loaded yet
			if( $this->load_all )
			{ // It's ok to just load everything:
				$this->load_all();
			}
			else
			{ // Load just the requested object:
				$Debuglog->add( "Loading <strong>$this->objtype($req_ID)</strong> into cache", 'dataobjects' );
				// Note: $req_ID MUST be an unsigned integer. This is how DataObject works.
				$sql = "SELECT *
				          FROM $this->dbtablename
				         WHERE $this->dbIDname = $req_ID";
				if( $row = $DB->get_row( $sql, OBJECT, 0, 'DataObjectCache::get_by_ID()' ) )
				{
					if( ! $this->instantiate( $row ) )
					{
						$Debuglog->add( 'Could not add() object to cache!', 'dataobjects' );
					}
				}
				else
				{
					$Debuglog->add( 'Could not get DataObject by ID. Query: '.$sql, 'dataobjects' );
				}
			}
		}

		if( empty( $this->cache[ $req_ID ] ) )
		{ // Requested object does not exist
			// $Debuglog->add( 'failure', 'dataobjects' );
			if( $halt_on_error )
			{
				debug_die( "Requested $this->objtype does not exist!" );
			}
			$r = false;
			return $r;
		}

		return $this->cache[ $req_ID ];
	}


	/**
	 * Get an object from cache by name
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param integer ID of object to load
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return reference on cached object
	 */
	function & get_by_name( $req_name, $halt_on_error = true, $halt_on_empty = true )
	{
		global $DB, $Debuglog;

		if( empty( $this->name_field ) )
		{
			debug_die( 'DataObjectCache::get_by_name() : No name field to query on' );
		}

		if( empty($req_name) )
		{
			if($halt_on_empty) { debug_die( "Requested $this->objtype from $this->dbtablename without name!" ); }
			$r = NULL;
			return $r;
		}

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_name)</strong>", 'dataobjects' );
		$sql = "SELECT *
						  FROM $this->dbtablename
						 WHERE $this->name_field = ".$DB->quote($req_name);

		if( $db_row = $DB->get_row( $sql, OBJECT, 0, 'DataObjectCache::get_by_name()' ) )
		{
			$resolved_ID = $db_row->{$this->dbIDname};
			$Debuglog->add( 'success; ID = '.$resolved_ID, 'dataobjects' );
			if( ! isset( $this->cache[$resolved_ID] ) )
			{	// Object is not already in cache:
				$Debuglog->add( 'Adding to cache...', 'dataobjects' );
				//$Obj = new $this->objtype( $row ); // COPY !!
				//if( ! $this->add( $this->new_obj( $db_row ) ) )
				if( ! $this->add( $this->new_obj( $db_row ) ) )
				{	// could not add
					$Debuglog->add( 'Could not add() object to cache!', 'dataobjects' );
				}
			}
			return $this->cache[$resolved_ID];
		}
		else
		{
			$Debuglog->add( 'Could not get DataObject by name.', 'dataobjects' );
			if( $halt_on_error )
			{
				debug_die( "Requested $this->objtype does not exist!" );
			}
			$r = NULL;
			return $r;
		}
	}


	/**
	 * Remove an object from cache by ID
	 *
	 * @param integer ID of object to remove
	 */
	function remove_by_ID( $req_ID )
	{
		unset( $this->cache[$req_ID] );
		unset( $marcus );
	}


	/**
	 * Delete an object from DB by ID.
	 *
	 * @param integer ID of object to delete
	 * @return boolean
	 */
	function dbdelete_by_ID( $req_ID )
	{
		if( isset( $this->cache[$req_ID] ) )
		{
			// Delete from db
			$this->cache[$req_ID]->dbdelete();

			// Remove from cache
			$this->remove_by_ID( $req_ID );

			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Returns form option list with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID ''
	 * @param string Callback method name
	 * @return string
	 */
	function get_option_list( $default = 0, $allow_none = false, $method = 'get_name' )
	{
		if( (! $this->all_loaded) && $this->load_all )
		{ // We have not loaded all items so far, but we're allowed to... so let's go:
			$this->load_all();
		}

		$r = '';

		if( $allow_none )
		{
			$r .= '<option value=""';
			if( empty($default) ) $r .= ' selected="selected"';
			$r .= '>'.$this->get_None_option_string().'</option>'."\n";
		}

		foreach( $this->cache as $loop_Obj )
		{
			$r .=  '<option value="'.$loop_Obj->ID.'"';
			if( $loop_Obj->ID == $default ) $r .= ' selected="selected"';
			$r .= '>';
			$r .= format_to_output( $loop_Obj->$method() );
			$r .=  '</option>'."\n";
		}

		return $r;
	}


	/**
	 * Get the string that gets used for the "None" option in the objects
	 * options list. This is especially useful for i18n, because there are
	 * several "None"s!
	 *
	 * Subclasses should override this, e.g. "No user" for {@link UserCache}.
	 *
	 * {@internal dh> QUESTION: I've made this a callback to not translate a string to early,
	 *  but it would require to have real classes for e.g. GroupCache. Should it be a
	 *  constructor param instead? }}
	 * fp> yes I think an added param to the constructor could be ok. Or a set_none_text() method. Please use 'none' instead of 'None' in function name.
	 *
	 * @return string
	 */
	function get_None_option_string()
	{
		return /* TRANS: the default value for option lists where "None" is allowed */ T_('None');
	}
}


/*
 * $Log$
 * Revision 1.14  2006/09/11 22:06:08  blueyed
 * Cleaned up option_list callback handling
 *
 * Revision 1.13  2006/09/11 19:34:34  fplanque
 * fully powered the ChapterCache
 *
 * Revision 1.12  2006/09/10 16:23:00  blueyed
 * suggestion
 *
 * Revision 1.11  2006/09/10 16:16:29  blueyed
 * question
 *
 * Revision 1.10  2006/09/10 14:50:48  fplanque
 * minor / doc
 *
 * Revision 1.9  2006/09/10 00:49:56  blueyed
 * get_None_option_string proposal
 *
 * Revision 1.8  2006/09/09 22:28:08  fplanque
 * ChapterCache Restricts categories to a specific blog
 *
 * Revision 1.7  2006/09/06 21:39:21  fplanque
 * ItemList2 fixes
 *
 * Revision 1.6  2006/08/02 16:34:16  yabs
 * corrected $row to $db_row in function get_by_name()
 *
 * Revision 1.5  2006/06/14 17:26:13  fplanque
 * minor
 *
 * Revision 1.4  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.3  2006/04/14 19:25:32  fplanque
 * evocore merge with work app
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.34  2006/02/08 12:24:37  blueyed
 * doc
 *
 * Revision 1.33  2005/12/30 20:13:39  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.32  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.28  2005/11/16 21:53:49  fplanque
 * minor
 *
 * Revision 1.27  2005/11/16 12:21:15  blueyed
 * use debug_die()
 *
 * Revision 1.26  2005/11/09 03:20:05  blueyed
 * minor
 *
 * Revision 1.25  2005/10/03 22:50:53  blueyed
 * Fixed E_NOTICE for PHP 4.4.0 and probably 5.1.x (again). Functions that return by reference must not return values!
 *
 * Revision 1.24  2005/09/29 15:26:15  fplanque
 * added get_by_name()
 *
 * Revision 1.23  2005/09/18 01:46:55  blueyed
 * Fixed E_NOTICE for return by reference (PHP 4.4.0)
 *
 * Revision 1.22  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.21  2005/08/02 18:15:14  fplanque
 * fix for correct NULL handling
 *
 * Revision 1.20  2005/07/15 18:10:07  fplanque
 * allow instantiating of member objects (used for preloads)
 *
 * Revision 1.19  2005/06/10 18:25:44  fplanque
 * refactoring
 *
 * Revision 1.18  2005/05/16 15:17:13  fplanque
 * minor
 *
 * Revision 1.17  2005/05/11 13:21:38  fplanque
 * allow disabling of mediua dir for specific blogs
 *
 * Revision 1.16  2005/04/19 16:23:02  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.15  2005/03/14 20:22:19  fplanque
 * refactoring, some cacheing optimization
 *
 * Revision 1.14  2005/03/02 15:24:29  fplanque
 * allow get_by_ID(NULL) in some situations
 *
 * Revision 1.13  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.12  2005/02/14 21:17:45  blueyed
 * optimized cache handling
 *
 * Revision 1.11  2005/02/09 00:27:13  blueyed
 * Removed deprecated globals / userdata handling
 *
 * Revision 1.10  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.9  2005/01/20 18:46:26  fplanque
 * debug
 *
 * Revision 1.8  2005/01/13 19:53:50  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
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