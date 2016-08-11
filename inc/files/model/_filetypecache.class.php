<?php
/**
 * This file implements the file type cache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

/**
 * FiletypeCache Class
 *
 * @package evocore
 */
class FiletypeCache extends DataObjectCache
{
	var $extension_cache = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		// Call parent constructor:
		parent::__construct( 'Filetype', true, 'T_filetypes', 'ftyp_', 'ftyp_ID', 'ftyp_extensions' );
	}


	/**
	 * Add a dataobject to the cache
	 *
	 * @param object Object to add in cache
	 * @return boolean TRUE on adding, FALSE on wrong object or if it is already in cache
	 */
	function add( $Obj )
	{
		global $Debuglog;

		if( empty($Obj->ID) )
		{
			$Debuglog->add( 'No object to add!', 'dataobjects' );
			return false;
		}

		if( isset($this->cache[$Obj->ID]) )
		{
			$Debuglog->add( $this->objtype.': Object with ID '.$Obj->ID.' is already cached', 'dataobjects' );
			return false;
		}

		// If the object is valid and not already cached:
		$this->cache[$Obj->ID] = $Obj;

		// cache all extensions
		$extensions = explode( ' ', $Obj->extensions );

		foreach( $extensions as $extension )
		{
			$this->extension_cache[$extension] = $Obj; // not & $Obj
		}

		$this->mimetype_cache[$Obj->mimetype] = $Obj; // not & $Obj

		return true;
	}


 	/**
	 * Get an object from cache by extensions ID
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param string Extension string of object to load
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return reference on cached object
	 */
	function & get_by_extension( $req_ID, $halt_on_error = true, $halt_on_empty = true )
	{
		global $DB, $Debuglog;

		if( empty($req_ID) )
		{
			if($halt_on_empty) { debug_die( "Requested $this->objtype from $this->dbtablename without ID!" ); }
			$r = NULL;
			return $r;
		}

		$this->load_all();

		if( empty( $this->extension_cache[ $req_ID ] ) )
		{ // Requested object does not exist
			// $Debuglog->add( 'failure', 'dataobjects' );
			if( $halt_on_error )
			{
				debug_die( "Requested $this->objtype does not exist!" );
			}
			$r = false;
			return $r;
		}

		return $this->extension_cache[ $req_ID ];
	}


 	/**
	 * Get an object from cache by mimetype.
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @todo dh> this copies nearly the whole code of get_by_extension! Have not checked DataObjectCache, but this needs refactoring.
	 *
	 * @param string Mimetype string of object to load
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return reference on cached object
	 */
	function & get_by_mimetype( $mimetype, $halt_on_error = true, $halt_on_empty = true )
	{
		global $DB, $Debuglog;

		if( empty($mimetype) )
		{
			if($halt_on_empty) { debug_die( "Requested $this->objtype from $this->dbtablename without mimetype!" ); }
			$r = NULL;
			return $r;
		}

		$this->load_all();

		if( empty( $this->mimetype_cache[ $mimetype ] ) )
		{ // Requested object does not exist
			if( $halt_on_error )
			{
				debug_die( "Requested $this->objtype does not exist!" );
			}
			$r = false;
			return $r;
		}

		return $this->mimetype_cache[ $mimetype ];
	}
}

?>