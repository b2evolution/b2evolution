<?php
/**
 * Function for handling Classes in PHP 4.
 *
 * Note: in PHP 5, another file should be included. It shoudl handle clone for example.
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
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Load class file
 */
function load_class( $class_path )
{
	global $inc_path;
	require_once $inc_path.$class_path;
}


/**
 * Load functions file
 */
function load_funcs( $funcs_path )
{
	global $inc_path;
	require_once $inc_path.$funcs_path;
}


function & get_Cache( $objectName )
{
	global $Plugins;
	global $$objectName;

	if( isset( $$objectName ) )
	{	// Cache already exists:
		return $$objectName;
	}

	switch( $objectName )
	{
		case 'BlogCache';
			load_class( 'MODEL/collections/_blogcache.class.php' );
			$BlogCache = new BlogCache(); // COPY (FUNC)
			return $BlogCache;

		case 'ChapterCache';
			load_class( 'MODEL/collections/_chaptercache.class.php' );
			$ChapterCache = new ChapterCache(); // COPY (FUNC)
			return $ChapterCache;

		case 'FileCache';
			load_class( 'MODEL/files/_filecache.class.php' );
			$FileCache = new FileCache(); // COPY (FUNC)
			return $FileCache;

		case 'FileRootCache';
			load_class( 'MODEL/files/_filerootcache.class.php' );
			$Plugins->get_object_from_cacheplugin_or_create( 'FileRootCache' );
			return $FileRootCache;

		case 'FiletypeCache';
			load_class( 'MODEL/files/_filerootcache.class.php' );
			$Plugins->get_object_from_cacheplugin_or_create( 'FiletypeCache' );
			return $FiletypeCache;

		case 'GroupCache';
			$Plugins->get_object_from_cacheplugin_or_create( 'GroupCache', 'new DataObjectCache( \'Group\', true, \'T_groups\', \'grp_\', \'grp_ID\', \'grp_name\' )' );
			return $GroupCache;

		case 'ItemCache';
			load_class( 'MODEL/items/_itemcache.class.php' );
			$ItemCache = new ItemCache(); // COPY (FUNC)
			return $ItemCache;

		case 'ItemStatusCache';
			$Plugins->get_object_from_cacheplugin_or_create( 'ItemStatusCache', 'new GenericCache( \'GenericElement\', true, \'T_itemstatuses\', \'pst_\', \'pst_ID\' )' );
			return $ItemStatusCache;

		case 'ItemTypeCache';
			load_class( 'MODEL/items/_itemtypecache.class.php' );
			$Plugins->get_object_from_cacheplugin_or_create( 'ItemTypeCache', 'new ItemTypeCache( \'ptyp_\', \'ptyp_ID\' )' );
			return $ItemTypeCache;

		case 'LinkCache';
			load_class( 'MODEL/items/_linkcache.class.php' );
			$LinkCache = new LinkCache(); // COPY (FUNC)
			return $LinkCache;

		case 'Plugins_admin':
			load_class( '_misc/_plugins_admin.class.php' );
			$Plugins_admin = new Plugins_admin(); // COPY (FUNC)
			return $LinkCache;

		case 'UserCache';
			load_class( 'MODEL/users/_usercache.class.php' );
			$UserCache = new UserCache(); // COPY (FUNC)
			return $UserCache;

		default:
			debug_die( 'getCache(): Unknown Cache type:'.$objectName );
	}
}

/*
 * $Log$
 * Revision 1.6  2006/12/01 02:01:38  blueyed
 * Added "Plugins_admin" to get_Cache() + doc
 *
 * Revision 1.5  2006/11/30 22:34:15  fplanque
 * bleh
 *
 * Revision 1.4  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.3  2006/09/10 23:35:56  fplanque
 * new permalink styles
 * (decoding not implemented yet)
 *
 * Revision 1.2  2006/08/19 08:50:27  fplanque
 * moved out some more stuff from main
 *
 * Revision 1.1  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 */
?>