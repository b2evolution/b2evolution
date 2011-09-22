<?php
/**
 * This is the init file for the core module.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
		'T_antispam'              => $tableprefix.'antispam',
		'T_cron__log'             => $tableprefix.'cron__log',
		'T_cron__task'            => $tableprefix.'cron__task',
		'T_country'               => $tableprefix.'country',
		'T_currency'              => $tableprefix.'currency',
		'T_groups'                => $tableprefix.'groups',
		'T_groups__groupsettings' => $tableprefix.'groups__groupsettings',
		'T_global__cache'         => $tableprefix.'global__cache',
		'T_locales'               => $tableprefix.'locales',
		'T_plugins'               => $tableprefix.'plugins',
		'T_pluginevents'          => $tableprefix.'pluginevents',
		'T_pluginsettings'        => $tableprefix.'pluginsettings',
		'T_pluginusersettings'    => $tableprefix.'pluginusersettings',
		'T_settings'              => $tableprefix.'settings',
		'T_users'                 => $tableprefix.'users',
		'T_users__fielddefs'      => $tableprefix.'users__fielddefs',
		'T_users__fields'         => $tableprefix.'users__fields',
		'T_users__usersettings'   => $tableprefix.'users__usersettings',
		'T_slug'                  => $tableprefix.'slug',
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
		'antispam'     => 'antispam/antispam.ctrl.php',
		'crontab'      => 'cron/cronjobs.ctrl.php',
		'countries'    => 'regional/countries.ctrl.php',
		'currencies'   => 'regional/currencies.ctrl.php',
		'features'     => 'settings/features.ctrl.php',
		'locales'      => 'locales/locales.ctrl.php',
		'plugins'      => 'plugins/plugins.ctrl.php',
		'gensettings'  => 'settings/settings.ctrl.php',
		'settings'     => 'settings/settings_dispatch.ctrl.php',
		'stats'        => 'sessions/stats.ctrl.php',
		'system'       => 'tools/system.ctrl.php',
		'user'         => 'users/user.ctrl.php',
		'users'        => 'users/users.ctrl.php',
		'userfields'   => 'users/userfields.ctrl.php',
		'usersettings' => 'users/settings.ctrl.php',
		'registration' => 'users/registration.ctrl.php',
		'groups'       => 'users/groups.ctrl.php',
		'upload'       => 'files/upload.ctrl.php',
		'slugs'        => 'slugs/slugs.ctrl.php',
	);


/**
 * Get the CountryCache
 *
 * @return CountryCache
 */
function & get_CountryCache()
{
	global $CountryCache;

	if( ! isset( $CountryCache ) )
	{	// Cache doesn't exist yet:
		$CountryCache = new DataObjectCache( 'Country', true, 'T_country', 'ctry_', 'ctry_ID', 'ctry_code', 'ctry_name', 'Unknown');
	}

	return $CountryCache;
}

/**
 * Get the CurrencyCache
 *
 * @return CurrencyCache
 */
function & get_CurrencyCache()
{
	global $CurrencyCache;

	if( ! isset( $CurrencyCache ) )
	{	// Cache doesn't exist yet:
		$CurrencyCache = new DataObjectCache( 'Currency', true, 'T_currency', 'curr_', 'curr_ID', 'curr_code', 'curr_code');
	}

	return $CurrencyCache;
}


/**
 * Get the GroupCache
 *
 * @return GroupCache
 */
function & get_GroupCache()
{
	global $Plugins;
	global $GroupCache;

	if( ! isset( $GroupCache ) )
	{	// Cache doesn't exist yet:
		$Plugins->get_object_from_cacheplugin_or_create( 'GroupCache', 'new DataObjectCache( \'Group\', true, \'T_groups\', \'grp_\', \'grp_ID\', \'grp_name\', \'\', T_(\'No group\') )' );
	}

	return $GroupCache;
}


/**
 * Get the Plugins_admin
 *
 * @return Plugins_admin
 */
function & get_Plugins_admin()
{
	global $Plugins_admin;

	if( ! isset( $Plugins_admin ) )
	{	// Cache doesn't exist yet:
		load_class( 'plugins/model/_plugins_admin.class.php', 'Plugins_admin' );
		$Plugins_admin = new Plugins_admin(); // COPY (FUNC)
	}

	return $Plugins_admin;
}


/**
 * Get the UserCache
 *
 * @return UserCache
 */
function & get_UserCache()
{
	global $UserCache;

	if( ! isset( $UserCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'users/model/_usercache.class.php', 'UserCache' );
		$UserCache = new UserCache(); // COPY (FUNC)
	}

	return $UserCache;
}


/**
 * Get the UserFieldCache
 *
 * @return UserFieldCache
 */
function & get_UserFieldCache()
{
	global $UserFieldCache;

	if( ! isset( $UserFieldCache ) )
	{	// Cache doesn't exist yet:
		$UserFieldCache = new DataObjectCache( 'Userfield', false, 'T_users__fielddefs', 'ufdf_', 'ufdf_ID', 'ufdf_name', 'ufdf_name' ); // COPY (FUNC)
	}

	return $UserFieldCache;
}


/**
 * Get the SlugCache
 *
 * @return SlugCache
 */
function & get_SlugCache()
{
	global $SlugCache;

	if( ! isset( $SlugCache ) )
	{	// Cache doesn't exist yet:
		$SlugCache = new DataObjectCache( 'Slug', false, 'T_slug', 'slug_', 'slug_ID', 'slug_title', 'slug_title' );
	}

	return $SlugCache;
}


/**
 * _core_Module definition
 */
class _core_Module extends Module
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
		load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );
		load_class( 'generic/model/_genericelement.class.php', 'GenericElement' );
		load_class( 'generic/model/_genericcache.class.php', 'GenericCache' );
		load_funcs( 'users/model/_user.funcs.php' );
		load_funcs( '_core/_template.funcs.php' );
		load_funcs( '_core/ui/forms/_form.funcs.php');
		load_class( '_core/ui/forms/_form.class.php', 'Form' );
		load_class( '_core/model/db/_sql.class.php', 'SQL' );
		load_class( '_core/ui/results/_results.class.php', 'Results' );
		load_class( '_core/model/_blockcache.class.php', 'BlockCache' );
		load_class( 'slugs/model/_slug.class.php', 'Slug' );
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
				$permadmin = 'normal';
				$permspam = 'edit';
				$permslugs = 'edit';
				$permtemplates = 'allowed';
				$permoptions = 'edit';
				break;

			case 2:		// Privileged bloggers (group ID 2) have permission by default:
				$permadmin = 'normal';
				$permspam = 'edit';
				$permslugs = 'none';
				$permtemplates = 'denied';
				$permoptions = 'view';
				break;

			case 3:		// Bloggers (group ID 3) have permission by default:
				$permadmin = 'normal';
				$permspam = 'view';
				$permslugs = 'none';
				$permtemplates = 'denied';
				$permoptions = 'none';
				break;

			default:
				// Other groups have no permission by default
				$permadmin = 'none';
				$permspam = 'none';
				$permslugs = 'none';
				$permtemplates = 'denied';
				$permoptions = 'none';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array(
			'perm_admin' => $permadmin,
			'perm_spamblacklist' => $permspam,
			'perm_slugs' => $permslugs,
			'perm_templates' => $permtemplates,
			'perm_options' => $permoptions );
	}


	/**
	 * Get available group permissions
	 *
	 * @return array
	 */
	function get_available_group_permissions( $grp_ID = NULL )
	{
		$none_option = array( 'none', T_( 'No Access' ), '' );
		$view_option = array( 'view', T_( 'View only' ), '' );
		$full_option = array( 'edit', T_( 'Full Access' ), '' );
		$view_details = array( 'view', T_('View details') );
		$edit_option = array( 'edit', T_('Edit/delete all') );
		// 'label' is used in the group form as label for radio buttons group
		// 'user_func' function used to check user permission. This function should be defined in Module.
		// 'group_func' function used to check group permission. This function should be defined in Module.
		// 'perm_block' group form block where this permissions will be displayed. Now available, the following blocks: additional, system
		// 'options' is permission options
		// 'perm_type' is used in the group form to decide to show radiobox or checkbox
		// 'field_lines' is used in the group form to decide to show radio options in multiple lines or not
		if( $grp_ID == 1 )
		{
			$perm_admin_values = array(
				'label' => T_( 'Access to Admin area' ),
				'perm_block' => 'core_general',
				'perm_type' => 'info',
				'info' => T_( 'Visible link' ),
			);
		}
		else
		{
			$perm_admin_values = array(
				'label' => T_( 'Access to Admin area' ),
				'user_func'  => 'check_admin_user_perm',
				'group_func' => 'check_admin_group_perm',
				'perm_block' => 'core_general',
				'options'  => array(
					$none_option,
					array( 'restricted', T_( 'Restricted' ) ),
					array( 'normal', T_( 'Normal' ) ) ),
				'perm_type' => 'radiobox',
				'field_lines' => false,
			);
		}

		$permissions = array(
			'perm_admin' => $perm_admin_values,
			'perm_spamblacklist' => array(
				'label' => T_( 'Antispam' ),
				'user_func'  => 'check_core_user_perm',
				'group_func' => 'check_core_group_perm',
				'perm_block' => 'core',
				'options'  => array( $none_option, $view_option, $full_option ),
				'perm_type' => 'radiobox',
				'field_lines' => false,
				),
			'perm_slugs' => array(
				'label' => T_('Slug manager'),
				'user_func'  => 'check_core_user_perm',
				'group_func' => 'check_core_group_perm',
				'perm_block' => 'core',
				'options'  => array( $none_option, $view_option, $full_option ),
				'perm_type' => 'radiobox',
				'field_lines' => false,
				),
			'perm_templates' => array(
				'label' => T_('Skins'),
				'user_func'  => 'check_template_user_perm',
				'group_func' => 'check_template_group_perm',
				'perm_block' => 'core',
				'perm_type' => 'checkbox',
				'note' => T_( 'Check to allow access to skin files.' ),
				),
			'perm_options' => array(
				'label' => T_('Settings'),
				'user_func'  => 'check_core_user_perm',
				'group_func' => 'check_core_group_perm',
				'perm_block' => 'core2',
				'options'  => array( $none_option, $view_details, $edit_option ),
				'perm_type' => 'radiobox',
				'field_lines' => false,
				),
			// put here perm_options with perm_block 'core1'
			);
		return $permissions;
	}


	/**
	 * Check admin permission for the group
	 */
	function check_admin_group_perm( $permlevel, $permvalue, $permtarget )
	{
		$perm = false;
		switch( $permvalue )
		{
			case 'full':
			case 'normal':
				if( $permlevel == 'normal' )
				{
					$perm = true;
					break;
				}

			case 'restricted':
				if( $permlevel == 'restricted' || $permlevel == 'any' )
				{
					$perm = true;
					break;
				}
		}

		return $perm;
	}


	/**
	 * Check a permission for the user. ( see 'user_func' in get_available_group_permissions() function  )
	 *
	 * @param string Requested permission level
	 * @param string Permission value, this is the value on the database
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_core_user_perm( $permlevel, $permvalue, $permtarget )
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
	function check_core_group_perm( $permlevel, $permvalue, $permtarget )
	{
		$perm = false;
		switch ( $permvalue )
		{
			case 'edit':
				// Users has edit perms
				if( $permlevel == 'edit' )
				{
					$perm = true;
					break;
				}

			case 'view':
				// Users has view perms
				if( $permlevel == 'view' )
				{
					$perm = true;
					break;
				}

		}

		return $perm;
	}

	/**
	 * Check permission for the group
	 */
	function check_template_group_perm( $permlevel, $permvalue, $permtarget )
	{
		// Only 'allowed' value means group has permission
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
		global $home_url, $admin_url, $dispatcher, $debug, $seo_page_type, $robots_index;
		global $Blog, $blog;

		global $Settings;

		$perm_admin_normal = $current_User->check_perm( 'admin', 'normal' );
		$perm_admin_restricted = $current_User->check_perm( 'admin', 'restricted' );
		$entries = NULL;

		if( $perm_admin_normal )
		{	// Normal Access to Admin:
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
				'blog' => array(
						'text' => T_('Blog'),
						'title' => T_('No blog is currently selected'),
						'disabled' => true,
					),
				'tools' => array(
						'text' => T_('System'),
						'disabled' => true,
					),
			);
		}
		elseif( $perm_admin_restricted )
		{	// restricted Access to Admin:
			$entries = array(
				'see' => array(
						'text' => T_('Site'),
						'href' => $home_url,
						'title' => T_('See the home page'),
					),
				'tools' => array(
						'text' => T_('System'),
						'disabled' => true,
					),
			);
		}


		if( !empty($Blog) )
		{	// A blog is currently selected:
			if( $perm_admin_normal )
			{
				$entries['dashboard']['href'] = $admin_url.'?blog='.$Blog->ID;
			}

			if( $perm_admin_restricted )
			{
				$entries['see']['href'] = $Blog->get( 'url' );
				$entries['see']['title'] = T_('See the public view of this blog');
			}

			if( $perm_admin_normal )
			{
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


				// BLOG MENU:
	 			$items_url = $admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=restore';
				$entries['blog']['href'] = $items_url;
				$entries['blog']['disabled'] = false;
				$entries['blog']['title'] = T_('Manage this blog');
				$entries['blog']['entries']['posts'] = array(
								'text' => T_('Posts').'&hellip;',
								'href' => $items_url,
							);

				// Check if user has permission for published, draft or depreceted comments (any of these)
				if( $current_User->check_perm( 'blog_comments', 'edit', false, $Blog->ID ) )
				{	// Comments:
					$entries['blog']['entries']['comments'] = array(
							'text' => T_('Comments').'&hellip;',
							'href' => $admin_url.'?ctrl=comments&amp;blog='.$Blog->ID.'&amp;filter=restore',
						);
				}

				// Chapters / Categories:
				if( $current_User->check_perm( 'blog_cats', 'edit', false, $Blog->ID ) )
				{	// Either permission for a specific blog or the global permission:
					$entries['blog']['entries']['chapters'] = array(
							'text' => T_('Categories').'&hellip;',
							'href' => $admin_url.'?ctrl=chapters&amp;blog='.$Blog->ID,
						);
				}

				// PLACE HOLDER FOR FILES MODULE:
				$entries['blog']['entries']['files'] = NULL;

				// BLOG SETTINGS:
				if( $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
				{	// We have permission to edit blog properties:
					$blog_param = '&amp;blog='.$Blog->ID;

					if( ! empty($entries['blog']['entries']) )
					{	// There are already entries aboce, insert a separator:
						$entries['blog']['entries'][] = array(
							'separator' => true,
						);
					}
					$entries['blog']['entries']['general'] = array(
								'text' => T_('Blog settings').'&hellip;',
								'href' => $admin_url.'?ctrl=coll_settings'.$blog_param,
						);
				}
			}
		}

		// SYSTEM MENU:
		if( $perm_admin_restricted )
		{
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

				$entries['tools']['entries']['noindex'] = array(
						'text' => $debug_text,
						'disabled' => true,
					);
				$entries['tools']['entries'][''] = array(
						'separator' => true,
					);
			}

			if( $current_User->check_perm( 'users', 'view' ) )
			{	// Users:
				$entries['tools']['disabled'] = false;
				$entries['tools']['entries']['users'] = array(
						'text' => T_('Users').'&hellip;',
						'href' => $admin_url.'?ctrl=users',
					);
			}

			// PLACE HOLDER FOR FILES MODULE:
			$entries['tools']['entries']['files'] = NULL;

			$perm_spam = $current_User->check_perm( 'spamblacklist', 'view' );
			$perm_options = $current_User->check_perm( 'options', 'view' );
			$perm_slugs = $current_User->check_perm( 'slugs', 'view' );
			$perm_maintenance = $current_User->check_perm( 'perm_maintenance', 'upgrade' );

			if( $perm_spam || $perm_options || $perm_slugs || $perm_maintenance )
			{
				$entries['tools']['disabled'] = false;
				$entries['tools']['entries']['tools_sep'] = array(
						'separator' => true,
					);

				if( $perm_spam )
				{
					$entries['tools']['entries']['antispam'] = array(
							'text' => T_('Antispam').'&hellip;',
							'href' => $admin_url.'?ctrl=antispam',
						);
				}

				if( $perm_options )
				{
						$entries['tools']['entries']['status'] = array(
									'text' => T_('System status').'&hellip;',
									'href' => $admin_url.'?ctrl=system',
								);
				}

				if( $perm_options || $perm_slugs || $perm_maintenance )
				{
					$entries['tools']['entries']['tools'] = array(
							'text' => T_('More tools'),
						);

					if( $perm_options )
					{
							$entries['tools']['entries']['tools']['entries']['crontab'] = array(
										'text' => T_('Scheduler').'&hellip;',
										'href' => $admin_url.'?ctrl=crontab',
									);
							$entries['tools']['entries']['tools']['entries']['misc'] = array(
										'text' => T_('Misc tools').'&hellip;',
										'href' => $admin_url.'?ctrl=tools',
									);
					}

					if( $perm_slugs )
					{
							$entries['tools']['entries']['tools']['entries']['slugs'] = array(
										'text' => T_('Slugs').'&hellip;',
										'href' => $admin_url.'?ctrl=slugs',
									);
					}

					if( $perm_maintenance )
					{
						$entries['tools']['entries']['tools']['entries']['backup'] = array(
										'text' => T_('Backup').'&hellip;',
										'href' => $admin_url.'?ctrl=backup',
									);
						$entries['tools']['entries']['tools']['entries']['upgrade'] = array(
										'text' => T_('Upgrade').'&hellip;',
										'href' => $admin_url.'?ctrl=upgrade',
									);
					}
				}
			}


			if( $perm_options )
			{	// Global settings:
				$entries['tools']['disabled'] = false;
				if( !empty($entries['tools']['entries']))
				{
					$entries['tools']['entries']['settings_sep'] = array(
							'separator' => true,
						);
				}
				$entries['tools']['entries']['settings'] = array(
						'text' => T_('Global settings').'&hellip;',
						'href' => $admin_url.'?ctrl=settings',
					);
			}
		}

		if( $entries !== NULL )
		{
			$topleft_Menu->add_menu_entries( NULL, $entries );
		}




		// ---------------------------------------------------------------------------

		/*
		 * RIGHT MENU
		 */
		global $localtimenow, $is_admin_page;

		$entries = array(
			'userprefs' => array(
					'text' => ($current_User->has_avatar() ? $current_User->get_avatar_imgtag( 'crop-15x15', '', 'top' ).' ' : '' ).'<strong>'.$current_User->login.'</strong>',
					'href' => get_user_profile_url(),
					'entries' => array(
						'profile' => array(
								'text' => T_('Edit your profile').'&hellip;',
								'href' => get_user_profile_url(),
							),
						'avatar' => array(
								'text' => T_('Change your profile picture').'&hellip;',
								'href' => get_user_avatar_url(),
							),
						'pwdchange' => array(
								'text' => T_('Change your password').'&hellip;',
								'href' => get_user_pwdchange_url(),
							),
						'userprefs' => array(
								'text' => T_('Set your preferences').'&hellip;',
								'href' => get_user_preferences_url(),
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
				$redirect_to = rawurlencode(regenerate_url('', '', '', '&'));
				foreach( $admin_skins as $admin_skin )
				{
					$entries['userprefs']['entries']['admskins']['entries'][$admin_skin] = array(
							'text' => $admin_skin,
							'href' => $dispatcher.'?ctrl=users&amp;action=change_admin_skin&amp;new_admin_skin='.rawurlencode($admin_skin)
								.'&amp;redirect_to='.$redirect_to
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

		// AB switch:
		if( $perm_admin_normal )
		{	// User must have permission to access admin...
			if( $is_admin_page )
			{
				if( !empty( $Blog ) )
				{
					$entries['abswitch'] = array(
							'text' => T_('Blog'),
							'class' => 'icon switch-to-blog',
							'href' => $Blog->get( 'url' ),
						);
				}
				else
				{
					$entries['abswitch'] = array(
							'text' => T_('Home'),
							'class' => 'icon switch-to-blog',
							'href' => $home_url,
						);
				}
			}
			else
			{
				$entries['abswitch'] = array(
						'text' => T_('Admin'),
						'class' => 'icon switch-to-admin',
						'href' => $admin_url,
					);
			}
		}

		$topright_Menu->add_menu_entries( NULL, $entries );

		$topright_Menu->add_menu_entries( NULL, array(
			'logout' => array(
				'text' => T_('Logout'),
				'class' => 'icon logout',
				'href' => get_user_logout_url(),
				)
		 ) );

	}


	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
		global $blog, $loc_transinfo, $ctrl, $dispatcher, $Settings;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		$perm_admin_normal = $current_User->check_perm( 'admin', 'normal' );

		if( $perm_admin_normal && $current_User->check_perm( 'options', 'view' ) )
		{	// Permission to view settings:
			$AdminUI->add_menu_entries( NULL, array(
						'options' => array(
							'text' => T_('Global settings'),
							'href' => $dispatcher.'?ctrl=settings',
							'entries' => array(
								'general' => array(
									'text' => T_('General'),
									'href' => '?ctrl=settings' ),
								'features' => array(
									'text' => T_('Features'),
									'href' => '?ctrl=features' ),
								'regional' => array(
									'text' => T_('Regional'),
									'href' => '?ctrl=locales'.( (isset($loc_transinfo) && $loc_transinfo) ? '&amp;loc_transinfo=1' : '' ) ),
								'countries' => array(
									'text' => T_('Countries'),
									'href' => '?ctrl=countries'),
								'currencies' => array(
									'text' => T_('Currencies'),
									'href' => '?ctrl=currencies'),
								'plugins' => array(
									'text' => T_('Plugins'),
									'href' => '?ctrl=plugins'),
							)
						),
					) );
		}

		if( $perm_admin_normal && $current_User->check_perm( 'users', 'view' ) )
		{	// Permission to view users:
			$users_entries = array(
						'text' => T_('Users'),
						'title' => T_('User management'),
						'href' => '?ctrl=users' );

			$user_ID = param( 'user_ID', 'integer', NULL );
		}
		else
		{
			$user_ID = $current_User->ID;
			// Only perm to view his own profile:
			$users_entries = array(
						'text' => T_('My profile'),
						'title' => T_('User profile'),
						'href' => '?ctrl=user&amp;user_tab=profile&amp;user_ID='.$user_ID );
		}

		if( $perm_admin_normal && $current_User->check_perm( 'users', 'view' ) )
		{ // Has permission for viewing all users
			// fp> the following submenu needs even further breakdown.
			$users_entries['entries'] = array(
							'users' => array(
								'text' => T_('Users & Groups'),
								'href' => '?ctrl=users'	),
							'usersettings' => array(
								'text' => T_('User settings'),
 								'href' => '?ctrl=usersettings',
 								'entries' => array(
									'usersettings' => array(
										'text' => T_('User latitude'),
 										'href' => '?ctrl=usersettings' ),
									'registration' => array(
										'text' => T_('Registration'),
 										'href' => '?ctrl=registration' ),
									'userfields' => array(
										'text' => T_('User fields'),
										'href' => '?ctrl=userfields' ),
									),
								),
							);
		}

		$AdminUI->add_menu_entries( NULL, array( 'users' => $users_entries ) );

		$perm_options = $current_User->check_perm( 'options', 'view' );
		$perm_spam = $current_User->check_perm( 'spamblacklist', 'view' );
		$perm_slugs = $current_User->check_perm( 'slugs', 'view' );
		if( $perm_admin_normal && ( $perm_options || $perm_spam || $perm_slugs ) )
		{	// Permission to view tools, antispam or slugs.
			$tools_entries = array( 'tools' => array(
					'text' => T_('Tools'),
					'entries' => array(),
				) );

			if( $perm_options )
			{	// Permission to view settings:
				// FP> This assumes that we don't let regular users access the tools, including plugin tools.
				$tools_entries['tools']['href'] = '?ctrl=crontab';
				$tools_entries['tools']['entries']['cron'] = array(
									'text' => T_('Scheduler'),
									'href' => '?ctrl=crontab' );
				$tools_entries['tools']['entries']['system'] = array(
									'text' => T_('System'),
									'href' => '?ctrl=system' );
			}
			if( $perm_spam )
			{	// Permission to view antispam:
				if( !$perm_options )
				{
					$tools_entries['tools']['href'] = '?ctrl=antispam';
				}
				$tools_entries['tools']['entries']['antispam'] = array(
									'text' => T_('Antispam'),
									'href' => '?ctrl=antispam' );

				if( $perm_options )
				{	// If we have access to options, then we add a submenu:
					$tools_entries['tools']['entries']['antispam']['entries']['blacklist'] = array(
											'text' => T_('Blacklist'),
											'href' => '?ctrl=antispam' );

					$tools_entries['tools']['entries']['antispam']['entries']['settings'] = array(
											'text' => T_('Settings'),
											'href' => '?ctrl=antispam&amp;tab3=settings' );

					if( $current_User->check_perm( 'options', 'edit' ) )
					{
						$tools_entries['tools']['entries']['antispam']['entries']['tools'] = array(
												'text' => T_('Tools'),
												'href' => '?ctrl=antispam&amp;tab3=tools' );
					}
				}
			}
			if( $perm_slugs )
			{	// Permission to view slugs:
				if( !$perm_options && !$perm_spam )
				{
					$tools_entries['tools']['href'] = '?ctrl=slugs';
				}
				$tools_entries['tools']['entries']['slugs'] = array(
									'text' => T_('Slugs'),
									'href' => '?ctrl=slugs' );
			}
			$AdminUI->add_menu_entries( NULL, $tools_entries );
		}
	}
}

$_core_Module = new _core_Module();


/*
 * $Log$
 * Revision 1.81  2011/09/22 07:43:43  efy-yurybakh
 * icons in a single sprite
 *
 * Revision 1.80  2011/09/15 08:58:45  efy-asimo
 * Change user tabs display
 *
 * Revision 1.79  2011/09/13 16:00:18  fplanque
 * Enhanced back-office navigation.
 *
 * Revision 1.78  2011/09/13 15:31:35  fplanque
 * Enhanced back-office navigation.
 *
 * Revision 1.77  2011/09/06 00:54:38  fplanque
 * i18n update
 *
 * Revision 1.76  2011/09/05 23:00:25  fplanque
 * minor/doc/cleanup/i18n
 *
 * Revision 1.75  2011/09/05 14:17:25  sam2kb
 * Refactor antispam controller
 *
 * Revision 1.74  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.73  2011/05/11 07:11:51  efy-asimo
 * User settings update
 *
 * Revision 1.72  2011/04/06 13:30:55  efy-asimo
 * Refactor profile display
 *
 * Revision 1.71  2011/02/23 21:45:18  fplanque
 * minor / cleanup
 *
 * Revision 1.70  2011/02/22 06:51:06  efy-asimo
 * Fix right menu
 *
 * Revision 1.69  2011/02/15 15:37:00  efy-asimo
 * Change access to admin permission
 *
 * Revision 1.68  2010/11/04 18:29:46  sam2kb
 * View personal blogs in user profile
 *
 * Revision 1.67  2010/11/03 19:44:14  sam2kb
 * Increased modularity - files_Module
 * Todo:
 * - split core functions from _file.funcs.php
 * - check mtimport.ctrl.php and wpimport.ctrl.php
 * - do not create demo Photoblog and posts with images (Blog A)
 *
 * Revision 1.66  2010/10/15 13:10:09  efy-asimo
 * Convert group permissions to pluggable permissions - part1
 *
 * Revision 1.65  2010/06/13 22:29:05  sam2kb
 * minor
 *
 * Revision 1.64  2010/06/01 11:33:19  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.63  2010/05/10 14:26:17  efy-asimo
 * Paged Comments & filtering & add comments listview
 *
 * Revision 1.62  2010/04/23 09:39:44  efy-asimo
 * "SEO setting" for help link and Groups slugs permission implementation
 *
 * Revision 1.61  2010/03/29 12:25:30  efy-asimo
 * allow multiple slugs per post
 *
 * Revision 1.60  2010/02/26 15:52:20  efy-asimo
 * combine skin and skin settings tab into one single tab
 *
 * Revision 1.59  2010/02/08 17:51:26  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.58  2010/01/30 18:55:15  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.57  2010/01/10 23:24:09  fplanque
 * crumbs...
 *
 * Revision 1.56  2009/12/12 19:14:12  fplanque
 * made avatars optional + fixes on img props
 *
 * Revision 1.55  2009/12/06 22:55:21  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.54  2009/12/02 02:22:36  fplanque
 * add menu entry
 *
 * Revision 1.53  2009/11/30 23:16:24  fplanque
 * basic cache invalidation is working now
 *
 * Revision 1.52  2009/11/22 19:06:48  efy-maxim
 * show all user tabs
 *
 * Revision 1.51  2009/11/21 16:39:55  fplanque
 * fix / doc
 *
 * Revision 1.50  2009/11/21 13:31:57  efy-maxim
 * 1. users controller has been refactored to users and user controllers
 * 2. avatar tab
 * 3. jQuery to show/hide custom duration
 *
 * Revision 1.49  2009/11/15 20:01:09  fplanque
 * todo
 *
 * Revision 1.48  2009/11/11 03:24:49  fplanque
 * misc/cleanup
 *
 * Revision 1.46  2009/10/26 12:59:34  efy-maxim
 * users management
 *
 * Revision 1.45  2009/10/25 19:20:30  efy-maxim
 * users settings
 *
 * Revision 1.44  2009/10/25 18:43:35  efy-maxim
 * -action
 *
 * Revision 1.43  2009/10/25 15:22:42  efy-maxim
 * user - identity, password, preferences tabs
 *
 * Revision 1.42  2009/10/18 20:15:51  efy-maxim
 * 1. backup, upgrade have been moved to maintenance module
 * 2. maintenance module permissions
 *
 * Revision 1.41  2009/10/17 16:49:10  efy-maxim
 * upgrade
 *
 * Revision 1.40  2009/10/17 16:31:32  efy-maxim
 * Renamed: T_groupsettings to T_groups__groupsettings, T_usersettings to T_users__usersettings
 *
 * Revision 1.39  2009/10/17 15:56:45  efy-maxim
 * updates has been moved from backup to updates folder
 *
 * Revision 1.38  2009/10/17 14:12:22  efy-maxim
 * Upgrader prototype
 *
 * Revision 1.37  2009/10/16 18:18:11  efy-maxim
 * files and database backup
 *
 * Revision 1.36  2009/10/12 23:54:56  blueyed
 * Return to current page when changing admin skin (via regenerate_url)
 *
 * Revision 1.35  2009/10/08 20:05:51  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.34  2009/09/24 21:05:39  fplanque
 * no message
 *
 * Revision 1.33  2009/09/24 10:15:21  efy-bogdan
 * Separate controller added for groups
 *
 * Revision 1.32  2009/09/23 09:38:52  efy-bogdan
 * Added group controller to controllers array
 *
 * Revision 1.31  2009/09/21 03:14:35  fplanque
 * modularized a little more
 *
 * Revision 1.30  2009/09/16 00:48:50  fplanque
 * getting a bit more serious with modules
 *
 * Revision 1.29  2009/09/15 19:31:55  fplanque
 * Attempt to load classes & functions as late as possible, only when needed. Also not loading module specific stuff if a module is disabled (module granularity still needs to be improved)
 * PHP 4 compatible. Even better on PHP 5.
 * I may have broken a few things. Sorry. This is pretty hard to do in one swoop without any glitch.
 * Thanks for fixing or reporting if you spot issues.
 *
 * Revision 1.28  2009/09/14 18:37:07  fplanque
 * doc/cleanup/minor
 *
 * Revision 1.27  2009/09/14 11:54:21  efy-bogdan
 * Moved Default user permissions under a new tab
 *
 * Revision 1.26  2009/09/11 18:34:05  fplanque
 * userfields editing module.
 * needs further cleanup but I think it works.
 *
 * Revision 1.25  2009/09/10 13:01:30  efy-maxim
 * Messaging module - added initializer
 *
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
