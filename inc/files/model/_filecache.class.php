<?php
/**
 * This file implements the FileCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

/**
 * FileCache Class
 *
 * @package evocore
 */
class FileCache extends DataObjectCache
{
	/**
	 * Cache for 'root_type:root_in_type_ID:relative_path' -> File object reference
	 * @access private
	 * @var array
	 */
	var $cache_root_and_path = array();

	/**
	 * Constructor
	 */
	function FileCache()
	{
		parent::DataObjectCache( 'File', false, 'T_files', 'file_', 'file_ID' );
	}


	/**
	 * Load the cache **extensively**
	 */
	function load_all()
	{
		if( $this->all_loaded )
		{ // Already loaded
			return false;
		}

		debug_die( 'Load all is not allowed for FileCache!' );
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

		if( ! empty( $obj_ID ) )
		{ // If the object ID is valid:
			if( ! isset( $this->cache[$obj_ID] ) )
			{ // If not already cached:
				// Instantiate a File object for this line:
				$current_File = new File( $db_row->file_root_type, $db_row->file_root_ID, $db_row->file_path ); // COPY!
				if( ! $current_File->_FileRoot )
				{ // File root is not initialized for this file, probably it is disabled by settings.
					// We cannot work with such file object. Use NULL instead.
					$current_File = NULL;
				}
				else
				{ // Flow meta data into File object:
					$current_File->load_meta( false, $db_row );
				}
				$this->add( $current_File );
			}
			else
			{ // Already cached:
				$current_File = & $this->cache[$obj_ID];
				if( $current_File )
				{ // Flow meta data into File object:
					$current_File->load_meta( false, $db_row );
				}
			}
		}

		return $this->cache[$obj_ID];
	}


  /**
	 * Creates an object of the {@link File} class, while providing caching
	 * and making sure that only one reference to a file exists.
	 *
	 * @param string Root type: 'user', 'group' or 'collection'
	 * @param integer ID of the user, the group or the collection the file belongs to...
	 * @param string Subpath for this file/folder, relative the associated root, including trailing slash (if directory)
	 * @param boolean check for meta data?
	 * @return File a {@link File} object
	 */
	function & get_by_root_and_path( $root_type, $root_in_type_ID, $rel_path, $load_meta = false )
	{
		global $Debuglog, $cache_File;

		if( is_windows() )
		{
			$rel_path = strtolower( str_replace( '\\', '/', $rel_path ) );
		}

		// Generate cache key for this file:
		$cacheindex = $root_type.':'.$root_in_type_ID.':'.$rel_path;

		if( isset( $this->cache_root_and_path[$cacheindex] ) )
		{ // Already in cache
			$Debuglog->add( 'File retrieved from cache: '.$cacheindex, 'files' );
			$File = & $this->cache_root_and_path[$cacheindex];
			if( $File && $load_meta )
			{ // Make sure meta is loaded:
				$File->load_meta();
			}
		}
		else
		{ // Not in cache
			$Debuglog->add( 'File not in cache: '.$cacheindex, 'files' );
			$File = new File( $root_type, $root_in_type_ID, $rel_path, $load_meta ); // COPY !!
			if( ! $File->_FileRoot )
			{ // File root is not initialized for this file, probably it is disabled by settings.
				// We cannot work with such file object. Use NULL instead.
				$File = NULL;
			}
			$this->cache_root_and_path[$cacheindex] = & $File;
		}

		return $File;
	}


}

?>