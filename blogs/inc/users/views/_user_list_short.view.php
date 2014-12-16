<?php
/**
 * This file implements the UI view for the user list for user viewing.
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
 * @package admin
 *
 * @version $Id: _user_list_short.view.php 7818 2014-12-15 14:41:11Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_userlist.class.php', 'UserList' );

/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var UserSettings
 */
global $UserSettings;
/**
 * @var Blog
 */
global $Blog;

if( !isset( $display_params ) )
{ // init display_params
	$display_params = array();
}

$UserList = new UserList( '', $UserSettings->get('results_per_page'), 'u_', array(
		'join_group'          => is_logged_in() ? false : true, /* Anonymous users have a limit by user group level */
		'join_country'        => false,
		'keywords_fields'     => 'user_login, user_firstname, user_lastname, user_nickname',
		'where_status_closed' => false,
	) );

$default_filters = array();

if( is_logged_in() && has_cross_country_restriction( 'users', 'list' ) )
{ // Set default filter by country
	$default_filters['country'] = $current_User->ctry_ID;
}


if( $Settings->get( 'allow_avatars' ) )
{ // Sort by picture
	$default_filters['order'] = 'D';
}
else
{ // Sort by login (if pictures are not allowed )
	$default_filters['order'] = 'A';
}


/*
 * Data columns:
 */

if( $Settings->get( 'allow_avatars' ) )
{
	function user_avatar( $user_ID )
	{
		global $Blog;

		$UserCache = & get_UserCache();
		$User = & $UserCache->get_by_ID( $user_ID );

		return $User->get_identity_link( array(
			'link_text' => 'only_avatar',
			'thumb_size' => $Blog->get_setting('image_size_user_list'),
			) );
	}
	$UserList->cols[] = array(
							'th' => T_('Picture'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap center',
							'order' => 'has_picture',
							'default_dir' => 'D',
							'td' => '%user_avatar( #user_ID# )%',
						);
}

$UserList->cols[] = array(
						'th' => T_('Login'),
						'order' => 'user_login',
						'td' => '%get_user_identity_link( #user_login#, #user_ID#, "profile", "login" )%',
					);

$UserList->cols[] = array(
						'th' => T_('City'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'order' => 'city_name',
						'td' => '$city_name$<div class="note">$city_postcode$</div>',
					);

$UserList->set_default_filters( $default_filters );
$UserList->load_from_Request();
$UserList->query();


$filter_presets = array();
$filter_presets['all'] = array( T_('All users'), get_dispctrl_url( 'users&amp;filter=new' ) );
if( ! is_admin_page() && ! empty( $Blog ) && $Blog->get_setting( 'allow_access' ) == 'members' )
{ // Allow to filter by members when Blog has an access only for members
	$filter_presets['members'] = array( T_('Members'), get_dispctrl_url( 'users', 'membersonly=1&amp;filter=new' ) );
}
$filter_presets['men'] = array( T_('Men'), get_dispctrl_url( 'users', 'gender_men=1&amp;filter=new' ) );
$filter_presets['women'] = array( T_('Women'), get_dispctrl_url( 'users', 'gender_women=1&amp;filter=new' ) );

if( is_admin_page() )
{ // Add show only activated users filter only on admin interface
	$filter_presets['activated'] = array( T_('Activated users'), get_dispctrl_url( 'users', 'status_activated=1&amp;filter=new' ) );
}

if( $UserList->is_filtered() )
{	// Display link to reset filters only if some filter is applied
	$filter_presets['reset'] = array( T_('Reset Filters'), get_dispctrl_url( 'users&amp;filter=reset' ), 'class="floatright"' );
}

$UserList->filter_area = array(
	'callback' => 'callback_filter_userlist',
	'url_ignore' => 'users_paged,u_paged,keywords',
	'presets' => $filter_presets,
	);


// Display result :
$UserList->display( $display_params );

load_funcs( 'users/model/_user_js.funcs.php' );

?>