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
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 
function load_class( $class_path )
{
	global $inc_path;
	require_once $inc_path.$class_path;
}


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
			load_class( '/MODEL/collections/_blogcache.class.php' );
			$BlogCache = new BlogCache(); // COPY (FUNC)
			return $BlogCache;

		case 'FileCache';
			load_class( '/MODEL/files/_filecache.class.php' );
			$FileCache = new FileCache(); // COPY (FUNC)
			return $FileCache;

		case 'FileRootCache';
			load_class( '/MODEL/files/_filerootcache.class.php' );
			$Plugins->get_object_from_cacheplugin_or_create( 'FileRootCache' );
			return $FileRootCache;

		case 'FiletypeCache';
			load_class( '/MODEL/files/_filerootcache.class.php' );
			$Plugins->get_object_from_cacheplugin_or_create( 'FiletypeCache' );
			return $FiletypeCache;

		case 'GroupCache';
			$Plugins->get_object_from_cacheplugin_or_create( 'GroupCache', 'new DataObjectCache( \'Group\', true, \'T_groups\', \'grp_\', \'grp_ID\', \'grp_name\' )' );
			return $GroupCache;

		case 'ItemCache';
			load_class( '/MODEL/items/_itemcache.class.php' );
			$ItemCache = new ItemCache(); // COPY (FUNC)
			return $ItemCache;
			
		case 'ItemStatusCache';
			$Plugins->get_object_from_cacheplugin_or_create( 'ItemStatusCache', 'new GenericCache( \'GenericElement\', true, \'T_itemstatuses\', \'pst_\', \'pst_ID\' )' );
			return $ItemStatusCache;

		case 'ItemTypeCache';
			load_class( '/MODEL/items/_itemtypecache.class.php' );
			$Plugins->get_object_from_cacheplugin_or_create( 'ItemTypeCache', 'new ItemTypeCache( \'ptyp_\', \'ptyp_ID\' )' );
			return $ItemTypeCache;

		case 'LinkCache';
			load_class( '/MODEL/items/_linkcache.class.php' );
			$LinkCache = new LinkCache(); // COPY (FUNC)
			return $LinkCache;
			
		case 'UserCache';
			load_class( '/MODEL/users/_usercache.class.php' );
			$UserCache = new UserCache(); // COPY (FUNC)
			return $UserCache;
			
		default:
			debug_die( 'getCache(): Unknown Cache type:'.$objectName );		
	}
}
 
/*
 * $Log$
 * Revision 1.2  2006/08/19 08:50:27  fplanque
 * moved out some more stuff from main
 *
 * Revision 1.1  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 */
?>