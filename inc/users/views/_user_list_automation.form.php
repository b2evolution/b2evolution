<?php
/**
 * This file implements the UI view to add users list to automation.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $admin_url;

$Form = new Form( NULL, 'users_automation_checkchanges' );

$Form->switch_template_parts( array(
		'labelclass' => 'control-label col-sm-6',
		'inputstart' => '<div class="controls col-sm-6">',
		'inputstart_radio' => '<div class="controls col-sm-6">',
		'infostart'  => '<div class="controls col-sm-6"><div class="form-control-static">',
	) );

$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";

$Form->begin_form( 'fform' );

$Form->add_crumb( 'users' );
$Form->hidden_ctrl();

// A link to close popup window:
$close_icon = action_icon( T_('Close this window'), 'close', '', '', 0, 0, array( 'id' => 'close_button', 'class' => 'floatright' ) );

$Form->begin_fieldset( T_('Add current selection to an Automation...').get_manual_link( 'add-users-list-to-automation' ).$close_icon );

$AutomationCache = & get_AutomationCache();
$AutomationCache->load_all();
$Form->select_input_object( 'autm_ID', '', $AutomationCache, T_('Select automation'), array( 'allow_none' => true, 'required' => true ) );

$Form->select_input_array( 'enlt_ID', '', array(), T_('Select email list'), '', array( 'allow_none' => true, 'required' => true ) );

echo '<span class="loader_img loader_userlist_automation_data" title="'.T_('Loading...').'" style="display:none"></span>';
echo '<div id="userlist_automation_details">';

$Form->info( T_('Users in current selection'), count( get_filterset_user_IDs() ) );

$Form->radio( 'users_no_subs', 'ignore', array(
		array( 'ignore', T_('Ignore') ),
		array( 'add', T_('Add anyway') ),
	), sprintf( T_('Users who are not subscribed to "%s" any more').': <span id="autm_users_no_subs_num"></span>', '<span id="autm_newsletter_name"></span>' ), true );

$Form->radio( 'users_automated', 'ignore', array(
		array( 'ignore', T_('Ignore') ),
		array( 'requeue', T_('Requeue to Start') ),
	), sprintf( T_('Users who are already in automation "%s"').': <span id="autm_users_automated_num"></span>', '<span id="autm_automation_name"></span>' ), true );

$Form->radio( 'users_new', 'add', array(
		array( 'ignore', T_('Ignore') ),
		array( 'add', T_('Add to automation') ),
	), T_('New users').': <span id="autm_users_new_num"></span>', true );

echo '</div>';

$Form->end_fieldset();

$Form->button( array( '', 'actionArray[add_automation]', T_('Add selected users to "%s"'), 'SaveButton' ) );

$Form->end_form();
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( '.modal-footer .btn-primary, #userlist_automation_details, #ffield_enlt_ID' ).addClass( 'hidden' );
	jQuery( '#autm_ID' ).change( function()
	{
		jQuery( '.modal-footer .btn-primary, #userlist_automation_details, #ffield_enlt_ID' ).addClass( 'hidden' );
		if( jQuery( this ).val() != '' )
		{	// If automation is selected:
			jQuery( '.loader_userlist_automation_data' ).show();
			jQuery.ajax(
			{	// Request data for selected automation:
				type: 'POST',
				url: '<?php echo get_htsrv_url(); ?>async.php',
				data:
				{
					'action': 'get_userlist_automation',
					'autm_ID': jQuery( this ).val(),
					'crumb_users': '<?php echo get_crumb( 'users' ); ?>',
				},
				success: function( result )
				{	// Display selector with newsletters tied to selected automation:
					result = JSON.parse( result );
					var newsletters_options = '<option value=""><?php echo TS_('None'); ?></option>';
					for( var newsletter_ID in result.newsletters )
					{
						newsletters_options += '<option value="' + newsletter_ID + '">' + result.newsletters[ newsletter_ID ] + '</option>';
					}
					jQuery( '#enlt_ID' ).html( newsletters_options );
					jQuery( '#ffield_enlt_ID' ).removeClass( 'hidden' );
					jQuery( '.loader_userlist_automation_data' ).hide();
				}
			} );
		}
	} );
	jQuery( '#enlt_ID' ).change( function()
	{
		jQuery( '.modal-footer .btn-primary, #userlist_automation_details' ).addClass( 'hidden' );
		if( jQuery( this ).val() != '' )
		{	// If newsletter is selected:
			jQuery( '.loader_userlist_automation_data' ).show();
			var automation_name = jQuery( '#autm_ID' ).find( 'option:selected' ).html();
			jQuery.ajax(
			{	// Request data for selected automation and newsletter:
				type: 'POST',
				url: '<?php echo get_htsrv_url(); ?>async.php',
				data:
				{
					'action': 'get_userlist_automation',
					'autm_ID': jQuery( '#autm_ID' ).val(),
					'enlt_ID': jQuery( this ).val(),
					'crumb_users': '<?php echo get_crumb( 'users' ); ?>',
				},
				success: function( result )
				{	// Display additional form field before adding:
					result = JSON.parse( result );
					jQuery( '#autm_automation_name' ).html( automation_name );
					jQuery( '#autm_newsletter_name' ).html( result.newsletter_name );
					jQuery( '#autm_users_no_subs_num' ).html( result.users_no_subs_num );
					jQuery( '#autm_users_automated_num' ).html( result.users_automated_num );
					jQuery( '#autm_users_new_num' ).html( result.users_new_num );
					jQuery( '.modal-footer .btn-primary' ).html( '<?php echo TS_('Add selected users to "%s"'); ?>'.replace( '%s', automation_name ) );
					jQuery( '.modal-footer .btn-primary, #userlist_automation_details' ).removeClass( 'hidden' );
					jQuery( '.loader_userlist_automation_data' ).hide();
				}
			} );
		}
	} );
} );
</script>