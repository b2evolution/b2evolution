<?php
/**
 * This file implements the DataObjectCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_dataobjects'] = false;


load_class( '_core/model/db/_sql.class.php', 'SQL' );


/**
 * Data Object Cache Class
 *
 * @todo dh> Provide iteration "interface"!
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
	 * Object array by ID
	 */
	var $cache = array();

	/**
	 * Copy of previous object array
	 * @see DataObjectCache::clear()
	 */
	var $shadow_cache = NULL;

	/**
	 * NON indexed object array
	 * @var array of DataObjects
	 */
	var $DataObject_array = array();

	/**
	 * Index of current iteration
	 * @see DataObjectCache::get_next()
	 */
	var $current_idx = 0;

	var $load_all = false;
	var $all_loaded = false;


	var $name_field;
	var $order_by;

	/**
	 * The text that gets used for the "None" option in the objects options list.
	 *
	 * This is especially useful for i18n, because there are several "None"s!
	 *
	 * @var string
	 */
	var $none_option_text;

	/**
	 * The value that gets used for the "None" option in the objects options list.
	 *
	 * @var mixed
	 */
	var $none_option_value;

	/**
	 * List of object IDs.
	 * @see get_ID_array()
	 * @access protected
	 */
	var $ID_array;


	/**
	 * Constructor
	 *
	 * @param string Name of DataObject class we are caching
	 * @param boolean true if it's OK to just load all items!
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param string Name of the name field (including prefix)
	 * @param string field names or NULL to use name field
	 * @param string The text that gets used for the "None" option in the objects options list (Default: T_('None')).
	 * @param mixed  The value that gets used for the "None" option in the objects options list.
	 */
	function DataObjectCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname, $name_field = NULL, $order_by = '', $allow_none_text = NULL, $allow_none_value = '' )
	{
		$this->objtype = $objtype;
		$this->load_all = $load_all;
		$this->dbtablename = $tablename;
		$this->dbprefix = $prefix;
		$this->dbIDname = $dbIDname;
		$this->name_field = $name_field;
		$this->none_option_value = $allow_none_value;

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

		if( isset($allow_none_text) )
		{
			$this->none_option_text = $allow_none_text;
		}
		else
		{
			$this->none_option_text = /* TRANS: the default value for option lists where "None" is allowed */ T_('None');
		}
	}


	/**
	 * Instanciate a new object within this cache
	 */
	function new_obj( $row = NULL )
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

		$SQL = $this->get_SQL_object('Loading '.$this->objtype.'(ALL) into cache');
		$this->load_by_sql($SQL);

		$this->all_loaded = true;

		return true;
	}


	/**
	 * Load a list of objects into the cache.
	 *
	 * @param array List of IDs of objects to load
	 * @param boolean Invert list: Load all objects except those listed in the first parameter
	 */
	function load_list( $req_list, $invert = false )
	{
		global $Debuglog;

		if( ! $invert )
			$req_list = array_diff($req_list, $this->get_ID_array());

		if( empty( $req_list ) )
			return false;

		$SQL = $this->get_SQL_object();
		$SQL->WHERE_and($this->dbIDname.( $invert ? ' NOT' : '' ).' IN ('.implode(',', $req_list).')');

		return $this->load_by_sql($SQL);
	}


	/**
	 * Load a set of objects into the cache.
	 *
	 * @param string SQL where expression
	 */
	function load_where( $sql_where )
	{
		$SQL = $this->get_SQL_object();
		$SQL->WHERE($sql_where);
		return $this->load_by_sql($SQL);
	}


	/**
	 * Load a set of objects into the cache.
	 * Already loaded objects get excluded via "NOT IN()"
	 *
	 * @param SQL SQL object
	 * @return array List of DataObjects
	 */
	function load_by_sql( $SQL )
	{
		global $DB, $Debuglog;

		if( is_a($Debuglog, 'Log') )
		{
			$sql_where = trim($SQL->get_where(''));
			if( empty($sql_where) )
				$sql_where = 'ALL';
			$Debuglog->add( 'Loading <strong>'.$this->objtype.'('.$sql_where.')</strong> into cache', 'dataobjects' );
		}

		// Do not request already loaded objects
		if( $loaded_IDs = $this->get_ID_array() )
		{
			$SQL->WHERE_and($this->dbIDname.' NOT IN ('.implode(',', $loaded_IDs).')');
		}
		
		return $this->instantiate_list($DB->get_results( $SQL->get(), OBJECT, $SQL->title ));
	}


	/**
	 * Get base SQL object for queries.
	 * This gets used internally and is a convenient method for derived caches to override SELECT behaviour.
	 * @param string Optional query title
	 * @return SQL
	 */
	function get_SQL_object($title = NULL)
	{
		$SQL = new SQL( $title );
		$SQL->SELECT( '*' );
		$SQL->FROM( $this->dbtablename );
		$SQL->ORDER_BY( $this->order_by );
		return $SQL;
	}


	/**
	 * Get list of objects, referenced by list of IDs.
	 * @param array
	 * @return array
	 */
	function get_list( $ids )
	{
		$this->load_list($ids);
		$r = array();
		foreach( $ids as $id )
		{
			$r[] = $this->get_by_ID($id);
		}
		return $r;
	}


	/**
	 * Get an array of all (loaded) IDs.
	 *
	 * @return array
	 */
	function get_ID_array()
	{
		if( ! isset($this->ID_array) )
		{
			$this->ID_array = array();
			foreach( $this->cache as $obj )
			{
				$this->ID_array[] = $obj->ID;
			}
		}

		return $this->ID_array;
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

		// fplanque: I don't want an extra (and expensive) comparison here. $this->cache[$Obj->ID] === $Obj.
		// If you need this you're probably misusing the cache.
		if( isset($this->cache[$Obj->ID]) )
		{
			$Debuglog->add( $this->objtype.': Object with ID '.$Obj->ID.' is already cached', 'dataobjects' );
			return false;
		}

		// If the object is valid and not already cached:
		// Add object to cache:
		$this->cache[$Obj->ID] = & $Obj;
		// Add a reference in the object list:
		$this->DataObject_array[] = & $Obj;
		// Add the ID to the list of IDs
		$this->ID_array[] = $Obj->ID;

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
		if( is_null($db_row) )
		{	// we can't access NULL as an object
			return $db_row;
		}

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
			// echo "adding shadow {$this->objtype} $obj_ID ";
			$this->add( $this->shadow_cache[$obj_ID] );
		}
		else
		{ // Not already cached, add new object:
			// echo "adding new {$this->objtype} $obj_ID ";
			$this->add( $this->new_obj( $db_row ) );
		}

		return $this->cache[$obj_ID];
	}


	/**
	 * @access public
	 * @param array List of DB rows
	 * @return array List of DataObjects
	 */
	function instantiate_list($db_rows)
	{
		$r = array();
		foreach( $db_rows as $db_row )
		{
			$r[] = $this->instantiate($db_row);
		}
		return $r;
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
		$this->DataObject_array = array();
		$this->all_loaded = false;
		$this->ID_array = NULL;
		$this->rewind();
	}


  /**
	 * This provides a simple interface for looping over the contents of the Cache.
	 *
	 * This should only be used for basic enumeration.
	 * If you need complex filtering of the cache contents, you should probably use a DataObjectList instead.
	 *
	 * @see DataObject::get_next()
	 *
	 * @return DataObject
	 */
	function & get_first()
	{
		$this->load_all();

		$this->rewind();
		return $this->get_next();
	}


	/**
	 * Rewind internal index to first position.
	 * @access public
	 */
	function rewind()
	{
		$this->current_idx = 0;
	}


  /**
	 * This provides a simple interface for looping over the contents of the Cache.
	 *
	 * This should only be used for basic enumeration.
	 * If you need complex filtering of the cache contents, you should probably use a DataObjectList instead.
	 *
	 * @see DataObject::get_first()
	 *
	 * @return DataObject
	 */
	function & get_next()
	{
		// echo 'getting idx:'.$this->current_idx;

		if( ! isset( $this->DataObject_array[$this->current_idx] ) )
		{
			$this->rewind();
			$r = NULL;
			return $r;
		}

		return $this->DataObject_array[$this->current_idx++];
	}


	/**
	 * Get an object from cache by ID
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param integer ID of object to load
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return DataObject reference on cached object or NULL if not found
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
				$SQL = $this->get_SQL_object();
				$SQL->WHERE_and("$this->dbIDname = $req_ID");
				if( $row = $DB->get_row( $SQL->get(), OBJECT, 0, 'DataObjectCache::get_by_ID()' ) )
				{
					if( ! $this->instantiate( $row ) )
					{
						$Debuglog->add( 'Could not add() object to cache!', 'dataobjects' );
					}
				}
				else
				{
					$Debuglog->add( 'Could not get DataObject by ID. Query: '.$SQL->get(), 'dataobjects' );
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
		$SQL = $this->get_SQL_object();
		$SQL->WHERE_and($this->name_field.' = '.$DB->quote($req_name));

		if( $db_row = $DB->get_row( $SQL->get(), OBJECT, 0, 'DataObjectCache::get_by_name()' ) )
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
		# if( ($k = array_search($this->ID_array, $req_ID)) !== false )
		# 	unset($this->ID_array[$k]);
		$this->ID_array = NULL;
		unset( $this->cache[$req_ID] );
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
	 * @param array IDs to ignore.
	 * @return string
	 */
	function get_option_list( $default = 0, $allow_none = false, $method = 'get_name', $ignore_IDs = array() )
	{
		if( ! $this->all_loaded && $this->load_all )
		{ // We have not loaded all items so far, but we're allowed to.
			if ( empty( $ignore_IDs ) )
			{	// just load all items
				$this->load_all();
			}
			else
			{	// only load those items not listed in $ignore_IDs
				$this->load_list( $ignore_IDs, true );
			}
		}

		$r = '';

		if( $allow_none )
		{
			$r .= '<option value="'.$this->none_option_value.'"';
			if( empty($default) ) $r .= ' selected="selected"';
			$r .= '>'.format_to_output($this->none_option_text).'</option>'."\n";
		}

		foreach( $this->cache as $loop_Obj )
		{
			if ( in_array( $loop_Obj->ID, $ignore_IDs ) )
			{	// Ignore this ID
				continue;
			}

			$r .=  '<option value="'.$loop_Obj->ID.'"';
			if( $loop_Obj->ID == $default ) $r .= ' selected="selected"';
			$r .= '>';
			$r .= format_to_output( $loop_Obj->$method(), 'htmlbody' );
			$r .=  '</option>'."\n";
		}

		return $r;
	}


	/**
	 * Returns option array with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * @param string Callback method name
	 * @param array IDs to ignore.
	 * @return string
	 */
	function get_option_array( $method = 'get_name', $ignore_IDs = array() )
	{
		if( ! $this->all_loaded && $this->load_all )
		{ // We have not loaded all items so far, but we're allowed to.
			if ( empty( $ignore_IDs ) )
			{	// just load all items
				$this->load_all();
			}
			else
			{	// only load those items not listed in $ignore_IDs
				$this->load_list( $ignore_IDs, true );
			}
		}

		$r = array();

		foreach( $this->cache as $loop_Obj )
		{
			if( in_array( $loop_Obj->ID, $ignore_IDs ) )
			{	// Ignore this ID
				continue;
			}

			$r[$loop_Obj->ID] = $loop_Obj->$method();
		}

		return $r;
	}

}


/*
 * $Log$
 * Revision 1.22  2010/02/08 17:51:50  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.21  2010/01/30 18:55:16  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.20  2009/12/11 23:18:23  fplanque
 * doc
 *
 * Revision 1.19  2009/12/06 22:20:29  blueyed
 * DataObjectCache:
 *  - Fix get_next to return first element on first call
 *  - use SQL object internally, which makes it easy to extend
 *  - cache ID_array (from get_ID_array)
 *  - adds new methods: load_by_sql, load_where, get_SQL_object, get_list,
 *    rewind
 *  - Add test for get_next
 *
 * Revision 1.18  2009/12/01 20:53:39  blueyed
 * indent
 *
 * Revision 1.17  2009/12/01 02:04:45  fplanque
 * minor
 *
 * Revision 1.16  2009/11/30 22:59:32  blueyed
 * DataObjectCache: Add instantiate_list. load_list: remove already loaded objects from SQL query.
 *
 * Revision 1.15  2009/11/30 00:22:04  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.14  2009/10/19 21:50:36  blueyed
 * doc
 *
 * Revision 1.13  2009/09/20 13:46:47  blueyed
 * doc
 *
 * Revision 1.12  2009/09/05 18:17:40  tblue246
 * DataObjectCache/BlogCache::get_option_list(): Back again... Allow custom value for "None" option and use 0 for BlogCache.
 *
 * Revision 1.10  2009/09/03 15:51:51  tblue246
 * Doc, "refix", use "0" instead of an empty string for the "No blog" option.
 *
 * Revision 1.9  2009/03/15 20:35:18  fplanque
 * Universal Item List proof of concept
 *
 * Revision 1.8  2009/03/08 23:57:40  fplanque
 * 2009
 *
 * Revision 1.7  2009/01/25 14:05:08  tblue246
 * DataObjectCache::load_list(): Allow loading all objects except those given
 *
 * Revision 1.6  2009/01/23 22:08:12  tblue246
 * - Filter reserved post types from dropdown box on the post form (expert tab).
 * - Indent/doc fixes
 * - Do not check whether a post title is required when only e. g. switching tabs.
 *
 * Revision 1.5  2008/12/22 01:56:54  fplanque
 * minor
 *
 * Revision 1.4  2008/09/28 05:05:07  fplanque
 * minor
 *
 * Revision 1.3  2008/09/26 19:02:30  tblue246
 * Do not instantiate NULL "objects" in the cache (fixes http://forums.b2evolution.net/viewtopic.php?t=15973)
 *
 * Revision 1.2  2008/01/21 09:35:24  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:58:56  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.31  2007/05/09 01:00:39  fplanque
 * minor
 *
 * Revision 1.30  2007/04/26 00:11:09  fplanque
 * (c) 2007
 *
 * Revision 1.29  2007/02/12 15:42:40  fplanque
 * public interface for looping over a cache
 *
 * Revision 1.28  2006/12/29 01:10:06  fplanque
 * basic skin registering
 *
 * Revision 1.27  2006/12/24 01:09:55  fplanque
 * Rollback. Non geeks do not know how to use select multiple.
 * Checkbox lists should be used instead.
 * The core does. There is not reason for plugins not to do so also.
 *
 * Revision 1.24  2006/12/12 02:53:56  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.23  2006/12/05 01:35:27  blueyed
 * Hooray for less complexity and the 8th param for DataObjectCache()
 *
 * Revision 1.22  2006/12/05 00:59:46  fplanque
 * doc
 *
 * Revision 1.21  2006/12/05 00:34:39  blueyed
 * Implemented custom "None" option text in DataObjectCache; Added for $ItemStatusCache, $GroupCache, UserCache and BlogCache; Added custom text for Item::priority_options()
 *
 * Revision 1.20  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.19  2006/11/10 20:14:42  blueyed
 * TODO
 *
 * Revision 1.18  2006/10/13 09:58:53  blueyed
 * Removed bogus unset()
 */
?>
