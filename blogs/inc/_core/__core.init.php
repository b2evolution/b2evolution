<?php
/**
 * This is the init file for the core module.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: __core.init.php 8214 2015-02-10 10:17:40Z yura $
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * This is supposed to be overriden by sth more useful when a more useful module is loaded
 * Typically should be 'dashboard'
 */
$default_ctrl = 'settings';

/**
 * Minimum PHP version required for _core module to function properly.
 * This value can't be higher then the application required php version.
 */
$required_php_version[ '_core' ] = '5.0';

/**
 * Minimum MYSQL version required for _core module to function properly.
 */
$required_mysql_version[ '_core' ] = '5.0.3';

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases'] = array(
		'T_antispam'               => $tableprefix.'antispam',
		'T_antispam__iprange'      => $tableprefix.'antispam__iprange',
		'T_cron__log'              => $tableprefix.'cron__log',
		'T_cron__task'             => $tableprefix.'cron__task',
		'T_regional__country'      => $tableprefix.'regional__country',
		'T_regional__region'       => $tableprefix.'regional__region',
		'T_regional__subregion'    => $tableprefix.'regional__subregion',
		'T_regional__city'         => $tableprefix.'regional__city',
		'T_regional__currency'     => $tableprefix.'regional__currency',
		'T_groups'                 => $tableprefix.'groups',
		'T_groups__groupsettings'  => $tableprefix.'groups__groupsettings',
		'T_global__cache'          => $tableprefix.'global__cache',
		'T_i18n_original_string'   => $tableprefix.'i18n_original_string',
		'T_i18n_translated_string' => $tableprefix.'i18n_translated_string',
		'T_locales'                => $tableprefix.'locales',
		'T_plugins'                => $tableprefix.'plugins',
		'T_pluginevents'           => $tableprefix.'pluginevents',
		'T_pluginsettings'         => $tableprefix.'pluginsettings',
		'T_pluginusersettings'     => $tableprefix.'pluginusersettings',
		'T_settings'               => $tableprefix.'settings',
		'T_users'                  => $tableprefix.'users',
		'T_users__fielddefs'       => $tableprefix.'users__fielddefs',
		'T_users__fieldgroups'     => $tableprefix.'users__fieldgroups',
		'T_users__fields'          => $tableprefix.'users__fields',
		'T_users__reports'         => $tableprefix.'users__reports',
		'T_users__usersettings'    => $tableprefix.'users__usersettings',
		'T_slug'                   => $tableprefix.'slug',
		'T_email__log'             => $tableprefix.'email__log',
		'T_email__returns'         => $tableprefix.'email__returns',
		'T_email__address'         => $tableprefix.'email__address',
		'T_email__campaign'        => $tableprefix.'email__campaign',
		'T_email__campaign_send'   => $tableprefix.'email__campaign_send',
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
		'antispam'         => 'antispam/antispam.ctrl.php',
		'crontab'          => 'cron/cronjobs.ctrl.php',
		'regional'         => 'regional/regional_dispatch.ctrl.php',
		'time'             => 'regional/time.ctrl.php',
		'countries'        => 'regional/countries.ctrl.php',
		'regions'          => 'regional/regions.ctrl.php',
		'subregions'       => 'regional/subregions.ctrl.php',
		'cities'           => 'regional/cities.ctrl.php',
		'currencies'       => 'regional/currencies.ctrl.php',
		'locales'          => 'locales/locales.ctrl.php',
		'translation'      => 'locales/translation.ctrl.php',
		'plugins'          => 'plugins/plugins.ctrl.php',
		'gensettings'      => 'settings/settings.ctrl.php',
		'remotepublish'    => 'settings/remotepublish.ctrl.php',
		'settings'         => 'settings/settings_dispatch.ctrl.php',
		'stats'            => 'sessions/stats.ctrl.php',
		'system'           => 'tools/system.ctrl.php',
		'user'             => 'users/user.ctrl.php',
		'users'            => 'users/users.ctrl.php',
		'userfields'       => 'users/userfields.ctrl.php',
		'userfieldsgroups' => 'users/userfieldsgroups.ctrl.php',
		'usersettings'     => 'users/settings.ctrl.php',
		'registration'     => 'users/registration.ctrl.php',
		'display'          => 'users/display.ctrl.php',
		'groups'           => 'users/groups.ctrl.php',
		'accountclose'     => 'users/account_close.ctrl.php',
		'upload'           => 'files/upload.ctrl.php',
		'slugs'            => 'slugs/slugs.ctrl.php',
		'email'            => 'tools/email.ctrl.php',
		'campaigns'        => 'email_campaigns/campaigns.ctrl.php',
	);


/**
 * Get the CountryCache
 *
 * @param string The text that gets used for the "None" option in the objects options list (Default: T_('Unknown')).
 * @return CountryCache
 */
function & get_CountryCache( $allow_none_text = NULL )
{
	global $CountryCache;

	if( ! isset( $CountryCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'regional/model/_country.class.php', 'Country' );
		if( ! isset( $allow_none_text ) )
		{
			$allow_none_text = T_('Unknown');
		}
		$CountryCache = new DataObjectCache( 'Country', true, 'T_regional__country', 'ctry_', 'ctry_ID', 'ctry_code', 'ctry_name', $allow_none_text );
	}

	return $CountryCache;
}


/**
 * Get the RegionCache
 *
 * @return RegionCache
 */
function & get_RegionCache()
{
	global $RegionCache;

	if( ! isset( $RegionCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'regional/model/_region.class.php', 'Region' );
		$RegionCache = new DataObjectCache( 'Region', false, 'T_regional__region', 'rgn_', 'rgn_ID', 'rgn_name', 'rgn_name', T_('Unknown') );
	}

	return $RegionCache;
}


/**
 * Get the SubregionCache
 *
 * @return SubregionCache
 */
function & get_SubregionCache()
{
	global $SubregionCache;

	if( ! isset( $SubregionCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'regional/model/_subregion.class.php', 'Subregion' );
		$SubregionCache = new DataObjectCache( 'Subregion', false, 'T_regional__subregion', 'subrg_', 'subrg_ID', 'subrg_name', 'subrg_name', T_('Unknown') );
	}

	return $SubregionCache;
}


/**
 * Get the CityCache
 *
 * @return CityCache
 */
function & get_CityCache()
{
	global $CityCache;

	if( ! isset( $CityCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'regional/model/_city.class.php', 'City' );
		$CityCache = new DataObjectCache( 'City', false, 'T_regional__city', 'city_', 'city_ID', 'city_name', 'city_name', T_('Unknown') );
	}

	return $CityCache;
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
		$CurrencyCache = new DataObjectCache( 'Currency', true, 'T_regional__currency', 'curr_', 'curr_ID', 'curr_code', 'curr_code');
	}

	return $CurrencyCache;
}


/**
 * Get the GroupCache
 *
 * @param boolean TRUE to ignore cached object and create new cache object
 * @param string The text that gets used for the "None" option in the objects options list (Default: T_('No group')).
 * @return GroupCache
 */
function & get_GroupCache( $force_cache = false, $allow_none_text = NULL )
{
	global $Plugins;
	global $GroupCache;

	if( $force_cache || ! isset( $GroupCache ) )
	{ // Cache doesn't exist yet:
		if( is_null( $allow_none_text ) )
		{ // Set default value for "None" option
			$allow_none_text = T_('No group');
		}
		$Plugins->get_object_from_cacheplugin_or_create( 'GroupCache', 'new DataObjectCache( \'Group\', true, \'T_groups\', \'grp_\', \'grp_ID\', \'grp_name\', \'\', \''.str_replace( "'", "\'", $allow_none_text ).'\' )' );
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
 * Get the UserFieldGroupCache
 *
 * @return UserFieldGroupCache
 */
function & get_UserFieldGroupCache()
{
	global $UserFieldGroupCache;

	if( ! isset( $UserFieldGroupCache ) )
	{	// Cache doesn't exist yet:
		$UserFieldGroupCache = new DataObjectCache( 'UserfieldGroup', false, 'T_users__fieldgroups', 'ufgp_', 'ufgp_ID', 'ufgp_name', 'ufgp_name' ); // COPY (FUNC)
	}

	return $UserFieldGroupCache;
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
 * Get the IPRangeCache
 *
 * @return IPRangeCache
 */
function & get_IPRangeCache()
{
	global $IPRangeCache;

	if( ! isset( $IPRangeCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'antispam/model/_iprangecache.class.php', 'IPRangeCache' );
		$IPRangeCache = new IPRangeCache();
	}

	return $IPRangeCache;
}


/**
 * Get the DomainCache
 *
 * @return DomainCache
 */
function & get_DomainCache()
{
	global $DomainCache;

	if( ! isset( $DomainCache ) )
	{ // Cache doesn't exist yet:
		load_class( 'sessions/model/_domain.class.php', 'Domain' );
		$DomainCache = new DataObjectCache( 'Domain', false, 'T_basedomains', 'dom_', 'dom_ID', 'dom_name' );
	}

	return $DomainCache;
}


/**
 * Get the EmailAddressCache
 *
 * @return EmailAddressCache
 */
function & get_EmailAddressCache()
{
	global $EmailAddressCache;

	if( ! isset( $EmailAddressCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'tools/model/_emailaddress.class.php', 'EmailAddress' );
		$EmailAddressCache = new DataObjectCache( 'EmailAddress', false, 'T_email__address', 'emadr_', 'emadr_ID', 'emadr_address' );
	}

	return $EmailAddressCache;
}


/**
 * Get the EmailCampaignCache
 *
 * @return EmailCampaignCache
 */
function & get_EmailCampaignCache()
{
	global $EmailCampaignCache;

	if( ! isset( $EmailCampaignCache ) )
	{ // Cache doesn't exist yet:
		load_class( 'email_campaigns/model/_emailcampaign.class.php', 'EmailCampaign' );
		$EmailCampaignCache = new DataObjectCache( 'EmailCampaign', false, 'T_email__campaign', 'ecmp_', 'ecmp_ID' );
	}

	return $EmailCampaignCache;
}


/**
 * Get the CronjobCache
 *
 * @return CronjobCache
 */
function & get_CronjobCache()
{
	global $CronjobCache;

	if( ! isset( $CronjobCache ) )
	{	// Cache doesn't exist yet:
		load_class( 'cron/model/_cronjob.class.php', 'Cronjob' );
		$CronjobCache = new DataObjectCache( 'Cronjob', false, 'T_cron__task', 'ctsk_', 'ctsk_ID', 'ctsk_name', 'ctsk_name', T_('Unknown') );
	}

	return $CronjobCache;
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
		$this->check_required_php_version( '_core' );

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
		load_class( 'antispam/model/_iprange.class.php', 'IPRange' );
	}


	/**
	 * Get default module permissions
	 *
	 * @param integer Group ID
	 * @return array
	 */
	function get_default_group_permissions( $grp_ID )
	{
		// Deny browse/contact users from other countries in case of suspect and spammer users.
		$cross_country_settings_default = ( $grp_ID == 5 || $grp_ID == 6 ) ? 'denied' : 'allowed';
		switch( $grp_ID )
		{
			case 1:		// Administrators (group ID 1) have permission by default:
				$permadmin = 'normal'; // Access to Admin area
				$permusers = 'edit'; // Users & Groups
				$permoptions = 'edit'; // Global settings
				$permspam = 'edit'; // Antispam settings
				$permslugs = 'edit'; // Slug manager
				$permtemplates = 'allowed'; // Skin settings
				$permemails = 'edit'; // Email management
				$def_notification = 'full'; // Default notification type: short/full
				break;

			case 2:		// Moderators (group ID 2) have permission by default:
				$permadmin = 'normal';
				$permusers = 'view';
				$permoptions = 'view';
				$permspam = 'edit';
				$permslugs = 'none';
				$permtemplates = 'denied';
				$permemails = 'view';
				$def_notification = 'short';
				break;

			case 3:		// Trusted Users (group ID 3) have permission by default:
			case 4: 	// Normal Users (group ID 4) have permission by default:
				$permadmin = 'normal';
				$permusers = 'none';
				$permoptions = 'none';
				$permspam = 'view';
				$permslugs = 'none';
				$permtemplates = 'denied';
				$permemails = 'none';
				$def_notification = 'short';
				break;

			// case 5:		// Misbehaving/Suspect users (group ID 5) have permission by default:
			// case 6:  // Spammers/restricted Users
			default:
				// Other groups have no permission by default
				$permadmin = 'none';
				$permusers = 'none';
				$permoptions = 'none';
				$permspam = 'none';
				$permslugs = 'none';
				$permtemplates = 'denied';
				$permemails = 'none';
				$def_notification = 'short';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array(
			'perm_admin' => $permadmin,
			'perm_users' => $permusers,
			'perm_options' => $permoptions,
			'perm_spamblacklist' => $permspam,
			'perm_slugs' => $permslugs,
			'perm_templates' => $permtemplates,
			'perm_emails' => $permemails,
			'pm_notif' => $def_notification,
			'comment_subscription_notif' => $def_notification,
			'comment_moderation_notif' => $def_notification,
			'post_subscription_notif' => $def_notification,
			'post_moderation_notif' => $def_notification,
			'cross_country_allow_profiles' => $cross_country_settings_default,
			'cross_country_allow_contact' => $cross_country_settings_default
		 );
	}


	/**
	 * Get available group permissions
	 *
	 * @return array
	 */
	function get_available_group_permissions( $grp_ID = NULL )
	{
		global $Settings;

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
			$perm_users_values = array(
				'label' => T_( 'Users & Groups' ),
				'perm_block' => 'core',
				'perm_type' => 'info',
				'info' => T_( 'Full Access' ),
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
					array( 'no_toolbar', T_( 'No Toolbar' ) ),
					$none_option,
					array( 'restricted', T_( 'Restricted' ) ),
					array( 'normal', T_( 'Normal' ) ) ),
				'perm_type' => 'radiobox',
				'field_lines' => false,
			);
			$perm_users_values = array(
				'label' => T_('Users & Groups'),
				'user_func'  => 'check_core_user_perm',
				'group_func' => 'check_core_group_perm',
				'perm_block' => 'core',
				'options'  => array( $none_option, $view_details, $edit_option ),
				'perm_type' => 'radiobox',
				'field_lines' => false,
			);
		}

		$notification_options = array(
				array( 'short', T_( 'Short' ) ),
				array( 'full', T_( 'Full text' ) ) );
		$notifications_array = array(
				'group_func' => 'check_notification',
				'perm_block' => 'notifications',
				'options' => $notification_options,
				'perm_type' => 'radiobox',
				'field_note' => T_( 'Selecting "Full text" may generate email containing unwanted spam.' ),
				'field_lines' => false,
		);

		// Set additional note for cross country users restriction, if anonymous users can see the users list or users profiles
		$cross_country_note = '';
		if( $Settings->get('allow_anonymous_user_list') || $Settings->get('allow_anonymous_user_profiles') )
		{
			$cross_country_note = ' <span class="warning">'.T_('Browsing / Viewing users is currently allowed for anonymous users').'</span>';
		}

		$permissions = array(
			'perm_admin' => $perm_admin_values,
			'perm_users' => $perm_users_values,
			'perm_options' => array(
				'label' => T_('Settings'),
				'user_func'  => 'check_core_user_perm',
				'group_func' => 'check_core_group_perm',
				'perm_block' => 'core',
				'options'  => array( $none_option, $view_details, $edit_option ),
				'perm_type' => 'radiobox',
				'field_lines' => false,
				),
			'perm_spamblacklist' => array(
				'label' => T_( 'Antispam' ),
				'user_func'  => 'check_core_user_perm',
				'group_func' => 'check_core_group_perm',
				'perm_block' => 'core2',
				'options'  => array( $none_option, $view_option, $full_option ),
				'perm_type' => 'radiobox',
				'field_lines' => false,
				),
			'perm_slugs' => array(
				'label' => T_('Slug manager'),
				'user_func'  => 'check_core_user_perm',
				'group_func' => 'check_core_group_perm',
				'perm_block' => 'core2',
				'options'  => array( $none_option, $view_option, $full_option ),
				'perm_type' => 'radiobox',
				'field_lines' => false,
				),
			'perm_emails' => array(
				'label' => T_('Email management'),
				'user_func'  => 'check_core_user_perm',
				'group_func' => 'check_core_group_perm',
				'perm_block' => 'core2',
				'options'  => array( $none_option, $view_details, $edit_option ),
				'perm_type' => 'radiobox',
				'field_lines' => false,
				),
			'perm_templates' => array(
				'label' => T_('Skins'),
				'user_func'  => 'check_template_user_perm',
				'group_func' => 'check_template_group_perm',
				'perm_block' => 'core3',
				'perm_type' => 'checkbox',
				'note' => T_( 'Check to allow access to skin files.' ),
				),
			'pm_notif' => array_merge( 
				array( 'label' => T_( 'New Private Message notifications' ) ), $notifications_array
				),
			'comment_subscription_notif' => array_merge(
				array( 'label' => T_( 'New Comment subscription notifications' ) ), $notifications_array
				),
			'comment_moderation_notif' => array_merge(
				array( 'label' => T_( 'New Comment moderation notifications' ) ), $notifications_array
				),
			'post_subscription_notif' => array_merge(
				array( 'label' => T_( 'New Post subscription notifications' ) ), $notifications_array
				),
			'post_moderation_notif' => array_merge(
				array( 'label' => T_( 'New Post moderation notifications' ) ), $notifications_array
				),
			'cross_country_allow_profiles' => array(
				'label' => T_('Cross country'),
				'user_func'  => 'check_cross_country_user_perm',
				'group_func' => 'check_cross_country_group_perm',
				'perm_block' => 'additional',
				'perm_type' => 'checkbox',
				'note' => T_('Allow to browse users from other countries').$cross_country_note,
				),
			'cross_country_allow_contact' => array(
				'label' => '',
				'user_func'  => 'check_cross_country_user_perm',
				'group_func' => 'check_cross_country_group_perm',
				'perm_block' => 'additional',
				'perm_type' => 'checkbox',
				'note' => T_('Allow to contact users from other countries'),
				),
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

			case 'none':
				// display toolbar check
				if( $permlevel == 'toolbar' )
				{ // Even in case of No Access the toolbar must be displayed
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
	 * Check notification setting
	 */
	function check_notification( $permlevel, $permvalue, $permtarget )
	{
		// Check if user should receive full text notification or not. In every other case short notificaiton must be sent.
		return $permvalue == 'full';
	}

	/**
	 * Check permission for the group
	 */
	function check_cross_country_group_perm( $permlevel, $permvalue, $permtarget )
	{
		// Check if browse/contact users from other countries is allowed
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
						'text' => T_('More'),
						'disabled' => true,
					),
			);
		}
		elseif( $perm_admin_restricted )
		{	// restricted Access to Admin:
			$entries = array(
				'see' => array(
						'text' => T_('Structure'),
						'href' => $home_url,
						'title' => T_('See the home page'),
					),
			);
			if( $current_User->check_perm( 'blogs', 'view' ) ||
			    $current_User->check_role( 'member' ) ||
			    $current_User->check_perm( 'blogs', 'create' ) )
			{
				$entries[ 'blog' ] = array(
					'text' => T_('Blog'),
					'title' => T_('No blog is currently selected'),
					'disabled' => true,
				);
			}
			$entries[ 'tools' ] = array(
				'text' => T_('More'),
				'disabled' => true,
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

			if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
			{ // We have permission to add a post with at least one status:
				$write_item_url = $Blog->get_write_item_url();
				if( $write_item_url )
				{ // write item URL is not empty, so it's sure that user can create new post
					if( !$perm_admin_normal )
					{
						$entries[ 'write' ] = array(
							'text' => T_('Write'),
						);
					}
					$entries['write']['href'] = $write_item_url;
					$entries['write']['disabled'] = false;
					$entries['write']['title'] = T_('Write a new post into this blog');
				}
			}

			if( $perm_admin_normal )
			{
				if( empty( $write_item_url) )
				{ // Display restricted message on this blog
					$entries['write']['title'] = T_('You don\'t have permission to post into this blog');
				}

				// BLOG MENU:
				$items_url = $admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=restore';
				$entries['blog']['href'] = $items_url;
				$entries['blog']['disabled'] = false;
				$entries['blog']['title'] = T_('Manage this blog');

				if( $Blog->get_setting( 'use_workflow' ) )
				{ // Workflow view
					$entries['blog']['entries']['workflow'] = array(
									'text' => T_('Workflow view').'&hellip;',
									'href' => $items_url.'&amp;tab=tracker',
								);
				}

				if( $Blog->get( 'type' ) == 'manual' )
				{ // Manual view
					$entries['blog']['entries']['manual'] = array(
									'text' => T_('Manual view').'&hellip;',
									'href' => $items_url.'&amp;tab=manual',
								);
				}

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
								'text' => T_('Blog settings'),
								'href' => $admin_url.'?ctrl=coll_settings'.$blog_param,
								'entries' => array(
									'general' => array(
										'text' => T_('General').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=general'.$blog_param,
									),
									'features' => array(
										'text' => T_('Features').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=home'.$blog_param,
									),
									'skin' => array(
										'text' => T_('Skin').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=skin'.$blog_param,
									),
									'plugin_settings' => array(
										'text' => T_('Plugins').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=plugin_settings'.$blog_param,
									),
									'widgets' => array(
										'text' => T_('Widgets').'&hellip;',
										'href' => $admin_url.'?ctrl=widgets'.$blog_param,
									),
									'urls' => array(
										'text' => T_('URLs').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=urls'.$blog_param,
									),
									'seo' => array(
										'text' => T_('SEO').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=seo'.$blog_param,
									),
									'advanced' => array(
										'text' => T_('Advanced').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=advanced'.$blog_param,
									),
								)
						);

						if( $Blog && $Blog->advanced_perms )
						{
							$entries['blog']['entries']['general']['entries']['userperms'] = array(
								'text' => T_('User perms').'&hellip;',
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=perm'.$blog_param,
							);
							$entries['blog']['entries']['general']['entries']['groupperms'] = array(
								'text' => T_('Group perms').'&hellip;',
								'href' => $admin_url.'?ctrl=coll_settings&amp;tab=permgroup'.$blog_param,
							);
						}
				}

				if( ! is_admin_page() )
				{ // Display a menu to turn on/off the debug containers
					global $ReqURI, $Session;

					if( $Session->get( 'debug_containers_'.$Blog->ID ) == 1 )
					{ // To hide the debug containers
						$entries['blog']['entries']['containers'] = array(
							'text' => T_('Hide containers'),
							'href' => url_add_param( $ReqURI, 'debug_containers=hide' ),
						);
					}
					else
					{ // To show the debug containers
						$entries['blog']['entries']['containers'] = array(
							'text' => T_('Show containers'),
							'href' => url_add_param( $ReqURI, 'debug_containers=show' ),
						);
					}
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

			// PLACE HOLDER FOR MESSAGING MODULE:
			$entries['tools']['entries']['messaging'] = NULL;

			// PLACE HOLDER FOR FILES MODULE:
			$entries['tools']['entries']['files'] = NULL;

			$perm_spam = $current_User->check_perm( 'spamblacklist', 'view' );
			$perm_options = $current_User->check_perm( 'options', 'view' );
			$perm_emails = $current_User->check_perm( 'emails', 'view' );
			$perm_slugs = $current_User->check_perm( 'slugs', 'view' );
			$perm_maintenance = $current_User->check_perm( 'perm_maintenance', 'upgrade' );

			if( $perm_spam || $perm_options || $perm_slugs || $perm_maintenance )
			{
				if( $perm_emails )
				{
					$entries['tools']['entries']['email'] = array(
							'text' => T_('Emails'),
							'href' => $admin_url.'?ctrl=campaigns',
							'entries' => array(
								'campaigns' => array(
									'text' => T_('Campaigns').'&hellip;',
									'href' => $admin_url.'?ctrl=campaigns' ),
								'blocked' => array(
									'text' => T_('Addresses').'&hellip;',
									'href' => $admin_url.'?ctrl=email' ),
								'sent' => array(
									'text' => T_('Sent').'&hellip;',
									'href' => $admin_url.'?ctrl=email&amp;tab=sent' ),
								'return' => array(
									'text' => T_('Returned').'&hellip;',
									'href' => $admin_url.'?ctrl=email&amp;tab=return' ),
								'settings' => array(
									'text' => T_('Settings').'&hellip;',
									'href' => $admin_url.'?ctrl=email&amp;tab=settings' ),
								)
						);
				}

				$entries['tools']['disabled'] = false;
				$entries['tools']['entries']['tools_sep'] = array(
						'separator' => true,
					);

				if( $perm_options )
				{
					$entries['tools']['entries']['status'] = array(
							'text' => T_('System status').'&hellip;',
							'href' => $admin_url.'?ctrl=system',
						);
				}

				if( $perm_options )
				{
						$entries['tools']['entries']['crontab'] = array(
									'text' => T_('Scheduler').'&hellip;',
									'href' => $admin_url.'?ctrl=crontab',
								);
				}

				if( $perm_spam )
				{
					$entries['tools']['entries']['antispam'] = array(
							'text' => T_('Antispam'),
							'href' => $admin_url.'?ctrl=antispam',
							'entries' => array(
									'blacklist' => array(
										'text' => T_('Blacklist').'&hellip;',
										'href' => $admin_url.'?ctrl=antispam' )
								)
						);

					if( $perm_options )
					{	// If we have access to options, then we add a submenu:
						$entries['tools']['entries']['antispam']['entries']['ipranges'] = array(
								'text' => T_('IP Ranges').'&hellip;',
								'href' => $admin_url.'?ctrl=antispam&amp;tab3=ipranges' );
						$entries['tools']['entries']['antispam']['entries']['countries'] = array(
								'text' => T_('Countries').'&hellip;',
								'href' => $admin_url.'?ctrl=antispam&amp;tab3=countries' );

						if( $current_User->check_perm( 'stats', 'list' ) )
						{
							$entries['tools']['entries']['antispam']['entries']['domains'] = array(
									'text' => T_('Referring domains').'&hellip;',
									'href' => $admin_url.'?ctrl=antispam&amp;tab3=domains' );
						}

						$entries['tools']['entries']['antispam']['entries']['settings'] = array(
								'text' => T_('Settings').'&hellip;',
								'href' => $admin_url.'?ctrl=antispam&amp;tab3=settings' );

						if( $current_User->check_perm( 'options', 'edit' ) )
						{
							$entries['tools']['entries']['antispam']['entries']['tools'] = array(
									'text' => T_('Tools').'&hellip;',
									'href' => $admin_url.'?ctrl=antispam&amp;tab3=tools' );
						}
					}
				}

				if( $perm_slugs )
				{
					$entries['tools']['entries']['slugs'] = array(
							'text' => T_('Slugs').'&hellip;',
							'href' => $admin_url.'?ctrl=slugs'
						);
				}
			}


			if( $perm_options )
			{	// Global settings:
				$entries['tools']['entries']['general'] = array(
						'text' => T_('General').'&hellip;',
						'href' => $admin_url.'?ctrl=gensettings',
					);
				$entries['tools']['entries']['regional'] = array(
						'text' => T_('Regional'),
						'href' => $admin_url.'?ctrl=regional',
						'entries' => array(
							'locales' => array(
								'text' => T_('Locales').'&hellip;',
								'href' => $admin_url.'?ctrl=locales' ),
							'time' => array(
								'text' => T_('Time').'&hellip;',
								'href' => $admin_url.'?ctrl=time' ),
							'countries' => array(
								'text' => T_('Countries').'&hellip;',
								'href' => $admin_url.'?ctrl=countries' ),
							'regions' => array(
								'text' => T_('Regions').'&hellip;',
								'href' => $admin_url.'?ctrl=regions' ),
							'subregions' => array(
								'text' => T_('Sub-regions').'&hellip;',
								'href' => $admin_url.'?ctrl=subregions' ),
							'cities' => array(
								'text' => T_('Cities').'&hellip;',
								'href' => $admin_url.'?ctrl=cities' ),
							'currencies' => array(
								'text' => T_('Currencies').'&hellip;',
								'href' => $admin_url.'?ctrl=currencies' ),
						)
					);
				$entries['tools']['entries']['plugins'] = array(
						'text' => T_('Plugins').'&hellip;',
						'href' => $admin_url.'?ctrl=plugins',
					);
				$entries['tools']['entries']['remote'] = array(
						'text' => T_('Remote publishing').'&hellip;',
						'href' => $admin_url.'?ctrl=remotepublish',
					);
				$entries['tools']['entries']['maintenance'] = array(
						'text' => T_('Maintenance'),
						'href' => $admin_url.'?ctrl=tools',
						'entries' => array(
							'tools' => array(
								'text' => T_('Tools').'&hellip;',
								'href' => $admin_url.'?ctrl=tools' ),
							'import' => array(
								'text' => T_('Import').'&hellip;',
								'href' => $admin_url.'?ctrl=tools&amp;tab3=import' ),
							'test' => array(
								'text' => T_('Testing').'&hellip;',
								'href' => $admin_url.'?ctrl=tools&amp;tab3=test' ),
							'backup' => array(
								'text' => T_('Backup').'&hellip;',
								'href' => $admin_url.'?ctrl=backup' ),
							'upgrade' => array(
								'text' => T_('Check for updates').'&hellip;',
								'href' => $admin_url.'?ctrl=upgrade' ),
							)
					);
			}
		}

		global $debug, $debug_jslog;
		if( $debug || $debug_jslog )
		{	// Show JS log menu if debug is enabled
			$entries['jslog'] = array(
				'text'  => T_('JS log'),
				'title' => T_('JS log'),
				'class' => 'jslog_switcher'
			);
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
					'text' => $current_User->get_avatar_imgtag( 'crop-top-15x15', '', 'top' ).' <strong>'.$current_User->get_colored_login().'</strong>',
					'href' => get_user_profile_url(),
					'entries' => array(
						'profile' => array(
								'text' => T_('Edit your profile').'&hellip;',
								'href' => get_user_profile_url(),
							),
						'avatar' => array(
								'text' => T_('Your profile picture').'&hellip;',
								'href' => get_user_avatar_url(),
							),
						'pwdchange' => array(
								'text' => T_('Change password').'&hellip;',
								'href' => get_user_pwdchange_url(),
							),
						'userprefs' => array(
								'text' => T_('Preferences').'&hellip;',
								'href' => get_user_preferences_url(),
							),
						'subs' => array(
								'text' => T_('Notifications').'&hellip;',
								'href' => get_user_subs_url(),
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
				'text' => T_('Log out'),
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
		}

		$topright_Menu->add_menu_entries( NULL, $entries );

		$topright_Menu->add_menu_entries( NULL, array(
			'logout' => array(
				'text' => T_('Logout').' '.get_icon('close'),
				'class' => get_icon( 'close', 'rollover' ) ? 'rollover_sprite' : '',
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
		$perm_options = $current_User->check_perm( 'options', 'view' );
		$perm_users = $current_User->check_perm( 'users', 'view' );

		/**** Users | My profile ****/
		if( $perm_admin_normal && $perm_users )
		{ // Permission to view users:
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

		if( $perm_admin_normal && $perm_users )
		{ // Has permission for viewing all users
			// fp> the following submenu needs even further breakdown.
			$users_entries['entries'] = array(
					'users' => array(
						'text' => T_('Users'),
						'href' => '?ctrl=users' ),
					'stats' => array(
						'text' => T_('Stats'),
						'href' => '?ctrl=users&amp;tab=stats' ),
					'groups' => array(
						'text' => T_('User groups'),
						'href' => '?ctrl=groups' ),
					'usersettings' => array(
						'text' => T_('User settings'),
						'href' => '?ctrl=usersettings',
						'entries' => array(
							'usersettings' => array(
								'text' => T_('Profiles'),
								'href' => '?ctrl=usersettings' ),
							'registration' => array(
								'text' => T_('Registration'),
								'href' => '?ctrl=registration' ),
							'display' => array(
								'text' => T_('Display'),
								'href' => '?ctrl=display' ),
							'userfields' => array(
								'text' => T_('User fields'),
								'href' => '?ctrl=userfields' ),
							'accountclose' => array(
								'text' => T_('Account closing'),
								'href' => '?ctrl=accountclose' ),
							),
						),
				);
		}

		$AdminUI->add_menu_entries( NULL, array( 'users' => $users_entries ) );

		/**** Emails ****/
		$perm_emails = $current_User->check_perm( 'emails', 'view' );
		if( $perm_admin_normal && $perm_options && $perm_emails )
		{ // Permission to view email management:
			$AdminUI->add_menu_entries( NULL, array( 'email' => array(
					'text' => T_('Emails'),
					'href' => '?ctrl=campaigns',
					'entries' => array(
						'campaigns' => array(
							'text' => T_('Campaigns'),
							'href' => '?ctrl=campaigns' ),
						'blocked' => array(
							'text' => T_('Addresses'),
							'href' => '?ctrl=email' ),
						'sent' => array(
							'text' => T_('Sent'),
							'href' => '?ctrl=email&amp;tab=sent' ),
						'return' => array(
							'text' => T_('Returned'),
							'href' => '?ctrl=email&amp;tab=return' ),
						'settings' => array(
							'text' => T_('Settings'),
							'href' => '?ctrl=email&amp;tab=settings' ),
						) ) ) );
		}

		/**** System ****/
		if( $perm_admin_normal && $perm_options )
		{	// Permission to view settings:
			$AdminUI->add_menu_entries( NULL, array(
						'options' => array(
							'text' => T_('System'),
							'href' => $dispatcher.'?ctrl=system'
				) ) );

			$perm_spam = $current_User->check_perm( 'spamblacklist', 'view' );
			$perm_slugs = $current_User->check_perm( 'slugs', 'view' );

			if( $perm_admin_normal && ( $perm_options || $perm_spam || $perm_slugs ) )
			{	// Permission to view tools, antispam or slugs.
				if( $perm_options )
				{	// Permission to view settings:
					// FP> This assumes that we don't let regular users access the tools, including plugin tools.
					$AdminUI->add_menu_entries( 'options', array(
						'system' => array(
							'text' => T_('Status'),
							'href' => '?ctrl=system' ),
						'cron' => array(
							'text' => T_('Scheduler'),
							'href' => '?ctrl=crontab' ) ) );
				}
				if( $perm_spam )
				{	// Permission to view antispam:
					$AdminUI->add_menu_entries( 'options', array(
						'antispam' => array(
							'text' => T_('Antispam'),
							'href' => '?ctrl=antispam',
							'entries' => array(
								'blacklist' => array(
									'text' => T_('Blacklist'),
									'href' => '?ctrl=antispam' ) ) ) ) );

					if( $perm_options )
					{	// If we have access to options, then we add a submenu:
						$AdminUI->add_menu_entries( array( 'options', 'antispam' ), array(
							'ipranges' => array(
								'text' => T_('IP Ranges'),
								'href' => '?ctrl=antispam&amp;tab3=ipranges' ) ) );
						$AdminUI->add_menu_entries( array( 'options', 'antispam' ), array(
							'countries' => array(
								'text' => T_('Countries'),
								'href' => '?ctrl=antispam&amp;tab3=countries' ) ) );

						if( $current_User->check_perm( 'stats', 'list' ) )
						{
							$AdminUI->add_menu_entries( array( 'options', 'antispam' ), array(
								'domains' => array(
									'text' => T_('Referring domains'),
									'href' => '?ctrl=antispam&amp;tab3=domains' ) ) );
						}

						$AdminUI->add_menu_entries( array( 'options', 'antispam' ), array(
							'settings' => array(
								'text' => T_('Settings'),
								'href' => '?ctrl=antispam&amp;tab3=settings' ) ) );

						if( $current_User->check_perm( 'options', 'edit' ) )
						{
							$AdminUI->add_menu_entries( array( 'options', 'antispam' ), array(
								'tools' => array(
									'text' => T_('Tools'),
									'href' => '?ctrl=antispam&amp;tab3=tools' ) ) );
						}
					}
				}

				if( $perm_slugs )
				{	// Permission to view slugs:
					/*if( !$perm_options && !$perm_spam )
					{
						$tools_entries['tools']['href'] = '?ctrl=slugs';
					}*/
					$AdminUI->add_menu_entries( 'options', array(
						'slugs' => array(
							'text' => T_('Slugs'),
							'href' => '?ctrl=slugs' ) ) );
				}
			}


			$AdminUI->add_menu_entries( 'options', array(
				'general' => array(
					'text' => T_('General'),
					'href' => '?ctrl=gensettings', ),
				'regional' => array(
					'text' => T_('Regional'),
					'href' => '?ctrl=regional',
					'entries' => array(
						'locales' => array(
							'text' => T_('Locales'),
							'href' => '?ctrl=locales'.( (isset($loc_transinfo) && $loc_transinfo) ? '&amp;loc_transinfo=1' : '' ) ),
						'time' => array(
							'text' => T_('Time'),
							'href' => '?ctrl=time' ),
						'countries' => array(
							'text' => T_('Countries'),
							'href' => '?ctrl=countries' ),
						'regions' => array(
							'text' => T_('Regions'),
							'href' => '?ctrl=regions' ),
						'subregions' => array(
							'text' => T_('Sub-regions'),
							'href' => '?ctrl=subregions' ),
						'cities' => array(
							'text' => T_('Cities'),
							'href' => '?ctrl=cities' ),
						'currencies' => array(
							'text' => T_('Currencies'),
							'href' => '?ctrl=currencies' ),
						) ),
				'plugins' => array(
					'text' => T_('Plugins'),
					'href' => '?ctrl=plugins'),
				'remotepublish' => array(
					'text' => T_('Remote Publishing'),
					'href' => '?ctrl=remotepublish',
					'entries' => array(
						'eblog' => array(
							'text' => T_('Post by Email'),
							'href' => '?ctrl=remotepublish&amp;tab=eblog' ),
						'xmlrpc' => array(
							'text' => T_('XML-RPC'),
							'href' => '?ctrl=remotepublish&amp;tab=xmlrpc' )
					) ),
			) );
		}

	}
}

$_core_Module = new _core_Module();

?>