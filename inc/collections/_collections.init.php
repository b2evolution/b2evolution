<?php
/**
 * This is the init file for the collections module
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @version _collections.init.php,v 1.20 2010/05/02 19:50:50 fplanque Exp
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Make this omething useful:
 */
$default_ctrl = 'dashboard';

/**
 * Minimum PHP version required for collections module to function properly
 */
$required_php_version[ 'collections' ] = '5.2';

/**
 * Minimum MYSQL version required for collections module to function properly
 */
$required_mysql_version[ 'collections' ] = '5.0.3';

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases'] = array_merge( $db_config['aliases'], array(
		'T_blogs'                    => $tableprefix.'blogs',
		'T_categories'               => $tableprefix.'categories',
		'T_coll_group_perms'         => $tableprefix.'bloggroups',
		'T_coll_user_perms'          => $tableprefix.'blogusers',
		'T_coll_user_favs'           => $tableprefix.'coll_favs',
		'T_coll_settings'            => $tableprefix.'coll_settings',
		'T_comments'                 => $tableprefix.'comments',
		'T_comments__votes'          => $tableprefix.'comments__votes',
		'T_comments__prerendering'   => $tableprefix.'comments__prerendering',
		'T_items__item'              => $tableprefix.'items__item',
		'T_items__item_settings'     => $tableprefix.'items__item_settings',
		'T_items__itemtag'           => $tableprefix.'items__itemtag',
		'T_items__prerendering'      => $tableprefix.'items__prerendering',
		'T_items__status'            => $tableprefix.'items__status',
		'T_items__subscriptions'     => $tableprefix.'items__subscriptions',
		'T_items__tag'               => $tableprefix.'items__tag',
		'T_items__type'              => $tableprefix.'items__type',
		'T_items__type_custom_field' => $tableprefix.'items__type_custom_field',
		'T_items__type_coll'         => $tableprefix.'items__type_coll',
		'T_items__user_data'         => $tableprefix.'items__user_data',
		'T_items__version'           => $tableprefix.'items__version',
		'T_items__votes'             => $tableprefix.'items__votes',
		'T_items__status_type'       => $tableprefix.'items__status_type',
		'T_links'                    => $tableprefix.'links',
		'T_links__vote'              => $tableprefix.'links__vote',
		'T_postcats'                 => $tableprefix.'postcats',
		'T_skins__container'         => $tableprefix.'skins__container',
		'T_skins__skin'              => $tableprefix.'skins__skin',
		'T_subscriptions'            => $tableprefix.'subscriptions',
		'T_widget'                   => $tableprefix.'widget',
		'T_temporary_ID'             => $tableprefix.'temporary_ID',
	) );

/**
 * Controller mappings.
 *
 * For each controller name, we associate a controller file to be found in /inc/ .
 * The advantage of this indirection is that it is easy to reorganize the controllers into
 * subdirectories by modules. It is also easy to deactivate some controllers if you don't
 * want to provide this functionality on a given installation.
 *
 * Note: while the controller mappings might more or less follow the menu structure, we do not merge
 * the two tables since we could, at any time, decide to make a skin with a different menu structure.
 * The controllers however would most likely remain the same.
 *
 * @global array
 */
$ctrl_mappings = array_merge( $ctrl_mappings, array(
		'chapters'     => 'chapters/chapters.ctrl.php',
		'collections'  => 'collections/collections.ctrl.php',
		'coll_settings'=> 'collections/coll_settings.ctrl.php',
		'comments'     => 'comments/_comments.ctrl.php',
		'dashboard'    => 'dashboard/dashboard.ctrl.php',
		'items'        => 'items/items.ctrl.php',
		'itemstatuses' => 'items/item_statuses.ctrl.php',
		'itemtypes'    => 'items/item_types.ctrl.php',
		'itemtags'     => 'items/item_tags.ctrl.php',
		'links'        => 'links/links.ctrl.php',
		'mtimport'     => 'tools/mtimport.ctrl.php',
		'skins'        => 'skins/skins.ctrl.php',
		'tools'        => 'tools/tools.ctrl.php',
		'widgets'      => 'widgets/widgets.ctrl.php',
		'wpimportxml'  => 'tools/wpimportxml.ctrl.php',
		'phpbbimport'  => 'tools/phpbbimport.ctrl.php',
	) );


/**
 * Get the BlogCache
 *
 * @param string Name of the order field or NULL to use name field
 * @return BlogCache
 */
function & get_BlogCache( $order_by = 'blog_order' )
{
	global $BlogCache;

	if( ! isset( $BlogCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'collections/model/_blogcache.class.php', 'BlogCache' );
		$BlogCache = new BlogCache( $order_by ); // COPY (FUNC)
	}

	return $BlogCache;
}


/**
 * Get the ChapterCache
 *
 * @return ChapterCache
 */
function & get_ChapterCache()
{
	global $ChapterCache;

	if( ! isset( $ChapterCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'chapters/model/_chaptercache.class.php', 'ChapterCache' );
		$ChapterCache = new ChapterCache(); // COPY (FUNC)
	}

	return $ChapterCache;
}


/**
 * Get the ItemCacheLight
 *
 * @return ItemCacheLight
 */
function & get_ItemCacheLight()
{
	global $ItemCacheLight;

	if( ! isset( $ItemCacheLight ) )
	{	// Cache doesn't exist yet:
		$ItemCacheLight = new DataObjectCache( 'ItemLight', false, 'T_items__item', 'post_', 'post_ID' ); // COPY (FUNC)
	}

	return $ItemCacheLight;
}

/**
 * Get the ItemCache
 *
 * @return ItemCache
 */
function & get_ItemCache()
{
	global $ItemCache;

	if( ! isset( $ItemCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'items/model/_itemcache.class.php', 'ItemCache' );
		$ItemCache = new ItemCache(); // COPY (FUNC)
	}

	return $ItemCache;
}

/**
 * Get the ItemPrerenderingCache
 *
 * @return ItemPrerenderingCache
 */
function & get_ItemPrerenderingCache()
{
	global $ItemPrerenderingCache;

	if( ! isset( $ItemPrerenderingCache ) )
	{	// Cache doesn't exist yet:
		$ItemPrerenderingCache = array();
	}

	return $ItemPrerenderingCache;
}

/**
 * Get the ItemTagsCache
 *
 * @return ItemTagsCache
 */
function & get_ItemTagsCache()
{
	global $ItemTagsCache;

	if( ! isset( $ItemTagsCache ) )
	{	// Cache doesn't exist yet:
		$ItemTagsCache = array();
	}

	return $ItemTagsCache;
}

/**
 * Get the ItemStatusCache
 *
 * @return ItemStatusCache
 */
function & get_ItemStatusCache()
{
	global $ItemStatusCache;

	if( ! isset( $ItemStatusCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'items/model/_itemstatus.class.php', 'ItemStatus' );
		$ItemStatusCache = new DataObjectCache( 'ItemStatus', false, 'T_items__status', 'pst_', 'pst_ID', 'pst_name', 'pst_name', NT_('No status') );
	}

	return $ItemStatusCache;
}

/**
 * Get the ItemTypeCache
 *
 * @return ItemTypeCache
 */
function & get_ItemTypeCache()
{
	global $Plugins;
	global $ItemTypeCache;

	if( ! isset( $ItemTypeCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'items/model/_itemtypecache.class.php', 'ItemTypeCache' );
		$Plugins->get_object_from_cacheplugin_or_create( 'ItemTypeCache', 'new ItemTypeCache( \'ityp_\', \'ityp_ID\' )' );
	}

	return $ItemTypeCache;
}

/**
 * Get the ItemTagCache
 *
 * @return ItemTagCache
 */
function & get_ItemTagCache()
{
	global $ItemTagCache;

	if( ! isset( $ItemTagCache ) )
	{ // Cache doesn't exist yet:
		load_class( 'items/model/_itemtag.class.php', 'ItemTag' );
		$ItemTagCache = new DataObjectCache( 'ItemTag', false, 'T_items__tag', 'tag_', 'tag_ID', 'tag_name' );
	}

	return $ItemTagCache;
}

/**
 * Get the CommentCache
 *
 * @return CommentCache
 */
function & get_CommentCache()
{
	global $Plugins;
	global $CommentCache;

	if( ! isset( $CommentCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'comments/model/_commentcache.class.php', 'CommentCache' );
		$Plugins->get_object_from_cacheplugin_or_create( 'CommentCache', 'new CommentCache()' );
	}

	return $CommentCache;
}

/**
 * Get the CommentPrerenderingCache
 *
 * @return CommentPrerenderingCache
 */
function & get_CommentPrerenderingCache()
{
	global $CommentPrerenderingCache;

	if( ! isset( $CommentPrerenderingCache ) )
	{	// Cache doesn't exist yet:
		$CommentPrerenderingCache = array();
	}

	return $CommentPrerenderingCache;
}


/**
 * Get the LinkCache
 *
 * @return LinkCache
 */
function & get_LinkCache()
{
	global $LinkCache;

	if( ! isset( $LinkCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'links/model/_linkcache.class.php', 'LinkCache' );
		$LinkCache = new LinkCache(); // COPY (FUNC)
	}

	return $LinkCache;
}


/**
 * Get the TemporaryIDCache
 *
 * @return TemporaryIDCache
 */
function & get_TemporaryIDCache()
{
	global $TemporaryIDCache;

	if( ! isset( $TemporaryIDCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'links/model/_temporaryid.class.php', 'TemporaryID' );
		$TemporaryIDCache = new DataObjectCache( 'TemporaryID', false, 'T_temporary_ID', 'tmp_', 'tmp_ID', 'tmp_ID', 'tmp_ID' );
	}

	return $TemporaryIDCache;
}


/**
 * Get the SkinCache
 *
 * @return SkinCache
 */
function & get_SkinCache()
{
	global $SkinCache;

	if( ! isset( $SkinCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'skins/model/_skincache.class.php', 'SkinCache' );
		$SkinCache = new SkinCache(); // COPY (FUNC)
	}

	return $SkinCache;
}


/**
 * Get the WidgetCache
 *
 * @return WidgetCache
 */
function & get_WidgetCache()
{
	global $WidgetCache;

	if( ! isset( $WidgetCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'widgets/model/_widgetcache.class.php', 'WidgetCache' );
		$WidgetCache = new WidgetCache(); // COPY (FUNC)
	}

	return $WidgetCache;
}

/**
 * Get the EnabledWidgetCache
 *
 * @return EnabledWidgetCache
 */
function & get_EnabledWidgetCache()
{
	global $EnabledWidgetCache;

	if( ! isset( $EnabledWidgetCache ) )
	{	// Cache doesn't exist yet:
		// This simply instantiates a WidgetCache object, setting the
		// $enabled_only parameter to true. Using a member variable
		// instead of per-method parameters to load only the enabled
		// widgets should be cleaner when there will be more methods
		// in the WidgetCache class in the future.
		load_class( 'widgets/model/_widgetcache.class.php', 'WidgetCache' );
		$EnabledWidgetCache = new WidgetCache( true );
	}

	return $EnabledWidgetCache;
}


/**
 * adsense_Module definition
 */
class collections_Module extends Module
{
	/**
	 * Do the initializations. Called from in _main.inc.php.
	 * This is typically where classes matching DB tables for this module are registered/loaded.
	 *
	 * Note: this should only load/register things that are going to be needed application wide,
	 * for example: for constructing menus.
	 * Anything that is needed only in a specific controller should be loaded only there.
	 * Anything that is needed only in a specific view should be loaded only there.
	 */
	function init()
	{
		$this->check_required_php_version( 'collections' );

		load_class( 'collections/model/_blog.class.php', 'Blog' );
		load_funcs( 'collections/model/_blog.funcs.php' );
		load_funcs( 'collections/model/_category.funcs.php' );
		load_funcs( 'items/model/_item.funcs.php' );
		load_class( 'items/model/_itemtype.class.php', 'ItemType' );
		load_class( 'links/model/_link.class.php', 'Link' );
		load_funcs( 'links/model/_link.funcs.php' );
		load_funcs( 'comments/model/_comment.funcs.php');
		load_class( 'comments/model/_commentlist.class.php', 'CommentList2' );
		load_class( 'items/model/_itemquery.class.php', 'ItemQuery' );
		load_class( 'comments/model/_commentquery.class.php', 'CommentQuery' );
	}

	/**
	 * Get default module permissions
	 *
	 * @param integer Group ID
	 * @return array
	 */
	function get_default_group_permissions( $grp_ID )
	{
		switch( $grp_ID )
		{
			case 1:		// Administrators (group ID 1) have permission by default:
				$permapi = 'always'; // Can use APIs
				$permcreateblog = 'allowed'; // Creating new blogs
				$permgetblog = 'denied'; // Automatically add a new blog to the new users
				$permmaxcreateblognum = '';
				break;

			case 2:		// Moderators (group ID 2) have permission by default:
				$permapi = 'always';
				$permcreateblog = 'allowed';
				$permgetblog = 'denied';
				$permmaxcreateblognum = '';
				break;

			case 3:		// Editors (group ID 3) have permission by default:
			case 4: 	// Normal Users (group ID 4) have permission by default:
				$permapi = 'always';
				$permcreateblog = 'denied';
				$permgetblog = 'denied';
				$permmaxcreateblognum = '';
				break;

			default:
				// Other groups have no permission by default
				$permapi = 'never';
				$permcreateblog = 'denied';
				$permgetblog = 'denied';
				$permmaxcreateblognum = '';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array(
				'perm_api' => $permapi,
				'perm_createblog' => $permcreateblog,
				'perm_getblog' => $permgetblog,
				'perm_max_createblog_num' => $permmaxcreateblognum
				);
	}


	/**
	 * Get available group permissions
	 *
	 * @return array
	 */
	function get_available_group_permissions()
	{
		// 'label' is used in the group form as label for radio buttons group
		// 'user_func' function used to check user permission. This function should be defined in Module.
		// 'group_func' function used to check group permission. This function should be defined in Module.
		// 'perm_block' group form block where this permissions will be displayed. Now available, the following blocks: additional, system
		// 'options' is permission options
		$permissions = array(
			'perm_api' => array(
				'label' => T_('Can use APIs'),
				'user_func'  => 'check_api_user_perm',
				'group_func' => 'check_api_group_perm',
				'perm_block' => 'blogging',
				'options'  => array(
						// format: array( radio_button_value, radio_button_label, radio_button_note )
						array( 'never', T_( 'Never' ), '' ),
						array( 'always', T_( 'Always' ), '' ),
					),
				),
			'perm_createblog' => array(
				'label' => T_( 'Creating new blogs' ),
				'user_func'  => 'check_createblog_user_perm',
				'group_func' => 'check_createblog_group_perm',
				'perm_block' => 'blogging',
				'perm_type' => 'checkbox',
				'note' => T_( 'Users can create new blogs for themselves'),
				),
			'perm_getblog' => array(
				'label' => '',
				'user_func'  => 'check_getblog_user_perm',
				'group_func' => 'check_getblog_group_perm',
				'perm_block' => 'blogging',
				'perm_type' => 'checkbox',
				'note' => T_( 'New users automatically get a new blog'),
				),
			'perm_max_createblog_num' => array(
				'label' => T_('Maximum collections'),
				'user_func' => 'check_createblog_user_perm',
				'group_funct' => 'check_createblog_group_perm',
				'perm_block' => 'blogging',
				'perm_type' => 'text_input',
				'maxlength' => 2,
				'note' => T_('Users will not be able to create collections if they already own the maximum number of collections (or more).'),
				),
			);
		return $permissions;
	}


	/**
	 * Check a permission for the user. ( see 'user_func' in get_available_group_permissions() function  )
	 *
	 * @param string Requested permission level
	 * @param string Permission value
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_api_user_perm( $permlevel, $permvalue, $permtarget )
	{
		return true;
	}


	/**
	 * Check a permission for the group. ( see 'group_func' in get_available_group_permissions() function )
	 *
	 * @param string Requested permission level
	 * @param string Permission value
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_api_group_perm( $permlevel, $permvalue, $permtarget )
	{
		$perm = false;
		switch ( $permvalue )
		{
			case 'always':
				// Users can use APIs
				if( $permlevel == 'always' )
				{
					$perm = true;
					break;
				}

			case 'never':
				// Users can`t use APIs
				if( $permlevel == 'never' )
				{
					$perm = false;
					break;
				}
		}

		return $perm;
	}


	function check_createblog_group_perm( $permlevel, $permvalue, $permtarget )
	{
		return $permvalue == 'allowed';
	}

	function check_getblog_group_perm( $permlevel, $permvalue, $permtarget )
	{
		return $permvalue == 'allowed';
	}


	/**
	 * Build teh evobar menu
	 */
	function build_evobar_menu()
	{
		/**
		 * @var Menu
		 */
		global $topleft_Menu, $topright_Menu;
		global $current_User;
		global $home_url, $admin_url, $debug, $seo_page_type, $robots_index;
		global $Collection, $Blog, $blog;

		global $Settings;

		if( ! $current_User->check_perm( 'admin', 'normal' ) )
		{
			return;
		}

		$entries = array();
		$entries['b2evonet'] = array(
								'text' => T_('Open b2evolution.net'),
								'href' => 'http://b2evolution.net/',
								'target' => '_blank',
							);
		$entries['forums'] = array(
								'text' => T_('Open Support forums'),
								'href' => 'http://forums.b2evolution.net/',
								'target' => '_blank',
							);
		$entries['manual'] = array(
								'text' => T_('Open Online manual'),
								'href' => get_manual_url( NULL ),
								'target' => '_blank',
							);
		$entries[] = array( 'separator' => true );
		$entries['twitter'] = array(
								'text' => T_('b2evolution on twitter'),
								'href' => 'http://twitter.com/b2evolution',
								'target' => '_blank',
							);
		$entries['facebook'] = array(
								'text' => T_('b2evolution on facebook'),
								'href' => 'http://www.facebook.com/b2evolution',
								'target' => '_blank',
							);

		$topleft_Menu->add_menu_entries( 'b2evo', $entries );
	}


	/**
	 * Builds the 1st half of the menu. This is the one with the most important features
	 */
	function build_menu_1()
	{
		global $blog, $admin_url;
		/**
		 * @var User
		 */
		global $current_User;
		global $Collection, $Blog;
		global $Settings;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( ! $current_User->check_perm( 'admin', 'restricted' ) )
		{ // don't show these menu entries if user hasn't at least admin restricted permission
			return;
		}

		$perm_admin_normal = $current_User->check_perm( 'admin', 'normal' );

		$site_menu = array(
			'text' => T_('Site'),
			'href' => $admin_url.'?ctrl=dashboard',
			'entries' => array(
				'dashboard' => array(
					'text' => T_('Site Dashboard'),
					'href' => $admin_url.'?ctrl=dashboard' ),
				)
		);
		if( $perm_admin_normal )
		{ // User has an access to backoffice
			if( $current_User->check_perm( 'options', 'view' ) )
			{ // User has an access to view settings
				$site_menu['entries']['settings'] = array(
					'text' => T_('Site Settings'),
					'href' => $admin_url.'?ctrl=collections&amp;tab=site_settings'
				);
			}
			if( $current_User->check_perm( 'slugs', 'view' ) )
			{ // User has an access to view slugs
				$site_menu['entries']['slugs'] = array(
					'text' => T_('Slugs'),
					'href' => $admin_url.'?ctrl=slugs'
				);
			}
			if( $current_User->check_perm( 'options', 'view' ) )
			{ // User has an access to view settings
				$site_menu['entries']['tags'] = array(
					'text' => T_('Tags'),
					'href' => $admin_url.'?ctrl=itemtags'
				);
			}
		}

		$working_blog = get_working_blog();
		if( $working_blog )
		{ // User is member of some blog or has at least view perms, so Dashboard and Collections menus should be visible
			$AdminUI->add_menu_entries(
				NULL, // root
				array(
					'site' => $site_menu,
					'collections' => array(
						'text' => T_('Collections'),
						'href' => $admin_url.'?ctrl=coll_settings&tab=dashboard&blog='.$working_blog
					)
				)
			);
		}
		elseif( $perm_admin_normal )
		{ // User is not member of any blogs, but has admin normal permission. Only the dashboard menu ( no Collections ) should be visible.
			$AdminUI->add_menu_entries(
				NULL, // root
				array( 'site' => $site_menu )
			);
		}
	}


	/**
	 * Builds the 2nd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_2()
	{
		global $loc_transinfo, $ctrl, $admin_url;
		/**
		 * @var User
		 */
		global $current_User;
		global $Collection, $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		$blog = get_working_blog();
		if( ! $blog )
		{ // No available blogs for current user
			return;
		}

		// Collection Dashboard
		$collection_menu_entries = array(
				'dashboard' => array(
					'text' => T_('Collection Dashboard'),
					'href' => $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog='.$blog,
					'order' => 'group_last' ),
			);

		$perm_comments = $current_User->check_perm( 'blog_comments', 'view', false, $blog );
		$perm_cats = $current_User->check_perm( 'blog_cats', '', false, $blog );

		// Posts
		$collection_menu_entries['posts'] = array(
				'text' => T_('Posts'),
				'href' => $admin_url.'?ctrl=items&amp;tab=full&amp;filter=restore&amp;blog='.$blog,
			);
		$last_group_menu_entry = 'posts';

		if( $perm_comments || $current_User->check_perm( 'meta_comment', 'view', false, $blog ) )
		{	// Initialize comments menu tab if user can view normal or meta comments of the collection:
			$collection_menu_entries['comments'] = array(
					'text' => T_('Comments'),
					'href' => $admin_url.'?ctrl=comments&amp;blog='.$blog.'&amp;filter=restore'
						// Set url to meta comments page if user has a perm to view only meta comments:
						.( $perm_comments ? '' : '&amp;tab3=meta' ),
				);
			$last_group_menu_entry = 'comments';
		}

		if( $perm_cats )
		{ // Categories
			$collection_menu_entries['categories'] = array(
				'text' => T_('Categories'),
				'href' => $admin_url.'?ctrl=chapters&amp;blog='.$blog
			);
			$last_group_menu_entry = 'categories';
		}

		// Mark last menu entry in group
		$collection_menu_entries[ $last_group_menu_entry ]['order'] = 'group_last';

		$AdminUI->add_menu_entries( 'collections', $collection_menu_entries );

		if( $current_User->check_perm( 'blog_properties', 'edit', false, $blog ) )
		{ // Display these menus only when some blog is selected and current user has an access to edit the blog properties

			// BLOG SETTINGS:

			// We're on any other page, we may have a direct destination
			// + we have subtabs (fp > maybe the subtabs should go into the controller as for _items ?)

			$AdminUI->add_menu_entries( 'collections', array(
					'features' => array(
						'text' => T_('Features'),
						'href' => $admin_url.'?ctrl=coll_settings&amp;tab=home&amp;blog='.$blog,
						'entries' => array(
							'home' => array(
								'text' => T_('Front page'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=home&amp;blog='.$blog ),
							'features' => array(
								'text' => T_('Posts'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=features&amp;blog='.$blog ),
							'comments' => array(
								'text' => T_('Comments'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=comments&amp;blog='.$blog ),
							'userdir' => array(
								'text' => T_('User directory'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=userdir&amp;blog='.$blog ),
							'other' => array(
								'text' => T_('Other displays'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=other&amp;blog='.$blog ),
							'more' => array(
								'text' => T_('More'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=more&amp;blog='.$blog ),
						),
					),
					'skin' => array(
						'text' => T_('Skin'),
						'href' => $admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$blog,
						'entries' => array(
							'current_skin' => array(
								'text' => T_('Skins for this blog'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$blog
							)
						),
					),
					'widgets' => array(
						'text' => T_('Widgets'),
						'href' => $admin_url.'?ctrl=widgets&amp;blog='.$blog,
						'order' => 'group_last', ),
					'settings' => array(
						'text' => T_('Settings'),
						'href' => $admin_url.'?ctrl=coll_settings&amp;tab=general&amp;blog='.$blog,
						'entries' => array(
							'general' => array(
								'text' => T_('General'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=general&amp;blog='.$blog, ),
							'urls' => array(
								'text' => T_('URLs'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=urls&amp;blog='.$blog, ),
							'seo' => array(
								'text' => T_('SEO'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=seo&amp;blog='.$blog, ),
							'plugins' => array(
								'text' => T_('Plugins'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=plugins&amp;blog='.$blog, ),
						),
					),
				) );

			if( $current_User->check_perm( 'options', 'view' ) )
			{ // Manage skins
				$AdminUI->add_menu_entries( array( 'collections', 'skin' ), array(
					'manage_skins' => array(
						'text' => T_('Manage skins'),
						'href' => $admin_url.'?ctrl=skins&amp;blog='.$blog )
				) );
			}

			if( $current_User->check_perm( 'options', 'view', false, $blog ) )
			{ // Post Types & Statuses
				$AdminUI->add_menu_entries( array( 'collections', 'settings' ), array(
					'types' => array(
						'text' => T_('Post Types'),
						'title' => T_('Post Types Management'),
						'href' => $admin_url.'?ctrl=itemtypes&amp;tab=settings&amp;tab3=types&amp;blog='.$blog
						),
					'statuses' => array(
						'text' => T_('Post Statuses'),
						'title' => T_('Post Statuses Management'),
						'href' => $admin_url.'?ctrl=itemstatuses&amp;tab=settings&amp;tab3=statuses&amp;blog='.$blog
						),
					)
				);
			}

			$AdminUI->add_menu_entries( array( 'collections', 'settings' ), array(
					'advanced' => array(
						'text' => T_('Advanced'),
						'href' => $admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$blog, ),
				) );

			if( $Blog && $Blog->advanced_perms )
			{ // Permissions
				$AdminUI->add_menu_entries( array( 'collections', 'settings' ), array(
						'perm' => array(
							'text' => T_('User perms'), // keep label short
							'href' => $admin_url.'?ctrl=coll_settings&amp;tab=perm&amp;blog='.$blog, ),
						'permgroup' => array(
							'text' => T_('Group perms'), // keep label short
							'href' => $admin_url.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$blog, ),
					) );
			}

			if( $current_User->check_perm( 'options', 'view' ) )
			{ // Check if current user has a permission to view the common settings of the blogs
				$AdminUI->add_menu_entries( array( 'collections', 'settings' ), array(
					'blog_settings' => array(
						'text' => T_('Common Settings'),
						'href' => $admin_url.'?ctrl=collections&amp;tab=blog_settings&amp;blog='.$blog,
						)
					)
				);
			}
		}
	}


	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
		global $blog, $loc_transinfo, $ctrl, $admin_url;
		/**
		 * @var User
		 */
		global $current_User;
		global $Collection, $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( !$current_User->check_perm( 'admin', 'normal' ) )
		{
			return;
		}

		if( $current_User->check_perm( 'options', 'view' ) )
		{	// Permission to view settings:
			$AdminUI->add_menu_entries( 'options', array(
					'misc' => array(
						'text' => T_('Maintenance'),
						'href' => $admin_url.'?ctrl=tools',
						'entries' => array(
							'tools' => array(
								'text' => T_('Tools'),
								'href' => $admin_url.'?ctrl=tools' ),
							'import' => array(
								'text' => T_('Import'),
								'href' => $admin_url.'?ctrl=tools&amp;tab3=import' ),
							'test' => array(
								'text' => T_('Testing'),
								'href' => $admin_url.'?ctrl=tools&amp;tab3=test' ),
							'backup' => array(
								'text' => T_('Backup'),
								'href' => $admin_url.'?ctrl=backup' ),
							'upgrade' => array(
								'text' => T_('Check for updates'),
								'href' => $admin_url.'?ctrl=upgrade' ),
							),
				) ), 'remotepublish' );

		}

	}


	/**
	 * Get the collections module cron jobs
	 *
	 * @see Module::get_cron_jobs()
	 */
	function get_cron_jobs()
	{
		return array(
			'create-post-by-email' => array(
				'name'   => T_('Create posts by email'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_post_by_email.job.php',
				'params' => NULL,
			),
			'send-comment-notifications' => array( // not user schedulable
				'name'   => T_('Send notifications about new comment on &laquo;%s&raquo;'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_comment_notifications.job.php',
				'params' => NULL, // 'comment_ID', 'executed_by_userid', 'is_new_comment', 'already_notified_user_IDs', 'force_members', 'force_community'
			),
			'send-post-notifications' => array( // not user schedulable
				'name'   => T_('Send notifications for &laquo;%s&raquo;'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_post_notifications.job.php',
				'params' => NULL, // 'item_ID', 'executed_by_userid', 'is_new_item', 'already_notified_user_IDs', 'force_members', 'force_community', 'force_pings'
			),
			'send-email-campaign' => array( // not user schedulable
				'name'   => T_('Send a chunk of %s emails for the campaign "%s"'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_email_campaign.job.php',
				'params' => NULL, // 'ecmp_ID'
			),
			'send-unmoderated-comments-reminders' => array(
				'name'   => T_('Send reminders about comments awaiting moderation'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_comment_moderation_reminder.job.php',
				'params' => NULL,
			),
			'send-unmoderated-posts-reminders' => array(
				'name'   => T_('Send reminders about posts awaiting moderation'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_post_moderation_reminder.job.php',
				'params' => NULL,
			),
			'monthly-alert-old-contents' => array(
				'name'   => T_('Monthly alert on old contents'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_monthly_alert_old_contents.job.php',
				'params' => NULL,
			),
		);
	}


	/**
	 * Handle collections module htsrv actions
	 */
	function handle_htsrv_action()
	{
		global $demo_mode, $current_User, $DB, $Session, $Messages;
		global $UserSettings;

		if( !is_logged_in() )
		{ // user must be logged in
			bad_request_die( $this->T_( 'You are not logged in.' ) );
		}

		// Init the objects we want to work on.
		$action = param_action( true );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collections_'.$action );

		switch( $action )
		{
			case 'unlink':
				// Unlink a file from a LinkOwner ( Item, Comment ) object, and delete that file if it's not linked to any other object

				$link_ID = param( 'link_ID', 'integer', true );
				$redirect_to = param( 'redirect_to', 'url', '' );
				$LinkCache = & get_LinkCache();
				$edited_Link = & $LinkCache->get_by_ID( $link_ID, false );

				if( !$edited_Link )
				{ // the edited Link object doesn't exists
					$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Link') ), 'error' );
					header_redirect();
				}

				// We have a link, get the LinkOwner it is attached to:
				$LinkOwner = & $edited_Link->get_LinkOwner();
				$linked_File = & $edited_Link->get_File();

				// Load the blog we're in:
				$Collection = $Blog = & $LinkOwner->get_Blog();
				set_working_blog( $Blog->ID );

				// Check permission:
				$LinkOwner->check_perm( 'edit', true );

				if( $current_User->check_perm( 'files', 'edit' ) )
				{	// If current User has permission to edit/delete files:
					// Get number of objects where this file is attached to:
					// TODO: attila>this must be handled with a different function
					$file_links = get_file_links( $linked_File->ID, array( 'separator' => '<br />' ) );
					$links_count = ( strlen( $file_links ) > 0 ) ? substr_count( $file_links, '<br />' ) + 1 : 0;
				}

				$confirmed = param( 'confirmed', 'integer', 0 );
				if( $confirmed )
				{ // Unlink File from Item:
					$deleted_link_ID = $edited_Link->ID;
					$edited_Link->dbdelete();
					unset($edited_Link);

					$LinkOwner->after_unlink_action( $deleted_link_ID );

					$Messages->add( $LinkOwner->translate( 'Link has been deleted from $xxx$.' ), 'success' );

					if( $current_User->check_perm( 'files', 'edit' ) )
					{ // current User has permission to edit/delete files
						$file_name = $linked_File->get_name();
						$links_count--;
						if( $links_count > 0 )
						{ // File is linked to other objects
							$Messages->add( sprintf( T_('File %s is still linked to %d other objects'), $file_name, $links_count ), 'note' );
						}
						else
						{ // File is not linked to other objects
							if( $linked_File->unlink() )
							{ // File removed successful ( removed from db and from storage device also )
								$Messages->add( sprintf( T_('File %s has been deleted.'), $file_name ), 'success' );
							}
							else
							{ // Could not completly remove the file
								$Messages->add( sprintf( T_('File %s could not be deleted.'), $file_name ), 'error' );
							}
						}
					}
				}
				else
				{ // Display confirm unlink/delete message
					$delete_url = get_htsrv_url().'action.php?mname=collections&action=unlink&link_ID='.$edited_Link->ID.'&confirmed=1&crumb_collections_unlink='.get_crumb( 'collections_unlink' );
					$ok_button = '<a href="'.$delete_url.'" class="btn btn-danger">'.T_('I am sure!').'</a>';
					$cancel_button = '<a href="'.$redirect_to.'" class="btn btn-default">'.T_('CANCEL').'</a>';
					if( isset( $links_count ) && $links_count == 1 )
					{	// If the file will be deleted after confirmation:
						$msg = sprintf( T_( 'You are about to unlink and delete the attached file from %s path.' ), $linked_File->get_root_and_rel_path() );
					}
					else
					{	// If the file will be only unlinked after confirmation because it is also attached to other objects:
						$msg = sprintf( T_( 'You are about to unlink the attached file %s.' ), $linked_File->get_root_and_rel_path() );
					}
					$msg .= '<br />'.T_( 'This CANNOT be undone!').'<br />'.T_( 'Are you sure?' ).'<br /><br />'.$ok_button."\t".$cancel_button;
					$Messages->add( $msg, 'error' );
				}
				header_redirect( $redirect_to );
				break;

			case 'subs_update':
				// Subscribe/Unsubscribe user on the selected collection

				if( $demo_mode && ( $current_User->ID <= 3 ) )
				{ // don't allow default users profile change on demo mode
					bad_request_die( 'Demo mode: you can\'t edit the admin and demo users profile!<br />[<a href="javascript:history.go(-1)">'
								. T_('Back to profile') . '</a>]' );
				}

				// Get params
				$blog = param( 'subscribe_blog', 'integer', true );
				$notify_items = param( 'sub_items', 'integer', NULL );
				$notify_comments = param( 'sub_comments', 'integer', NULL );

				if( ( $notify_items < 0 ) || ( $notify_items > 1 ) || ( $notify_comments < 0 ) || ( $notify_comments > 1 ) )
				{ // Invalid notify param. It should be 0 for unsubscribe and 1 for subscribe.
					$Messages->add( 'Invalid params!', 'error' );
				}

				if( ! is_email( $current_User->get( 'email' ) ) )
				{ // user doesn't have a valid email address
					$Messages->add( T_( 'Your email address is invalid. Please set your email address first.' ), 'error' );
				}

				if( $Messages->has_errors() )
				{ // errors detected
					header_redirect();
					// already exited here
				}

				if( set_user_subscription( $current_User->ID, $blog, $notify_items, $notify_comments ) )
				{
					if( $notify_items === 0 )
					{
						$Messages->add( T_( 'You have successfully unsubscribed to new posts notifications.' ), 'success' );
					}
					elseif( $notify_items === 1 )
					{
						$Messages->add( T_( 'You have successfully subscribed to new posts notifications.' ), 'success' );
					}

					if( $notify_comments === 0 )
					{
						$Messages->add( T_( 'You have successfully unsubscribed to new comments notifications.' ), 'success' );
					}
					elseif( $notify_comments === 1 )
					{
						$Messages->add( T_( 'You have successfully subscribed to new comments notifications.' ), 'success' );
					}
				}
				else
				{
					$Messages->add( T_( 'Could not subscribe to notifications.' ), 'error' );
				}

				header_redirect();
				break; // already exited here

			case 'isubs_update':
				// Subscribe/Unsubscribe user on the selected item

				if( $demo_mode && ( $current_User->ID <= 3 ) )
				{ // don't allow default users profile change on demo mode
					bad_request_die( 'Demo mode: you can\'t edit the admin and demo users profile!<br />[<a href="javascript:history.go(-1)">'
								. T_('Back to profile') . '</a>]' );
				}

				// Get params
				$item_ID = param( 'p', 'integer', true );
				$notify = param( 'notify', 'integer', 0 );

				if( ( $notify < 0 ) || ( $notify > 1 ) )
				{ // Invalid notify param. It should be 0 for unsubscribe and 1 for subscribe.
					$Messages->add( 'Invalid params!', 'error' );
				}

				if( ! is_email( $current_User->get( 'email' ) ) )
				{ // user doesn't have a valid email address
					$Messages->add( T_( 'Your email address is invalid. Please set your email address first.' ), 'error' );
				}

				if( $Messages->has_errors() )
				{ // errors detected
					header_redirect();
					// already exited here
				}

				if( set_user_isubscription( $current_User->ID, $item_ID, $notify ) )
				{
					if( $notify == 0 )
					{
						$Messages->add( T_( 'You have successfully unsubscribed.' ), 'success' );
					}
					else
					{
						$Messages->add( T_( 'You have successfully subscribed to notifications.' ), 'success' );
					}
				}
				else
				{
					$Messages->add( T_( 'Could not subscribe to notifications.' ), 'error' );
				}

				header_redirect();
				break; // already exited here
		}
	}
}

$collections_Module = new collections_Module();

?>