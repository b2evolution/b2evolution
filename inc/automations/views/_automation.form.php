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


global $edited_Automation, $action, $admin_url;

// Determine if we are creating or updating:
$creating = is_create_action( $action );

$Form = new Form( NULL, 'automation_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing').'!', 'close', regenerate_url( 'action,autm_ID' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New automation') : T_('Automation') ).get_manual_link( 'automation-form' ) );

$Form->add_crumb( 'automation' );
$Form->hidden( 'action',  $creating ? 'create' : 'update' );
$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',autm_ID' : '' ) ) );

$Form->text_input( 'autm_name', $edited_Automation->get( 'name' ), 40, T_('Name'), '', array( 'maxlength' => 255, 'required' => true ) );

$Form->select_input_array( 'autm_status', $edited_Automation->get( 'status' ), autm_get_status_titles(), T_('Status'), '', array( 'force_keys_as_values' => true, 'required' => true ) );

$Form->end_form( array(
		array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' )
	) );


if( $edited_Automation->ID > 0 )
{	// Display steps of the edited Automation:
	$SQL = new SQL( 'Get all steps of automation #'.$edited_Automation->ID );
	$SQL->SELECT( 'step_ID, step_order, step_label, step_type, step_info, step_yes_next_step_ID, step_yes_next_step_delay, step_no_next_step_ID, step_no_next_step_delay, step_error_next_step_ID, step_error_next_step_delay' );
	$SQL->FROM( 'T_automation__step' );
	$SQL->WHERE( 'step_autm_ID = '.$edited_Automation->ID );

	$Results = new Results( $SQL->get(), 'step_', 'A', NULL );

	$Results->global_icon( T_('New step'), 'new', regenerate_url( 'action', 'action=new_step&amp;autm_ID='.$edited_Automation->ID ), T_('New step').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );

	$Results->title = T_('Steps').get_manual_link( 'automation-steps-list' );

	$Results->cols[] = array(
			'th'       => T_('Number'),
			'order'    => 'step_order',
			'td'       => '$step_order$',
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
		);

	$Results->cols[] = array(
			'th'    => T_('Label'),
			'order' => 'step_label',
			'td'    => '%step_td_label( #step_label#, #step_type# )%',
		);

	$Results->cols[] = array(
			'th_group' => T_('Next'),
			'th'       => T_('Yes'),
			'order'    => 'step_label',
			'td'       => '%step_td_next_step( #step_yes_next_step_ID#, #step_yes_next_step_delay# )%',
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
		);

	$Results->cols[] = array(
			'th_group' => T_('Next'),
			'th'       => T_('No'),
			'order'    => 'step_label',
			'td'       => '%step_td_next_step( #step_no_next_step_ID#, #step_no_next_step_delay# )%',
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
		);

	$Results->cols[] = array(
			'th_group' => T_('Next'),
			'th'       => T_('Error'),
			'order'    => 'step_label',
			'td'       => '%step_td_next_step( #step_error_next_step_ID#, #step_error_next_step_delay# )%',
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
		);

	$Results->cols[] = array(
			'th'       => T_('Actions'),
			'td'       => action_icon( T_('Edit this step'), 'edit', $admin_url.'?ctrl=automations&amp;action=edit_step&amp;step_ID=$step_ID$' )
									 .action_icon( T_('Delete this step!'), 'delete', regenerate_url( 'step_ID,action', 'step_ID=$step_ID$&amp;action=delete_step&amp;'.url_crumb( 'automationstep' ) ) ),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
		);

	$Results->display();
}
?>