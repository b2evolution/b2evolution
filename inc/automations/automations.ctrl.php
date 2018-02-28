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
param( 'display_mode', 'string', 'normal' );
param( 'tab', 'string', 'settings', true );

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

		if( param( 'confirm', 'integer', 0 ) )
		{	// Delete from DB if confirmed:
			$msg = sprintf( T_('The automation "%s" has been deleted.'), $edited_Automation->dget( 'name' ) );
			$edited_Automation->dbdelete();
			unset( $edited_Automation );
			forget_param( 'autm_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=automations', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// Check for restrictions if not confirmed yet:
			if( ! $edited_Automation->check_delete( sprintf( T_('Cannot delete automation "%s"'), $edited_Automation->dget( 'name' ) ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

	case 'status_paused':
	case 'status_active':
		// Toggle Automation status:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automation' );

		// Check that current user has permission to edit automations:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an autm_ID:
		param( 'autm_ID', 'integer', true );
		param( 'enlt_ID', 'integer', NULL );

		// Change automation status depending on action:
		$edited_Automation->set( 'status', ( $action == 'status_paused' ? 'paused' : 'active' ) );

		// Update automation in DB:
		if( $edited_Automation->dbupdate() )
		{
			$Messages->add( T_('Automation status has been changed.'), 'success' );
			// We want to highlight the moved Step on next list display:
			$Session->set( 'fadeout_array', array( 'autm_ID' => array( $edited_Automation->ID ) ) );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $enlt_ID > 0 ? $admin_url.'?ctrl=newsletters&tab=automations&action=edit&enlt_ID='.$enlt_ID : $admin_url.'?ctrl=automations', 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'requeue':
		// Requeue Automation for finished steps:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automation' );

		// Check that current user has permission to edit automations:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got IDs:
		param( 'autm_ID', 'integer', true );
		param( 'target_step_ID', 'integer', true );

		// Additional options to requeue a specific step or user:
		param( 'source_step_ID', 'integer', NULL );
		param( 'source_user_ID', 'integer', NULL );
		if( $source_user_ID > 0 )
		{	// Requeue only one specific user:
			$requeue_sql_where = 'aust_user_ID = '.$DB->quote( $source_user_ID );
		}
		elseif( $source_step_ID > 0 )
		{	// Requeue a specific step:
			$requeue_sql_where = 'aust_next_step_ID = '.$DB->quote( $source_step_ID );
		}
		else
		{	// Requeue all finished steps:
			$requeue_sql_where = 'aust_next_step_ID IS NULL';
		}

		$requeued_users_num = intval( $DB->query( 'UPDATE T_automation__user_state
			  SET aust_next_step_ID = '.$DB->quote( $target_step_ID ).',
			      aust_next_exec_ts = '.$DB->quote( date2mysql( $servertimenow ) ).'
			WHERE aust_autm_ID = '.$edited_Automation->ID.'
			  AND '.$requeue_sql_where ) );

		if( $requeued_users_num )
		{
			$Messages->add( sprintf( T_('Automation has been requeued for %d users.'), $requeued_users_num ), 'success' );
			// We want to highlight the reduced Step on list display:
			$Session->set( 'fadeout_array', array( 'aust_user_ID' => array( $source_user_ID ) ) );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		if( $source_step_ID > 0 && $source_user_ID > 0 )
		{	// Redirect to an edit page of the requeued step:
			header_redirect( $admin_url.'?ctrl=automations&action=edit_step&step_ID='.$target_step_ID, 303 ); // Will EXIT
		}
		elseif( $source_user_ID > 0 )
		{	// Redirect to a page of Automation users:
			header_redirect( $admin_url.'?ctrl=automations&action=edit&tab=users&autm_ID='.$edited_Automation->ID, 303 ); // Will EXIT
		}
		else
		{	// Redirect to a page of Automation steps:
			header_redirect( $admin_url.'?ctrl=automations&action=edit&tab=steps&autm_ID='.$edited_Automation->ID, 303 ); // Will EXIT
		}
		// We have EXITed already at this point!!
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
			header_redirect( $admin_url.'?ctrl=automations&action=edit&tab=steps&autm_ID='.$edited_AutomationStep->get( 'autm_ID' ), 303 ); // Will EXIT
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
			header_redirect( $admin_url.'?ctrl=automations&action=edit&tab=steps&autm_ID='.$edited_AutomationStep->get( 'autm_ID' ), 303 ); // Will EXIT
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
			header_redirect( $admin_url.'?ctrl=automations&action=edit&tab=steps&autm_ID='.$edited_AutomationStep->get( 'autm_ID' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		// Display the same edit automation page with steps list because step cannot be deleted by some restriciton:
		$action = 'edit';
		// We want to highlight the Step which cannot de leted on next list display:
		$Session->set( 'fadeout_array', array( 'step_ID' => array( $edited_AutomationStep->ID ) ) );
		break;

	case 'reduce_step_delay':
		// Reduce step delay for a specific user:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automation' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'user_ID', 'integer', true );
		$UserCache = & get_UserCache();
		$step_User = & $UserCache->get_by_ID( $user_ID );

		// Change execution time to NOW:
		$r = $DB->query( 'UPDATE T_automation__user_state
			  SET aust_next_exec_ts = '.$DB->quote( date2mysql( $servertimenow ) ).'
			WHERE aust_autm_ID = '.$edited_Automation->ID.'
			  AND aust_user_ID = '.$step_User->ID.'
			  AND aust_next_step_ID IS NOT NULL' ); // exclude finished steps

		if( $r )
		{	// Display a result message if user really has been affected:
			$Messages->add( sprintf( T_('Execution time has been changed to now for user %s.'), '"'.$step_User->dget( 'login' ).'"' ), 'success' );
			// We want to highlight the reduced Step on list display:
			$Session->set( 'fadeout_array', array( 'aust_user_ID' => array( $step_User->ID ) ) );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		if( isset( $edited_AutomationStep ) )
		{	// Redirect to a page of Step edit form:
			header_redirect( $admin_url.'?ctrl=automations&action=edit_step&step_ID='.$edited_AutomationStep->ID, 303 ); // Will EXIT
		}
		else
		{	// Redirect to a page of Automation users:
			header_redirect( $admin_url.'?ctrl=automations&action=edit&tab=users&autm_ID='.$edited_Automation->ID, 303 ); // Will EXIT
		}
		// We have EXITed already at this point!!
		break;

	case 'stop_user':
		// Stop automation for a specific user:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automation' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'user_ID', 'integer', true );
		$UserCache = & get_UserCache();
		$step_User = & $UserCache->get_by_ID( $user_ID );

		// Change execution time to NOW:
		$r = $DB->query( 'UPDATE T_automation__user_state
			  SET aust_next_step_ID = NULL,
			      aust_next_exec_ts = NULL
			WHERE aust_autm_ID = '.$edited_Automation->ID.'
			  AND aust_user_ID = '.$step_User->ID );

		if( $r )
		{	// Display a result message if user really has been affected:
			$Messages->add( sprintf( T_('Automation has been stopped for user %s.'), '"'.$step_User->dget( 'login' ).'"' ), 'success' );
			// We want to highlight the reduced Step on list display:
			$Session->set( 'fadeout_array', array( 'aust_user_ID' => array( $step_User->ID ) ) );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=automations&action=edit&tab=users&autm_ID='.$edited_Automation->ID, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'remove_user':
		// Remove a specific user from automation:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'automation' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'user_ID', 'integer', true );
		$UserCache = & get_UserCache();
		$step_User = & $UserCache->get_by_ID( $user_ID );

		// Change execution time to NOW:
		$r = $DB->query( 'DELETE FROM T_automation__user_state
			WHERE aust_autm_ID = '.$edited_Automation->ID.'
			  AND aust_user_ID = '.$step_User->ID );

		if( $r )
		{	// Display a result message if user really has been affected:
			$Messages->add( sprintf( T_('User %s has been removed from this automation.'), '"'.$step_User->dget( 'login' ).'"' ), 'success' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=automations&action=edit&tab=users&autm_ID='.$edited_Automation->ID, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Emails'), $admin_url.'?ctrl=campaigns' );
$AdminUI->breadcrumbpath_add( T_('Automations'), $admin_url.'?ctrl=automations' );

$AdminUI->display_breadcrumbpath_init();

$AdminUI->set_path( 'email', 'automations' );

// Set an url for manual page:
switch( $action )
{
	case 'new':
	case 'edit':
	case 'delete':
	case 'new_step':
	case 'edit_step':
		$AdminUI->display_breadcrumbpath_add( T_('Automations'), $admin_url.'?ctrl=automations' );
		if( $action != 'new' )
		{	// Add menu level 3 entries:
			if( empty( $edited_Automation ) )
			{	// Get Automation of the edited Step:
				$edited_Automation = & $edited_AutomationStep->get_Automation();
				set_param( 'tab', 'steps' );
			}
			$AdminUI->add_menu_entries( array( 'email', 'automations' ), array(
					'settings' => array(
						'text' => T_('Settings'),
						'href' => $admin_url.'?ctrl=automations&amp;action=edit&amp;tab=settings&amp;autm_ID='.$edited_Automation->ID ),
					'steps' => array(
						'text' => T_('Steps'),
						'href' => $admin_url.'?ctrl=automations&amp;action=edit&amp;tab=steps&amp;autm_ID='.$edited_Automation->ID ),
					'users' => array(
						'text' => T_('Users'),
						'href' => $admin_url.'?ctrl=automations&amp;action=edit&amp;tab=users&amp;autm_ID='.$edited_Automation->ID ),
				) );
			if( in_array( $action, array( 'edit', 'delete' ) ) )
			{
				$AdminUI->display_breadcrumbpath_add( $edited_Automation->dget( 'name' ) );
			}
		}
		else
		{	// Don't add level 3 entries for new creating automation, and force tab only for settings:
			set_param( 'tab', 'settings' );
			$AdminUI->display_breadcrumbpath_add( T_('New automation') );
		}

		switch( $tab )
		{
			case 'steps':
				$AdminUI->set_page_manual_link( 'automation-steps' );
				$AdminUI->set_path( 'email', 'automations', 'steps' );
				break;

			case 'users':
				$AdminUI->set_page_manual_link( 'automation-users-queued' );
				$AdminUI->set_path( 'email', 'automations', 'users' );
				break;

			default:
			case 'settings':
				$AdminUI->set_page_manual_link( 'automation-form-settings' );
				$AdminUI->set_path( 'email', 'automations', 'settings' );
				// Init JS to autcomplete the user logins
				init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );
		}
		break;
	default:
		$AdminUI->display_breadcrumbpath_add( T_('Automations') );
		$AdminUI->set_page_manual_link( 'automations-list' );
		break;
}

if( in_array( $action, array( 'new_step', 'edit_step' ) ) )
{	// Load jQuery QueryBuilder plugin files:
	$step_Automation = & $edited_AutomationStep->get_Automation();
	init_querybuilder_js( 'rsc_url' );
	if( $action == 'new_step' )
	{
		$AdminUI->display_breadcrumbpath_add( $edited_Automation->dget( 'name' ), $admin_url.'?ctrl=automations&amp;action=edit&amp;autm_ID='.$step_Automation->ID );
		$AdminUI->display_breadcrumbpath_add( T_('New step') );
	}
	else
	{
		$AdminUI->display_breadcrumbpath_add( $edited_Automation->dget( 'name' ), $admin_url.'?ctrl=automations&amp;action=edit&amp;autm_ID='.$step_Automation->ID );
		$AdminUI->display_breadcrumbpath_add( T_('Step').' #'.$edited_AutomationStep->dget( 'order' ) );
	}
}

if( $display_mode != 'js' )
{
	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();

	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();

	// Begin payload block:
	$AdminUI->disp_payload_begin();

	evo_flush();
}

switch( $action )
{
	case 'delete':
		// We need to ask for confirmation:
		$edited_Automation->confirm_delete(
				sprintf( T_('Delete automation "%s"?'), $edited_Automation->dget( 'name' ) ),
				'automation', $action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'edit':
		// Display a form of automation:
		switch( $tab )
		{
			case 'steps':
				$AdminUI->disp_view( 'automations/views/_automation_steps.view.php' );
				break;

			case 'users':
				$AdminUI->disp_view( 'automations/views/_automation_users.view.php' );
				break;

			case 'settings':
			default:
				$AdminUI->disp_view( 'automations/views/_automation.form.php' );
		}
		break;

	case 'requeue_form':
		// Display a form to requeue automation:
		$AdminUI->disp_view( 'automations/views/_automation_requeue.form.php' );
		// Do not append Debuglog & Debug JSlog to response!
		$debug = false;
		$debug_jslog = false;
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

if( $display_mode != 'js' )
{
	// End payload block:
	$AdminUI->disp_payload_end();

	// Display body bottom, debug info and close </html>:
	$AdminUI->disp_global_footer();
}
?>