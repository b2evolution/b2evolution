<?php
/**
 * This file display the automation form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_AutomationStep, $action;

// Get Automation of the creating/editing Step:
$step_Automation = & $edited_AutomationStep->get_Automation();

// Determine if we are creating or updating:
$creating = is_create_action( $action );

$Form = new Form( NULL, 'automation_checkchanges', 'post', 'compact' );

$edit_automation_url = regenerate_url( 'action,step_ID', 'action=edit&amp;autm_ID='.$step_Automation->ID );

$Form->global_icon( T_('Cancel editing').'!', 'close', $edit_automation_url );

$Form->begin_form( 'fform', sprintf( $creating ? T_('New step') : T_('Step') ).get_manual_link( 'automation-step-form' ) );

$Form->add_crumb( 'automationstep' );
$Form->hidden( 'action', $creating ? 'create_step' : 'update_step' );
$Form->hidden( 'autm_ID', $step_Automation->ID );
$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',step_ID' : '' ) ) );

$Form->info( T_('Automation'), '<a href="'.$edit_automation_url.'">'.$step_Automation->get( 'name' ).'</a>' );

if( $step_Automation->ID > 0 )
{
	$Form->info( T_('ID'), $step_Automation->ID );
}

$Form->text_input( 'step_order', $edited_AutomationStep->get( 'order' ), 10, T_('Order'), '', array( 'maxlength' => 11, 'required' => ! $creating, 'note' => $creating ? T_('Leave empty to set an order automatically.') : '' ) );

$Form->text_input( 'step_label', $edited_AutomationStep->get( 'label' ), 40, T_('Label'), '', array( 'maxlength' => 255 ) );

$Form->select_input_array( 'step_type', $edited_AutomationStep->get( 'type' ), step_get_type_titles(), T_('Type'), '', array( 'force_keys_as_values' => true, 'required' => true ) );

$EmailCampaignCache = & get_EmailCampaignCache();
$EmailCampaignCache->load_all();
$Form->select_input_object( 'step_email_campaign',
	( $edited_AutomationStep->get( 'type' ) == 'send_campaign' ? $edited_AutomationStep->get( 'info' ) : '' ),
	$EmailCampaignCache, T_('Email Campaign'), array( 'allow_none' => true, 'required' => true ) );

// Load all steps of the edited step's automation excluding current step:
$AutomationStepCache = & get_AutomationStepCache();
$AutomationStepCache->clear();
$AutomationStepCache->load_where( 'step_autm_ID = '.$step_Automation->ID
	.( $creating ? '' : ' AND step_ID != '.$edited_AutomationStep->ID ) );

$Form->begin_line( T_('Next step if YES') );
	$Form->select_input_object( 'step_yes_next_step_ID', $edited_AutomationStep->get( 'yes_next_step_ID' ), $AutomationStepCache, '', array( 'allow_none' => true ) );
	$Form->duration_input( 'step_yes_next_step_delay', $edited_AutomationStep->get( 'yes_next_step_delay' ), T_('Delay') );
$Form->end_line();

$Form->begin_line( T_('Next step if NO') );
	$Form->select_input_object( 'step_no_next_step_ID', $edited_AutomationStep->get( 'no_next_step_ID' ), $AutomationStepCache, '', array( 'allow_none' => true ) );
	$Form->duration_input( 'step_no_next_step_delay', $edited_AutomationStep->get( 'no_next_step_delay' ), T_('Delay') );
$Form->end_line();

$Form->begin_line( T_('Next step if Error') );
	$Form->select_input_object( 'step_error_next_step_ID', $edited_AutomationStep->get( 'error_next_step_ID' ), $AutomationStepCache, '', array( 'allow_none' => true ) );
	$Form->duration_input( 'step_error_next_step_delay', $edited_AutomationStep->get( 'error_next_step_delay' ), T_('Delay') );
$Form->end_line();

$Form->end_form( array(
		array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' )
	) );
?>
<script type="text/javascript">
// Display additional info fields depending on step type:
function step_type_display_info( step_type )
{
	jQuery( '#ffield_step_email_campaign' ).hide();

	switch( step_type )
	{
		case 'send_campaign':
			jQuery( '#ffield_step_email_campaign' ).show();
			break;
	}
}
jQuery( '#step_type' ).change( function()
{
	step_type_display_info( jQuery( this ).val() );
} );
jQuery( document ).ready( function()
{
	step_type_display_info( jQuery( '#step_type' ).val() );
} );
</script>