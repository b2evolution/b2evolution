<?php
/**
 * This is the init file for the billing module
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Make this omething useful:
 */
$default_ctrl = 'dashboard';


/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases'] = array_merge( $db_config['aliases'], array(
		'T_blogs'               => $tableprefix.'blogs',
		'T_categories'          => $tableprefix.'categories',
		'T_coll_group_perms'    => $tableprefix.'bloggroups',
		'T_coll_user_perms'     => $tableprefix.'blogusers',
		'T_coll_settings'       => $tableprefix.'coll_settings',
		'T_comments'            => $tableprefix.'comments',
		'T_items__item'         => $tableprefix.'items__item',
		'T_items__itemtag'      => $tableprefix.'items__itemtag',
		'T_items__prerendering' => $tableprefix.'items__prerendering',
		'T_items__status'       => $tableprefix.'items__status',
		'T_items__tag'          => $tableprefix.'items__tag',
		'T_items__type'         => $tableprefix.'items__type',
		'T_items__version'      => $tableprefix.'items__version',
		'T_links'               => $tableprefix.'links',
		'T_postcats'            => $tableprefix.'postcats',
		'T_skins__container'    => $tableprefix.'skins__container',
		'T_skins__skin'         => $tableprefix.'skins__skin',
		'T_subscriptions'       => $tableprefix.'subscriptions',
		'T_widget'              => $tableprefix.'widget',
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
		'mtimport'     => 'tools/mtimport.ctrl.php',
		'skins'        => 'skins/skins.ctrl.php',
		'tools'        => 'tools/tools.ctrl.php',
		'widgets'      => 'widgets/widgets.ctrl.php',
		'wpimport'     => 'tools/wpimport.ctrl.php',
	) );


/**
 * adsense_Module definition
 */
class collections_Module
{
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
		global $home_url, $admin_url, $dispatcher, $debug, $seo_page_type, $robots_index;
		global $Blog, $blog;

		global $Settings;

		$entries = array();

		if( $current_User->check_perm( 'blogs', 'create' ) )
		{
			$entries['entries']['newblog'] = array(
					'text' => T_('Create new blog').'&hellip;',
					'href' => $admin_url.'?ctrl=collections&amp;action=new',
				);
			$entries['entries'][] = array(
					'separator' => true,
				);
		}

		$entries['entries']['info'] = array(
				'text' => T_('More info'),
				'entries' => array(
						'b2evonet' => array(
								'text' => T_('Open b2evolution.net'),
								'href' => 'http://b2evolution.net/',
								'target' => '_blank',
							),
						'forums' => array(
								'text' => T_('Open Support forums'),
								'href' => 'http://forums.b2evolution.net/',
								'target' => '_blank',
							),
						'manual' => array(
								'text' => T_('Open Online manual'),
								'href' => 'http://manual.b2evolution.net/',
								'target' => '_blank',
							),
						'info_sep' => array(
								'separator' => true,
							),
						'twitter' => array(
								'text' => T_('b2evolution on twitter'),
								'href' => 'http://twitter.com/b2evolution',
								'target' => '_blank',
							),
						'facebook' => array(
								'text' => T_('b2evolution on facebook'),
								'href' => 'http://www.facebook.com/b2evolution',
								'target' => '_blank',
							),
						),
				);

		$topleft_Menu->add_menu_entries( 'b2evo', $entries );
	}


	/**
	 * Builds the 1st half of the menu. This is the one with the most important features
	 */
	function build_menu_1()
	{
		global $blog, $dispatcher;
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

		$AdminUI->add_menu_entries(
				NULL, // root
				array(
					'dashboard' => array(
						'text' => T_('Dashboard'),
						'href' => $dispatcher.'?ctrl=dashboard&amp;blog='.$blog,
						'style' => 'font-weight: bold;'
						),

					'items' => array(
						'text' => T_('Posts / Comments'),
						'href' => $dispatcher.'?ctrl=items&amp;blog='.$blog.'&amp;filter=restore',
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
		global $blog, $loc_transinfo, $ctrl, $dispatcher;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		// BLOG SETTINGS:
		if( $ctrl == 'collections' )
		{ // We are viewing the blog list, nothing fancy involved:
			$AdminUI->add_menu_entries(
					NULL, // root
					array(
						'blogs' => array(
							'text' => T_('Blog settings'),
							'href' => $dispatcher.'?ctrl=collections',
						),
					) );
		}
		else
		{	// We're on any other page, we may have a direct destination
		  // + we have subtabs (fp > maybe the subtabs should go into the controller as for _items ?)

			// What perms do we have?
			$coll_settings_perm = $current_User->check_perm( 'blog_properties', 'any', false, $blog );
			$coll_chapters_perm = $current_User->check_perm( 'blog_cats', '', false, $blog );

			// Determine default page based on permissions:
			if( $coll_settings_perm )
			{	// Default: show General Blog Settings
				$default_page = $dispatcher.'?ctrl=coll_settings&amp;tab=general&amp;blog='.$blog;
			}
			elseif( $coll_chapters_perm )
			{	// Default: show categories
				$default_page = $dispatcher.'?ctrl=chapters&amp;blog='.$blog;
			}
			else
			{	// Default: Show list of blogs
				$default_page = $dispatcher.'?ctrl=collections';
			}

			$AdminUI->add_menu_entries(
					NULL, // root
					array(
						'blogs' => array(
							'text' => T_('Blog settings'),
							'href' => $default_page,
							),
						) );

			if( $coll_settings_perm )
			{
				$AdminUI->add_menu_entries( 'blogs',	array(
							'general' => array(
								'text' => T_('General'),
								'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=general&amp;blog='.$blog, ),
							'features' => array(
								'text' => T_('Features'),
								'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=features&amp;blog='.$blog, ),
							'skin' => array(
								'text' => T_('Skin'),
								'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$blog, ),
							'skin_settings' => array(
								'text' => T_('Skin settings'),
								'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=skin_settings&amp;blog='.$blog, ),
							'plugin_settings' => array(
								'text' => T_('Plugin settings'),
								'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=plugin_settings&amp;blog='.$blog, ),
							'widgets' => array(
								'text' => T_('Widgets'),
								'href' => $dispatcher.'?ctrl=widgets&amp;blog='.$blog, ),
						) );
			}

			if( $coll_chapters_perm )
			{
				$AdminUI->add_menu_entries( 'blogs',	array(
							'chapters' => array(
								'text' => T_('Categories'),
								'href' => $dispatcher.'?ctrl=chapters&amp;blog='.$blog ),
						) );
			}

			if( $coll_settings_perm )
			{
				$AdminUI->add_menu_entries( 'blogs',	array(
							'urls' => array(
								'text' => T_('URLs'),
								'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=urls&amp;blog='.$blog, ),
							'seo' => array(
								'text' => T_('SEO'),
								'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=seo&amp;blog='.$blog, ),
							'advanced' => array(
								'text' => T_('Advanced'),
								'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$blog, ),
						) );

				if( $Blog && $Blog->advanced_perms )
				{
					$AdminUI->add_menu_entries( 'blogs',	array(
								'perm' => array(
									'text' => T_('User perms'), // keep label short
									'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=perm&amp;blog='.$blog, ),
								'permgroup' => array(
									'text' => T_('Group perms'), // keep label short
									'href' => $dispatcher.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$blog, ),
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
		global $blog, $loc_transinfo, $ctrl, $dispatcher;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( $current_User->check_perm( 'options', 'view' ) )
		{	// Permission to view settings:
			$AdminUI->add_menu_entries( 'options', array(
					'skins' => array(
						'text' => T_('Skins'),
						'href' => $dispatcher.'?ctrl=skins'),
					'types' => array(
						'text' => T_('Post types'),
						'title' => T_('Post types management'),
						'href' => $dispatcher.'?ctrl=itemtypes'),
					'statuses' => array(
						'text' => T_('Post statuses'),
						'title' => T_('Post statuses management'),
						'href' => $dispatcher.'?ctrl=itemstatuses'),
				) );

			$AdminUI->add_menu_entries( 'tools', array(
						'' => array(	// fp> '' is dirty
							'text' => T_('Misc'),
							'href' => $dispatcher.'?ctrl=tools' ),
					) );

		}

	}
}

$collections_Module = & new collections_Module();


/*
 * $Log$
 * Revision 1.1  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 */
?>
