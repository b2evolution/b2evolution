<?php
/**
 * This file implements the file type cache class.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package gsbcore
 *
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobjectcache.class.php';

/**
 * Division Class
 *
 * @package gsbcore
 */
class FiletypeCache extends DataObjectCache
{
	var $extension_cache = array();

	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function FiletypeCache( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObjectCache( 'Filetype', true, 'T_filetypes', 'ftyp_', 'ftyp_ID', 'ftyp_extension' );
	}

	
	/**
	 * Add a dataobject to the cache
	 */
	function add( & $Obj )
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
		$this->cache[$Obj->ID] = & $Obj;
		
		// cache all extensions
		$extensions = explode( ' ', $Obj->extensions );
		
		foreach( $extensions as $extension )
		{
			$this->extension_cache[$extension] = $Obj; // not & $Obj
		}
		
		return true;
	}
	
	
 	/**
	 * Get an object from cache by extensions ID
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param integer ID of object to load
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
	
	
}
?>