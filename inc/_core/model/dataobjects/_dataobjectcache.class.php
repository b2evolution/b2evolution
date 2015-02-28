<?php
/**
 * This file implements the DataObjectCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
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


	/**
	 * @var string SQL name field (not necessarily with the object).
	 */
	var $name_field;

	/**
	 * @var string SQL ORDER BY.
	 */
	var $order_by;

	/**
	 * @var string SQL additional SELECT fields.
	 */
	var $select;

	/**
	 * The text that gets used for the "None" option in the objects options list.
	 *
	 * This is especially useful for i18n, because there are several "None"s!
	 * 
	 * !!! NOTE !!! Do NOT use T_() for this value, Use only NT_()
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
	 * @param string Name of the order field or NULL to use name field
	 * @param string The text that gets used for the "None" option in the objects options list (Default: NT_('None')).
	 *               !!! NOTE !!! Do NOT use T_() for this value, Use only NT_()
	 * @param mixed  The value that gets used for the "None" option in the objects options list.
	 * @param string Additional part for SELECT clause of sql query
	 */
	function DataObjectCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname, $name_field = NULL, $order_by = '', $allow_none_text = NULL, $allow_none_value = '', $select = '' )
	{
		$this->objtype = $objtype;
		$this->load_all = $load_all;
		$this->dbtablename = $tablename;
		$this->dbprefix = $prefix;
		$this->dbIDname = $dbIDname;
		$this->name_field = $name_field;
		$this->none_option_value = $allow_none_value;
		$this->select = $select;

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
			$this->none_option_text = /* TRANS: the default value for option lists where "None" is allowed */ NT_('None');
		}
	}


	/**
	 * Instanciate a new object within this cache.
	 *
	 * This is used by {@link instantiate()} to get the object which then gets passed to {@link add()}.
	 *
	 * @param object DB row
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
		$select = '';
		if( !empty( $this->select ) )
		{	// Additional select fields
			$select = ', '.$this->select;
		}
		$SQL = new SQL( $title );
		$SQL->SELECT( '*'.$select );
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
				if( $obj !== NULL )
				{ // A cached object can be NULL, e.g. File object when the directory is disabled by settings
					$this->ID_array[] = $obj->ID;
				}
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

/* fp>blueyed: yes move that to add()!
		// Add to named cache:
		if( ! empty($this->name_field) )
		{ // NOTE: this should get done in add() really, but there the mapping of $name_field => object property is not given.
			//       (handled by DataObject constructors).
			$this->cache_name[$db_row->{$this->name_field}] = & $this->cache[$obj_ID];
		}
*/

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

		$req_ID = intval( $req_ID );

		if( empty( $req_ID ) )
		{
			if( $halt_on_empty )
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
				$SQL->WHERE_and( $this->dbIDname.' = '.$DB->quote( $req_ID ) );
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

/* fp> code below  by blueyed, undocumented, except for cache insertion in instantiate which is self labeled as dirty
		if( isset($this->cache_name[$req_name]) )
		{
			return $this->cache_name[$req_name];
		}

		if( ! $this->all_loaded )
		{
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
				$this->cache_name[$req_name] = $this->cache[$resolved_ID];
				return $this->cache[$resolved_ID];
			}
		}

		$Debuglog->add( 'Could not get DataObject by name.', 'dataobjects' );
		if( $halt_on_error )
		{
			debug_die( "Requested $this->objtype does not exist!" );
		}
		$r = NULL;
		return $r;
*/
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
		// remove Obj with req_ID from DataObject_array
		$remove_index = 0;
		foreach( $this->DataObject_array as $DataObject )
		{
			if( $DataObject->ID == $req_ID )
			{
				break;
			}
			$remove_index++;
		}
		unset( $this->DataObject_array[$remove_index] );
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
	 * @param integer|array selected ID(s) (list for multi-selects)
	 * @param boolean provide a choice for "none" with ID ''
	 * @param string Callback method name
	 * @param array IDs to ignore.
	 * @return string
	 */
	function get_option_list( $default = 0, $allow_none = false, $method = 'get_name', $ignore_IDs = array() )
	{
		if( !is_array( $default ) )
		{
			$default = array( $default );
		}

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
			$r .= '>'.format_to_output( T_( $this->none_option_text ) ).'</option>'."\n";
		}

		foreach( $this->cache as $loop_Obj )
		{
			if ( in_array( $loop_Obj->ID, $ignore_IDs ) )
			{	// Ignore this ID
				continue;
			}

			$r .=  '<option value="'.$loop_Obj->ID.'"';
			if( in_array( $loop_Obj->ID, $default ) ) $r .= ' selected="selected"';
			$r .= '>';
			$r .= format_to_output( $loop_Obj->$method(), 'htmlbody' );
			$r .=  '</option>'."\n";
		}

		return $r;
	}

	/**
	 * Returns form option list with cache contents grouped by country preference
	 *
	 * Load the cache if necessary
	 *
	 * @param integer|array selected ID(s) (list for multi-selects)
	 * @param boolean provide a choice for "none" with ID ''
	 * @param string Callback method name
	 * @param array IDs to ignore.
	 * @return string
	 */
	function get_group_country_option_list( $default = 0, $allow_none = false, $method = 'get_name', $ignore_IDs = array() )
	{
		if( !is_array( $default ) )
		{
			$default = array( $default );
		}

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
		{	// we set current value of a country if it is sent to function
			$r .= '<option value="'.$this->none_option_value.'"';
			if( empty($default) ) $r .= ' selected="selected"';
			$r .= '>'.format_to_output( T_( $this->none_option_text ) ).'</option>'."\n";
		}

		$pref_countries = array(); //preferred countries.
		$rest_countries = array(); // not preffered countries (the rest)

		foreach( $this->cache as $loop_Obj )
		{
			if ( in_array( $loop_Obj->ID, $ignore_IDs ) )
			{	// if we have ID of countries that we have to ignore we just do not include them here. 
				//Ignore this ID
				continue;
			}

			if($loop_Obj->preferred == 1)
			{  // if the country is preferred we add it to selected array.
				$pref_countries[] = $loop_Obj;
			}
			$rest_countries[] = $loop_Obj;

		}

		if(count($pref_countries))
		{	// if we don't have preferred countries in this case we don't have to show optgroup
			// in option list
			$r .= '<optgroup label="'.T_('Frequent countries').'">';
			foreach( $pref_countries as $loop_Obj )
			{
				$r .=  '<option value="'.$loop_Obj->ID.'"';
				if( in_array( $loop_Obj->ID, $default ) ) $r .= ' selected="selected"';
				$r .= '>';
				$r .= format_to_output( $loop_Obj->$method(), 'htmlbody' );
				$r .=  '</option>'."\n";
			}
			$r .= '</optgroup>';

			if(count($rest_countries))
			{ // if we don't have rest countries we do not create optgroup for them
				$r .= '<optgroup label="'.T_('Other countries').'">';
				foreach( $rest_countries as $loop_Obj )
				{
					$r .=  '<option value="'.$loop_Obj->ID.'"';
					if( in_array( $loop_Obj->ID, $default ) ) $r .= ' selected="selected"';
					$r .= '>';
					$r .= format_to_output( $loop_Obj->$method(), 'htmlbody' );
					$r .=  '</option>'."\n";
				}
				$r .= '</optgroup>';
			}
		}
		else
		{	// if we have only rest countries we get here
			foreach( $rest_countries as $loop_Obj )
			{
				$r .=  '<option value="'.$loop_Obj->ID.'"';
				if( in_array( $loop_Obj->ID, $default ) ) $r .= ' selected="selected"';
				$r .= '>';
				$r .= format_to_output( $loop_Obj->$method(), 'htmlbody' );
				$r .=  '</option>'."\n";
			}
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

?>