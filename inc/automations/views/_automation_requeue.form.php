<?php
/**
 * This file display the form to requeue automation
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


global $edited_Automation;

// Begin payload block:
$this->disp_payload_begin();

$Form = new Form( '', 'automation_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'automation' );
$Form->hidden_ctrl();
$Form->hidden( 'autm_ID', $edited_Automation->ID );
// To requeue by specific step:
$Form->hidden( 'source_step_ID', param( 'source_step_ID', 'integer', NULL ) );
// To requeue only a specific user:
$Form->hidden( 'source_user_ID', param( 'source_user_ID', 'integer', NULL ) );

$Form->begin_fieldset( T_('Requeue automation for finished steps').get_manual_link( 'requeue-automation-for-finished-steps' ), array( 'style' => 'width:420px' ) );

	// Get automations where user is NOT added yet:
	$AutomationStepCache = & get_AutomationStepCache();
	$automation_cache_SQL = $AutomationStepCache->get_SQL_object();
	$automation_cache_SQL->WHERE_and( 'step_autm_ID = '.$edited_Automation->ID );
	$AutomationStepCache->load_by_sql( $automation_cache_SQL );
	$Form->select_input_object( 'target_step_ID', '', $AutomationStepCache, T_('Step'), array( 'required' => true ) );

	echo '<p class="center">';
	$Form->button( array( '', 'actionArray[requeue]', T_('Requeue'), 'SaveButton' ) );
	echo '</p>';

$Form->end_fieldset();

$Form->end_form();

// End payload block:
$this->disp_payload_end();
?>