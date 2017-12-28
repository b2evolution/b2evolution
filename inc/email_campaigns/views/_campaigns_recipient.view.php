<?php
/**
 * This file implements the UI view for Emails > Campaigns > Edit > Recipient list
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-201 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_EmailCampaign;

// Display recipients of this email campaign:
users_results_block( array(
		'ecmp_ID'              => $edited_EmailCampaign->ID,
		'filterset_name'       => 'ecmp_'.$edited_EmailCampaign->ID,
		'results_param_prefix' => 'ecmp_',
		'results_title'        => T_('Recipients of this campaign').get_manual_link( 'email-campaign-recipients' ),
		'results_order'        => '/emlog_timestamp/D',
		'page_url'             => get_dispctrl_url( 'campaigns', 'action=edit&amp;tab=recipient&amp;ecmp_ID='.$edited_EmailCampaign->ID.'&amp;recipient_type='.get_param( 'recipient_type' ) ),
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
		'display_enlt_status'  => true,
		'display_emlog_date'   => true,
		'th_class_login'       => 'shrinkwrap',
		'td_class_login'       => '',
		'th_class_nickname'    => 'shrinkwrap',
		'td_class_nickname'    => '',
	) );
?>