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

// Load City class (PHP4):
load_class( 'regional/model/_city.class.php', 'City' );
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
$AdminUI->set_path( 'options', 'regional', 'cities' );

// Get action parameter from request:
param_action();

if( param( 'city_ID', 'integer', '', true) )
{// Load city from cache:
	$CityCache = & get_CityCache();
	if( ($edited_City = & $CityCache->get_by_ID( $city_ID, false )) === false )
	{	unset( $edited_City );
		forget_param( 'city_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('City') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'disable_city':
	case 'enable_city':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'city' );

		// Disable a city only if it is enabled, and user has edit access.
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure the city information was loaded. If not, just exit with error.
		if( empty($edited_City) )
		{
			$Messages->add( sprintf( 'The city with ID %d could not be instantiated.', $city_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_city' )
		{	// Disable this city by setting flag to false.
			$edited_City->set( 'enabled', 0 );
			$Messages->add( sprintf( T_('Disabled city (%s, #%d).'), $edited_City->name, $edited_City->ID ), 'success' );
		}
		elseif ( $action == 'enable_city' )
		{	// Enable city by setting flag to true.
			$edited_City->set( 'enabled', 1 );
			$Messages->add( sprintf( T_('Enabled city (%s, #%d).'), $edited_City->name, $edited_City->ID ), 'success' );
		}

		// Update db with new flag value.
		$edited_City->dbupdate();

		param( 'results_city_page', 'integer', '', true );
		param( 'results_city_order', 'string', '', true );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'enable_city_pref':
	case 'disable_city_pref':

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'city' );

		// Disable a city only if it is enabled, and user has edit access.
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure the city information was loaded. If not, just exit with error.
		if( empty($edited_City) )
		{
			$Messages->add( sprintf( 'The city with ID %d could not be instantiated.', $city_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_city_pref' )
		{	// Disable this city by setting flag to false.
			$edited_City->set( 'preferred', 0 );
			$Messages->add( sprintf( T_('Removed from preferred cities (%s, #%d).'), $edited_City->name, $edited_City->ID ), 'success' );
		}
		elseif ( $action == 'enable_city_pref' )
		{	// Enable city by setting flag to true.
			$edited_City->set( 'preferred', 1 );
			$Messages->add( sprintf( T_('Added to preferred cities (%s, #%d).'), $edited_City->name, $edited_City->ID ), 'success' );
		}

		// Update db with new flag value.
		$edited_City->dbupdate();

		param( 'results_city_page', 'integer', '', true );
		param( 'results_city_order', 'string', '', true );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( ! isset($edited_City) )
		{	// We don't have a model to use, start with blank object:
			$edited_City = new City();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_City = duplicate( $edited_City ); // PHP4/5 abstraction
			$edited_City->ID = 0;
		}
		break;

	case 'csv':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an city_ID:
		param( 'city_ID', 'integer', true );
		break;

	case 'create': // Record new city
	case 'create_new': // Record city and create new
	case 'create_copy': // Record city and create similar
		// Insert new city:
		$edited_City = new City();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'city' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Load data from request
		if( $edited_City->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$DB->begin();
			$edited_City->dbinsert();
			$Messages->add( T_('New city created.'), 'success' );
			$DB->commit();

			if( empty($q) )
			{	// What next?

				switch( $action )
				{
					case 'create_copy':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=cities&action=new&city_ID='.$edited_City->ID, 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
					case 'create_new':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=cities&action=new', 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
					case 'create':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=cities', 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
				}
			}
		}
		break;

	case 'update':
		// Edit city form:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'city' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an city_ID:
		param( 'city_ID', 'integer', true );

		// load data from request
		if( $edited_City->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$DB->begin();
			$edited_City->dbupdate();
			$Messages->add( T_('City updated.'), 'success' );
			$DB->commit();

			if( empty($q) )
			{	// If no error, Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=cities', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}
		break;

	case 'delete':
		// Delete city:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'city' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an city_ID:
		param( 'city_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('City &laquo;%s&raquo; deleted.'), $edited_City->dget('name') );
			$edited_City->dbdelete();
			unset( $edited_City );
			forget_param( 'city_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=cities', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_City->check_delete( sprintf( T_('Cannot delete city &laquo;%s&raquo;'), $edited_City->dget('name') ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

	case 'import': // Import new cities
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'city' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Country Id
		param( 'ctry_ID', 'integer', true );
		param_check_number( 'ctry_ID', T_('Please select a country'), true );

		// CSV File
		$csv = $_FILES['csv'];
		if( $csv['size'] == 0 )
		{	// File is empty
			$Messages->add( T_('Please select a CSV file to import.'), 'error' );
		}
		else if( !preg_match( '/\.csv$/i', $csv['name'] ) )
		{	// Extension is incorrect
			$Messages->add( sprintf( T_('&laquo;%s&raquo; has an unrecognized extension.'), $csv['name'] ), 'error' );
		}

		if( param_errors_detected() )
		{	// Some errors are exist, Stop the importing
			$action = 'csv';
			break;
		}

		// Import a new cities from CSV file
		$count_cities = import_cities( $ctry_ID, $csv['tmp_name'] );

		load_class( 'regional/model/_country.class.php', 'Country' );
		$CountryCache = & get_CountryCache();
		$Country = $CountryCache->get_by_ID( $ctry_ID );

		$Messages->add( sprintf( T_('%s cities added and %s cities updated for country %s.'), $count_cities['inserted'], $count_cities['updated'], $Country->get_name() ), 'success' );
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=cities', 303 ); // Will EXIT
		break;

}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('Regional'), $admin_url.'?ctrl=locales' );
$AdminUI->breadcrumbpath_add( T_('Cities'), $admin_url.'?ctrl=cities' );


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
		$edited_City->confirm_delete(
				sprintf( T_('Delete city &laquo;%s&raquo;?'), $edited_City->dget('name') ),
				'city', $action, get_memorized( 'action' ) );
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':
		$AdminUI->disp_view( 'regional/views/_city.form.php' );
		break;

	case 'csv':
		$AdminUI->disp_view( 'regional/views/_city_import.form.php' );
		break;

	default:
		// No specific request, list all cities:
		// Cleanup context:
		forget_param( 'city_ID' );
		// Display cities list:
		$AdminUI->disp_view( 'regional/views/_city_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>