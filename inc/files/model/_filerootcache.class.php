<?php
/**
 * This file implements the FileRootCache class.
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
	 * @param string Special file root ID (Used e.g. to view file root of the special user)
	 * @return array of FileRoots (key being the FileRoot's ID)
	 */
	function get_available_FileRoots( $special_root_ID = NULL )
	{
		global $current_User;
		global $collections_Module;

		$r = array();

		if( ! empty( $special_root_ID ) &&
		    ( $special_FileRoot = & $this->get_by_ID( $special_root_ID, true ) ) &&
		    $current_User->check_perm( 'files', 'edit', false, $special_FileRoot ) )
		{ // Try to add special file root if current user has an access
			$r[ $special_FileRoot->ID ] = & $special_FileRoot;
		}

		// The user's blog (if available) is the default/first one:
		$user_FileRoot = & $this->get_by_type_and_ID( 'user', $current_User->ID, true );
		if( $user_FileRoot )
		{ // We got a user media dir:
			$r[ $user_FileRoot->ID ] = & $user_FileRoot;
		}

		if( isset($collections_Module) )
		{	// Blog/collection media dirs:
			$BlogCache = & get_BlogCache();
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

		// Import root:
		$import_FileRoot = & $this->get_by_type_and_ID( 'import', 0, true );
		if( $import_FileRoot )
		{ // We got an import dir:
			$r[ $import_FileRoot->ID ] = & $import_FileRoot;
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


	/**
	 * Clear the cache
	 */
	function clear()
	{
		$this->cache = array();
	}
}

?>