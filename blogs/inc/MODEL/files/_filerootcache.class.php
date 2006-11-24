<?php
/**
 * This file implements the FileRootCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Includes
 */
require_once dirname(__FILE__).'/_fileroot.class.php';


/**
 * This class provides info about File Roots.
 *
 * These are root directories available for media file storage, under access permission.
 *
 * @package evocore
 */
class FileRootCache
{
	/**
	 * @var array Internal cache
	 */
	var $cache = array();


	/**
	 * Get a FileRoot (cached) by ID.
	 *
	 * @uses FileRootCache::get_by_type_and_ID()
	 * @param string ID of the FileRoot (e.g. 'user_X' or 'collection_X')
	 * @param boolean Create the directory, if it does not exist yet?
	 * @return FileRoot|false FileRoot on success, false on failure (ads_path is false).
	 */
	function & get_by_ID( $id, $create = false )
	{
		$part = explode( '_', $id );
		$root_type = $part[0];
		$root_in_type_ID = $part[1];

		return $this->get_by_type_and_ID( $root_type, $root_in_type_ID, $create );
	}


	/**
	 * Get a FileRoot (cached).
	 *
	 * @param string Root type: 'user', 'group', 'collection' or 'absolute'
	 * @param integer ID of the user, the group or the collection the file belongs to...
	 * @param boolean Create the directory, if it does not exist yet?
	 * @return FileRoot|false FileRoot on success, false on failure (ads_path is false).
	 */
	function & get_by_type_and_ID( $root_type, $root_in_type_ID, $create = false )
	{
		$root_ID = FileRoot::gen_ID( $root_type, $root_in_type_ID );

		if( ! isset( $this->cache[$root_ID] ) )
		{	// Not in Cache, let's instantiate:
			$Root = new FileRoot( $root_type, $root_in_type_ID, $create ); // COPY
			if( empty($Root->ads_path) ) // false
			{
				$Root = false;
			}
			$this->cache[$root_ID] = & $Root;
		}

		return $this->cache[$root_ID];
	}


	/**
	 * Get the absolute path (FileRoot::ads_path) to a given root (with ending slash).
	 *
	 * @deprecated since 1.9
	 * @param boolean Create the directory, if it does not exist yet?
	 * @return string
	 */
	function get_root_dir( $root_type, $root_in_type_ID, $create = false )
	{
		$tmp_FileRoot = & $this->get_by_type_and_ID( $root_type, $root_in_type_ID, $create );
		return $tmp_FileRoot->ads_path;
	}
}


/*
 * $Log$
 * Revision 1.5  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.4  2006/09/30 16:55:58  blueyed
 * $create param for media dir handling, which allows to just get the dir, without creating it.
 *
 * Revision 1.3  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.8  2006/01/20 16:40:56  blueyed
 * Cleanup
 *
 * Revision 1.7  2006/01/09 18:51:22  blueyed
 * get_by_type_and_ID(): return false if this root has no valid path/does not exist!
 *
 * Revision 1.6  2005/12/16 13:50:49  blueyed
 * FileRoot::get_by_ID() from post-phoenix
 *
 * Revision 1.5  2005/12/14 19:36:16  fplanque
 * Enhanced file management
 *
 * Revision 1.4  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.3  2005/11/18 07:53:05  blueyed
 * use $_FileRoot / $FileRootCache for absolute path, url and name of roots.
 *
 * Revision 1.2  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.1  2005/07/29 17:56:18  fplanque
 * Added functionality to locate files when they're attached to a post.
 * permission checking remains to be done.
 *
 */
?>