<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load Subregion class (PHP4):
load_class( 'regional/model/_subregion.class.php', 'Subregion' );
load_funcs( 'regional/model/_regional.funcs.php' );

/**
 * @var User
 */
global $current_User;

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

// Memorize this as the last "tab" used in the Global Settings:
$UserSettings->set( 'pref_glob_settings_tab', $ctrl );
$UserSettings->set( 'pref_glob_regional_tab', $ctrl );
$UserSettings->dbupdate();

// Set options path:
$AdminUI->set_path( 'options', 'regional', 'subregions' );

// Get action parameter from request:
param_action();

if( param( 'subrg_ID', 'integer', '', true) )
{	// Load subregion from cache:
	$SubregionCache = & get_SubregionCache();
	if( ($edited_Subregion = & $SubregionCache->get_by_ID( $subrg_ID, false )) === false )
	{	unset( $edited_Subregion );
		forget_param( 'subrg_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Region') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'disable_subregion':
	case 'enable_subregion':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'subregion' );

		// Disable a subregion only if it is enabled, and user has edit access.
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure the subregion information was loaded. If not, just exit with error.
		if( empty($edited_Subregion) )
		{
			$Messages->add( sprintf( 'The sub-region with ID %d could not be instantiated.', $subrg_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_subregion' )
		{	// Disable this subregion by setting flag to false.
			$edited_Subregion->set( 'enabled', 0 );
			$Messages->add( sprintf( T_('Disabled sub-region (%s, #%d).'), $edited_Subregion->name, $edited_Subregion->ID ), 'success' );
		}
		elseif ( $action == 'enable_subregion' )
		{	// Enable subregion by setting flag to true.
			$edited_Subregion->set( 'enabled', 1 );
			$Messages->add( sprintf( T_('Enabled sub-region (%s, #%d).'), $edited_Subregion->name, $edited_Subregion->ID ), 'success' );
		}

		// Update db with new flag value.
		$edited_Subregion->dbupdate();

		param( 'results_subrg_page', 'integer', '', true );
		param( 'results_subrg_order', 'string', '', true );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'enable_subregion_pref':
	case 'disable_subregion_pref':

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'subregion' );

		// Disable a subregion only if it is enabled, and user has edit access.
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure the subregion information was loaded. If not, just exit with error.
		if( empty($edited_Subregion) )
		{
			$Messages->add( sprintf( 'The sub-region with ID %d could not be instantiated.', $subrg_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_subregion_pref' )
		{	// Disable this subregion by setting flag to false.
			$edited_Subregion->set( 'preferred', 0 );
			$Messages->add( sprintf( T_('Removed from preferred sub-regions (%s, #%d).'), $edited_Subregion->name, $edited_Subregion->ID ), 'success' );
		}
		elseif ( $action == 'enable_subregion_pref' )
		{	// Enable subregion by setting flag to true.
			$edited_Subregion->set( 'preferred', 1 );
			$Messages->add( sprintf( T_('Added to preferred sub-regions (%s, #%d).'), $edited_Subregion->name, $edited_Subregion->ID ), 'success' );
		}

		// Update db with new flag value.
		$edited_Subregion->dbupdate();

		param( 'results_subrg_page', 'integer', '', true );
		param( 'results_subrg_order', 'string', '', true );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( ! isset($edited_Subregion) )
		{	// We don't have a model to use, start with blank object:
			$edited_Subregion = new Subregion();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_Subregion = duplicate( $edited_Subregion ); // PHP4/5 abstraction
			$edited_Subregion->ID = 0;
		}
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an subrg_ID:
		param( 'subrg_ID', 'integer', true );
 		break;

	case 'create': // Record new subregion
	case 'create_new': // Record subregion and create new
	case 'create_copy': // Record subregion and create similar
		// Insert new subregion:
		$edited_Subregion = new Subregion();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'subregion' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Load data from request
		if( $edited_Subregion->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$DB->begin();
			$q = $edited_Subregion->dbexists();
			if($q)
			{	// We have a duplicate entry:

				param_error( 'subrg_code',
					sprintf( T_('This sub-region already exists. Do you want to <a %s>edit the existing sub-region</a>?'),
						'href="?ctrl=subregions&amp;action=edit&amp;subrg_ID='.$q.'"' ) );
			}
			else
			{
				unset( $edited_Subregion->dbchanges['subrg_ctry_ID'] );
				$edited_Subregion->dbinsert();
				$Messages->add( T_('New region created.'), 'success' );
			}
			$DB->commit();

			if( empty($q) )
			{	// What next?

				switch( $action )
				{
					case 'create_copy':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=subregions&action=new&subrg_ID='.$edited_Subregion->ID, 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
					case 'create_new':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=subregions&action=new', 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
					case 'create':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=subregions', 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
				}
			}
		}
		break;

	case 'update':
		// Edit subregion form:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'subregion' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an subrg_ID:
		param( 'subrg_ID', 'integer', true );

		// load data from request
		if( $edited_Subregion->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$DB->begin();
			$q = $edited_Subregion->dbexists();
			if($q)
			{	// We have a duplicate entry:
				param_error( 'subrg_code',
					sprintf( T_('This sub-region already exists. Do you want to <a %s>edit the existing sub-region</a>?'),
						'href="?ctrl=subregions&amp;action=edit&amp;subrg_ID='.$q.'"' ) );
			}
			else
			{
				unset( $edited_Subregion->dbchanges['subrg_ctry_ID'] );
				$edited_Subregion->dbupdate();
				$Messages->add( T_('Region updated.'), 'success' );
			}
			$DB->commit();

			if( empty($q) )
			{	// If no error, Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=subregions', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}
		break;

	case 'delete':
		// Delete subregion:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'subregion' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an subrg_ID:
		param( 'subrg_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Region &laquo;%s&raquo; deleted.'), $edited_Subregion->dget('name') );
			$edited_Subregion->dbdelete();
			unset( $edited_Subregion );
			forget_param( 'subrg_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=subregions', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Subregion->check_delete( sprintf( T_('Cannot delete sub-region &laquo;%s&raquo;'), $edited_Subregion->dget('name') ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('Regional'), $admin_url.'?ctrl=locales' );
$AdminUI->breadcrumbpath_add( T_('Sub-regions'), $admin_url.'?ctrl=subregions' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;

	case 'delete':
		// We need to ask for confirmation:
		$edited_Subregion->confirm_delete(
				sprintf( T_('Delete sub-region &laquo;%s&raquo;?'), $edited_Subregion->dget('name') ),
				'subregion', $action, get_memorized( 'action' ) );
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':
		$AdminUI->disp_view( 'regional/views/_subregion.form.php' );
		break;

	default:
		// No specific request, list all subregions:
		// Cleanup context:
		forget_param( 'subrg_ID' );
		// Display subregions list:
		$AdminUI->disp_view( 'regional/views/_subregion_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>