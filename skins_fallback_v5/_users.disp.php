<?php
/**
 * This is the template that displays users
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load classes
load_class( 'users/model/_user.class.php', 'User' );

global $Blog, $Skin, $Settings;

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
		'filterset_name'       => '',
		'results_param_prefix' => 'u_',
		'results_title'        => T_('Users'),
		'results_order'        => $Settings->get( 'allow_avatars' ) ? 'D' : 'A',
		'join_group'           => is_logged_in() ? false : true, /* Anonymous users have a limit by user group level */
		'join_city'            => true,
		'join_country'         => false,
		'keywords_fields'      => 'user_login, user_firstname, user_lastname, user_nickname',
		'where_status_closed'  => false,
		'display_params'       => $display_params,
		'display_btn_refresh'  => true,
		'display_btn_adduser'  => false,
		'display_btn_addgroup' => false,
		'display_ID'           => false,
		'display_avatar'       => true,
		'display_login'        => true,
		'display_nickname'     => false,
		'display_name'         => false,
		'display_gender'       => false,
		'display_country'      => false,
		'display_city'         => true,
		'display_blogs'        => false,
		'display_source'       => false,
		'display_regdate'      => false,
		'display_regcountry'   => false,
		'display_update'       => false,
		'display_lastvisit'    => false,
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
		'avatar_size'          => $Blog->get_setting( 'image_size_user_list' ),
		'th_class_login'       => '',
		'td_class_login'       => '',
		'th_class_city'        => 'shrinkwrap',
		'td_class_city'        => 'shrinkwrap',
	), $params );

users_results_block( $params );

load_funcs( 'users/model/_user_js.funcs.php' );
?>