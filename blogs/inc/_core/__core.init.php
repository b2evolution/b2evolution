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
 * This is supposed to be overriden by sth more useful when a more useful module is loaded
 * Typically should be 'dashboard'
 */
$default_ctrl = 'settings';

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases'] = array(
		'T_antispam'            => $tableprefix.'antispam',
		'T_cron__log'           => $tableprefix.'cron__log',
		'T_cron__task'          => $tableprefix.'cron__task',
		'T_country'             => $tableprefix.'country',
		'T_currency'            => $tableprefix.'currency',
		'T_groups'              => $tableprefix.'groups',
		'T_global__cache'       => $tableprefix.'global__cache',
		'T_locales'             => $tableprefix.'locales',
		'T_messaging__thread'   => $tableprefix.'messaging__thread',
		'T_messaging__message'  => $tableprefix.'messaging__message',
		'T_messaging__msgstatus'=> $tableprefix.'messaging__msgstatus',
		'T_plugins'             => $tableprefix.'plugins',
		'T_pluginevents'        => $tableprefix.'pluginevents',
		'T_pluginsettings'      => $tableprefix.'pluginsettings',
		'T_pluginusersettings'  => $tableprefix.'pluginusersettings',
		'T_settings'            => $tableprefix.'settings',
		'T_users'               => $tableprefix.'users',
		'T_users__fielddefs'    => $tableprefix.'users__fielddefs',
		'T_users__fields'       => $tableprefix.'users__fields',
		'T_usersettings'        => $tableprefix.'usersettings',
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
		'crontab'      => 'cron/cronjobs.ctrl.php',
		'countries'    => 'regional/countries.ctrl.php',
		'currencies'   => 'regional/currencies.ctrl.php',
		'features'     => 'settings/features.ctrl.php',
		'locales'      => 'locales/locales.ctrl.php',
		'messages'     => 'messaging/messages.ctrl.php',
		'plugins'      => 'plugins/plugins.ctrl.php',
		'settings'     => 'settings/settings.ctrl.php',
		'set_antispam' => 'antispam/antispam_settings.ctrl.php',
		'stats'        => 'sessions/stats.ctrl.php',
		'system'       => 'tools/system.ctrl.php',
		'threads'      => 'messaging/threads.ctrl.php',
		'users'        => 'users/users.ctrl.php',
		'upload'       => 'files/upload.ctrl.php',
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
		global $home_url, $admin_url, $dispatcher, $debug, $seo_page_type, $robots_index;
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
		// Either permission for a specific blog or the global permission:
		$perm_files    = $Settings->get( 'fm_enabled' ) && $current_User->check_perm( 'files', 'view', false, isset( $Blog ) ? $Blog->ID : NULL );
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




		// ---------------------------------------------------------------------------

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
					// fp> TODO href to Timezone settings if permission
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
							'href' => $dispatcher.'?ctrl=users&amp;action=change_admin_skin&amp;new_admin_skin='.rawurlencode($admin_skin),
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


		$topright_Menu->add_menu_entries( NULL, $entries );

		$topright_Menu->add_menu_entries( NULL, array(
			'logout' => array(
				'text' => T_('Logout').' '.get_icon('close'),
				'class' => 'rollover',
				'href' => get_user_logout_url(),
				)
		 ) );

	}


	/**
	 * Builds the 1st half of the menu. This is the one with the most important features
	 */
	function build_menu_1()
	{
	}


	/**
	 * Builds the 2nd half of the menu. This is the one with the most important features
	 */
	function build_menu_2()
	{
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
			$AdminUI->add_menu_entries( NULL, array(
						'options' => array(
							'text' => T_('Global settings'),
							'href' => $dispatcher.'?ctrl=settings',
							'entries' => array(
								'general' => array(
									'text' => T_('General'),
									'href' => $dispatcher.'?ctrl=settings' ),
								'features' => array(
									'text' => T_('Features'),
									'href' => $dispatcher.'?ctrl=features' ),
								'antispam' => array(
									'text' => T_('Antispam'),
									'href' => $dispatcher.'?ctrl=set_antispam'),
								'regional' => array(
									'text' => T_('Regional'),
									'href' => $dispatcher.'?ctrl=locales'.( (isset($loc_transinfo) && $loc_transinfo) ? '&amp;loc_transinfo=1' : '' ) ),
								'countries' => array(
									'text' => T_('Countries'),
									'href' => $dispatcher.'?ctrl=countries'),
								'currencies' => array(
									'text' => T_('Currencies'),
									'href' => $dispatcher.'?ctrl=currencies'),
								'plugins' => array(
									'text' => T_('Plugins'),
									'href' => $dispatcher.'?ctrl=plugins'),
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
						'href' => $dispatcher.'?ctrl=users',
					),
				) );
		}
		else
		{	// Only perm to view his own profile:
			$AdminUI->add_menu_entries( NULL, array(
						'users' => array(
						'text' => T_('My profile'),
						'title' => T_('User profile'),
						'href' => $dispatcher.'?ctrl=users',
					),
				) );
		}


		if( $current_User->check_perm( 'options', 'view' ) )
		{	// Permission to view settings:
			// FP> This assumes that we don't let regular users access the tools, including plugin tools.
				$AdminUI->add_menu_entries( NULL, array(
						'tools' => array(
							'text' => T_('Tools'),
							'href' => $dispatcher.'?ctrl=crontab',
							'entries' =>  array(
								'cron' => array(
									'text' => T_('Scheduler'),
									'href' => $dispatcher.'?ctrl=crontab' ),
								'system' => array(
									'text' => T_('System'),
									'href' => $dispatcher.'?ctrl=system' ),
									),
								),
							) );

				if( $current_User->check_perm( 'spamblacklist', 'view' ) )
				{	// Permission to view antispam:
					$AdminUI->add_menu_entries( 'tools', array(
									'antispam' => array(
										'text' => T_('Antispam'),
										'href' => $dispatcher.'?ctrl=antispam'	),
									) );
				}
		}
		elseif( $current_User->check_perm( 'spamblacklist', 'view' ) )
		{	// Permission to view antispam but NOT tools:
			// Give it it's own tab:
			$AdminUI->add_menu_entries( NULL, array(
						'tools' => array(
							'text' => T_('Tools'),
							'href' => $dispatcher.'?ctrl=antispam',
							'entries' =>  array(
								'antispam' => array(
									'text' => T_('Antispam'),
									'href' => $dispatcher.'?ctrl=antispam'	),
								),
						),
					) );
		}

		if( $current_User->check_perm( 'options', 'view' ) )
		{	// Permission to view messaging:
			$AdminUI->add_menu_entries( NULL, array(
						'messaging' => array(
						'text' => T_('Messaging'),
						'title' => T_('Messaging'),
						'href' => $dispatcher.'?ctrl=threads',
					),
				) );
		}
	}
}

$_core_Module = & new _core_Module();


/*
 * $Log$
 * Revision 1.24  2009/09/10 12:13:33  efy-maxim
 * Messaging Module
 *
 * Revision 1.23  2009/09/03 14:08:24  fplanque
 * minor
 *
 * Revision 1.22  2009/09/03 10:43:37  efy-maxim
 * Countries tab in Global Settings section
 *
 * Revision 1.21  2009/09/02 17:47:26  fplanque
 * doc/minor
 *
 * Revision 1.20  2009/09/02 06:23:58  efy-maxim
 * Currencies Tab in Global Settings
 *
 * Revision 1.19  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 * Revision 1.18  2009/08/29 12:23:55  tblue246
 * - SECURITY:
 * 	- Implemented checking of previously (mostly) ignored blog_media_(browse|upload|change) permissions.
 * 	- files.ctrl.php: Removed redundant calls to User::check_perm().
 * 	- XML-RPC APIs: Added missing permission checks.
 * 	- items.ctrl.php: Check permission to edit item with current status (also checks user levels) for update actions.
 * - XML-RPC client: Re-added check for zlib support (removed by update).
 * - XML-RPC APIs: Corrected method signatures (return type).
 * - Localization:
 * 	- Fixed wrong permission description in blog user/group permissions screen.
 * 	- Removed wrong TRANS comment
 * 	- de-DE: Fixed bad translation strings (double quotes + HTML attribute = mess).
 * - File upload:
 * 	- Suppress warnings generated by move_uploaded_file().
 * 	- File browser: Hide link to upload screen if no upload permission.
 * - Further code optimizations.
 *
 * Revision 1.17  2009/07/06 23:52:24  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.16  2009/07/02 18:08:50  fplanque
 * minor
 *
 * Revision 1.15  2009/05/26 19:31:49  fplanque
 * Plugins can now have Settings that are specific to each blog.
 *
 * Revision 1.14  2009/05/26 18:41:46  blueyed
 * Rename "Plugins install" to "Plugins". Shorter and it includes setup, too.
 *
 * Revision 1.13  2009/05/23 22:49:10  fplanque
 * skin settings
 *
 * Revision 1.12  2009/05/15 19:08:00  fplanque
 * doc
 *
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
