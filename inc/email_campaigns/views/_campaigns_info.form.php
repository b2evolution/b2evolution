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

if( check_user_perm( 'emails', 'edit' ) )
{	// Print out this fake button on top in order to use submit action "save" on press "Enter" key:
	echo '<input type="submit" name="actionArray[save]" style="position:absolute;left:-1000px" />';
}

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'current_tab', $tab );
$Form->hidden( 'ecmp_ID', $edited_EmailCampaign->ID );

$Form->begin_fieldset( TB_('Campaign info').get_manual_link( 'campaign-info-panel' ) );
	$Form->text_input( 'ecmp_name', $edited_EmailCampaign->get( 'name' ), 60, TB_('Campaign name'), TB_('for internal use'), array( 'maxlength' => 255, 'required' => true ) );
	$Form->info( TB_('Campaign created'), mysql2localedatetime_spans( $edited_EmailCampaign->get( 'date_ts' ) ) );
	$Form->info( TB_('Last sent manually'), $edited_EmailCampaign->get( 'sent_ts' ) ? mysql2localedatetime_spans( $edited_EmailCampaign->get( 'sent_ts' ) ) : TB_('Not sent yet') );
	$Form->info( TB_('Last sent automatically'), $edited_EmailCampaign->get( 'auto_sent_ts' ) ? mysql2localedatetime_spans( $edited_EmailCampaign->get( 'auto_sent_ts' ) ) : TB_('Not sent yet') );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('List recipients').get_manual_link( 'campaign-recipients-panel' ) );
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
	$Form->usertag_input( 'ecmp_user_tag_sendskip', param( 'ecmp_user_tag_sendskip', 'string', $edited_EmailCampaign->get( 'user_tag_sendskip' ) ), 60, TB_('Skip users who have any of these tags'),
		TB_('users will be skipped').' '.action_icon( TB_('Refresh'), 'refresh', '#', NULL, NULL, NULL, array( 'onclick' => 'return update_campaign_recipients_count( '.$edited_EmailCampaign->ID.' )' ) ),
		array(
			'maxlength' => 255,
			'input_prefix' => '<div class="evo_input__tags">',
			'input_suffix' => '</div><span id="skipped_tag_count">'.$edited_EmailCampaign->get_recipients_count( 'skipped_tag' ).'</span>',
		) );
	$Form->info( TB_('Ready to send'), '<span id="ready_to_send_count">'.$edited_EmailCampaign->get_recipients_count( 'wait', 'only_subscribed' ).'</span>', '('.TB_('Accounts which meet all criteria to receive this campaign').')' );
	$Form->usertag_input( 'ecmp_user_tag_sendsuccess', param( 'ecmp_user_tag_sendsuccess', 'string', $edited_EmailCampaign->get( 'user_tag_sendsuccess' ) ), 60, TB_('On successful send, tag users with'), '', array(
		'maxlength' => 255,
	) );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('Click tagging').get_manual_link( 'campaign-tagging-panel' ) );
	$tag_options = array(
			array( TB_('Tag users who click on content links with'), 'user_tag' ),
			array( /* TRANS: CTA means Call To Action */ TB_('Tag users who click CTA 1 with'), 'user_tag_cta1' ),
			array( /* TRANS: CTA means Call To Action */ TB_('Tag users who click CTA 2 with'), 'user_tag_cta2' ),
			array( /* TRANS: CTA means Call To Action */ TB_('Tag users who click CTA 3 with'), 'user_tag_cta3' ),
			array( TB_('Tag users who liked the email with'), 'user_tag_like' ),
			array( TB_('Tag users who disliked the email with'), 'user_tag_dislike' ),
			array( TB_('Tag users who click Activate with'), 'user_tag_activate' ),
			array( TB_('Tag users who (really) unsubscribe with'), 'user_tag_unsubscribe' ),
		);
	foreach( $tag_options as $tag_option )
	{
		$Form->usertag_input( 'ecmp_'.$tag_option[1], $edited_EmailCampaign->get( $tag_option[1] ), 60, $tag_option[0], '', array( 'maxlength' => 255 ) );
	}

	?>
	<script>
	function update_campaign_recipients_count( ecmp_ID )
	{
		jQuery.ajax(
		{	// Update a number of Email Campaign recipients on the HTML form depending on current entered skip tags:
			type: "GET",
			dataType: "JSON",
			url: '<?php echo get_htsrv_url().'async.php'; ?>',
			data:
			{
				action: 'get_campaign_recipients',
				ecmp_ID: ecmp_ID,
				skip_tags: jQuery( '#ecmp_user_tag_sendskip' ).val(),
				'crumb_campaign': '<?php echo get_crumb( 'campaign' ); ?>',
			},
			success: function( data )
			{
				if( data.status == 'ok' )
				{	// Update the recipient numbers:
					jQuery( '#skipped_tag_count' ).html( data.skipped_tag );
					jQuery( '#ready_to_send_count' ).html( data.wait );
				}
				else
				{	// Display an error:
					alert( data.error );
				}
			}
		} );
		return false;
	}
	</script>
	<?php
$Form->end_fieldset();

$Form->begin_fieldset( TB_('Automations').get_manual_link( 'campaign-automations-panel' ) );
	$AutomationCache = & get_AutomationCache();
	$AutomationCache->load_all();
	$AutomationCache->none_option_value = 0;
	$automation_options = array(
			array( TB_('Add users who click CTA 1 to'),       'cta1_autm_ID',    'cta1_autm_execute' ),
			array( TB_('Add users who click CTA 2 to'),       'cta2_autm_ID',    'cta2_autm_execute' ),
			array( TB_('Add users who click CTA 3 to'),       'cta3_autm_ID',    'cta3_autm_execute' ),
			array( TB_('Add users who like the email to'),    'like_autm_ID',    'like_autm_execute' ),
			array( TB_('Add users who dislike the email to'), 'dislike_autm_ID', 'dislike_autm_execute' ),
			array( TB_('Add users who click Activate to'),    'activate_autm_ID','activate_autm_execute' ),
		);
	foreach( $automation_options as $automation_option )
	{
		$Form->begin_line( $automation_option[0] );
			$action_autm_ID = $edited_EmailCampaign->get( $automation_option[1] );
			$Form->select_input_object( 'ecmp_'.$automation_option[1], $action_autm_ID, $AutomationCache, '', array( 'allow_none' => true ) );
			echo action_icon( TB_('Steps'), 'edit', $admin_url.'?ctrl=automations&amp;action=edit&amp;tab=steps&amp;autm_ID='.$action_autm_ID, NULL, NULL, NULL, empty( $action_autm_ID ) ? array( 'style' => 'display:none' ) : array() );
			$Form->checkbox_input( 'ecmp_'.$automation_option[2], $edited_EmailCampaign->get( $automation_option[2] ), '', array( 'input_prefix' => '<label>', 'input_suffix' => ' '.TB_('Execute first step(s) immediately').'</label> &nbsp; ' ) );
		$Form->end_line();
	}
$Form->end_fieldset();

$buttons = array();
if( check_user_perm( 'emails', 'edit' ) )
{ // User must has a permission to edit emails
	$buttons[] = array( 'submit', 'actionArray[save]', TB_('Save info'), 'SaveButton' );
}
$Form->end_form( $buttons );

?>
<script>
jQuery( 'select[name$=_autm_ID]' ).change( function()
{	// Show/Hide icon to view automation steps:
	var edit_icon = jQuery( this ).next( 'a' );
	if( jQuery( this ).val() > 0 )
	{
		edit_icon.show().attr( 'href', '<?php echo $admin_url; ?>?ctrl=automations&action=edit&tab=steps&autm_ID=' + jQuery( this ).val() );
	}
	else
	{
		edit_icon.hide();
	}
} );
</script>