<?php
/**
 * This file implements the FileCache class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobjectcache.class.php';

/**
 * FileCache Class
 *
 * @package evocore
 */
class FileCache extends DataObjectCache
{
	/**
	 * Cache for 'root_type:root_ID:relative_path' -> File object reference
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
	 * Creates an object of the {@link File} class, while providing caching
	 * and making sure that only one reference to a file exists.
	 *
	 * @param string Root type: 'user', 'group', 'collection' or 'absolute'
	 * @param integer ID of the user, the group or the collection the file belongs to...
	 * @param string Subpath for this file/folder, relative the associated root, including trailing slash (if directory)
	 * @param boolean check for meta data?
	 * @return File an {@link File} object
	 */
	function & get_by_root_and_path( $root_type, $root_ID, $rel_path, $load_meta = false )
	{
		global $Debuglog, $cache_File;

		if( is_windows() )
		{
			$rel_path = strtolower(str_replace( '\\', '/', $rel_path ));
		}

		// Generate cache key for this file:
		$cacheindex = $root_type.':'.$root_ID.':'.$rel_path;

		if( isset( $this->cache_root_and_path[$cacheindex] ) )
		{	// Already in cache
			$Debuglog->add( 'File retrieved from cache: '.$cacheindex, 'files' );
			$File = & $this->cache_root_and_path[$cacheindex];
			if( $load_meta )
			{	// Make sure meta is loaded:
				$File->load_meta();
			}
		}
		else
		{	// Not in cache
			$Debuglog->add( 'File not in cache: '.$cacheindex, 'files' );
			$File = new File( $root_type, $root_ID, $rel_path, $load_meta ); // COPY !!
			$this->cache_root_and_path[$cacheindex] = & $File;
		}
		return $File;
	}

}

/*
 * $Log$
 * Revision 1.4  2005/05/17 19:26:07  fplanque
 * FM: copy / move debugging
 *
 * Revision 1.3  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.2  2005/04/26 18:19:25  fplanque
 * no message
 *
 * Revision 1.1  2005/04/19 16:23:02  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 */
?>