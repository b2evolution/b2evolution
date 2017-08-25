<?php
/**
 * This is the init file for the core module.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
$required_php_version[ '_core' ] = '5.2';

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
		'T_antispam__keyword'      => $tableprefix.'antispam__keyword',
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
		'T_users__invitation_code' => $tableprefix.'users__invitation_code',
		'T_users__reports'         => $tableprefix.'users__reports',
		'T_users__usersettings'    => $tableprefix.'users__usersettings',
		'T_users__organization'    => $tableprefix.'users__organization',
		'T_users__user_org'        => $tableprefix.'users__user_org',
		'T_users__secondary_user_groups' => $tableprefix.'users__secondary_user_groups',
		'T_users__profile_visits'  => $tableprefix.'users__profile_visits',
		'T_slug'                   => $tableprefix.'slug',
		'T_email__log'             => $tableprefix.'email__log',
		'T_email__returns'         => $tableprefix.'email__returns',
		'T_email__address'         => $tableprefix.'email__address',
		'T_email__campaign'        => $tableprefix.'email__campaign',
		'T_email__campaign_send'   => $tableprefix.'email__campaign_send',
		'T_syslog'                 => $tableprefix.'syslog',
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
		'remotepublish'    => 'settings/remotepublish.ctrl.php',
		'stats'            => 'sessions/stats.ctrl.php',
		'system'           => 'tools/system.ctrl.php',
		'user'             => 'users/user.ctrl.php',
		'users'            => 'users/users.ctrl.php',
		'userfields'       => 'users/userfields.ctrl.php',
		'userfieldsgroups' => 'users/userfieldsgroups.ctrl.php',
		'usersettings'     => 'users/settings.ctrl.php',
		'registration'     => 'users/registration.ctrl.php',
		'invitations'      => 'users/invitations.ctrl.php',
		'display'          => 'users/display.ctrl.php',
		'groups'           => 'users/groups.ctrl.php',
		'organizations'    => 'users/organizations.ctrl.php',
		'accountclose'     => 'users/account_close.ctrl.php',
		'upload'           => 'files/upload.ctrl.php',
		'slugs'            => 'slugs/slugs.ctrl.php',
		'email'            => 'tools/email.ctrl.php',
		'campaigns'        => 'email_campaigns/campaigns.ctrl.php',
		'syslog'           => 'tools/syslog.ctrl.php',
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
			$allow_none_text = NT_('Unknown');
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
		$RegionCache = new DataObjectCache( 'Region', false, 'T_regional__region', 'rgn_', 'rgn_ID', 'rgn_name', 'rgn_name', NT_('Unknown') );
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
		$SubregionCache = new DataObjectCache( 'Subregion', false, 'T_regional__subregion', 'subrg_', 'subrg_ID', 'subrg_name', 'subrg_name', NT_('Unknown') );
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
		$CityCache = new DataObjectCache( 'City', false, 'T_regional__city', 'city_', 'city_ID', 'city_name', 'city_name', NT_('Unknown') );
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
 * @param string The text that gets used for the "None" option in the objects options list (Default: NT_('No group')).
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
			$allow_none_text = NT_('No group');
		}
		$Plugins->get_object_from_cacheplugin_or_create( 'GroupCache', 'new DataObjectCache( \'Group\', true, \'T_groups\', \'grp_\', \'grp_ID\', \'grp_name\', \'grp_level DESC, grp_name ASC\', \''.str_replace( "'", "\'", $allow_none_text ).'\' )' );
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
 * Get the InvitationCache
 *
 * @return InvitationCache
 */
function & get_InvitationCache()
{
	global $InvitationCache;

	if( ! isset( $InvitationCache ) )
	{ // Cache doesn't exist yet:
		load_class( 'users/model/_invitation.class.php', 'Invitation' );
		$InvitationCache = new DataObjectCache( 'Invitation', false, 'T_users__invitation_code', 'ivc_', 'ivc_ID', 'ivc_code', 'ivc_code' ); // COPY (FUNC)
	}

	return $InvitationCache;
}


/**
 * Get the OrganizationCache
 *
 * @param string The text that gets used for the "None" option in the objects options list (Default: T_('Unknown')).
 * @return OrganizationCache
 */
function & get_OrganizationCache( $allow_none_text = NULL )
{
	global $OrganizationCache;

	if( ! isset( $OrganizationCache ) )
	{ // Cache doesn't exist yet:
		load_class( 'users/model/_organization.class.php', 'Organization' );
		$OrganizationCache = new DataObjectCache( 'Organization', false, 'T_users__organization', 'org_', 'org_ID', 'org_name', 'org_name', $allow_none_text ); // COPY (FUNC)
	}

	return $OrganizationCache;
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
		load_class( 'tools/model/_emailaddresscache.class.php', 'EmailAddressCache' );
		$EmailAddressCache = new EmailAddressCache();
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
 * Get the EmailCampaignPrerenderingCache
 *
 * @return EmailCampaignPrerenderingCache
 */
function & get_EmailCampaignPrerenderingCache()
{
	global $EmailCampaignPrerenderingCache;

	if( ! isset( $EmailCampaignPrerenderingCache ) )
	{	// Cache doesn't exist yet:
		$EmailCampaignPrerenderingCache = array();
	}

	return $EmailCampaignPrerenderingCache;
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
		$CronjobCache = new DataObjectCache( 'Cronjob', false, 'T_cron__task', 'ctsk_', 'ctsk_ID', 'ctsk_name', 'ctsk_name', NT_('Unknown') );
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
				$permorgs = 'edit';
				break;

			case 2:		// Moderators (group ID 2) have permission by default:
				$permadmin = 'normal';
				$permusers = 'moderate';
				$permoptions = 'view';
				$permspam = 'edit';
				$permslugs = 'none';
				$permtemplates = 'denied';
				$permemails = 'view';
				$def_notification = 'short';
				$permorgs = 'create';
				break;

			case 3:		// Editors (group ID 3) have permission by default:
				$permadmin = 'restricted';
				$permusers = 'none';
				$permoptions = 'none';
				$permspam = 'view';
				$permslugs = 'none';
				$permtemplates = 'denied';
				$permemails = 'none';
				$def_notification = 'short';
				$permorgs = 'create';
				break;

			case 4: 	// Normal Users (group ID 4) have permission by default:
				$permadmin = 'no_toolbar';
				$permusers = 'none';
				$permoptions = 'none';
				$permspam = 'view';
				$permslugs = 'none';
				$permtemplates = 'denied';
				$permemails = 'none';
				$def_notification = 'short';
				$permorgs = 'none';
				break;

			// case 5:		// Misbehaving/Suspect users (group ID 5) have permission by default:
			// case 6:  // Spammers/restricted Users
			default:
				// Other groups have no permission by default
				$permadmin = 'no_toolbar';
				$permusers = 'none';
				$permoptions = 'none';
				$permspam = 'none';
				$permslugs = 'none';
				$permtemplates = 'denied';
				$permemails = 'none';
				$def_notification = 'short';
				$permorgs = 'none';
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
			'perm_emails' => $permemails,
			'pm_notif' => $def_notification,
			'comment_subscription_notif' => $def_notification,
			'comment_moderation_notif' => $def_notification,
			'post_subscription_notif' => $def_notification,
			'post_moderation_notif' => $def_notification,
			'cross_country_allow_profiles' => $cross_country_settings_default,
			'cross_country_allow_contact' => $cross_country_settings_default,
			'perm_orgs' => $permorgs,
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
		$moderate_option = array( 'moderate', T_( 'Moderate' ), '' );
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
				'perm_block' => 'core_evobar',
				'perm_type' => 'info',
				'info' => T_( 'Visible link' ),
			);
			$perm_users_values = array(
				'label' => T_( 'Users & Groups' ),
				'perm_block' => 'core',
				'perm_type' => 'info',
				'info' => T_( 'Full Access' ).get_admin_badge( 'user', '#', '#', T_('This group has User Admin permission.') ),
			);
		}
		else
		{
			$perm_admin_values = array(
				'label' => T_( 'Evobar & Back-office' ),
				'user_func'  => 'check_admin_user_perm',
				'group_func' => 'check_admin_group_perm',
				'perm_block' => 'core_evobar',
				'options'  => array(
					array( 'no_toolbar', T_( 'No Toolbar' ) ),
					array( 'none', T_( 'No Back-office Access' ) ),
					array( 'restricted', T_( 'Restricted Back-office Access' ) ),
					array( 'normal', T_( 'Normal Back-office Access' ) ) ),
				'perm_type' => 'radiobox',
				'field_lines' => true,
			);
			$user_edit_option = $edit_option;
			$user_edit_option[1] .= get_admin_badge( 'user', '#', '#', T_('Select to give User Admin permission') );
			$perm_users_values = array(
				'label' => T_('Users & Groups'),
				'user_func'  => 'check_core_user_perm',
				'group_func' => 'check_core_group_perm',
				'perm_block' => 'core',
				'options'  => array( $none_option, $view_details, $moderate_option, $user_edit_option ),
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
			'pm_notif' => array_merge(
				array( 'label' => T_( 'New Private Message notifications' ) ), $notifications_array
				),
			'comment_subscription_notif' => array_merge(
				array( 'label' => T_( 'New Comment subscription notifications' ) ), $notifications_array
				),
			'comment_moderation_notif' => array_merge(
				array( 'label' => T_( 'Comment moderation notifications' ) ), $notifications_array
				),
			'post_subscription_notif' => array_merge(
				array( 'label' => T_( 'New Post subscription notifications' ) ), $notifications_array
				),
			'post_moderation_notif' => array_merge(
				array( 'label' => T_( 'Post moderation notifications' ) ), $notifications_array
				),
			'cross_country_allow_profiles' => array(
				'label' => T_('Users'),
				'user_func'  => 'check_cross_country_user_perm',
				'group_func' => 'check_cross_country_group_perm',
				'perm_block' => 'additional',
				'perm_type' => 'checkbox',
				'note' => T_('Allow to browse users from other countries').$cross_country_note,
				),
			'cross_country_allow_contact' => array(
				'label' => T_('Messages'),
				'user_func'  => 'check_cross_country_user_perm',
				'group_func' => 'check_cross_country_group_perm',
				'perm_block' => 'additional',
				'perm_type' => 'checkbox',
				'note' => T_('Allow to contact users from other countries'),
				),
			'perm_orgs' => array(
				'label'      => T_('Organizations'),
				'user_func'  => 'check_orgs_user_perm',
				'group_func' => 'check_orgs_group_perm',
				'perm_block' => 'additional',
				'options'    => array(
						// format: array( radio_button_value, radio_button_label, radio_button_note )
						array( 'none', T_('No Access') ),
						array( 'create', T_('Create & Edit owned organizations only') ),
						array( 'view', T_('Create & Edit owned organizations + View all') ),
						array( 'edit', T_('Full Access') )
					),
				'perm_type' => 'radiobox',
				'field_lines' => true,
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

			case 'moderate':
				// Users has moderate perms
				if( $permlevel == 'moderate' )
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
	 * Check an user permission for the organization. ( see 'user_func' in get_available_group_permissions() function  )
	 *
	 * @param string Requested permission level
	 * @param string Permission value, this is the value on the database
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_orgs_user_perm( $permlevel, $permvalue, $permtarget )
	{
		return true;
	}

	/**
	 * Check a group permission for the organization. ( see 'group_func' in get_available_group_permissions() function )
	 *
	 * @param string Requested permission level
	 * @param string Permission value
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_orgs_group_perm( $permlevel, $permvalue, $permtarget )
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

			case 'create':
				// Users has a create permisson:
				if( $permlevel == 'create' )
				{
					$perm = true;
					break;
				}
		}

		if( ! $perm && is_logged_in() && ! empty( $permtarget )
		    && ( $permlevel == 'edit' || $permlevel == 'view' ) )
		{	// If this perm level is still not allowed, check if current user is owner of the requested Poll:
			global $current_User;
			if( $current_User->ID == $permtarget->owner_user_ID )
			{	// Current user is owner
				$perm = true;
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
	 * Build the evobar menu
	 */
	function build_evobar_menu()
	{
		/**
		 * @var Menu
		 */
		global $topleft_Menu, $topright_Menu;
		global $current_User;
		global $baseurl, $home_url, $admin_url, $debug, $debug_jslog, $dev_menu, $seo_page_type, $robots_index;
		global $Collection, $Blog, $blog, $activate_collection_toolbar;

		global $Settings;

		$perm_admin_normal = $current_User->check_perm( 'admin', 'normal' );
		$perm_admin_restricted = $current_User->check_perm( 'admin', 'restricted' );
		$perm_users_view = $current_User->check_perm( 'users', 'view' );
		$perm_options = $current_User->check_perm( 'options', 'view' );
		$perm_spam = $perm_options && $current_User->check_perm( 'spamblacklist', 'view' );
		$perm_emails = $current_User->check_perm( 'emails', 'view' );
		$perm_maintenance = $current_User->check_perm( 'perm_maintenance', 'upgrade' );
		$entries = NULL;

		$working_blog = get_working_blog();
		if( $working_blog )
		{ // Set collection url only when current user has an access to the working blog
			if( is_admin_page() )
			{ // Front page of the working blog
				$BlogCache = & get_BlogCache();
				$working_Blog = & $BlogCache->get_by_ID( $working_blog );
				$collection_url = $working_Blog->get( 'url' );
			}
			else
			{ // Dashboard of the working blog
				$collection_url = $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog='.$working_blog;
			}
		}

		if( $perm_admin_normal || $perm_admin_restricted )
		{ // Normal OR Restricted Access to Admin:
			$entries = array();

			// ---- START OF "Site" MENU ----:
			$entries['site'] = array(
				'text' => T_('Site'),
				'href' => is_admin_page() ? $baseurl : $admin_url,
				'title' => is_admin_page() ? T_('Go to the site home page (Front-office)') : T_('Go to the site dashboard (Back-office)'),
				'entries' => array(
					'front' => array(
						'text' => T_('Site Front Page'),
						'href' => $baseurl,
						'title' => T_('Go to the site home page (Front-office)'),
					),
					'dashboard' => array(
						'text' => T_('Site Dashboard'),
						'href' => $admin_url,
						'title' => T_('Go to the site dashboard (Back-office)'),
					)
				)
			);

			// More site options:
			if( $perm_users_view )
			{
				$entries['site']['entries'][] = array( 'separator' => true );
				$entries['site']['entries']['users'] = array(
						'text' => T_('Users').'&hellip;',
						'href' => $admin_url.'?ctrl=users',
					);
			}

			// PLACE HOLDER FOR MESSAGING MODULE:
			$entries['site']['entries']['messaging'] = NULL;

			// PLACE HOLDER FOR FILES MODULE:
			$entries['site']['entries']['files'] = NULL;

			if( $perm_spam || $perm_options || $perm_maintenance || $perm_emails )
			{
				$entries['site']['entries'][] = array( 'separator' => true );

				if( $perm_emails )
				{
					$entries['site']['entries']['email'] = array(
							'text' => T_('Emails'),
							'href' => $admin_url.'?ctrl=campaigns',
							'entries' => array(
								'campaigns' => array(
									'text' => T_('Campaigns').'&hellip;',
									'href' => $admin_url.'?ctrl=campaigns' ),
								'settings' => array(
									'text' => T_('Settings').'&hellip;',
									'href' => $admin_url.'?ctrl=email&amp;tab=settings' ),
								'blocked' => array(
									'text' => T_('Addresses').'&hellip;',
									'href' => $admin_url.'?ctrl=email' ),
								'sent' => array(
									'text' => T_('Sent').'&hellip;',
									'href' => $admin_url.'?ctrl=email&amp;tab=sent' ),
								'return' => array(
									'text' => T_('Returned').'&hellip;',
									'href' => $admin_url.'?ctrl=email&amp;tab=return' ),
								)
						);
				}

				$entries['site']['entries']['system'] = array(
						'text' => T_('System'),
						'href' => $admin_url.'?ctrl=system',
					);

				if( $perm_options )
				{
					$entries['site']['entries']['system']['entries']['status'] = array(
							'text' => T_('Status').'&hellip;',
							'href' => $admin_url.'?ctrl=system',
						);
					$entries['site']['entries']['system']['entries']['crontab'] = array(
							'text' => T_('Scheduler').'&hellip;',
							'href' => $admin_url.'?ctrl=crontab',
						);
				}

				if( $perm_spam )
				{
					$entries['site']['entries']['system']['entries']['antispam'] = array(
							'text' => T_('Antispam').'&hellip;',
							'href' => $admin_url.'?ctrl=antispam',
						);
				}
			}

			if( $perm_options )
			{	// Global settings:
				$entries['site']['entries']['system']['entries']['regional'] = array(
						'text' => T_('Regional').'&hellip;',
						'href' => $admin_url.'?ctrl=regional',
					);
				$entries['site']['entries']['system']['entries']['skins'] = array(
						'text' => T_('Skins').'&hellip;',
						'href' => $admin_url.'?ctrl=skins&amp;tab=system'
					);
				$entries['site']['entries']['system']['entries']['plugins'] = array(
						'text' => T_('Plugins').'&hellip;',
						'href' => $admin_url.'?ctrl=plugins',
					);
				$entries['site']['entries']['system']['entries']['remote'] = array(
						'text' => T_('Remote publishing').'&hellip;',
						'href' => $admin_url.'?ctrl=remotepublish',
					);
				$entries['site']['entries']['system']['entries']['maintenance'] = array(
						'text' => T_('Maintenance').'&hellip;',
						'href' => $admin_url.'?ctrl=tools',
					);
				$entries['site']['entries']['system']['entries']['syslog'] = array(
						'text' => T_('System log'),
						'href' => $admin_url.'?ctrl=syslog',
					);
			}

			// PLACE HOLDER FOR SESSIONS MODULE:
			$entries['site']['entries']['stats_separator'] = NULL;
			$entries['site']['entries']['stats'] = NULL;

			// b2evolution info links:
			$entries['site']['entries'][] = array( 'separator' => true );
			$entries['site']['entries']['b2evo'] = array(
				'text' => 'b2evolution',
				'href' => $home_url,
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
							'href' => get_manual_url( NULL ),
							'target' => '_blank',
						),
					'sep' => array( 'separator' => true ),
					'twitter' => array(
							'text' => T_('b2evolution on twitter'),
							'href' => 'http://twitter.com/b2evolution',
							'target' => '_blank',
						),
					'facebook' => array(
							'text' => T_('b2evolution on facebook'),
							'href' => 'http://www.facebook.com/b2evolution',
							'target' => '_blank',
						)
					)
				);
			// ---- END OF "Site" MENU ----

			// ---- "Collection" MENU ----
			if( $working_blog )
			{	// Display a link to manage first available collection:
				$entries['blog'] = array(
					'text' => T_('Collection'),
					'href' => $collection_url,
					'disabled' => true,
				);
			}

			if( ! is_admin_page() )
			{	// ---- "Page" MENU ----
				$entries['page'] = array(
						'text' => T_('Page'),
						'entries' => array(
							// PLACE HOLDER FOR ENTRY "Edit contents":
							'edit'       => NULL,
							// PLACE HOLDERS FOR SESSIONS MODULE:
							'stats_sep'  => NULL,
							'stats_page' => NULL,
						)
					);
			}

			// ---- "Post"/"Edit" MENU ----
			if( $perm_admin_normal )
			{	// Only for normal access display a menu item to create new:
				$entries['post'] = array(
						'text' => get_icon( 'new' ).' './* TRANS: noun */ T_('Post'),
						'title' => T_('No blog is currently selected'),
						'disabled' => true,
						'entry_class' => 'rwdhide evobar-entry-new-post',
					);
			}
		}


		if( ( ! is_admin_page() || ! empty( $activate_collection_toolbar ) ) && ! empty( $Blog ) )
		{ // A collection is currently selected AND we can activate toolbar items for selected collection:

			if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
			{ // We have permission to add a post with at least one status:
				global $disp, $ctrl, $action, $Item, $edited_Item;
				if( ( $disp == 'edit' || $ctrl == 'items' ) &&
				    isset( $edited_Item ) &&
				    $edited_Item->ID > 0 &&
				    $view_item_url = $edited_Item->get_permanent_url() )
				{	// If curent user has a permission to edit a current viewing post:
					$entries['post'] = array(
							'text'        => get_icon( 'permalink' ).' '.T_('Permalink'),
							'href'        => $view_item_url,
							'title'       => T_('Permanent link to full entry'),
							'entry_class' => 'rwdhide',
						);
				}
				elseif( ! is_admin_page() &&
				  ( $disp == 'single' || $disp == 'page' ) &&
				  ! empty( $Item ) &&
				  $edit_item_url = $Item->get_edit_url() )
				{	// If curent user has a permission to edit a current viewing post:
					$entries['post'] = array(
							'text'        => '<span class="fa fa-pencil-square"></span> '.T_('Edit'),
							'href'        => $edit_item_url,
							'title'       => T_('Edit current post'),
							'entry_class' => 'rwdhide',
						);
					$entries['page']['entries']['edit'] = array(
							'text'  => T_('Edit contents').'&hellip;',
							'title' => T_('Edit current post'),
							'href'  => $edit_item_url,
						);
				}
				elseif( $write_item_url = $Blog->get_write_item_url() )
				{	// If write item URL is not empty, it's sure that user can create new post:
					if( ! $perm_admin_normal )
					{	// Initialize this menu item when user has no back-office access but can create new post:
						$entries['post'] = array(
							'text'        => get_icon( 'new' ).' './* TRANS: noun */ T_('Post'),
							'entry_class' => 'rwdhide evobar-entry-new-post',
						);
					}
					$entries['post']['href'] = $write_item_url;
					$entries['post']['disabled'] = false;
					$entries['post']['title'] = T_('Write a new post into this blog');
				}
			}

			if( $perm_admin_restricted && $working_blog )
			{
				if( empty( $write_item_url ) && empty( $edit_item_url ) && empty( $view_item_url ) )
				{	// Display a restricted message to create new post on this collection:
					$entries['post']['title'] = T_('You don\'t have permission to post into this blog');
				}

				// BLOG MENU:
				$entries['blog'] = array(
					'text' => T_('Collection'),
					'title' => T_('Manage this blog'),
					'href' => $collection_url,
				);

				$display_separator = false;
				if( $current_User->check_perm( 'blog_ismember', 'view', false, $Blog->ID ) )
				{ // Check if current user has an access to post lists
					$items_url = $admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=restore';

					// Collection front page
					$entries['blog']['entries']['coll_front'] = array(
							'text' => /* TRANS: %s is collection short name */ sprintf( T_('%s Front Page'), $Blog->get( 'shortname' ) ).'&hellip;',
							'href' => $Blog->get( 'url' )
						);

					// Collection dashboard
					$entries['blog']['entries']['coll_dashboard'] = array(
							'text' => /* TRANS: %s is collection short name */ sprintf( T_('%s Dashboard'), $Blog->get( 'shortname' ) ).'&hellip;',
							'href' => $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog='.$Blog->ID
						);

					$entries['blog']['entries'][] = array( 'separator' => true );

					if( $Blog->get( 'type' ) == 'manual' )
					{ // Manual view
						global $cat, $Item;
						if( ! empty( $cat ) && is_number( $cat ) )
						{	// Set category param from current selected category:
							$manual_view_cat_param = '&amp;cat_ID='.$cat;
						}
						elseif( ! empty( $Item ) &&
						        $Item->ID > 0 &&
						        ( $item_Chapter = & $Item->get_main_Chapter() ) )
						{	// Set category param from current selected item/post:
							$manual_view_cat_param = '&amp;cat_ID='.$item_Chapter->ID;
						}
						else
						{	// No selected category and item/post:
							$manual_view_cat_param = '';
						}
						$entries['blog']['entries']['manual'] = array(
								'text' => T_('Manual view').'&hellip;',
								'href' => $items_url.'&amp;tab=manual'.$manual_view_cat_param,
							);
					}

					if( $Blog->get_setting( 'use_workflow' ) && $current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID ) )
					{ // Workflow view
						$entries['blog']['entries']['workflow'] = array(
								'text' => T_('Workflow view').'&hellip;',
								'href' => $items_url.'&amp;tab=tracker',
							);
					}

					$entries['blog']['entries']['posts'] = array(
							'text' => T_('Posts').'&hellip;',
							'href' => $items_url,
						);
					$display_separator = true;
				}

				$perm_comments = $current_User->check_perm( 'blog_comments', 'view', false, $Blog->ID );
				if( $perm_comments || $current_User->check_perm( 'meta_comment', 'view', false, $Blog->ID ) )
				{	// Initialize comments menu tab if user can view normal or meta comments of the collection:
					$entries['blog']['entries']['comments'] = array(
							'text' => T_('Comments').'&hellip;',
							'href' => $admin_url.'?ctrl=comments&amp;blog='.$Blog->ID.'&amp;filter=restore'
								// Set url to meta comments page if user has a perm to view only meta comments:
								.( $perm_comments ? '' : '&amp;tab3=meta' ),
						);
					$display_separator = true;
				}

				// Chapters / Categories:
				if( $current_User->check_perm( 'blog_cats', 'edit', false, $Blog->ID ) )
				{ // Either permission for a specific blog or the global permission:
					$entries['blog']['entries']['chapters'] = array(
							'text' => T_('Categories').'&hellip;',
							'href' => $admin_url.'?ctrl=chapters&amp;blog='.$Blog->ID,
						);
					$display_separator = true;
				}

				if( $display_separator )
				{
					$entries['blog']['entries'][] = array( 'separator' => true );
				}

				// PLACEHOLDER FOR FILES MODULE:
				$entries['blog']['entries']['files'] = NULL;

				// BLOG SETTINGS:
				if( $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
				{ // We have permission to edit blog properties:
					$blog_param = '&amp;blog='.$Blog->ID;

					$entries['blog']['entries']['features'] = array(
							'text' => T_('Features'),
							'href' => $admin_url.'?ctrl=coll_settings&amp;tab=home'.$blog_param,
							'entries' => array(
									'front' => array(
											'text' => T_('Front page').'&hellip;',
											'href' => $admin_url.'?ctrl=coll_settings&amp;tab=home'.$blog_param,
										),
									'posts' => array(
											'text' => T_('Posts').'&hellip;',
											'href' => $admin_url.'?ctrl=coll_settings&amp;tab=features'.$blog_param,
										),
									'comments' => array(
											'text' => T_('Comments').'&hellip;',
											'href' => $admin_url.'?ctrl=coll_settings&amp;tab=comments'.$blog_param,
										),
									'other' => array(
											'text' => T_('Other displays').'&hellip;',
											'href' => $admin_url.'?ctrl=coll_settings&amp;tab=other'.$blog_param,
										),
									'more' => array(
											'text' => T_('More').'&hellip;',
											'href' => $admin_url.'?ctrl=coll_settings&amp;tab=more'.$blog_param,
										),
								)
						);
					$entries['blog']['entries']['skin'] = array(
							'text' => T_('Skin').'&hellip;',
							'href' => $admin_url.'?ctrl=coll_settings&amp;tab=skin'.$blog_param,
						);
					$entries['blog']['entries']['widgets'] = array(
							'text' => T_('Widgets').'&hellip;',
							'href' => $admin_url.'?ctrl=widgets'.$blog_param,
						);

					if( ! is_admin_page() )
					{ // Display an option to turn on/off containers display:
						global $ReqURI, $Session;

						if( $Session->get( 'display_containers_'.$Blog->ID ) == 1 )
						{ // To hide the debug containers
							$entries['blog']['entries']['containers'] = array(
								'text' => T_('Hide containers'),
								'href' => url_add_param( regenerate_url( 'display_containers' ), 'display_containers=hide' ),
							);
						}
						else
						{ // To show the debug containers
							$entries['blog']['entries']['containers'] = array(
								'text' => T_('Show containers'),
								'href' => url_add_param( regenerate_url( 'display_containers' ), 'display_containers=show' ),
							);
						}
					}

					$entries['blog']['entries']['general'] = array(
								'text' => T_('Settings'),
								'href' => $admin_url.'?ctrl=coll_settings'.$blog_param,
								'entries' => array(
									'general' => array(
										'text' => T_('General').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=general'.$blog_param,
									),
									'urls' => array(
										'text' => T_('URLs').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=urls'.$blog_param,
									),
									'seo' => array(
										'text' => T_('SEO').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=seo'.$blog_param,
									),
									'plugins' => array(
										'text' => T_('Plugins').'&hellip;',
										'href' => $admin_url.'?ctrl=coll_settings&amp;tab=plugins'.$blog_param,
									),
								)
						);

					if( $current_User->check_perm( 'options', 'view', false, $Blog->ID ) )
					{ // Post Types & Statuses
						$entries['blog']['entries']['general']['entries']['item_types'] = array(
								'text' => T_('Post Types').'&hellip;',
								'href' => $admin_url.'?ctrl=itemtypes&amp;tab=settings&amp;tab3=types'.$blog_param,
							);
						$entries['blog']['entries']['general']['entries']['item_statuses'] = array(
								'text' => T_('Post Statuses').'&hellip;',
								'href' => $admin_url.'?ctrl=itemstatuses&amp;tab=settings&amp;tab3=statuses'.$blog_param,
							);
					}

					$entries['blog']['entries']['general']['entries']['advanced'] = array(
							'text' => T_('Advanced').'&hellip;',
							'href' => $admin_url.'?ctrl=coll_settings&amp;tab=advanced'.$blog_param,
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

					if( $current_User->check_perm( 'options', 'view' ) )
					{ // Check if current user has a permission to view the common settings of the blogs
						$entries['blog']['entries']['general']['entries']['common_settings'] = array(
								'text' => T_('Common Settings').'&hellip;',
								'href' => $admin_url.'?ctrl=collections&amp;tab=blog_settings',
							);
					}
				}
			}
		}

		if( ! is_admin_page() && ! empty( $Blog ) )
		{	// Only front-office collection pages:


			// Display an option to turn on/off containers display:
			global $Session;
			$containers_status = $Session->get( 'display_containers_'.$Blog->ID );
			$entries['containers'] = array(
				'text'        => '<span class="fa fa-cubes"></span> '.T_('Widgets'),
				'href'        => url_add_param( regenerate_url( 'display_containers' ), 'display_containers='.( $containers_status ? 'hide' : 'show' ) ),
				'entry_class' => 'rwdhide',
				'class'       => ( $containers_status ? 'active' : '' ),
			);


			if( $perm_admin_restricted && $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
			{	// If current user has an access to back-office and to edit collection properties:
				$entries['skin'] = array(
					'text' => '<span class="fa fa-sliders"></span> '.T_('Skin'),
					'href' => $admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$Blog->ID,
					'entry_class' => 'rwdhide',
				);

				// Display menu item "Features" with depending on $disp:
				global $disp, $disp_detail;
				switch( $disp )
				{
					case 'front':
						$coll_features_url = $admin_url.'?ctrl=coll_settings&amp;tab=home&amp;blog='.$Blog->ID;
						break;
					case 'posts':
					case 'single':
					case 'page':
						$coll_features_url = $admin_url.'?ctrl=coll_settings&amp;tab=features&amp;blog='.$Blog->ID;
						break;
					case 'comments':
						$coll_features_url = $admin_url.'?ctrl=coll_settings&amp;tab=comments&amp;blog='.$Blog->ID;
						break;
					case 'users':
						$coll_features_url = $admin_url.'?ctrl=coll_settings&amp;tab=userdir&amp;blog='.$Blog->ID;
						break;
					default:
						$coll_features_url = $admin_url.'?ctrl=coll_settings&amp;tab=other&amp;blog='.$Blog->ID;
						break;
				}
				$entries['features'] = array(
					'text' => '<span class="fa fa-cog"></span> '.T_('Features'),
					'href' => $coll_features_url,
					'entry_class' => 'rwdhide',
				);
			}
		}

		if( $perm_admin_restricted )
		{

			// DEV MENU:
			$dev_entries = array();
			if( $dev_menu || $debug || $debug_jslog )
			{
				if( isset($Blog) )
				{
					$dev_entries['coll'] = array(
						'text' => 'Collection = '.$Blog->shortname,
						'disabled' => true,
					);
				}

				global $disp, $is_front;
				if( !empty($disp) )
				{
					$dev_entries['disp'] = array(
						'text' => '$disp = '.$disp,
						'disabled' => true,
					);
				}

				global $disp_detail;
				if( !empty($disp_detail) )
				{
					$dev_entries['disp_detail'] = array(
						'text' => '$disp_detail = '.$disp_detail,
						'disabled' => true,
					);
				}

				if( ! empty( $seo_page_type ) )
				{ // Set in skin_init()
					$dev_entries['seo_page_type'] = array(
						'text' => '> '.$seo_page_type,
						'disabled' => true,
					);
				}

				global $is_front;
				if( !empty($is_front) )
				{
					$dev_entries['front'] = array(
						'text' => 'This is the FRONT page',
						'disabled' => true,
					);
				}

				if( $robots_index === false )
				{
					$debug_text = 'NO INDEX';
				}
				else
				{
					$debug_text = 'do index';
				}

				$dev_entries['noindex'] = array(
						'text' => $debug_text,
						'disabled' => true,
					);
			}

			if( ! is_admin_page() && ! empty( $dev_entries ) )
			{	// Use the same dev entries in "Page" menu:
				if( empty( $entries['page']['entries'] ) )
				{
					$entries['page']['entries'] = array();
					$page_dev_entries = $dev_entries;
				}
				else
				{
					$page_dev_entries = array_merge( array( array( 'separator' => true ) ), $dev_entries );
				}
				if( isset( $page_dev_entries['coll'] ) )
				{	// Don't display collection debug info in "Page" menu:
					unset( $page_dev_entries['coll'] );
				}
				$entries['page']['entries'] = array_merge( $entries['page']['entries'], $page_dev_entries );
			}

			if( ( $dev_menu || $debug ) && ! is_admin_page() && ! empty( $Blog ) )
			{ // Display a menu to turn on/off the debug containers
				global $ReqURI, $Session;

				$dev_entries[] = array(
						'separator' => true,
					);

				if( $Session->get( 'display_containers_'.$Blog->ID ) == 1 )
				{ // To hide the debug containers
					$dev_entries['containers'] = array(
						'text' => T_('Hide containers'),
						'href' => url_add_param( regenerate_url( 'display_containers' ), 'display_containers=hide' ),
					);
				}
				else
				{ // To show the debug containers
					$dev_entries['containers'] = array(
						'text' => T_('Show containers'),
						'href' => url_add_param( regenerate_url( 'display_containers' ), 'display_containers=show' ),
					);
				}

				if( $Session->get( 'display_includes_'.$Blog->ID ) == 1 )
				{ // To hide the debug includes
					$dev_entries['includes'] = array(
						'text' => T_('Hide includes'),
						'href' => url_add_param( regenerate_url( 'display_containers' ), 'display_includes=hide' ),
					);
				}
				else
				{ // To show the debug includes
					$dev_entries['includes'] = array(
						'text' => T_('Show includes'),
						'href' => url_add_param( regenerate_url( 'display_containers' ), 'display_includes=show' ),
					);
				}
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

		$entries = array();

		// Dev menu:
		global $debug_jslog;
		if( $debug || $debug_jslog )
		{ // Show JS log menu if debug is enabled

			$dev_entries[] = array(
					'separator' => true,
				);

			$dev_entries['jslog'] = array(
				'text'  => T_('JS log'),
				'title' => T_('JS log'),
				'class' => 'jslog_switcher'
			);
		}

		if( ! empty( $dev_entries ) )
		{ // Add Dev menu if at least one entry is should be displayed
			$entries['dev'] = array(
					'href'    => $admin_url.'#',
					'text'    => '<span class="fa fa-wrench"></span> Dev',
					'entries' => $dev_entries,
					'class'   => 'debug_dev_button',
				);
		}

		// User menu:
		$current_user_Group = $current_User->get_Group();
		$userprefs_entries = array(
			'name' => array(
					'text' => $current_User->get_avatar_imgtag( 'crop-top-32x32', '', 'left' ).'&nbsp;'
										.$current_User->get_preferred_name()
										.'<br />&nbsp;<span class="note">'.$current_user_Group->get_name().'</span>',
					'href' => get_user_profile_url(),
				),
			);

		$userprefs_entries[] = array( 'separator' => true );

		$user_profile_url = get_user_profile_url();
		if( ! empty( $user_profile_url ) )
		{ // Display this menu item only when url is available to current user
			$userprefs_entries['profile'] = array(
					'text' => T_('Edit your profile').'&hellip;',
					'href' => $user_profile_url,
				);
		}
		$user_avatar_url = get_user_avatar_url();
		if( ! empty( $user_avatar_url ) )
		{ // Display this menu item only when url is available to current user
			$userprefs_entries['avatar'] = array(
					'text' => T_('Your profile picture').'&hellip;',
					'href' => $user_avatar_url,
				);
		}
		$user_pwdchange_url = get_user_pwdchange_url();
		if( ! empty( $user_pwdchange_url ) )
		{ // Display this menu item only when url is available to current user
			$userprefs_entries['pwdchange'] = array(
					'text' => T_('Change password').'&hellip;',
					'href' => $user_pwdchange_url,
				);
		}
		$user_preferences_url = get_user_preferences_url();
		if( ! empty( $user_preferences_url ) )
		{ // Display this menu item only when url is available to current user
			$userprefs_entries['userprefs'] = array(
					'text' => T_('Preferences').'&hellip;',
					'href' => $user_preferences_url,
				);
		}
		$user_subs_url = get_user_subs_url();
		if( ! empty( $user_subs_url ) )
		{ // Display this menu item only when url is available to current user
			$userprefs_entries['subs'] = array(
					'text' => T_('Notifications').'&hellip;',
					'href' => $user_subs_url,
				);
		}

		$entries['userprefs'] = array(
				'text'    => '<strong>'.$current_User->get_colored_login( array( 'login_text' => 'name' ) ).'</strong>',
				'href'    => get_user_profile_url(),
				'entries' => $userprefs_entries,
			);
		$entries['time'] = array(
				'text'        => date( locale_shorttimefmt(), $localtimenow ),
				'disabled'    => true,
				'entry_class' => 'rwdhide'
			);

		if( $current_User->check_perm( 'admin', 'normal' ) && $current_User->check_perm( 'options', 'view' ) )
		{ // Make time as link to Timezone settings if permission
			$entries['time']['disabled'] = false;
			$entries['time']['href'] = $admin_url.'?ctrl=time';
		}

		/*
		 * We currently support only one backoffice skin, so we don't need a system for selecting the backoffice skin.
		// ADMIN SKINS:
		if( $is_admin_page )
		{
			$admin_skins = get_admin_skins();
			if( count( $admin_skins ) > 1 )
			{	// We have several admin skins available: display switcher:
				$entries['userprefs']['entries']['admskins'] = array(
						'text' => T_('Admin skin'),
					);
				$redirect_to = rawurlencode(regenerate_url('', '', '', '&'));
				foreach( $admin_skins as $admin_skin )
				{
					$entries['userprefs']['entries']['admskins']['entries'][$admin_skin] = array(
							'text' => $admin_skin,
							'href' => $admin_url.'?ctrl=users&amp;action=change_admin_skin&amp;new_admin_skin='.rawurlencode($admin_skin)
								.'&amp;redirect_to='.$redirect_to
						);
				}
			}
		}
		 */

		$entries['userprefs']['entries'][] = array( 'separator' => true );

		$entries['userprefs']['entries']['logout'] = array(
				'text' => T_('Log out').'!',
				'href' => get_user_logout_url(),
			);

		$topright_Menu->add_menu_entries( NULL, $entries );
	}


	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
		global $blog, $loc_transinfo, $ctrl, $admin_url, $Settings;
		/**
		 * @var User
		 */
		global $current_User;
		global $Collection, $Blog;
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
					'groups' => array(
						'text' => T_('Groups'),
						'href' => '?ctrl=groups' ),
					'stats' => array(
						'text' => T_('Stats'),
						'href' => '?ctrl=users&amp;tab=stats' ),
					'usersettings' => array(
						'text' => T_('Settings'),
						'href' => '?ctrl=usersettings',
						'entries' => array(
							'usersettings' => array(
								'text' => T_('Profiles'),
								'href' => '?ctrl=usersettings' ),
							'registration' => array(
								'text' => T_('Registration & Login'),
								'href' => '?ctrl=registration' ),
							'invitations' => array(
								'text' => T_('Invitations'),
								'href' => '?ctrl=invitations' ),
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

		if( $current_User->check_perm( 'orgs', 'create' ) )
		{	// Display a menu item for organizations if user has a perm at least to create own organization:
			$AdminUI->add_menu_entries( array( 'users' ), array(
					'organizations' => array(
						'text' => T_('Organizations'),
						'href' => '?ctrl=organizations' )
				), 'groups' );
		}

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
						'settings' => array(
							'text' => T_('Settings'),
							'href' => '?ctrl=email&amp;tab=settings',
							'entries' => array(
								'plugins' => array(
									'text' => T_('Plugins'),
									'href' => '?ctrl=email&amp;tab=settings&amp;tab3=plugins' ),
								'envelope' => array(
									'text' => T_('Envelope'),
									'href' => '?ctrl=email&amp;tab=settings&amp;tab3=envelope' ),
								'smtp' => array(
									'text' => T_('SMTP gateway'),
									'href' => '?ctrl=email&amp;tab=settings&amp;tab3=smtp' ),
								'other' => array(
									'text' => T_('Other'),
									'href' => '?ctrl=email&amp;tab=settings&amp;tab3=other' ),
							) ),
						'sent' => array(
							'text' => T_('Sent'),
							'href' => '?ctrl=email&amp;tab=sent',
							'entries' => array(
								'log' => array(
									'text' => T_('Send Log'),
									'href' => '?ctrl=email&amp;tab=sent' ),
								'envelope' => array(
									'text' => T_('Envelope'),
									'href' => '?ctrl=email&amp;tab=settings&amp;tab2=sent&amp;tab3=envelope' ),
								'smtp' => array(
									'text' => T_('SMTP gateway'),
									'href' => '?ctrl=email&amp;tab=settings&amp;tab2=sent&amp;tab3=smtp' ),
							) ),
						'return' => array(
							'text' => T_('Returned'),
							'href' => '?ctrl=email&amp;tab=return',
							'entries' => array(
								'log' => array(
									'text' => T_('Return Log'),
									'href' => '?ctrl=email&amp;tab=return&amp;tab3=log' ),
								'settings' => array(
									'text' => T_('POP/IMAP Settings'),
									'href' => '?ctrl=email&amp;tab=return&amp;tab3=settings' ),
							) ),
						'addresses' => array(
							'text' => T_('Addresses'),
							'href' => '?ctrl=email' ),
						) ) ) );

			if( $current_User->check_perm( 'emails', 'edit' ) )
			{	// Allow to test a returned email only if user has a permission to edit email settings:
				$AdminUI->add_menu_entries( array( 'email', 'return' ), array(
						'test' => array(
							'text' => T_('Test'),
							'href' => '?ctrl=email&amp;tab=return&amp;tab3=test' ,
					) ) );
			}
		}

		/**** System ****/
		if( $perm_admin_normal && $perm_options )
		{ // Permission to view settings:
			$AdminUI->add_menu_entries( NULL, array(
						'options' => array(
							'text' => T_('System'),
							'href' => $admin_url.'?ctrl=system'
				) ) );

			$perm_spam = $current_User->check_perm( 'spamblacklist', 'view' );

			if( $perm_admin_normal && ( $perm_options || $perm_spam ) )
			{ // Permission to view tools or antispam.
				if( $perm_options )
				{ // Permission to view settings:
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
				{ // Permission to view antispam:
					$AdminUI->add_menu_entries( 'options', array(
						'antispam' => array(
							'text' => T_('Antispam'),
							'href' => '?ctrl=antispam',
							'entries' => array(
								'blacklist' => array(
									'text' => T_('Blacklist'),
									'href' => '?ctrl=antispam' ) ) ) ) );

					if( $perm_options )
					{ // If we have access to options, then we add a submenu:
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
			}


			$AdminUI->add_menu_entries( 'options', array(
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
				'skins' => array(
					'text' => T_('Skins'),
					'href' => '?ctrl=skins&amp;tab=system' ),
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

			if( $current_User->check_perm( 'options', 'edit' ) )
			{
				$AdminUI->add_menu_entries( 'options', array(
						'syslog' => array(
							'text' => T_('System log'),
							'href' => '?ctrl=syslog' ),
					) );
			}
		}

	}


	/**
	 * Get the core module cron jobs
	 *
	 * @see Module::get_cron_jobs()
	 */
	function get_cron_jobs()
	{
		return array(
			'poll-antispam-blacklist' => array(
				'name'   => T_('Poll the antispam blacklist'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_antispam_poll.job.php',
				'params' => NULL,
			),
			'process-return-path-inbox' => array(
				'name'   => T_('Process the return path inbox'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_decode_returned_emails.job.php',
				'params' => NULL,
			),
			'send-non-activated-account-reminders' => array(
				'name'   => T_('Send reminders about non-activated accounts'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_activate_account_reminder.job.php',
				'params' => NULL,
			),
		);
	}
}

$_core_Module = new _core_Module();

?>
