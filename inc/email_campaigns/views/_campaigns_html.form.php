<?php
/**
 * This file implements the UI view for Emails > Campaigns > Edit > HTML
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $tab;
global $edited_EmailCampaign;

$Form = new Form( NULL, 'campaign_form' );
$Form->begin_form( 'fform' );

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'current_tab', $tab );
$Form->hidden( 'ecmp_ID', $edited_EmailCampaign->ID );

$Form->begin_fieldset( T_('HTML message') );
	$Form->info( T_('Name'), $edited_EmailCampaign->get( 'name' ) );
	$Form->info( T_('Email title'), $edited_EmailCampaign->get( 'email_title' ) );
	$Form->info( T_('Campaign created'), mysql2localedatetime_spans( $edited_EmailCampaign->get( 'date_ts' ), 'M-d' ) );
	$Form->info( T_('Last sent'), $edited_EmailCampaign->get( 'sent_ts' ) ? mysql2localedatetime_spans( $edited_EmailCampaign->get( 'sent_ts' ), 'M-d' ) : T_('Not sent yet') );
	$Form->textarea_input( 'ecmp_email_html', $edited_EmailCampaign->get( 'email_html' ), 20, T_('HTML Message'), array( 'required' => true ) );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Newsletter recipients') );
	$Form->info( T_('Currently selected recipients'), $edited_EmailCampaign->get_users_count(), '('.T_('Accounts which accept newsletter emails').') - <a href="'.$admin_url.'?ctrl=campaigns&amp;action=change_users&amp;ecmp_ID='.$edited_EmailCampaign->ID.'">'.T_('Change selection').' &gt;&gt;</a>' );
	$Form->info( T_('Already received'), $edited_EmailCampaign->get_users_count( 'accept' ), '('.T_('Accounts which have already been sent this newsletter').')' );
	$Form->info( T_('Ready to send'), $edited_EmailCampaign->get_users_count( 'wait' ), '('.T_('Accounts which have not been sent this newsletter yet').')' );
$Form->end_fieldset();

$buttons = array();
if( $current_User->check_perm( 'emails', 'edit' ) )
{ // User must has a permission to edit emails
	$buttons[] = array( 'submit', 'actionArray[save]', T_('Save HTML message'), 'SaveButton' );
}
$Form->end_form( $buttons );

?>