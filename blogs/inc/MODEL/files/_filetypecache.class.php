<?php
/**
 * This file implements the file type cache class.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
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
	 * {@internal FiletypeCache::FiletypeCache(-)}}
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
	 *
	 * {@internal DataObjectCache::add(-) }}
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
	 * {@internal DataObjectCache::get_by_ID(-) }}
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