<?php
/**
 * This file implements the UI view for the user/group list for user/group editing.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( !isset( $display_params ) )
{ // init display_params
	$display_params = array();
}

users_results_block( array(
		'filterset_name'          => 'duplicate_email_users',
		'results_param_prefix'    => 'duplicates_',
		'results_title'           => T_('Find Duplicates'),
		'results_order'           => '/user_email/A',
		'page_url'                => get_dispctrl_url( 'users', 'tab3=duplicates' ),
		'display_user_count'      => true,
		'display_sec_groups'      => true,
		'display_params'          => $display_params,
		'display_contact'         => false,
		'display_email'           => true,
		'display_automation'      => true,
		'display_name'            => false,
		'display_nickname'        => false,
		'display_gender'          => false,
		'display_country'         => false,
		'display_blogs'           => false,
		'display_source'          => false,
		'display_regcountry'      => false,
		'display_update'          => false,
		'display_status'          => false,
		'display_level'           => false,
		'display_group'           => false,
		'display_sec_groups'      => false,
		'display_actions'         => false,
		'display_user_tags'       => true,
		'display_subscribed_list' => true,
		'display_pass_status'     => true,
		'where_duplicate_email'   => true,

	) );

if( is_admin_page() )
{	// Call plugins event:
	global $Plugins;
	$Plugins->trigger_event( 'AdminAfterUsersList' );
}

load_funcs( 'users/model/_user_js.funcs.php' );
?>