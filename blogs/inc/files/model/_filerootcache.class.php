<?php
/**
 * This file implements the FileRootCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( '/files/model/_fileroot.class.php', 'FileRoot' );


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
	 * Internal cache
	 * @var array
	 */
	var $cache = array();


	/**
	 * Get an array of ALL available Fileroots (not just the cached ones).
	 *
	 * @todo fp> it would probably make sense to refactor this as the constructor for the file roots
	 * and initialize the whole cache at construction time
	 *
	 * @static
	 *
	 * @return array of FileRoots (key being the FileRoot's ID)
	 */
	function get_available_FileRoots()
	{
		global $current_User;
		global $collections_Module;

		$r = array();

		// The user's blog (if available) is the default/first one:
		$user_FileRoot = & $this->get_by_type_and_ID( 'user', $current_User->ID, true );
		if( $user_FileRoot )
		{ // We got a user media dir:
			$r[ $user_FileRoot->ID ] = & $user_FileRoot;
		}

		if( isset($collections_Module) )
		{	// Blog/collection media dirs:
			$BlogCache = & get_Cache( 'BlogCache' );
			$bloglist = $BlogCache->load_user_blogs( 'blog_media_browse', $current_User->ID );
			foreach( $bloglist as $blog_ID )
			{
				if( $Root = & $this->get_by_type_and_ID( 'collection', $blog_ID, true ) )
				{
					$r[ $Root->ID ] = & $Root;
				}
			}
		}

		// Shared root:
		$shared_FileRoot = & $this->get_by_type_and_ID( 'shared', 0, true );
		if( $shared_FileRoot )
		{ // We got a shared dir:
			$r[ $shared_FileRoot->ID ] = & $shared_FileRoot;
		}

		if( isset($collections_Module) )
		{ // Skins root:
			$skins_FileRoot = & $this->get_by_type_and_ID( 'skins', 0, false );
			if( $skins_FileRoot )
			{ // We got a skins dir:
				$r[ $skins_FileRoot->ID ] = & $skins_FileRoot;
			}
		}
		
		return $r;
	}


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
			$Root = new FileRoot( $root_type, $root_in_type_ID, $create ); // COPY (func)
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
 * Revision 1.6  2009/09/14 13:04:53  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.5  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 * Revision 1.4  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.3  2008/09/23 06:18:38  fplanque
 * File manager now supports a shared directory (/media/shared/global/)
 *
 * Revision 1.2  2008/01/21 09:35:29  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:56  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.9  2007/05/09 01:01:32  fplanque
 * permissions cleanup
 *
 * Revision 1.8  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.7  2006/12/07 23:13:10  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.6  2006/12/07 15:23:42  fplanque
 * filemanager enhanced, refactored, extended to skins directory
 *
 * Revision 1.5  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>