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
global $track_email_image_load, $track_email_click_html, $track_email_click_plain_text;

$Form = new Form( NULL, 'campaign_form' );
$Form->begin_form( 'fform' );

if( check_user_perm( 'emails', 'edit' ) )
{	// Print out this fake button on top in order to use submit action "test" on press "Enter" key:
	echo '<input type="submit" name="actionArray[test]" style="position:absolute;left:-1000px" />';
}

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'current_tab', $tab );
$Form->hidden( 'ecmp_ID', $edited_EmailCampaign->ID );

if( !empty( $template_action ) && $template_action == 'send_campaign' )
{ // Execute action to send campaign to all users
	$Form->begin_fieldset( TB_('Send report') );
	$edited_EmailCampaign->send_all_emails();
	$Form->end_fieldset();
}

$Form->begin_fieldset( sprintf( TB_('Review message for: %s'), $edited_EmailCampaign->dget( 'name' ) ).get_manual_link( 'campaign-review-panel' ) );
	$Form->info( TB_('Email title'), mail_autoinsert_user_data( $edited_EmailCampaign->get( 'email_title' ), $current_User, 'text', NULL, NULL, array( 'enlt_ID' => $edited_EmailCampaign->get( 'enlt_ID' ) ) ) );
	$Form->info( TB_('Campaign created'), mysql2localedatetime_spans( $edited_EmailCampaign->get( 'date_ts' ) ) );
	$Form->info( TB_('Last sent'), $edited_EmailCampaign->get( 'sent_ts' ) ? mysql2localedatetime_spans( $edited_EmailCampaign->get( 'sent_ts' ) ) : TB_('Not sent yet') );

echo '<div style="display:table;width:100%;table-layout:fixed;">';
	echo '<div class="floatleft" style="width:50%">';
	echo '<p><b>'.TB_('HTML message').':</b></p>';
	echo '<div style="overflow:auto">'.$edited_EmailCampaign->get( 'html_template_preview' ).'</div>';
	echo '</div>';

	echo '<div class="floatright" style="width:49%">';
	echo '<p>';
		echo '<b>'.TB_('Plain-text message').':</b> &nbsp; ';
		$Form->switch_layout( 'none' );
		$Form->radio( 'ecmp_sync_plaintext', $edited_EmailCampaign->get( 'sync_plaintext' ), array(
				array( 1, TB_('Keep in sync with HTML') ),
				array( 0, TB_('Edit separately') ),
			), '' );
		$Form->button( array(
				'value' => TB_('Edit'),
				'class' => 'btn btn-info btn-sm'.( $edited_EmailCampaign->get( 'sync_plaintext' ) ? ' hidden' : '' ),
				'name'  => 'actionArray[save_sync_plaintext]',
				'id'    => 'ecmp_edit_plaintext_button',
			) );
		$Form->switch_layout( NULL );
	echo '</p>';
	echo '<div style="font-family:monospace;overflow:auto" id="ecmp_plaintext_block">'.$edited_EmailCampaign->get( 'plaintext_template_preview' ).'</div>';
	echo '</div>';
echo '</div>';
$Form->end_fieldset();

$Form->begin_fieldset( TB_('Campaign recipients').get_manual_link( 'campaign-recipients-panel' ) );
	$NewsletterCache = & get_NewsletterCache();
	$NewsletterCache->load_where( 'enlt_active = 1 OR enlt_ID = '.intval( $edited_EmailCampaign->get( 'enlt_ID' ) ) );
	$Form->select_input_object( 'ecmp_enlt_ID', $edited_EmailCampaign->get( 'enlt_ID' ), $NewsletterCache, TB_('Send to subscribers of'), array(
			'required'     => true,
			'field_suffix' => '<input type="submit" name="actionArray[update_newsletter]" class="btn btn-default" value="'.format_to_output( TB_('Update'), 'htmlattr' ).'" />' ) );
	evo_flush();
	$Form->info( TB_('Subscribers'), $edited_EmailCampaign->get_recipients_count( 'all', true ), '('.TB_('Accounts which currently accept this list').')' );
	$Form->info_field( TB_('After additional filter'), $edited_EmailCampaign->get_recipients_count( 'filter', true ), array(
			'class' => 'info_full_height',
			'note'  => '('.TB_('Accounts that match your additional filter').') '
			           .'<a href="'.$admin_url.'?ctrl=users&amp;action=campaign&amp;ecmp_ID='.$edited_EmailCampaign->ID.'" class="btn btn-default">'.TB_('Change filter').'</a>',
		) );
	$Form->info( TB_('Already received'), $edited_EmailCampaign->get_recipients_count( 'receive', true ), '('.TB_('Accounts which have already been sent this campaign').')' );
	$Form->info( TB_('Send error'), $edited_EmailCampaign->get_recipients_count( 'error', true ), '('.TB_('Accounts which had errors on receiving this campaign').')' );
	$Form->info( TB_('Manually skipped'), $edited_EmailCampaign->get_recipients_count( 'skipped', true ), '('.TB_('Accounts which will be skipped from receiving this campaign').')' );
	$Form->info( TB_('Ready to send'), $edited_EmailCampaign->get_recipients_count( 'wait', 'only_subscribed' ), '('.TB_('Accounts which meet all criteria to receive this campaign').')' );

	if( $edited_EmailCampaign->get_recipients_count( 'wait' ) > 0 )
	{	// Display message to send emails only when users exist for this campaign:
		$Form->checklist( array(
				array( 'track_email_image_load', 1, TB_('track image loads in HTML version'), 1 ),
				array( 'track_email_click_html', 1, TB_('track clickthroughs in HTML version'), 1 ),
				array( 'track_email_click_plain_text', 1, TB_('track clickthroughs in plain text version'), 1 )
			), 'track_email', TB_('Track email opens') );
		if( $Settings->get( 'email_campaign_send_mode' ) == 'cron' )
		{	// Asynchronous sending mode:
			if( $edited_EmailCampaign->get_Cronjob() )
			{	// Cron job was already created:
				$button_title = TB_('See scheduled send jobs for this campaign');
				$button_action = 'view_cron';
			}
			else
			{	// Cron job is not created yet:
				$button_title = sprintf( TB_('Start a job to send campaign to %s users'), $edited_EmailCampaign->get_recipients_count( 'wait' ) );
				$button_action = 'create_cron';
			}
		}
		else
		{	// Immediate sending mode:
			$button_title = sprintf( TB_('Send campaign to %s users now'), $edited_EmailCampaign->get_recipients_count( 'wait' ) );
			$button_action = 'send';
		}
		$send_button = array( array( 'name' => 'actionArray['.$button_action.']', 'value' => $button_title, 'class' => 'SaveButton btn btn-default' ) );
		$Form->buttons_input( $send_button );
	}
$Form->end_fieldset();

$buttons = array();
if( check_user_perm( 'emails', 'edit' ) )
{ // User must has a permission to edit emails

	$Form->begin_fieldset( TB_('Send test email').get_manual_link( 'campaign-send-test-panel' ) );
		$Form->checklist( array(
			array( 'track_test_email_image_load', 1, TB_('track image loads in HTML version'), 1 ),
			array( 'track_test_email_click_html', 1, TB_('track clickthroughs in HTML version'), 1 ),
			array( 'track_test_email_click_plain_text', 1, TB_('track clickthroughs in plain text version'), 1 )
		), 'track_test_email', TB_('Track email opens') );
		$Form->text_input( 'test_email_address', $Session->get( 'test_campaign_email' ), 30, TB_('Email address'), TB_('Fill your email address and press button "Send test email" if you want to test this list'), array( 'maxlength' => 255 ) );
		$test_button = array( array( 'name' => 'actionArray[test]', 'value' => TB_('Send test email'), 'class' => 'SaveButton btn btn-primary' ) );
		$Form->buttons_input( $test_button );
	$Form->end_fieldset();
}

$Form->end_form();
?>
<script>
jQuery( '[name=ecmp_sync_plaintext]' ).click( function()
{
	if( jQuery( this ).val() == 1 )
	{	// Keep in sync with HTML:
		if( ! confirm( '<?php echo TS_('WARNING: if you continue, all manual edits you made to the plain-text version will be lost.'); ?>' ) )
		{	// Don't continue to sync with HTML if it has not been confirmed:
			return false;
		}
		jQuery( '#ecmp_edit_plaintext_button, .ecmp_plaintext_tab' ).addClass( 'hidden' );
	}
	else
	{	// Edit separately:
		jQuery( '#ecmp_edit_plaintext_button, .ecmp_plaintext_tab' ).removeClass( 'hidden' );
	}
	// Update plain-text mode:
	jQuery.ajax(
	{
		type: 'POST',
		url: '<?php echo $admin_url; ?>',
		data:
		{
			'ctrl': 'campaigns',
			'action': 'save_sync_plaintext',
			'display_mode': 'js',
			'ecmp_ID': <?php echo $edited_EmailCampaign->ID; ?>,
			'ecmp_sync_plaintext': jQuery( this ).val(),
			'crumb_campaign': '<?php echo get_crumb( 'campaign' ); ?>',
		},
		success: function( result )
		{
			jQuery( '#ecmp_plaintext_block' ).html( result );
		}
	} );
} );
</script>