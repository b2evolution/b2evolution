<?php
/**
 * This file implements the UI view for a list of users which may be deleted as spammers
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


users_results_block( array(
		'results_title'        => T_('Delete the following spammers:'),
		'page_url'             => get_dispctrl_url( 'users', 'action=spammers' ),
		'display_filters'      => false,
		'display_btn_adduser'  => false,
		'display_btn_addgroup' => false,
		'display_email'        => true,
		'display_nickname'     => false,
		'display_gender'       => false,
		'display_country'      => false,
		'display_blogs'        => false,
		'display_source'       => false,
		'display_regdate'      => false,
		'display_regcountry'   => false,
		'display_update'       => false,
		'display_lastvisit'    => false,
		'display_contact'      => false,
		'display_level'        => false,
		'display_status'       => false,
		'display_newsletter'   => false,
		'display_delspam_info' => true,
	) );
?>