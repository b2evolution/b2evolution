<?php
/**
 * This file implements the UI view for Emails > Campaigns > Edit > Review & Send
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

global $admin_url, $tab;
global $current_User, $Session, $Settings;
global $edited_EmailCampaign;
global $template_action;

$Form = new Form( NULL, 'campaign_form' );
$Form->begin_form( 'fform' );

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'current_tab', $tab );
$Form->hidden( 'ecmp_ID', $edited_EmailCampaign->ID );

if( !empty( $template_action ) && $template_action == 'send_campaign' )
{ // Execute action to send campaign to all users
	$Form->begin_fieldset( T_('Send report') );
	$edited_EmailCampaign->send_all_emails();
	$Form->end_fieldset();
}

$Form->begin_fieldset( sprintf( T_('Review message for: %s'), $edited_EmailCampaign->dget( 'name' ) ).get_manual_link( 'creating-an-email-campaign' ) );
	$Form->info( T_('Email title'), $edited_EmailCampaign->get( 'email_title' ) );
	$Form->info( T_('Campaign created'), mysql2localedatetime_spans( $edited_EmailCampaign->get( 'date_ts' ) ) );
	$Form->info( T_('Last sent'), $edited_EmailCampaign->get( 'sent_ts' ) ? mysql2localedatetime_spans( $edited_EmailCampaign->get( 'sent_ts' ) ) : T_('Not sent yet') );

echo '<div style="display:table;width:100%;table-layout:fixed;">';
	echo '<div class="floatleft" style="width:50%">';
	echo '<p><b>'.T_('HTML message').':</b></p>';
	$html_mail_template = mail_template( 'newsletter', 'html', array( 'message_html' => $edited_EmailCampaign->get( 'email_html' ), 'include_greeting' => false ), $current_User );
	// Clear all html tags that may break styles of main html page:
	$html_mail_template = preg_replace( '#</?(html|head|meta|body)[^>]*>#i', '', $html_mail_template );
	echo '<div style="overflow:auto">'.$html_mail_template.'</div>';
	echo '</div>';

	echo '<div class="floatright" style="width:49%">';
	echo '<p><b>'.T_('Plain-text message').':</b></p>';
	echo '<div style="font-family:monospace;overflow:auto">'.nl2br( mail_template( 'newsletter', 'text', array( 'message_text' => $edited_EmailCampaign->get( 'email_plaintext' ), 'include_greeting' => false ), $current_User ) ).'</div>';
	echo '</div>';
echo '</div>';
$Form->end_fieldset();

$Form->begin_fieldset( T_('Newsletter recipients') );
	$Form->info( T_('Currently selected recipients'), $edited_EmailCampaign->get_users_count(), '('.T_('Accounts which accept newsletter emails').') - <a href="'.$admin_url.'?ctrl=campaigns&amp;action=change_users&amp;ecmp_ID='.$edited_EmailCampaign->ID.'">'.T_('Change selection').' &gt;&gt;</a>' );
	$Form->info( T_('Already received'), $edited_EmailCampaign->get_users_count( 'accept' ), '('.T_('Accounts which have already been sent this newsletter').')' );
	$Form->info( T_('Ready to send'), $edited_EmailCampaign->get_users_count( 'wait' ), '('.T_('Accounts which have not been sent this newsletter yet').')' );
$Form->end_fieldset();

$buttons = array();
if( $current_User->check_perm( 'emails', 'edit' ) )
{ // User must has a permission to edit emails

	$Form->begin_fieldset( T_('Send test email') );
		$Form->text_input( 'test_email_address', $Session->get( 'test_campaign_email' ), 30, T_('Email address'), T_('Fill your email address and press button "Send test email" if you want to test this newsletter'), array( 'maxlength' => 255 ) );
	$Form->end_fieldset();

	$buttons[] = array( 'submit', 'actionArray[test]', T_('Send test email'), 'SaveButton' );
	if( $edited_EmailCampaign->get_users_count( 'wait' ) > 0 )
	{	// Display message to send emails only when users exist for this campaign:
		if( $Settings->get( 'email_campaign_send_mode' ) == 'cron' )
		{	// Asynchronous sending mode:
			if( $edited_EmailCampaign->get_Cronjob() )
			{	// Cron job was already created:
				$button_title = T_('See scheduled send jobs for this campaign');
				$button_action = 'view_cron';
			}
			else
			{	// Cron job is not created yet:
				$button_title = sprintf( T_('Start a job to send campaign to %s users'), $edited_EmailCampaign->get_users_count( 'wait' ) );
				$button_action = 'create_cron';
			}
		}
		else
		{	// Immediate sending mode:
			$button_title = sprintf( T_('Send campaign to %s users now'), $edited_EmailCampaign->get_users_count( 'wait' ) );
			$button_action = 'send';
		}
		$buttons[] = array( 'submit', 'actionArray['.$button_action.']', $button_title, 'SaveButton' );
	}
}

$Form->end_form( $buttons );

?>