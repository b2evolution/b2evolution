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

$Form->global_icon( T_('Cancel editing').'!', 'close', regenerate_url( 'action,step_ID', 'action=edit&amp;autm_ID='.$step_Automation->ID ) );

$Form->begin_form( 'fform', sprintf( $creating ? T_('New step of automation %s') : T_('Step of automation %s'), '#'.$step_Automation->ID.' "'.$step_Automation->get( 'name' ).'"' ).get_manual_link( 'automation-stop-form' ) );

$Form->add_crumb( 'automationstep' );
$Form->hidden( 'action', $creating ? 'create_step' : 'update_step' );
$Form->hidden( 'autm_ID', $step_Automation->ID );
$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',step_ID' : '' ) ) );

$Form->text_input( 'step_order', $edited_AutomationStep->get( 'order' ), 5, T_('Order'), '', array( 'maxlength' => 11, 'required' => true ) );

$Form->text_input( 'step_label', $edited_AutomationStep->get( 'label' ), 40, T_('Label'), '', array( 'maxlength' => 255 ) );

$Form->select_input_array( 'step_type', $edited_AutomationStep->get( 'type' ), step_get_type_titles(), T_('Type'), '', array( 'force_keys_as_values' => true, 'required' => true ) );

$Form->end_form( array(
		array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' )
	) );
?>