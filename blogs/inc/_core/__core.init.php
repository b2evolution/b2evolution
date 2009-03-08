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