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

$Form->info_field( T_('IF Condition'), '<div id="step_if_condition"></div>', array( 'class' => 'ffield_step_if_condition' ) );
$Form->hidden( 'step_if_condition', '' );

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

$Form->begin_line( sprintf( T_('Next step if %s'), '<span id="step_result_title_yes">'.$edited_AutomationStep->get_result_title( 'YES' ).'</span>' ) );
	$Form->select_input_object( 'step_yes_next_step_ID', $edited_AutomationStep->get( 'yes_next_step_ID' ), $AutomationStepCache, '', array( 'allow_none' => true ) );
	$Form->duration_input( 'step_yes_next_step_delay', $edited_AutomationStep->get( 'yes_next_step_delay' ), T_('Delay') );
$Form->end_line();

$Form->begin_line( sprintf( T_('Next step if %s'), '<span id="step_result_title_no">'.$edited_AutomationStep->get_result_title( 'NO' ).'</span>' ) );
	$Form->select_input_object( 'step_no_next_step_ID', $edited_AutomationStep->get( 'no_next_step_ID' ), $AutomationStepCache, '', array( 'allow_none' => true ) );
	$Form->duration_input( 'step_no_next_step_delay', $edited_AutomationStep->get( 'no_next_step_delay' ), T_('Delay') );
$Form->end_line();

$Form->begin_line( sprintf( T_('Next step if %s'), '<span id="step_result_title_error">'.$edited_AutomationStep->get_result_title( 'ERROR' ).'</span>' ) );
	$Form->select_input_object( 'step_error_next_step_ID', $edited_AutomationStep->get( 'error_next_step_ID' ), $AutomationStepCache, '', array( 'allow_none' => true ) );
	$Form->duration_input( 'step_error_next_step_delay', $edited_AutomationStep->get( 'error_next_step_delay' ), T_('Delay') );
$Form->end_line();

$Form->end_form( array(
		array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' )
	) );
?>
<script type="text/javascript">
// Update form depending on step type:
function step_type_update_info( step_type )
{
	jQuery( '#ffield_step_email_campaign, .ffield_step_if_condition' ).hide();

	switch( step_type )
	{
		case 'send_campaign':
			jQuery( '#ffield_step_email_campaign' ).show();
			jQuery( '#step_result_title_yes' ).html( '<?php echo TS_( step_get_result_title( 'send_campaign', 'YES' ) ); ?>' );
			jQuery( '#step_result_title_no' ).html( '<?php echo TS_( step_get_result_title( 'send_campaign', 'NO' ) ); ?>' );
			jQuery( '#step_result_title_error' ).html( '<?php echo TS_( step_get_result_title( 'send_campaign', 'ERROR' ) ); ?>' );
			break;

		case 'if_condition':
			jQuery( '.ffield_step_if_condition' ).show();

		default:
			jQuery( '#step_result_title_yes' ).html( '<?php echo TS_( step_get_result_title( 'if_condition', 'YES' ) ); ?>' );
			jQuery( '#step_result_title_no' ).html( '<?php echo TS_( step_get_result_title( 'if_condition', 'NO' ) ); ?>' );
			jQuery( '#step_result_title_error' ).html( '<?php echo TS_( step_get_result_title( 'if_condition', 'ERROR' ) ); ?>' );
			break;
	}
}
jQuery( '#step_type' ).change( function()
{
	step_type_update_info( jQuery( this ).val() );
} );
jQuery( document ).ready( function()
{
	step_type_update_info( jQuery( '#step_type' ).val() );

	// Initialize Query Builder for the field "IF Condition":
	jQuery( '#step_if_condition' ).queryBuilder(
	{
		plugins: ['bt-tooltip-errors'],
		icons: {
			add_group: 'fa fa-plus-circle',
			add_rule: 'fa fa-plus',
			remove_group: 'fa fa-close',
			remove_rule: 'fa fa-close',
			error: 'fa fa-warning',
		},

		filters: [
		{
			id: 'user_has_tag',
			label: '<?php echo TS_('User tag' ); ?>',
			type: 'string',
			operators: ['equal', 'not_equal'],
		},
		{
			id: 'date',
			label: '<?php echo TS_('Date' ); ?>',
			type: 'date',
			operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal', 'between', 'not_between'],
			plugin: 'datepicker',
		}
		],
	} );

	// Prefill the field "IF Condition" with stored data from DB:
	jQuery( '#step_if_condition' ).queryBuilder( 'setRulesFromSQL', '<?php echo ( $edited_AutomationStep->get( 'type' ) == 'if_condition' ? format_to_js( $edited_AutomationStep->get( 'info' ) ) : '' ); ?>' );
} );

// Prepare form submit to convert "IF Condition" field to SQL format:
jQuery( 'form' ).on( 'submit', function()
{
	var result = jQuery( '#step_if_condition' ).queryBuilder( 'getSQL' );
	if( result === null )
	{	// Stop submitting on wrong SQL:
		return false;
	}
	else
	{	// Set SQL to hidden field before submitting:
		jQuery( 'input[name=step_if_condition]' ).val( result.sql );
	}
} );
</script>