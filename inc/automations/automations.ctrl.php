<?php
/**
 * This file implements the UI controller for browsing the automations.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_funcs( 'automations/model/_automation.funcs.php' );
load_class( 'automations/model/_automation.class.php', 'Automation' );
load_class( 'automations/model/_automationstep.class.php', 'AutomationStep' );

// Check permission:
$current_User->check_perm( 'options', 'view', true );

param_action( '', true );

if( param( 'autm_ID', 'integer', '', true ) )
{	// Load Automation object:
	$AutomationCache = & get_AutomationCache();
	if( ( $edited_Automation = & $AutomationCache->get_by_ID( $autm_ID, false ) ) === false )
	{	// We could not find the automation to edit:
		unset( $edited_Automation );
		forget_param( 'autm_ID' );
		$action = '';
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Automation') ), 'error' );
	}
}

if( param( 'step_ID', 'integer', '', true ) )
{	// Load AutomationStep object:
	$AutomationStepCache = & get_AutomationStepCache();
	if( ( $edited_AutomationStep = & $AutomationStepCache->get_by_ID( $step_ID, false ) ) === false )
	{	// We could not find the automation step to edit:
		unset( $edited_AutomationStep );
		forget_param( 'autm_ID' );
		$action = '';
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Automation step') ), 'error' );
	}
}

switch( $action )
{
	case 'new':
		// New Automation form:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Create object of new Automation:
		$edited_Automation = new Automation();
		break;

	case 'edit':
	case 'edit_step':
		// Edit Automation/Step forms:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
		break;

	case 'new_step':
		// New Automation Step form:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Create object of new Automation:
		$edited_AutomationStep = new AutomationStep();
		$edited_AutomationStep->set( 'autm_ID', $autm_ID );
		break;
 
	case 'create':
		// Create new Automation:
		$edited_Automation = new Automation();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automation' );

		// Check that current user has permission to create automations:
		$current_User->check_perm( 'options', 'edit', true );

		// load data from request
		if( $edited_Automation->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_Automation->dbinsert();
			$Messages->add( T_('New automation has been created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=automations', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'new';
		break;

	case 'update':
		// Update Automation:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automation' );

		// Check that current user has permission to edit automations:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an autm_ID:
		param( 'autm_ID', 'integer', true );

		// load data from request:
		if( $edited_Automation->load_from_Request() )
		{	// We could load data from form without errors:
			// Update automation in DB:
			$edited_Automation->dbupdate();
			$Messages->add( T_('Automation has been updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=automations', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'edit';
		break;

	case 'delete':
		// Delete Automation:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automation' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an autm_ID:
		param( 'autm_ID', 'integer', true );

		if( $edited_Automation->dbdelete() )
		{
			$Messages->add( T_('Automation has been deleted.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=automations', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'move_step_up':
	case 'move_step_down':
		// Move up/down Automation Step:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automationstep' );

		// Check that current user has permission to create automation steps:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an step_ID:
		param( 'step_ID', 'integer', true );

		if( $action == 'move_step_up' )
		{	// Set variables for "move up" action
			$order_condition = '<';
			$order_direction = 'DESC';
		}
		else
		{	// move down
			$order_condition = '>';
			$order_direction = 'ASC';
		}

		$DB->begin( 'SERIALIZABLE' );

		// Get near step, We should swap the order with this step:
		$SQL = new SQL( 'Get near Step to reorder it with moved Step #'.$edited_AutomationStep->ID );
		$SQL->SELECT( 'step_ID, step_order' );
		$SQL->FROM( 'T_automation__step' );
		$SQL->WHERE( 'step_autm_ID = '.$edited_AutomationStep->get( 'autm_ID' ) );
		$SQL->WHERE_and( 'step_order '.$order_condition.' '.$edited_AutomationStep->get( 'order' ) );
		$SQL->ORDER_BY( 'step_order '.$order_direction );
		$SQL->LIMIT( 1 );
		$swaped_step = $DB->get_row( $SQL );

		if( empty( $swaped_step ) )
		{	// Current step is first or last in group, no change ordering:
			$DB->commit(); // This is required only to not leave open transaction
			$action = 'edit'; // To keep same opened page
			break;
		}

		// Switch orders of the steps:
		$result = true;
		for( $i = 0; $i < 2; $i++ )
		{	// We can swap orders only in two SQL queries to avoid error of duplicate entry because of step_order is unique index per Automation:
			// By first SQL query we update the step orders to reserved values which cannot be assigned on edit form by user:
			$step_order_1 = ( $i == 0 ? -2147483647 : $swaped_step->step_order );
			$step_order_2 = ( $i == 0 ? -2147483648 : $edited_AutomationStep->get( 'order' ) );
			$result = ( $result !== false ) && $DB->query( 'UPDATE T_automation__step
				SET step_order = CASE 
					WHEN step_ID = '.$edited_AutomationStep->ID.' THEN '.$step_order_1.'
					WHEN step_ID = '.$swaped_step->step_ID.'    THEN '.$step_order_2.'
					ELSE step_order
				END
				WHERE step_ID IN ( '.$edited_AutomationStep->ID.', '.$swaped_step->step_ID.' )' );
		}

		if( $result !== false )
		{	// Update was successful:
			$DB->commit();
			$Messages->add( T_('Order has been changed.'), 'success' );
			// We want to highlight the moved Step on next list display:
			$Session->set( 'fadeout_array', array( 'step_ID' => array( $edited_AutomationStep->ID ) ) );
		}
		else
		{	// Couldn't update successfully, probably because of concurrent modification
			// Note: In this case we may try again to execute the same queries.
			$DB->rollback();
			// The message is not localized because it may appear very rarely
			$Messages->add( 'Order could not be changed. Please try again.', 'error' );
		}

		$action = 'edit'; // To keep same opened page
		break;
 
	case 'create_step':
		// Create new Automation Step:
		$edited_AutomationStep = new AutomationStep();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automationstep' );

		// Check that current user has permission to create automation steps:
		$current_User->check_perm( 'options', 'edit', true );

		// load data from request
		if( $edited_AutomationStep->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_AutomationStep->dbinsert();
			$Messages->add( T_('New automation step has been created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=automations&action=edit&autm_ID='.$edited_AutomationStep->get( 'autm_ID' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'new_step';
		break;

	case 'update_step':
		// Update Automation Step:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automationstep' );

		// Check that current user has permission to edit automation steps:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an step_ID:
		param( 'step_ID', 'integer', true );

		// load data from request:
		if( $edited_AutomationStep->load_from_Request() )
		{	// We could load data from form without errors:
			// Update automation step in DB:
			$edited_AutomationStep->dbupdate();
			$Messages->add( T_('Automation step has been updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=automations&action=edit&autm_ID='.$edited_AutomationStep->get( 'autm_ID' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'edit_step';
		break;

	case 'delete_step':
		// Delete Automation Step:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automationstep' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an autm_ID:
		param( 'autm_ID', 'integer', true );

		if( $edited_AutomationStep->dbdelete() )
		{
			$Messages->add( T_('Automation step has been deleted.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=automations&action=edit&autm_ID='.$edited_AutomationStep->get( 'autm_ID' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		// Display the same edit automation page with steps list because step cannot be deleted by some restriciton:
		$action = 'edit';
		// We want to highlight the Step which cannot de leted on next list display:
		$Session->set( 'fadeout_array', array( 'step_ID' => array( $edited_AutomationStep->ID ) ) );
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Emails'), $admin_url.'?ctrl=campaigns' );
$AdminUI->breadcrumbpath_add( T_('Automations'), $admin_url.'?ctrl=automations' );

// Set an url for manual page:
switch( $action )
{
	case 'new':
	case 'edit':
		$AdminUI->set_page_manual_link( 'automation-form' );
		// Init JS to autcomplete the user logins
		init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );
		break;
	default:
		$AdminUI->set_page_manual_link( 'automations-list' );
		break;
}

$AdminUI->set_path( 'email', 'automations' );

if( in_array( $action, array( 'new_step', 'edit_step' ) ) )
{	// Load jQuery QueryBuilder plugin files:
	init_querybuilder_js( 'rsc_url' );
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

evo_flush();

switch( $action )
{
	case 'new':
	case 'edit':
		// Display a form of automation:
		$AdminUI->disp_view( 'automations/views/_automation.form.php' );
		break;

	case 'new_step':
	case 'edit_step':
		// Display a form of automation step:
		$AdminUI->disp_view( 'automations/views/_automation_step.form.php' );
		break;

	default:
		// Display a list of automations:
		$AdminUI->disp_view( 'automations/views/_automations.view.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>