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

if( $current_User->check_perm( 'emails', 'edit' ) )
{	// Print out this fake button on top in order to use submit action "save" on press "Enter" key:
	echo '<input type="submit" name="actionArray[save]" style="position:absolute;left:-1000px" />';
}

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'current_tab', $tab );
$Form->hidden( 'ecmp_ID', $edited_EmailCampaign->ID );

$Form->begin_fieldset( T_('Campaign info').get_manual_link( 'creating-an-email-campaign' ) );
	$Form->text_input( 'ecmp_email_title', $edited_EmailCampaign->get( 'email_title' ) == '' ? $edited_EmailCampaign->get( 'name' ) : $edited_EmailCampaign->get( 'email_title' ), 60, T_('Email title'), '', array( 'maxlength' => 255, 'required' => true ) );
	$Form->info( T_('Campaign created'), mysql2localedatetime_spans( $edited_EmailCampaign->get( 'date_ts' ) ) );
	$Form->info( T_('Last sent manually'), $edited_EmailCampaign->get( 'sent_ts' ) ? mysql2localedatetime_spans( $edited_EmailCampaign->get( 'sent_ts' ) ) : T_('Not sent yet') );
	$Form->info( T_('Last sent automatically'), $edited_EmailCampaign->get( 'auto_sent_ts' ) ? mysql2localedatetime_spans( $edited_EmailCampaign->get( 'auto_sent_ts' ) ) : T_('Not sent yet') );
	$Form->radio_input( 'ecmp_auto_send', $edited_EmailCampaign->get( 'auto_send' ), array(
			array( 'value' => 'no',           'label' => T_('No (Manual sending only)') ),
			array( 'value' => 'subscription', 'label' =>  T_('At subscription') ),
		), T_('Auto send'), array( 'lines' => true ) );
$Form->end_fieldset();

$Form->begin_fieldset( T_('List recipients') );
	$NewsletterCache = & get_NewsletterCache();
	$NewsletterCache->load_where( 'enlt_active = 1 OR enlt_ID = '.intval( $edited_EmailCampaign->get( 'enlt_ID' ) ) );
	$Form->select_input_object( 'ecmp_enlt_ID', $edited_EmailCampaign->get( 'enlt_ID' ), $NewsletterCache, T_('Send to subscribers of'), array(
			'required'     => true,
			'field_suffix' => '<input type="submit" name="actionArray[update_newsletter]" class="btn btn-default" value="'.format_to_output( T_('Update'), 'htmlattr' ).'" />' ) );
	$Form->info( T_('Currently selected recipients'), $edited_EmailCampaign->get_recipients_count(), '('.T_('Accounts which currently accept this list').')' );
	$Form->info_field( T_('After additional filter'), $edited_EmailCampaign->get_recipients_count( 'filter', true ), array(
			'class' => 'info_full_height',
			'note'  => '('.T_('Accounts that match your additional filter').') '
			           .'<a href="'.$admin_url.'?ctrl=users&amp;action=campaign&amp;ecmp_ID='.$edited_EmailCampaign->ID.'" class="btn btn-default">'.T_('Change filter').'</a>',
		) );
	$Form->info( T_('Already received'), $edited_EmailCampaign->get_recipients_count( 'receive', true ), '('.T_('Accounts which have already been sent this campaign').')' );
	$Form->begin_line( T_('Skip users who have any of these tags'), 'ecmp_user_tag_sendskip' );
		$Form->text_input( 'ecmp_user_tag_sendskip', param( 'ecmp_user_tag_sendskip', 'string', $edited_EmailCampaign->get( 'user_tag_sendskip' ) ), 60, '',
				'', array(
			'maxlength' => 255,
			'style'     => 'width: 100%;',
			'input_prefix' => '<span id="user_admin_tags_sendskip" class="input-group user_admin_tags">',
			'input_suffix' => '</span>',
		) );
		$Form->info( '', '<span id="skipped_tag_count">'.$edited_EmailCampaign->get_recipients_count( 'skipped_tag' ).'</span>', T_('users will be skipped').' '.
				action_icon( T_('Refresh'), 'refresh', '#', NULL, NULL, NULL, array( 'onclick' => 'return update_campaign_recipients_count( '.$edited_EmailCampaign->ID.' )' ) ) );
	$Form->end_line();
	$Form->info( T_('Ready to send'), '<span id="ready_to_send_count">'.$edited_EmailCampaign->get_recipients_count( 'wait', true ).'</span>', '('.T_('Accounts which meet all criteria to receive this campaign').')' );
	$Form->text_input( 'ecmp_user_tag_sendsuccess', param( 'ecmp_user_tag_sendsuccess', 'string', $edited_EmailCampaign->get( 'user_tag_sendsuccess' ) ), 60, T_('On successful send, tag users with'), '', array(
		'maxlength' => 255,
		'style'     => 'width: 100%;',
		'input_prefix' => '<div id="user_admin_tags_sendsuccess" class="input-group user_admin_tags">',
		'input_suffix' => '</div>',
	) );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Click tracking') );
	$Form->text_input( 'ecmp_user_tag', param( 'ecmp_user_tag', 'string', $edited_EmailCampaign->get( 'user_tag' ) ), 60, T_('Tag users who click on content links with'), '', array(
		'maxlength' => 255,
		'style'     => 'width: 100%;',
		'input_prefix' => '<div id="user_admin_tags" class="input-group user_admin_tags">',
		'input_suffix' => '</div>',
	) );
	$Form->text_input( 'ecmp_user_tag_cta1', param( 'ecmp_user_tag_cta1', 'string', $edited_EmailCampaign->get( 'user_tag_cta1' ) ), 60, /* TRANS: CTA means Call To Action */ T_('Tag users who click CTA 1 with'), '', array(
		'maxlength' => 255,
		'style'     => 'width: 100%;',
		'input_prefix' => '<div id="user_admin_tags_cta1" class="input-group user_admin_tags">',
		'input_suffix' => '</div>',
	) );
	$Form->text_input( 'ecmp_user_tag_cta2', param( 'ecmp_user_tag_cta2', 'string', $edited_EmailCampaign->get( 'user_tag_cta2' ) ), 60, /* TRANS: CTA means Call To Action */ T_('Tag users who click CTA 2 with'), '', array(
		'maxlength' => 255,
		'style'     => 'width: 100%;',
		'input_prefix' => '<div id="user_admin_tags_cta2" class="input-group user_admin_tags">',
		'input_suffix' => '</div>',
	) );
	$Form->text_input( 'ecmp_user_tag_cta3', param( 'ecmp_user_tag_cta3', 'string', $edited_EmailCampaign->get( 'user_tag_cta3' ) ), 60, /* TRANS: CTA means Call To Action */ T_('Tag users who click CTA 3 with'), '', array(
		'maxlength' => 255,
		'style'     => 'width: 100%;',
		'input_prefix' => '<div id="user_admin_tags_cta3" class="input-group user_admin_tags">',
		'input_suffix' => '</div>',
	) );
	$Form->text_input( 'ecmp_user_tag_like', param( 'ecmp_user_tag_like', 'string', $edited_EmailCampaign->get( 'user_tag_like' ) ), 60, T_('Tag users who liked the email with'), '', array(
		'maxlength' => 255,
		'style'     => 'width: 100%;',
		'input_prefix' => '<div id="user_admin_tags_like" class="input-group user_admin_tags">',
		'input_suffix' => '</div>',
	) );
	$Form->text_input( 'ecmp_user_tag_dislike', param( 'ecmp_user_tag_dislike', 'string', $edited_EmailCampaign->get( 'user_tag_dislike' ) ), 60, T_('Tag users who disliked the email with'), '', array(
		'maxlength' => 255,
		'style'     => 'width: 100%;',
		'input_prefix' => '<div id="user_admin_tags_dislike" class="input-group user_admin_tags">',
		'input_suffix' => '</div>',
	) );

	?>
	<script type="text/javascript">
	function init_autocomplete_tags( selector )
	{
		var tags = jQuery( selector ).val();
		var tags_json = new Array();
		if( tags.length > 0 )
		{ // Get tags from <input>
			tags = tags.split( ',' );
			for( var t in tags )
			{
				tags_json.push( { id: tags[t], name: tags[t] } );
			}
		}

		jQuery( selector ).tokenInput( '<?php echo get_restapi_url().'usertags' ?>',
		{
			theme: 'facebook',
			queryParam: 's',
			propertyToSearch: 'name',
			tokenValue: 'name',
			preventDuplicates: true,
			prePopulate: tags_json,
			hintText: '<?php echo TS_('Type in a tag') ?>',
			noResultsText: '<?php echo TS_('No results') ?>',
			searchingText: '<?php echo TS_('Searching...') ?>',
			jsonContainer: 'tags',
		} );
	}

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

	jQuery( document ).ready( function()
	{
		jQuery( '#ecmp_user_tag_sendskip, #ecmp_user_tag_sendsuccess, #ecmp_user_tag, #ecmp_user_tag_cta1, #ecmp_user_tag_cta2, #ecmp_user_tag_cta3, #ecmp_user_tag_like, #ecmp_user_tag_dislike' ).hide();
		init_autocomplete_tags( '#ecmp_user_tag_sendskip' );
		init_autocomplete_tags( '#ecmp_user_tag_sendsuccess' );
		init_autocomplete_tags( '#ecmp_user_tag' );
		init_autocomplete_tags( '#ecmp_user_tag_cta1' );
		init_autocomplete_tags( '#ecmp_user_tag_cta2' );
		init_autocomplete_tags( '#ecmp_user_tag_cta3' );
		init_autocomplete_tags( '#ecmp_user_tag_like' );
		init_autocomplete_tags( '#ecmp_user_tag_dislike' );
		<?php
			// Don't submit a form by Enter when user is editing the tags
			echo get_prevent_key_enter_js( '#token-input-ecmp_user_tag_sendskip, #token-input-ecmp_user_tag_sendsuccess, #token-input-ecmp_user_tag, #token-input-ecmp_user_tag_cta1, #token-input-ecmp_user_tag_cta2, #token-input-ecmp_user_tag_cta3, #token-input-ecmp_user_tag_like, #token-input-ecmp_user_tag_dislike' );
		?>
	} );
	</script>
	<?php
$Form->end_fieldset();

$buttons = array();
if( $current_User->check_perm( 'emails', 'edit' ) )
{ // User must has a permission to edit emails
	$buttons[] = array( 'submit', 'actionArray[save]', T_('Save info'), 'SaveButton' );
}
$Form->end_form( $buttons );

?>