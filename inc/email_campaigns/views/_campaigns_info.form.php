<?php
/**
 * This file implements the UI view for Emails > Campaigns > Edit > Info
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

global $admin_url, $tab, $edited_EmailCampaign;

$Form = new Form( NULL, 'campaign_form' );
$Form->begin_form( 'fform' );

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'current_tab', $tab );
$Form->hidden( 'ecmp_ID', $edited_EmailCampaign->ID );

$Form->begin_fieldset( T_('Campaign info').get_manual_link( 'creating-an-email-campaign' ) );
	$Form->text_input( 'ecmp_email_title', $edited_EmailCampaign->get( 'email_title' ) == '' ? $edited_EmailCampaign->get( 'name' ) : $edited_EmailCampaign->get( 'email_title' ), 60, T_('Email title'), '', array( 'maxlength' => 255, 'required' => true ) );
	$Form->info( T_('Campaign created'), mysql2localedatetime_spans( $edited_EmailCampaign->get( 'date_ts' ) ) );
	$Form->info( T_('Last sent'), $edited_EmailCampaign->get( 'sent_ts' ) ? mysql2localedatetime_spans( $edited_EmailCampaign->get( 'sent_ts' ) ) : T_('Not sent yet') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Newsletter recipients') );
	$change_filter_url = $admin_url.'?ctrl=campaigns&amp;action=change_users&amp;ecmp_ID='.$edited_EmailCampaign->ID;
	$NewsletterCache = & get_NewsletterCache();
	$NewsletterCache->load_where( 'enlt_active = 1 OR enlt_ID = '.intval( $edited_EmailCampaign->get( 'enlt_ID' ) ) );
	$Form->select_input_object( 'ecmp_enlt_ID', $edited_EmailCampaign->get( 'enlt_ID' ), $NewsletterCache, T_('Send to subscribers of'), array( 'required' => true, 'field_suffix' => '<a href="'.$change_filter_url.'" class="btn btn-default">'.T_('Update').'</a>' ) );
	$Form->info( T_('Currently selected recipients'), $edited_EmailCampaign->get_users_count(), '('.T_('Accounts which currently accept this newsletter').')' );
	$Form->info( T_('After additional filter'), $edited_EmailCampaign->get_users_count( 'filter' ), '('.T_('Accounts that match your additional filter').') <a href="'.$change_filter_url.'" class="btn btn-default">'.T_('Change filter').'</a>' );
	$Form->info( T_('Already received'), $edited_EmailCampaign->get_users_count( 'receive' ), '('.T_('Accounts which have already been sent this newsletter').')' );
	$Form->info( T_('Ready to send'), $edited_EmailCampaign->get_users_count( 'wait' ), '('.T_('Accounts which have not been sent this newsletter yet').')' );
$Form->end_fieldset();

$buttons = array();
if( $current_User->check_perm( 'emails', 'edit' ) )
{ // User must has a permission to edit emails
	$buttons[] = array( 'submit', 'actionArray[save]', T_('Save info'), 'SaveButton' );
}
$Form->end_form( $buttons );

?>