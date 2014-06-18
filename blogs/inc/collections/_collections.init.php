<?php
/**
 * This is the init file for the collections module
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
$required_php_version[ 'collections' ] = '5.0';

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
		'T_blogs'                  => $tableprefix.'blogs',
		'T_categories'             => $tableprefix.'categories',
		'T_coll_group_perms'       => $tableprefix.'bloggroups',
		'T_coll_user_perms'        => $tableprefix.'blogusers',
		'T_coll_settings'          => $tableprefix.'coll_settings',
		'T_comments'               => $tableprefix.'comments',
		'T_comments__votes'        => $tableprefix.'comments__votes',
		'T_comments__prerendering' => $tableprefix.'comments__prerendering',
		'T_items__item'            => $tableprefix.'items__item',
		'T_items__item_settings'   => $tableprefix.'items__item_settings',
		'T_items__itemtag'         => $tableprefix.'items__itemtag',
		'T_items__prerendering'    => $tableprefix.'items__prerendering',
		'T_items__status'          => $tableprefix.'items__status',
		'T_items__tag'             => $tableprefix.'items__tag',
		'T_items__type'            => $tableprefix.'items__type',
		'T_items__subscriptions'   => $tableprefix.'items__subscriptions',
		'T_items__version'         => $tableprefix.'items__version',
		'T_links'                  => $tableprefix.'links',
		'T_links__vote'            => $tableprefix.'links__vote',
		'T_postcats'               => $tableprefix.'postcats',
		'T_skins__container'       => $tableprefix.'skins__container',
		'T_skins__skin'            => $tableprefix.'skins__skin',
		'T_subscriptions'          => $tableprefix.'subscriptions',
		'T_widget'                 => $tableprefix.'widget',
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
	global $Plugins;
	global $ItemStatusCache;

	if( ! isset( $ItemStatusCache ) )
	{	// Cache doesn't exist yet:
		$Plugins->get_object_from_cacheplugin_or_create( 'ItemStatusCache', 'new GenericCache( \'GenericElement\', true, \'T_items__status\', \'pst_\', \'pst_ID\', NULL, \'\', T_(\'No status\') )' );
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
		$Plugins->get_object_from_cacheplugin_or_create( 'ItemTypeCache', 'new ItemTypeCache( \'ptyp_\', \'ptyp_ID\' )' );
	}

	return $ItemTypeCache;
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
				break;

			case 2:		// Moderators (group ID 2) have permission by default:
				$permapi = 'always';
				$permcreateblog = 'allowed';
				$permgetblog = 'denied';
				break;

			case 3:		// Trusted Users (group ID 3) have permission by default:
			case 4: 	// Normal Users (group ID 4) have permission by default:
				$permapi = 'always';
				$permcreateblog = 'denied';
				$permgetblog = 'denied';
				break;

			default:
				// Other groups have no permission by default
				$permapi = 'never';
				$permcreateblog = 'denied';
				$permgetblog = 'denied';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array( 'perm_api' => $permapi, 'perm_createblog' => $permcreateblog, 'perm_getblog' => $permgetblog );
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
		global $Blog, $blog;

		global $Settings;

		if( !$current_User->check_perm( 'admin', 'restricted' ) )
		{
			return;
		}

		$entries = array();

		// Separator
		$entries['newblog_sep'] = array(
				'separator' => true,
			);

		if( $current_User->check_perm( 'options', 'view' ) )
		{ // Menu to edit site settings
			$entries['structure'] = array(
					'text' => T_('Site structure').'&hellip;',
					'href' => $admin_url.'?ctrl=collections',
				);
		}

		// Menu to view blogs list
		$entries['blogs'] = array(
				'text' => T_('Blogs').'&hellip;',
				'href' => $admin_url.'?ctrl=collections&amp;tab=list',
			);

		$topleft_Menu->add_menu_entries( 'blog', $entries );

		if( !$current_User->check_perm( 'admin', 'normal' ) )
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
		$entries['info_sep'] = array(
								'separator' => true,
							);
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
		global $Blog;
		global $Settings;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( !$current_User->check_perm( 'admin', 'restricted' ) )
		{ // don't show these menu entries if user hasn't at least admin restricted permission
			return;
		}

		if( !$current_User->check_perm( 'admin', 'normal' ) && !$current_User->check_role( 'member' ) )
		{ // don't show these menu entries if user has only admin restricted permission, and he is not member of any blog
			return;
		}

		$AdminUI->add_menu_entries(
				NULL, // root
				array(
					'dashboard' => array(
						'text' => T_('Dashboard'),
						'href' => $admin_url.'?ctrl=dashboard&amp;blog='.$blog,
						'style' => 'font-weight: bold;'
						),

					'items' => array(
						'text' => T_('Contents'),
						'href' => $admin_url.'?ctrl=items&amp;blog='.$blog.'&amp;filter=restore',
						// Controller may add subtabs
						),
					) );
	}

  /**
	 * Builds the 2nd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_2()
	{
		global $blog, $loc_transinfo, $ctrl, $admin_url;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( !$current_User->check_perm( 'admin', 'normal' ) )
		{
			return;
		}

		// BLOG SETTINGS:
		if( $ctrl == 'collections' )
		{ // We are viewing the blog list, nothing fancy involved:
			$AdminUI->add_menu_entries(
					NULL, // root
					array(
						'blogs' => array(
							'text' => T_('Structure'),
							'href' => $admin_url.'?ctrl=collections',
						),
					), 'dashboard' );
			if( $current_User->check_perm( 'options', 'view' ) )
			{
				$AdminUI->add_menu_entries( 'blogs', array(
						'site_settings' => array(
									'text' => T_('Site Settings'),
									'href' => $admin_url.'?ctrl=collections&amp;tab=site_settings'
						),
					) );
			}
			$AdminUI->add_menu_entries( 'blogs', array(
					'list' => array(
									'text' => T_('Blogs'),
									'href' => $admin_url.'?ctrl=collections&amp;tab=list',
					),
				) );
			if( $current_User->check_perm( 'options', 'view' ) )
			{
				$AdminUI->add_menu_entries( 'blogs', array(
						'blog_settings' => array(
							'text' => T_('Blog Settings'),
							'href' => $admin_url.'?ctrl=collections&amp;tab=blog_settings',
						),
					) );
			}
		}
		else
		{	// We're on any other page, we may have a direct destination
		  // + we have subtabs (fp > maybe the subtabs should go into the controller as for _items ?)

			// What perms do we have?
			$coll_settings_perm = $current_User->check_perm( 'blog_properties', 'any', false, $blog );

			// Determine default page based on permissions:
			if( $coll_settings_perm && ! empty( $blog ) )
			{	// Default: show General Blog Settings
				$default_page = $admin_url.'?ctrl=coll_settings&amp;blog='.$blog;
			}
			else
			{	// Default: Show site settings
				$default_page = $admin_url.'?ctrl=collections';
			}

			$AdminUI->add_menu_entries(
					NULL, // root
					array(
						'blogs' => array(
							'text' => T_('Structure'),
							'href' => $default_page,
							),
						), 'dashboard' );

			if( $coll_settings_perm )
			{
				$skin_entries['current_skin'] = array(
							'text' => T_('Skins for this blog'),
							'href' => $admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$blog );

				if( $current_User->check_perm( 'options', 'view' ) )
				{
					$skin_entries['manage_skins'] = array(
							'text' => T_('Manage skins'),
							'href' => $admin_url.'?ctrl=skins&amp;blog='.$blog );
				}

				$AdminUI->add_menu_entries( 'blogs',	array(
							'general' => array(
								'text' => T_('General'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=general&amp;blog='.$blog, ),
							'features' => array(
								'text' => T_('Features'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=features&amp;blog='.$blog,
								'entries' => array(
									'home' => array(
										'text' => T_('Front page'),
										'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=home&amp;blog='.$blog ),
									'features' => array(
										'text' => T_('Posts'),
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=features&amp;blog='.$blog ),
									'comments' => array(
										'text' => T_('Comments'),
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=comments&amp;blog='.$blog ),
									'other' => array(
										'text' => T_('Other'),
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=other&amp;blog='.$blog ),
								),
							),
							'skin' => array(
								'text' => T_('Skin'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$blog,
								'entries' => $skin_entries,
							),
							'plugin_settings' => array(
								'text' => T_('Plugins'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=plugin_settings&amp;blog='.$blog, ),
							'widgets' => array(
								'text' => T_('Widgets'),
								'href' => $admin_url.'?ctrl=widgets&amp;blog='.$blog, ),
							'urls' => array(
								'text' => T_('URLs'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=urls&amp;blog='.$blog, ),
							'seo' => array(
								'text' => T_('SEO'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=seo&amp;blog='.$blog, ),
							'advanced' => array(
								'text' => T_('Advanced'),
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$blog, ),
						) );

				if( $Blog && $Blog->advanced_perms )
				{
					$AdminUI->add_menu_entries( 'blogs', array(
								'perm' => array(
									'text' => T_('User perms'), // keep label short
									'href' => $admin_url.'?ctrl=coll_settings&amp;tab=perm&amp;blog='.$blog, ),
								'permgroup' => array(
									'text' => T_('Group perms'), // keep label short
									'href' => $admin_url.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$blog, ),
							) );
				}
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
		global $Blog;
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
				) ) );

		}

	}


	/**
	 * Handle collections module htsrv actions
	 */
	function handle_htsrv_action()
	{
		global $demo_mode, $current_User, $DB, $Session, $Messages;
		global $UserSettings, $samedomain_htsrv_url;

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
				$Blog = & $LinkOwner->get_Blog();
				set_working_blog( $Blog->ID );

				// Check permission:
				$LinkOwner->check_perm( 'edit', true );

				$confirmed = param( 'confirmed', 'integer', 0 );
				if( $confirmed )
				{ // Unlink File from Item:
					$deleted_link_ID = $edited_Link->ID;
					$edited_Link->dbdelete( true );
					unset($edited_Link);

					$LinkOwner->after_unlink_action( $deleted_link_ID );

					$Messages->add( $LinkOwner->translate( 'Link has been deleted from $ownerTitle$.' ), 'success' );

					if( $current_User->check_perm( 'files', 'edit' ) )
					{ // current User has permission to edit/delete files
						$file_name = $linked_File->get_name();
						// Get number of objects where this file is attahced to
						// TODO: attila>this must be handled with a different function
						$file_links = get_file_links( $linked_File->ID, array( 'separator' => '<br />' ) );
						$links_count = ( strlen( $file_links ) > 0 ) ? substr_count( $file_links, '<br />' ) + 1 : 0;
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
					$delete_url = $samedomain_htsrv_url.'action.php?mname=collections&action=unlink&link_ID='.$edited_Link->ID.'&confirmed=1&crumb_collections_unlink='.get_crumb( 'collections_unlink' );
					$ok_button = '<span class="linkbutton"><a href="'.$delete_url.'">'.T_( 'I am sure!' ).'!</a></span>';
					$cancel_button = '<span class="linkbutton"><a href="'.$redirect_to.'">CANCEL</a></span>';
					$msg = sprintf( T_( 'You are about to unlink and delete the attached file from %s path.' ), $linked_File->get_root_and_rel_path() );
					$msg .= '<br />'.T_( 'This CANNOT be undone!').'<br />'.T_( 'Are you sure?' ).'<br /><br />'.$ok_button."\t".$cancel_button;
					$Messages->add( $msg, 'error' );
				}
				header_redirect( $redirect_to );
				break;

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