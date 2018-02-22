<?php
/**
 * This file implements the UI view for Emails > Newsletters > Campaigns
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $edited_Newsletter;

if( $edited_Newsletter->ID > 0 )
{	// Display users which are subscribed to this Newsletter:
	users_results_block( array(
		'enlt_ID'              => $edited_Newsletter->ID,
		'enls_subscribed'      => NULL,
		'filterset_name'       => 'nltsub_'.$edited_Newsletter->ID,
		'results_param_prefix' => 'nltsub_',
		'results_title'        => T_('Subscribers').get_manual_link( 'list-subscribers' ),
		'results_order'        => '/enls_last_sent_manual_ts/D',
		'page_url'             => get_dispctrl_url( 'newsletters', 'action=edit&amp;enlt_ID='.$edited_Newsletter->ID ),
		'display_ID'           => false,
		'display_btn_adduser'  => false,
		'display_btn_addgroup' => false,
		'display_avatar'       => false,
		'display_firstname'    => true,
		'display_lastname'     => true,
		'display_name'         => false,
		'display_gender'       => false,
		'display_country'      => false,
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
		'display_enls_subscribed'      => true,
		'display_enls_subscribed_ts'   => true,
		'display_enls_unsubscribed_ts' => true,
		'display_enls_sent_manual'     => true,
		'display_enls_last_open'       => true,
		'display_enls_last_click'      => true,
		'display_enls_send_count'      => true,
		'th_class_login'       => 'shrinkwrap',
		'td_class_login'       => '',
		'th_class_nickname'    => 'shrinkwrap',
		'td_class_nickname'    => '',
	) );
}