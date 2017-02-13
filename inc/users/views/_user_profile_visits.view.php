<?php
/**
 * This is the template that displays users
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load classes
load_class( 'users/model/_user.class.php', 'User' );

global $Collection, $Blog, $Skin, $Settings, $edited_User, $is_admin_page;

/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;

global $user_tab, $user_ID;

global $current_User, $UserSettings;

if( !$current_User->can_moderate_user( $edited_User->ID ) )
{ // Check permission:
	debug_die( T_( 'You have no permission to see this tab!' ) );
}


memorize_param( 'user_tab', 'string', '', $user_tab );
memorize_param( 'user_ID', 'integer', 0, $user_ID );

// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'visits'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

if( !$user_profile_only )
{ // echo user edit action icons
	$Widget = new Widget();
	echo_user_actions( $Widget, $edited_User, 'edit' );
	echo '<span class="floatright">'.$Widget->gen_global_icons().'</span>';
}

if( $is_admin_page )
{
	echo '<div>'.get_usertab_header( $edited_User, $user_tab, ( $current_User->ID == $edited_User->ID ? T_('My Profile Visits') : T_('User Profile Visits') ).get_manual_link( 'profile-visits-tab' ) ).'</div>';
}

if( ! empty( $Skin ) )
{
	$display_params = array_merge( $Skin->get_template( 'Results' ), $Skin->get_template( 'users' ) );
}
else
{
	$display_params = array();
}

if( ! isset( $params ) )
{ // Init template params
	$params = array();
}

$params = array_merge( array(
		'page_url'             => is_admin_page() ? get_dispctrl_url( 'user', 'user_tab=visits&amp;user_ID='.$edited_User->ID ) : get_dispctrl_url( 'visits' ),
		'filterset_name'       => '',
		'results_param_prefix' => 'upv_',
		'results_no_text'      => T_('No-one visited your profile yet. Please check back later.'),
		'results_title'        => T_('People who have visited your profile'),
		'results_order'        => '/upv_last_visit_ts/D',
		'join_group'           => is_logged_in() ? false : true, /* Anonymous users have a limit by user group level */
		'join_city'            => true,
		'join_country'         => false, //$Blog->get_setting( 'userdir_country' ),
		'keywords_fields'      => 'user_login, user_firstname, user_lastname, user_nickname',
		'where_status_closed'  => false,
		'display_params'       => $display_params,
		'display_btn_refresh'  => true,
		'display_btn_adduser'  => false,
		'display_btn_addgroup' => false,
		'display_ID'           => false,
		//'display_avatar'       => $Blog->get_setting( 'userdir_picture' ),
		//'display_login'        => $Blog->get_setting( 'userdir_login' ),
		//'display_firstname'    => $Blog->get_setting( 'userdir_firstname' ),
		//'display_lastname'     => $Blog->get_setting( 'userdir_lastname' ),
		'display_nickname'     => false, //$Blog->get_setting( 'userdir_nickname' ),
		//'display_name'         => $Blog->get_setting( 'userdir_fullname' ),
		'order_name'           => false,
		'display_gender'       => false,
		'display_country'      => false, //$Blog->get_setting( 'userdir_country' ),
		//'display_country_type' => $Blog->get_setting( 'userdir_country_type' ),
		//'display_region'       => $Blog->get_setting( 'userdir_region' ),
		//'display_subregion'    => $Blog->get_setting( 'userdir_subregion' ),
		'display_city'         => true, // $Blog->get_setting( 'userdir_city' ),
		//'display_phone'        => $Blog->get_setting( 'userdir_phone' ),
		//'display_soclinks'     => $Blog->get_setting( 'userdir_soclinks' ),
		'display_blogs'        => false,
		'display_source'       => false,
		'display_regdate'      => false,
		'display_regcountry'   => false,
		'display_update'       => false,
		'display_lastvisit'    => false,
		//'display_lastvisit'    => $Blog->get_setting( 'userdir_lastseen' ),
		//'display_lastvisit_view' => $Blog->get_setting( 'userdir_lastseen_view' ),
		//'display_lastvisit_cheat' => $Blog->get_setting( 'userdir_lastseen_cheat' ),
		'display_contact'      => false,
		'display_reported'     => false,
		'display_group'        => false,
		'display_level'        => false,
		'display_status'       => false,
		'display_actions'      => false,
		'display_newsletter'   => false,
		'force_check_user'     => true,
		'th_class_avatar'      => 'shrinkwrap',
		'td_class_avatar'      => 'shrinkwrap center',
		//'avatar_size'          => $Blog->get_setting( 'image_size_user_list' ),
		'th_class_login'       => '',
		'td_class_login'       => '',
		'th_class_nickname'    => '',
		'td_class_nickname'    => '',
		'th_class_name'        => '',
		'td_class_name'        => '',
		'th_class_country'     => 'shrinkwrap',
		//'td_class_country'     => $Blog->get_setting( 'userdir_country_type' ) == 'flag' ? 'center' : 'nowrap',
		'th_class_city'        => 'shrinkwrap',
		'td_class_city'        => 'nowrap',
		//'th_class_lastvisit'   => $Blog->get_setting( 'userdir_lastseen_view' ) == 'blurred_date' ? '' : 'shrinkwrap',
		//'td_class_lastvisit'   => $Blog->get_setting( 'userdir_lastseen_view' ) == 'blurred_date' ? '' :'center',
		'viewed_user'          => $edited_User->ID,
	), $params );

users_results_block( $params ); // user.funcs.php 4974

load_funcs( 'users/model/_user_js.funcs.php' );
?>