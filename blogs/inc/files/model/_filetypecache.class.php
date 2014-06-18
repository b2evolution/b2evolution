<?php
/**
 * This file implements the file type cache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id: _filetypecache.class.php 6135 2014-03-08 07:54:05Z manuel $
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
	function FiletypeCache()
	{
		// Call parent constructor:
		parent::DataObjectCache( 'Filetype', true, 'T_filetypes', 'ftyp_', 'ftyp_ID', 'ftyp_extensions' );
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