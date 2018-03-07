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


global $UserSettings, $edited_EmailCampaign;

echo '<div class="well">';
// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'ecmp_ID, ecmp_date_ts, ecmp_enlt_ID, ecmp_email_title, ecmp_email_html, ecmp_email_text,
		ecmp_email_plaintext, ecmp_sent_ts, ecmp_auto_sent_ts, ecmp_renderers, ecmp_use_wysiwyg, ecmp_send_ctsk_ID, ecmp_auto_send,
		ecmp_user_tag, ecmp_user_tag_cta1, ecmp_user_tag_cta2, ecmp_user_tag_cta3, ecmp_user_tag_like, ecmp_user_tag_dislike,
		enlt_ID, enlt_name,
		SUM( IF( ecmp_sent_ts IS NULL AND ecmp_auto_sent_ts IS NULL, 0, 1 ) ) AS send_count,
		SUM( IF( emlog_last_open_ts IS NOT NULL OR emlog_last_click_ts IS NOT NULL OR
			csnd_like IS NOT NULL OR csnd_cta1 IS NOT NULL OR csnd_cta2 IS NOT NULL OR csnd_cta3 IS NOT NULL, 1, 0 ) ) /
			SUM( IF( ecmp_sent_ts IS NULL AND ecmp_auto_sent_ts IS NULL, 0, 1 ) ) AS open_rate,
		SUM( IF( emlog_last_open_ts IS NULL, 0, 1 ) ) AS open_count,
		SUM( IF( emlog_last_click_ts IS NULL, 0, 1 ) ) AS click_count,
		SUM( IF( csnd_cta1 = 1, 1, 0 ) ) AS cta1_count,
		SUM( IF( csnd_cta2 = 1, 1, 0 ) ) AS cta2_count,
		SUM( IF( csnd_cta3 = 1, 1, 0 ) ) AS cta3_count,
		SUM( IF( csnd_like = 1, 1, 0 ) ) AS like_count,
		SUM( IF( csnd_like = -1, 1, 0 ) ) AS dislike_count,
		SUM( COALESCE( csnd_clicked_unsubscribe, 0 ) ) AS unsubscribe_click_count' );
$SQL->FROM( 'T_email__campaign' );
$SQL->FROM_add( 'INNER JOIN T_email__newsletter ON ecmp_enlt_ID = enlt_ID' );
$SQL->FROM_add( 'LEFT JOIN T_email__campaign_send ON csnd_camp_ID = ecmp_ID AND csnd_emlog_ID IS NOT NULL' );
$SQL->FROM_add( 'LEFT JOIN T_email__log ON emlog_ID = csnd_emlog_ID' );
$SQL->WHERE( 'ecmp_ID ='.$DB->quote( $edited_EmailCampaign->ID ) );
$SQL->GROUP_BY( 'ecmp_ID, ecmp_date_ts, ecmp_enlt_ID, ecmp_email_title, ecmp_email_html, ecmp_email_text,
		ecmp_email_plaintext, ecmp_sent_ts, ecmp_auto_sent_ts, ecmp_renderers, ecmp_use_wysiwyg, ecmp_send_ctsk_ID, ecmp_auto_send, ecmp_user_tag, enlt_ID, enlt_name' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT( ecmp_ID )' );
$count_SQL->FROM( 'T_email__campaign' );

if( isset( $params['enlt_ID'] ) )
{
	$SQL->WHERE_and( 'ecmp_enlt_ID = '.$DB->quote( $params['enlt_ID'] ) );
	$count_SQL->WHERE_and( 'ecmp_enlt_ID = '.$DB->quote( $params['enlt_ID'] ) );
}

$Results = new Results( $SQL->get(), 'emcmp_', 'D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );
$Results->Cache = & get_EmailCampaignCache();

$Results->cols[] = array(
	'th' => T_('Sent manually'),
	'order' => 'ecmp_sent_ts',
	'default_dir' => 'D',
	'th_class' => 'shrinkwrap',
	'td_class' => 'timestamp compact_data',
	'td' => '%mysql2localedatetime_spans( #ecmp_sent_ts# )%',
);

$Results->cols[] = array(
	'th' => T_('Sent automatically'),
	'order' => 'ecmp_auto_sent_ts',
	'default_dir' => 'D',
	'th_class' => 'shrinkwrap',
	'td_class' => 'timestamp compact_data',
	'td' => '%mysql2localedatetime_spans( #ecmp_auto_sent_ts# )%',
);

$Results->cols[] = array(
	'th' => T_('Send count'),
	'order' => 'send_count',
	'default_dir' => 'D',
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'td' =>'$send_count$'
);

$Results->cols[] = array(
	'th' => T_('Open rate'),
	'order' => 'open_rate',
	'default_dir' => 'D',
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'td' =>'%empty( #send_count# ) ? "" : number_format( #open_rate# * 100, 1 )%%'
);

$Results->cols[] = array(
	'th' => /* TRANS: Image load count */ T_('Img loads'),
	'order' => 'open_count',
	'default_dir' => 'D',
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'td' =>'%campaign_td_recipient_action( {row}, "img_loaded" )%',
);

$Results->cols[] = array(
	'th' => T_('Link clicks'),
	'order' => 'click_count',
	'default_dir' => 'D',
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'td' =>'%campaign_td_recipient_action( {row}, "link_clicked" )%',
);

$Results->cols[] = array(
	'th' => /* TRANS: Call To Action 1*/ T_('CTA1'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'td' =>'%campaign_td_recipient_action( {row}, "cta1" )%',
);

$Results->cols[] = array(
	'th' => /* TRANS: Call To Action 2*/ T_('CTA2'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'td' =>'%campaign_td_recipient_action( {row}, "cta2" )%',
);

$Results->cols[] = array(
	'th' => /* TRANS: Call To Action 3*/ T_('CTA3'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'td' =>'%campaign_td_recipient_action( {row}, "cta3" )%',
);

$Results->cols[] = array(
	'th' => T_('Likes'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'td' =>'%campaign_td_recipient_action( {row}, "liked" )%',
);

$Results->cols[] = array(
	'th' => T_('Dislikes'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'td' =>'%campaign_td_recipient_action( {row}, "disliked" )%',
);

$Results->cols[] = array(
	'th' => T_('Unsub clicks'),
	'order' => 'unsubscribe_click_count',
	'default_dir' => 'D',
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'td' =>'%campaign_td_recipient_action( {row}, "clicked_unsubscribe" )%',
);

$Results->display();
echo '</div>';

// Display recipients of this email campaign:
$recipient_type = param( 'recipient_type', 'string' );
$recipient_action = param( 'recipient_action', 'string' );

users_results_block( array(
		'ecmp_ID'              => $edited_EmailCampaign->ID,
		'filterset_name'       => 'ecmp_'.$edited_EmailCampaign->ID,
		'results_param_prefix' => 'ecmp_',
		'results_title'        => T_('Recipients of this campaign').get_manual_link( 'email-campaign-recipients' ),
		'results_order'        => '/csnd_last_sent_ts/D',
		'page_url'             => get_dispctrl_url( 'campaigns', 'action=edit&amp;tab=recipient&amp;ecmp_ID='.$edited_EmailCampaign->ID.
				( empty( $recipient_type ) ? '' : '&amp;recipient_type='.$recipient_type ).
				( empty( $recipient_action ) ? '' : '&amp;recipient_action='.$recipient_action ) ),
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
		'display_campaign_actions' => true,
		'display_newsletter'   => false,
		'display_enlt_status'  => true,
		'display_camp_status'  => true,
		'display_emlog_date'   => true,
		'display_email_tracking' => true,
		'th_class_login'       => 'shrinkwrap',
		'td_class_login'       => '',
		'th_class_nickname'    => 'shrinkwrap',
		'td_class_nickname'    => '',
	) );
?>