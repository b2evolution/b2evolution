<?php
/**
 * Function for handling Classes in PHP 4.
 *
 * Note: in PHP 5, another file should be included. It should handle clone for example.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
function load_class( $class_path, $require = true )
{
	global $inc_path;
	if( ! $require && ! file_exists( $inc_path.$class_path ) )
	{
		return false;
	}
	require_once $inc_path.$class_path;
	return true;
}


/**
 * Load functions file
 */
function load_funcs( $funcs_path )
{
	global $inc_path;
	require_once $inc_path.$funcs_path;
}


/**
 * @todo fp> split into 1 function per case. (typed @return values)
 *
 * @return DataObjectCache
 */
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
		case 'BlogCache':
			load_class( 'collections/model/_blogcache.class.php' );
			$BlogCache = new BlogCache(); // COPY (FUNC)
			return $BlogCache;

		case 'ChapterCache':
			load_class( 'chapters/model/_chaptercache.class.php' );
			$ChapterCache = new ChapterCache(); // COPY (FUNC)
			return $ChapterCache;

		case 'FileCache':
			load_class( 'files/model/_filecache.class.php' );
			$FileCache = new FileCache(); // COPY (FUNC)
			return $FileCache;

		case 'FileRootCache':
			load_class( 'files/model/_filerootcache.class.php' );
			$Plugins->get_object_from_cacheplugin_or_create( 'FileRootCache' );
			return $FileRootCache;

		case 'FiletypeCache':
			load_class( 'files/model/_filetypecache.class.php' );
			$Plugins->get_object_from_cacheplugin_or_create( 'FiletypeCache' );
			return $FiletypeCache;

		case 'GoalCache';
			$GoalCache = new DataObjectCache( 'Goal', false, 'T_track__goal', 'goal_', 'goal_ID', 'goal_name', 'goal_name' ); // COPY (FUNC)
			return $GoalCache;

		case 'GroupCache':
			$Plugins->get_object_from_cacheplugin_or_create( 'GroupCache', 'new DataObjectCache( \'Group\', true, \'T_groups\', \'grp_\', \'grp_ID\', \'grp_name\', \'\', T_(\'No group\') )' );
			return $GroupCache;

		case 'ItemCacheLight';
			$ItemCacheLight = new DataObjectCache( 'ItemLight', false, 'T_items__item', 'post_', 'post_ID' ); // COPY (FUNC)
			return $ItemCacheLight;

		case 'ItemCache';
			load_class( 'items/model/_itemcache.class.php' );
			$ItemCache = new ItemCache(); // COPY (FUNC)
			return $ItemCache;

		case 'ItemPrerenderingCache':
			$ItemPrerenderingCache = array();
			return $ItemPrerenderingCache;

		case 'ItemStatusCache':
			$Plugins->get_object_from_cacheplugin_or_create( 'ItemStatusCache', 'new GenericCache( \'GenericElement\', true, \'T_items__status\', \'pst_\', \'pst_ID\', NULL, \'\', T_(\'No status\') )' );
			return $ItemStatusCache;

		case 'ItemTypeCache':
			load_class( 'items/model/_itemtypecache.class.php' );
			$Plugins->get_object_from_cacheplugin_or_create( 'ItemTypeCache', 'new ItemTypeCache( \'ptyp_\', \'ptyp_ID\' )' );
			return $ItemTypeCache;

		case 'LinkCache':
			load_class( 'items/model/_linkcache.class.php' );
			$LinkCache = new LinkCache(); // COPY (FUNC)
			return $LinkCache;

		case 'Plugins_admin':
			load_class('plugins/model/_plugins_admin.class.php');
			$Plugins_admin = new Plugins_admin(); // COPY (FUNC)
			return $Plugins_admin;

		case 'SkinCache':
			load_class( 'skins/model/_skincache.class.php' );
			$SkinCache = new SkinCache(); // COPY (FUNC)
			return $SkinCache;

		case 'UserCache':
			load_class( 'users/model/_usercache.class.php' );
			$UserCache = new UserCache(); // COPY (FUNC)
			return $UserCache;

		case 'WidgetCache':
			load_class( 'widgets/model/_widgetcache.class.php' );
			$WidgetCache = new WidgetCache(); // COPY (FUNC)
			return $WidgetCache;

		case 'EnabledWidgetCache':
			// This simply instantiates a WidgetCache object, setting the
			// $enabled_only parameter to true. Using a member variable
			// instead of per-method parameters to load only the enabled
			// widgets should be cleaner when there will be more methods
			// in the WidgetCache class in the future.
			load_class( 'widgets/model/_widgetcache.class.php' );
			$EnabledWidgetCache = new WidgetCache( true );
			return $EnabledWidgetCache;

		default:
			debug_die( 'getCache(): Unknown Cache type:'.$objectName );
	}
}

/*
 * $Log$
 * Revision 1.12  2009/02/26 22:07:20  blueyed
 * Fix typo
 *
 * Revision 1.11  2009/02/22 23:59:53  blueyed
 * ItemPrerenderingCache:
 *  - simple array to prefetch all prerendered MainList items
 *  - There's some flaw still, see the TODO(s)
 *  - add delete_prerendered_content method, also invalidating
 *    content_pages
 *
 * Revision 1.10  2009/02/05 21:33:33  tblue246
 * Allow the user to enable/disable widgets.
 * Todo:
 * 	* Fix CSS for the widget state bullet @ JS widget UI.
 * 	* Maybe find a better solution than modifying get_Cache() to get only enabled widgets... :/
 * 	* Buffer JS requests when toggling the state of a widget??
 *
 * Revision 1.9  2008/05/26 19:25:41  fplanque
 * minor
 *
 * Revision 1.8  2008/04/17 11:53:16  fplanque
 * Goal editing
 *
 * Revision 1.7  2008/03/31 21:13:47  fplanque
 * Reverted übergeekyness
 *
 * Revision 1.5  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.4  2007/12/06 20:04:34  blueyed
 * Fix indent
 *
 * Revision 1.3  2007/11/04 21:22:56  fplanque
 * version bump
 *
 * Revision 1.1  2007/06/25 10:58:52  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.19  2007/06/20 14:25:00  fplanque
 * fixes
 *
 * Revision 1.18  2007/06/18 21:25:48  fplanque
 * one class per core widget
 *
 * Revision 1.17  2007/05/14 02:43:05  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.16  2007/04/26 00:11:07  fplanque
 * (c) 2007
 *
 * Revision 1.15  2007/03/18 03:43:19  fplanque
 * EXPERIMENTAL
 * Splitting Item/ItemLight and ItemList/ItemListLight
 * Goal: Handle Items with less footprint than with their full content
 * (will be even worse with multiple languages/revisions per Item)
 *
 * Revision 1.14  2007/01/11 20:44:19  fplanque
 * skin containers proof of concept
 * (no params handling yet though)
 *
 * Revision 1.13  2007/01/11 02:57:25  fplanque
 * implemented removing widgets from containers
 *
 * Revision 1.12  2006/12/29 01:10:06  fplanque
 * basic skin registering
 *
 * Revision 1.11  2006/12/07 20:03:32  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.10  2006/12/05 01:35:27  blueyed
 * Hooray for less complexity and the 8th param for DataObjectCache()
 *
 * Revision 1.9  2006/12/05 00:59:46  fplanque
 * doc
 *
 * Revision 1.8  2006/12/05 00:34:39  blueyed
 * Implemented custom "None" option text in DataObjectCache; Added for $ItemStatusCache, $GroupCache, UserCache and BlogCache; Added custom text for Item::priority_options()
 *
 * Revision 1.7  2006/12/01 20:55:45  blueyed
 * Fixed load_Class() for $Plugins_admin
 *
 * Revision 1.6  2006/12/01 02:01:38  blueyed
 * Added "Plugins_admin" to get_Cache() + doc
 *
 * Revision 1.5  2006/11/30 22:34:15  fplanque
 * bleh
 *
 * Revision 1.4  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>