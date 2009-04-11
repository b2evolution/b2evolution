<?php
/**
 * This is the init file for the core module.
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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases'] = array(
		'T_antispam'            => $tableprefix.'antispam',
		'T_blogs'               => $tableprefix.'blogs',
		'T_categories'          => $tableprefix.'categories',
		'T_coll_group_perms'    => $tableprefix.'bloggroups',
		'T_coll_user_perms'     => $tableprefix.'blogusers',
		'T_coll_settings'       => $tableprefix.'coll_settings',
		'T_comments'            => $tableprefix.'comments',
		'T_cron__log'           => $tableprefix.'cron__log',
		'T_cron__task'          => $tableprefix.'cron__task',
		'T_files'               => $tableprefix.'files',
		'T_filetypes'           => $tableprefix.'filetypes',
		'T_groups'              => $tableprefix.'groups',
		'T_global__cache'       => $tableprefix.'global__cache',
		'T_items__item'         => $tableprefix.'items__item',
		'T_items__itemtag'      => $tableprefix.'items__itemtag',
		'T_items__prerendering' => $tableprefix.'items__prerendering',
		'T_items__status'       => $tableprefix.'items__status',
		'T_items__tag'          => $tableprefix.'items__tag',
		'T_items__type'         => $tableprefix.'items__type',
		'T_items__version'      => $tableprefix.'items__version',
		'T_links'               => $tableprefix.'links',
		'T_locales'             => $tableprefix.'locales',
		'T_plugins'             => $tableprefix.'plugins',
		'T_pluginevents'        => $tableprefix.'pluginevents',
		'T_pluginsettings'      => $tableprefix.'pluginsettings',
		'T_pluginusersettings'  => $tableprefix.'pluginusersettings',
		'T_postcats'            => $tableprefix.'postcats',
		'T_settings'            => $tableprefix.'settings',
		'T_skins__container'    => $tableprefix.'skins__container',
		'T_skins__skin'         => $tableprefix.'skins__skin',
		'T_subscriptions'       => $tableprefix.'subscriptions',
		'T_users'               => $tableprefix.'users',
		'T_users__fielddefs'    => $tableprefix.'users__fielddefs',
		'T_users__fields'       => $tableprefix.'users__fields',
		'T_usersettings'        => $tableprefix.'usersettings',
		'T_widget'              => $tableprefix.'widget',
	);


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
$ctrl_mappings = array(
		'antispam'     => 'antispam/antispam_list.ctrl.php',
		'chapters'     => 'chapters/chapters.ctrl.php',
		'collections'  => 'collections/collections.ctrl.php',
		'coll_settings'=> 'collections/coll_settings.ctrl.php',
		'comments'     => 'comments/_comments.ctrl.php',
		'crontab'      => 'cron/cronjobs.ctrl.php',
		'dashboard'    => 'dashboard/dashboard.ctrl.php',
		'features'     => 'settings/features.ctrl.php',
		'files'        => 'files/files.ctrl.php',
		'fileset'      => 'files/file_settings.ctrl.php',
		'filetypes'    => 'files/file_types.ctrl.php',
		'items'        => 'items/items.ctrl.php',
		'itemstatuses' => 'items/item_statuses.ctrl.php',
		'itemtypes'    => 'items/item_types.ctrl.php',
		'locales'      => 'locales/locales.ctrl.php',
		'mtimport'     => 'tools/mtimport.ctrl.php',
		'plugins'      => 'plugins/plugins.ctrl.php',
		'settings'     => 'settings/settings.ctrl.php',
		'set_antispam' => 'antispam/antispam_settings.ctrl.php',
		'skins'        => 'skins/skins.ctrl.php',
		'stats'        => 'sessions/stats.ctrl.php',
		'system'       => 'tools/system.ctrl.php',
		'tools'        => 'tools/tools.ctrl.php',
		'users'        => 'users/users.ctrl.php',
		'upload'       => 'files/upload.ctrl.php',
		'widgets'      => 'widgets/widgets.ctrl.php',
		'wpimport'     => 'tools/wpimport.ctrl.php',
	);


/**
 * _core_Module definition
 */
class _core_Module
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
		global $home_url, $admin_url, $debug, $seo_page_type, $robots_index;
		global $Blog, $blog;

		global $Settings;

		$entries = array(
			'b2evo' => array(
					'text' => '<strong>b2evolution</strong>',
					'href' => $home_url,
				),
			'dashboard' => array(
					'text' => T_('Dashboard'),
					'href' => $admin_url,
					'title' => T_('Go to admin dashboard'),
				),
			'see' => array(
					'text' => T_('See'),
					'href' => $home_url,
					'title' => T_('See the home page'),
				),
			'write' => array(
					'text' => T_('Write'),
					'title' => T_('No blog is currently selected'),
					'disabled' => true,
				),
			'manage' => array(
					'text' => T_('Manage'),
					'title' => T_('No blog is currently selected'),
					'disabled' => true,
				),
			'customize' => array(
					'text' => T_('Customize'),
					'title' => T_('No blog is currently selected'),
					'disabled' => true,
				),
			'tools' => array(
					'text' => T_('Tools'),
					'disabled' => true,
				),
		);

		if( !empty($Blog) )
		{	// A blog is currently selected:
			$entries['dashboard']['href'] = $admin_url.'?blog='.$Blog->ID;
			$entries['see']['href'] = $Blog->get( 'url' );


			$entries['see']['title'] = T_('See the public view of this blog');


			if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
			{	// We have permission to add a post with at least one status:
				$entries['write']['href'] = $admin_url.'?ctrl=items&amp;action=new&amp;blog='.$Blog->ID;
				$entries['write']['disabled'] = false;
				$entries['write']['title'] = T_('Write a new post into this blog');
			}
			else
			{
				$entries['write']['title'] = T_('You don\'t have permission to post into this blog');
			}


 			$items_url = $admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=restore';
			$entries['manage']['href'] = $items_url;
			$entries['manage']['disabled'] = false;
			$entries['manage']['title'] = T_('Manage the contents of this blog');
			$entries['manage']['entries'] = array(
					'posts' => array(
							'text' => T_('Posts').'&hellip;',
							'href' => $items_url.'&amp;tab=list',
						),
					'pages' => array(
							'text' => T_('Pages').'&hellip;',
							'href' => $items_url.'&amp;tab=pages',
						),
					'intros' => array(
							'text' => T_('Intro posts').'&hellip;',
							'href' => $items_url.'&amp;tab=intros',
						),
					'podcasts' => array(
							'text' => T_('Podcast episodes').'&hellip;',
							'href' => $items_url.'&amp;tab=podcasts',
						),
					'links' => array(
							'text' => T_('Sidebar links').'&hellip;',
							'href' => $items_url.'&amp;tab=links',
						),
				);
			if( $Blog->get_setting( 'use_workflow' ) )
			{	// We want to use workflow properties for this blog:
				$entries['manage']['entries']['tracker'] = array(
						'text' => T_('Tracker').'&hellip;',
						'href' => $items_url.'&amp;tab=tracker',
					);
			}
			$entries['manage']['entries']['full'] = array(
					'text' => T_('All Items').'&hellip;',
					'href' => $items_url.'&amp;tab=full',
				);
		}


		$perm_comments = (! empty($Blog)) && $current_User->check_perm( 'blog_comments', 'edit', false, $Blog->ID );
		$perm_files = $Settings->get( 'fm_enabled' ) && $current_User->check_perm( 'files', 'view' );
		$perm_chapters = (! empty($Blog)) && $current_User->check_perm( 'blog_cats', 'edit', false, $Blog->ID );

		if( $perm_comments || $perm_files || $perm_chapters )
		{
			$entries['manage']['disabled'] = false;

			if( ! empty($entries['manage']['entries']) )
			{	// There are already entries aboce, insert a separator:
				$entries['manage']['entries'][] = array(
					'separator' => true,
				);
			}

			if( $perm_comments )
			{	// Comments:
				$entries['manage']['entries']['comments'] = array(
						'text' => T_('Comments').'&hellip;',
						'href' => $admin_url.'?ctrl=comments&amp;blog='.$Blog->ID,
					);
			}

			if( $perm_files )
			{	// FM enabled and permission to view files:
				$entries['manage']['entries']['files'] = array(
						'text' => T_('Files').'&hellip;',
						'href' => $admin_url.'?ctrl=files&amp;blog='.$blog,
					);
			}

			if( $perm_chapters )
			{	// Chapters:
				$entries['manage']['entries']['chapters'] = array(
						'text' => T_('Categories').'&hellip;',
						'href' => $admin_url.'?ctrl=chapters&amp;blog='.$Blog->ID,
					);
			}
		}



		if( $current_User->check_perm( 'options', 'view' ) )
		{	// Permission to access system info
			$entries['b2evo']['entries']['system'] = array(
					'text' => T_('About this system').'&hellip;',
					'href' => $admin_url.'?ctrl=system',
				);
			$entries['b2evo']['entries'][] = array(
					'separator' => true,
				);
		}

		if( $current_User->check_perm( 'blogs', 'create' ) )
		{
			$entries['b2evo']['entries']['newblog'] = array(
					'text' => T_('Create new blog').'&hellip;',
					'href' => $admin_url.'?ctrl=collections&amp;action=new',
				);
			$entries['b2evo']['entries'][] = array(
					'separator' => true,
				);
		}

		$entries['b2evo']['entries']['info'] = array(
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
								'href' => 'http://www.facebook.com/pages/b2evolution/63634905896',
								'target' => '_blank',
							),
						),
				);


		// CUSTOMIZE:
		if( !empty($Blog) && $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
		{	// We have permission to edit blog properties:
			$blog_param = '&amp;blog='.$Blog->ID;

			$entries['customize']['href'] = $admin_url.'?ctrl=widgets'.$blog_param;
			$entries['customize']['disabled'] = false;
			$entries['customize']['title'] = T_('Customize this blog');

			$entries['customize']['entries'] = array(
				'general' => array(
						'text' => T_('Blog properties').'&hellip;',
						'href' => $admin_url.'?ctrl=coll_settings'.$blog_param,
					),
				'features' => array(
						'text' => T_('Blog features').'&hellip;',
						'href' => $admin_url.'?ctrl=coll_settings&amp;tab=features'.$blog_param,
					),
				'skin' => array(
						'text' => T_('Blog skin').'&hellip;',
						'href' => $admin_url.'?ctrl=coll_settings&amp;tab=skin'.$blog_param,
					),
				'widgets' => array(
						'text' => T_('Blog widgets').'&hellip;',
						'href' => $admin_url.'?ctrl=widgets'.$blog_param,
					),
				'urls' => array(
						'text' => T_('Blog URLs').'&hellip;',
						'href' => $admin_url.'?ctrl=coll_settings&amp;tab=urls'.$blog_param,
					),
			);
		}


		// TOOLS:
		$perm_spam = $current_User->check_perm( 'spamblacklist', 'view', false );
		$perm_options = $current_User->check_perm( 'options', 'view' );
		if( $perm_spam || $perm_options )
		{	// Permission to view settings:
			if( $perm_spam )
			{
				$entries['tools']['entries']['antispam'] = array(
						'text' => T_('Antispam blacklist').'&hellip;',
						'href' => $admin_url.'?ctrl=antispam',
					);
			}

			if( $perm_options )
			{
				$entries['tools']['entries']['crontab'] = array(
						'text' => T_('Scheduler').'&hellip;',
						'href' => $admin_url.'?ctrl=crontab',
					);
			}
		}

		if( $debug )
		{
			$debug_text = 'DEBUG: ';
			if( !empty($seo_page_type) )
			{	// Set in skin_init()
				$debug_text = $seo_page_type.': ';
			}
			if( $robots_index === false )
			{
				$debug_text .= 'NO INDEX';
			}
			else
			{
				$debug_text .= 'do index';
			}

			$entries['tools']['entries']['noindex_sep'] = array(
					'separator' => true,
				);
			$entries['tools']['entries']['noindex'] = array(
					'text' => $debug_text,
					'disabled' => true,
				);
		}

		$topleft_Menu->add_menu_entries( NULL, $entries );


		/*
		 * RIGHT MENU
		 */
		global $localtimenow, $is_admin_page;

		$entries = array(
			'userprefs' => array(
					'text' => $current_User->get_avatar_imgtag( 'crop-15x15', '', 'top' ).' <strong>'.$current_User->login.'</strong>',
					'href' => get_user_profile_url(),
					'entries' => array(
						'profile' => array(
								'text' => T_('Edit user profile').'&hellip;',
								'href' => get_user_profile_url(),
							),
						),
				),
			'time' => array(
					'text' => date( locale_shorttimefmt(), $localtimenow ),
					'disabled' => true,
					'class' => 'noborder',
				),
		);

		if( $subs_url = get_user_subs_url() )
		{
			$entries['userprefs']['entries']['subscriptions'] = array(
					'text' => T_('Email subscriptions').'&hellip;',
					'href' => $subs_url,
				);
		}

		// ADMIN SKINS:
		if( $is_admin_page )
		{
			$admin_skins = get_admin_skins();
			if( count( $admin_skins ) > 1 )
			{	// We have several admin skins available: display switcher:
				$entries['userprefs']['entries']['admskins_sep'] = array(
						'separator' => true,
					);
				$entries['userprefs']['entries']['admskins'] = array(
						'text' => T_('Admin skin'),
					);
				foreach( $admin_skins as $admin_skin )
				{
					$entries['userprefs']['entries']['admskins']['entries'][$admin_skin] = array(
							'text' => $admin_skin,
							'href' => 'admin.php?ctrl=users&amp;action=change_admin_skin&amp;new_admin_skin='.rawurlencode($admin_skin),
						);
				}
			}
		}


		$entries['userprefs']['entries']['logout_sep'] = array(
				'separator' => true,
			);
		$entries['userprefs']['entries']['logout'] = array(
				'text' => T_('Logout'),
				'href' => get_user_logout_url(),
			);

		if( $is_admin_page )
		{
			if( !empty( $Blog ) )
			{
				$entries['abswitch'] = array(
						'text' => T_('Blog').' '.get_icon('switch-to-blog'),
						'href' => $Blog->get( 'url' ),
					);
			}
			else
			{
				$entries['abswitch'] = array(
						'text' => T_('Home').' '.get_icon('switch-to-blog'),
						'href' => $home_url,
					);
			}
		}
		else
		{
			$entries['abswitch'] = array(
					'text' => T_('Admin').' '.get_icon('switch-to-admin'),
					'href' => $admin_url,
				);
		}

		$entries['logout'] = array(
				'text' => T_('Logout').' '.get_icon('close'),
				'class' => 'rollover',
				'href' => get_user_logout_url(),
			);


		$topright_Menu->add_menu_entries( NULL, $entries );

	}


	/**
	 * Builds the 1st half of the menu. This is the one with the most important features
	 */
	function build_menu_1()
	{
		global $blog;
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
						'href' => 'admin.php?ctrl=dashboard&amp;blog='.$blog,
						'style' => 'font-weight: bold;'
						),

					'items' => array(
						'text' => T_('Posts / Comments'),
						'href' => 'admin.php?ctrl=items&amp;blog='.$blog.'&amp;filter=restore',
						// Controller may add subtabs
						),
					) );


		if( $Settings->get( 'fm_enabled' ) && $current_User->check_perm( 'files', 'view' ) )
		{	// FM enabled and permission to view files:
			$AdminUI->add_menu_entries( NULL, array(
						'files' => array(
							'text' => T_('Files'),
							'title' => T_('File management'),
							'href' => 'admin.php?ctrl=files',
							// Controller may add subtabs
						),
					) );

		}
	}


	/**
	 * Builds the 2nd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_2()
	{
		global $blog, $loc_transinfo, $ctrl;
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
							'href' => 'admin.php?ctrl=collections',
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
				$default_page = 'admin.php?ctrl=coll_settings&amp;tab=general&amp;blog='.$blog;
			}
			elseif( $coll_chapters_perm )
			{	// Default: show categories
				$default_page = 'admin.php?ctrl=chapters&amp;blog='.$blog;
			}
			else
			{	// Default: Show list of blogs
				$default_page = 'admin.php?ctrl=collections';
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
								'href' => 'admin.php?ctrl=coll_settings&amp;tab=general&amp;blog='.$blog, ),
							'features' => array(
								'text' => T_('Features'),
								'href' => 'admin.php?ctrl=coll_settings&amp;tab=features&amp;blog='.$blog, ),
							'skin' => array(
								'text' => T_('Skin'),
								'href' => 'admin.php?ctrl=coll_settings&amp;tab=skin&amp;blog='.$blog, ),
							'widgets' => array(
								'text' => T_('Widgets'),
								'href' => 'admin.php?ctrl=widgets&amp;blog='.$blog, ),
						) );
			}

			if( $coll_chapters_perm )
			{
				$AdminUI->add_menu_entries( 'blogs',	array(
							'chapters' => array(
								'text' => T_('Categories'),
								'href' => 'admin.php?ctrl=chapters&amp;blog='.$blog ),
						) );
			}

			if( $coll_settings_perm )
			{
				$AdminUI->add_menu_entries( 'blogs',	array(
							'urls' => array(
								'text' => T_('URLs'),
								'href' => 'admin.php?ctrl=coll_settings&amp;tab=urls&amp;blog='.$blog, ),
							'seo' => array(
								'text' => T_('SEO'),
								'href' => 'admin.php?ctrl=coll_settings&amp;tab=seo&amp;blog='.$blog, ),
							'advanced' => array(
								'text' => T_('Advanced'),
								'href' => 'admin.php?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$blog, ),
						) );

				if( $Blog && $Blog->advanced_perms )
				{
					$AdminUI->add_menu_entries( 'blogs',	array(
								'perm' => array(
									'text' => T_('User perms'), // keep label short
									'href' => 'admin.php?ctrl=coll_settings&amp;tab=perm&amp;blog='.$blog, ),
								'permgroup' => array(
									'text' => T_('Group perms'), // keep label short
									'href' => 'admin.php?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$blog, ),
							) );
				}
			}
		}


		if( $current_User->check_perm( 'options', 'view' ) )
		{	// Permission to view settings:
			$AdminUI->add_menu_entries( NULL, array(
						'options' => array(
							'text' => T_('Global settings'),
							'href' => 'admin.php?ctrl=settings',
							'entries' => array(
								'general' => array(
									'text' => T_('General'),
									'href' => 'admin.php?ctrl=settings' ),
								'features' => array(
									'text' => T_('Features'),
									'href' => 'admin.php?ctrl=features' ),
								'skins' => array(
									'text' => T_('Skins install'),
									'href' => 'admin.php?ctrl=skins'),
								'plugins' => array(
									'text' => T_('Plugins install'),
									'href' => 'admin.php?ctrl=plugins'),
								'antispam' => array(
									'text' => T_('Antispam'),
									'href' => 'admin.php?ctrl=set_antispam'),
								'regional' => array(
									'text' => T_('Regional'),
									'href' => 'admin.php?ctrl=locales'.( (isset($loc_transinfo) && $loc_transinfo) ? '&amp;loc_transinfo=1' : '' ) ),
								'files' => array(
									'text' => T_('Files'),
									'href' => 'admin.php?ctrl=fileset' ),
								'filetypes' => array(
									'text' => T_('File types'),
									'href' => 'admin.php?ctrl=filetypes' ),
								'types' => array(
									'text' => T_('Post types'),
									'title' => T_('Post types management'),
									'href' => 'admin.php?ctrl=itemtypes'),
								'statuses' => array(
									'text' => T_('Post statuses'),
									'title' => T_('Post statuses management'),
									'href' => 'admin.php?ctrl=itemstatuses'),
							)
						),
					) );
		}


		if( $current_User->check_perm( 'users', 'view' ) )
		{	// Permission to view users:
			$AdminUI->add_menu_entries( NULL, array(
						'users' => array(
						'text' => T_('Users'),
						'title' => T_('User management'),
						'href' => 'admin.php?ctrl=users',
					),
				) );
		}
		else
		{	// Only perm to view his own profile:
			$AdminUI->add_menu_entries( NULL, array(
						'users' => array(
						'text' => T_('My profile'),
						'title' => T_('User profile'),
						'href' => 'admin.php?ctrl=users',
					),
				) );
		}


		if( $current_User->check_perm( 'options', 'view' ) )
		{	// Permission to view settings:
			// FP> This assumes that we don't let regular users access the tools, including plugin tools.
				$AdminUI->add_menu_entries( NULL, array(
						'tools' => array(
							'text' => T_('Tools'),
							'href' => 'admin.php?ctrl=crontab',
							'entries' =>  array(
								'cron' => array(
									'text' => T_('Scheduler'),
									'href' => 'admin.php?ctrl=crontab' ),
								'system' => array(
									'text' => T_('System'),
									'href' => 'admin.php?ctrl=system' ),
									),
								),
							) );

				if( $current_User->check_perm( 'spamblacklist', 'view' ) )
				{	// Permission to view antispam:
					$AdminUI->add_menu_entries( 'tools', array(
									'antispam' => array(
										'text' => T_('Antispam'),
										'href' => 'admin.php?ctrl=antispam'	),
									) );
				}

				$AdminUI->add_menu_entries( 'tools', array(
							'' => array(	// fp> '' is dirty
								'text' => T_('Misc'),
								'href' => 'admin.php?ctrl=tools' ),
						) );
		}
		elseif( $current_User->check_perm( 'spamblacklist', 'view' ) )
		{	// Permission to view antispam but NOT tools:
			// Give it it's own tab:
			$AdminUI->add_menu_entries( NULL, array(
						'tools' => array(
							'text' => T_('Tools'),
							'href' => 'admin.php?ctrl=antispam',
							'entries' =>  array(
								'antispam' => array(
									'text' => T_('Antispam'),
									'href' => 'admin.php?ctrl=antispam'	),
								),
						),
					) );
		}
	}
}

$_core_Module = & new _core_Module();


/*
 * $Log$
 * Revision 1.11  2009/04/11 13:52:03  tblue246
 * evobar: Do not display a border when hovering the time
 *
 * Revision 1.10  2009/03/23 22:19:43  fplanque
 * evobar right menu is now also customizable by plugins
 *
 * Revision 1.9  2009/03/23 18:27:48  waltercruz
 * Fixing warn when blog=0
 *
 * Revision 1.8  2009/03/23 04:09:43  fplanque
 * Best. Evobar. Menu. Ever.
 * menu is now extensible by plugins
 *
 * Revision 1.7  2009/03/08 23:57:38  fplanque
 * 2009
 *
 * Revision 1.6  2009/03/07 21:35:09  blueyed
 * doc
 *
 * Revision 1.5  2009/02/24 22:58:19  fplanque
 * Basic version history of post edits
 *
 * Revision 1.4  2008/10/06 01:55:06  fplanque
 * User fields proof of concept.
 * Needs UserFieldDef and UserFieldDefCache + editing of fields.
 * Does anyone want to take if from there?
 *
 * Revision 1.3  2008/10/03 22:00:47  blueyed
 * Indent fixes
 *
 * Revision 1.2  2008/05/10 22:59:09  fplanque
 * keyphrase logging
 *
 * Revision 1.1  2008/04/06 19:19:29  fplanque
 * Started moving some intelligence to the Modules.
 * 1) Moved menu structure out of the AdminUI class.
 * It is part of the app structure, not the UI. Up to this point at least.
 * Note: individual Admin skins can still override the whole menu.
 * 2) Moved DB schema to the modules. This will be reused outside
 * of install for integrity checks and backup.
 * 3) cleaned up config files
 *
 */
?>