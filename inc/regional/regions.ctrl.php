<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load Region class (PHP4):
load_class( 'regional/model/_region.class.php', 'Region' );

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
$AdminUI->set_path( 'options', 'regional', 'regions' );

// Get action parameter from request:
param_action();

if( param( 'rgn_ID', 'integer', '', true) )
{// Load region from cache:
	$RegionCache = & get_RegionCache();
	if( ($edited_Region = & $RegionCache->get_by_ID( $rgn_ID, false )) === false )
	{	unset( $edited_Region );
		forget_param( 'rgn_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Region') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'disable_region':
	case 'enable_region':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Disable a region only if it is enabled, and user has edit access.
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure the region information was loaded. If not, just exit with error.
		if( empty($edited_Region) )
		{
			$Messages->add( sprintf( 'The region with ID %d could not be instantiated.', $rgn_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_region' )
		{	// Disable this region by setting flag to false.
			$edited_Region->set( 'enabled', 0 );
			$Messages->add( sprintf( T_('Disabled region (%s, #%d).'), $edited_Region->name, $edited_Region->ID ), 'success' );
		}
		elseif ( $action == 'enable_region' )
		{	// Enable region by setting flag to true.
			$edited_Region->set( 'enabled', 1 );
			$Messages->add( sprintf( T_('Enabled region (%s, #%d).'), $edited_Region->name, $edited_Region->ID ), 'success' );
		}

		// Update db with new flag value.
		$edited_Region->dbupdate();

		param( 'results_rgn_page', 'integer', '', true );
		param( 'results_rgn_order', 'string', '', true );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'enable_region_pref':
	case 'disable_region_pref':

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Disable a region only if it is enabled, and user has edit access.
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure the region information was loaded. If not, just exit with error.
		if( empty($edited_Region) )
		{
			$Messages->add( sprintf( 'The region with ID %d could not be instantiated.', $rgn_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_region_pref' )
		{	// Disable this region by setting flag to false.
			$edited_Region->set( 'preferred', 0 );
			$Messages->add( sprintf( T_('Removed from preferred regions (%s, #%d).'), $edited_Region->name, $edited_Region->ID ), 'success' );
		}
		elseif ( $action == 'enable_region_pref' )
		{	// Enable region by setting flag to true.
			$edited_Region->set( 'preferred', 1 );
			$Messages->add( sprintf( T_('Added to preferred regions (%s, #%d).'), $edited_Region->name, $edited_Region->ID ), 'success' );
		}

		// Update db with new flag value.
		$edited_Region->dbupdate();

		param( 'results_rgn_page', 'integer', '', true );
		param( 'results_rgn_order', 'string', '', true );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( ! isset($edited_Region) )
		{	// We don't have a model to use, start with blank object:
			$edited_Region = new Region();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_Region = clone $edited_Region;
			$edited_Region->ID = 0;
		}
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an rgn_ID:
		param( 'rgn_ID', 'integer', true );
 		break;

	case 'create': // Record new region
	case 'create_new': // Record region and create new
	case 'create_copy': // Record region and create similar
		// Insert new region:
		$edited_Region = new Region();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Load data from request
		if( $edited_Region->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$edited_Region->dbinsert();
			$Messages->add( T_('New region created.'), 'success' );

			// What next?
			switch( $action )
			{
				case 'create_copy':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=regions&action=new&rgn_ID='.$edited_Region->ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create_new':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=regions&action=new', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=regions', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
			}
		}
		break;

	case 'update':
		// Edit region form:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an rgn_ID:
		param( 'rgn_ID', 'integer', true );

		// load data from request
		if( $edited_Region->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$edited_Region->dbupdate();
			$Messages->add( T_('Region updated.'), 'success' );

			// If no error, Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=regions', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'delete':
		// Delete region:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an rgn_ID:
		param( 'rgn_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Region &laquo;%s&raquo; deleted.'), $edited_Region->dget('name') );
			$edited_Region->dbdelete();
			unset( $edited_Region );
			forget_param( 'rgn_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=regions', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Region->check_delete( sprintf( T_('Cannot delete region &laquo;%s&raquo;'), $edited_Region->dget('name') ) ) )
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
$AdminUI->breadcrumbpath_add( T_('Regions'), $admin_url.'?ctrl=regions' );

// Set an url for manual page:
switch( $action )
{
	case 'delete':
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':
		$AdminUI->set_page_manual_link( 'regions-editing' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'regions-list' );
		break;
}

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
		$edited_Region->confirm_delete(
				sprintf( T_('Delete region &laquo;%s&raquo;?'), $edited_Region->dget('name') ),
				'region', $action, get_memorized( 'action' ) );
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':
		$AdminUI->disp_view( 'regional/views/_region.form.php' );
		break;

	default:
		// No specific request, list all regions:
		// Cleanup context:
		forget_param( 'rgn_ID' );
		// Display regions list:
		$AdminUI->disp_view( 'regional/views/_region_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>