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

// Check permission:
$current_User->check_perm( 'options', 'view', true );

param_action();

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

switch( $action )
{
	case 'new':
		// New Automation form:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Create object of new Automation:
		$edited_Automation = new Automation();
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

		// Make sure we got an pqst_ID:
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
			$Messages->add( T_('The automation has been deleted.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=automations', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
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
		break;
	default:
		$AdminUI->set_page_manual_link( 'automations-list' );
		break;
}

$AdminUI->set_path( 'email', 'automations' );

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