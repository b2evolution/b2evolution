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

// Load Country class (PHP4):
load_class( 'regional/model/_country.class.php', 'Country' );

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
$AdminUI->set_path( 'options', 'regional', 'countries' );

// Get action parameter from request:
param_action();

// Set redirect_to value when it is required
if( in_array( $action, array( 'enable_country', 'disable_country', 'enable_country_pref', 'disable_country_pref' ) ) )
{
	// Get page and order params before creating redirect url
	param( 'results_ctry_page', 'integer', '', true );
	param( 'results_ctry_order', 'string', '', true );
	// Get redirect ctrl param, and set redirect_to
	if( param( 'redirect_ctrl', 'string', 'countries' ) == 'countries' )
	{
		$redirect_to = regenerate_url( '', '', '', '&' );
	}
	else
	{
		$redirect_to = regenerate_url( 'ctrl', 'ctrl=antispam&tab3=countries', '', '&' );
	}
}

if( param( 'ctry_ID', 'integer', '', true) )
{// Load country from cache:
	$CountryCache = & get_CountryCache();
	if( ($edited_Country = & $CountryCache->get_by_ID( $ctry_ID, false )) === false )
	{	unset( $edited_Country );
		forget_param( 'ctry_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Country') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'disable_country':
	case 'enable_country':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'country' );

		// Disable a country only if it is enabled, and user has edit access.
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure the country information was loaded. If not, just exit with error.
		if( empty($edited_Country) )
		{
			$Messages->add( sprintf( 'The country with ID %d could not be instantiated.', $ctry_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_country' )
		{	// Disable this country by setting flag to false.
			$edited_Country->set( 'enabled', 0 );
			$Messages->add( sprintf( T_('Disabled country (%s, #%d).'), $edited_Country->name, $edited_Country->ID ), 'success' );
		}
		elseif ( $action == 'enable_country' )
		{	// Enable country by setting flag to true.
			$edited_Country->set( 'enabled', 1 );
			$Messages->add( sprintf( T_('Enabled country (%s, #%d).'), $edited_Country->name, $edited_Country->ID ), 'success' );
		}

		// Update db with new flag value.
		$edited_Country->dbupdate();

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $redirect_to, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'enable_country_pref':
	case 'disable_country_pref':

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'country' );

		// Disable a country only if it is enabled, and user has edit access.
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure the country information was loaded. If not, just exit with error.
		if( empty($edited_Country) )
		{
			$Messages->add( sprintf( 'The country with ID %d could not be instantiated.', $ctry_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_country_pref' )
		{	// Disable this country by setting flag to false.
			$edited_Country->set( 'preferred', 0 );
			$Messages->add( sprintf( T_('Removed from preferred countries (%s, #%d).'), $edited_Country->name, $edited_Country->ID ), 'success' );
		}
		elseif ( $action == 'enable_country_pref' )
		{	// Enable country by setting flag to true.
			$edited_Country->set( 'preferred', 1 );
			$Messages->add( sprintf( T_('Added to preferred countries (%s, #%d).'), $edited_Country->name, $edited_Country->ID ), 'success' );

			if( ! $edited_Country->get( 'status' ) )
			{ // If the country status is empty ( which means 'unknown' ) and the coutnry was marked as prefrerred, than the status must be changed to 'trusted'
				$edited_Country->set( 'status', 'trusted' );
			}
		}

		// Update db with new flag value.
		$edited_Country->dbupdate();

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $redirect_to, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( ! isset($edited_Country) )
		{	// We don't have a model to use, start with blank object:
			$edited_Country = new Country();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_Country = duplicate( $edited_Country ); // PHP4/5 abstraction
			$edited_Country->ID = 0;
		}
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ctry_ID:
		param( 'ctry_ID', 'integer', true );
 		break;

	case 'create': // Record new country
	case 'create_new': // Record country and create new
	case 'create_copy': // Record country and create similar
		// Insert new country:
		$edited_Country = new Country();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'country' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Load data from request
		if( $edited_Country->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$DB->begin();
			$q = $edited_Country->dbexists();
			if($q)
			{	// We have a duplicate entry:

				param_error( 'ctry_code',
					sprintf( T_('This country already exists. Do you want to <a %s>edit the existing country</a>?'),
						'href="?ctrl=countries&amp;action=edit&amp;ctry_ID='.$q.'"' ) );
			}
			else
			{
				$edited_Country->dbinsert();
				$Messages->add( T_('New country created.'), 'success' );
			}
			$DB->commit();

			if( empty($q) )
			{	// What next?

				switch( $action )
				{
					case 'create_copy':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=countries&action=new&ctry_ID='.$edited_Country->ID, 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
					case 'create_new':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=countries&action=new', 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
					case 'create':
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( '?ctrl=countries', 303 ); // Will EXIT
						// We have EXITed already at this point!!
						break;
				}
			}
		}
		break;

	case 'update':
		// Edit country form:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'country' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ctry_ID:
		param( 'ctry_ID', 'integer', true );

		// load data from request
		if( $edited_Country->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$DB->begin();
			$q = $edited_Country->dbexists();
			if($q)
			{ 	// We have a duplicate entry:
				param_error( 'ctry_code',
					sprintf( T_('This country already exists. Do you want to <a %s>edit the existing country</a>?'),
						'href="?ctrl=countries&amp;action=edit&amp;ctry_ID='.$q.'"' ) );
			}
			else
			{
				$edited_Country->dbupdate();
				$Messages->add( T_('Country updated.'), 'success' );
			}
			$DB->commit();

			if( empty($q) )
			{	// If no error, Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=countries', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}
		break;

	case 'delete':
		// Delete country:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'country' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ctry_ID:
		param( 'ctry_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Country &laquo;%s&raquo; deleted.'), $edited_Country->dget('name') );
			$edited_Country->dbdelete();
			unset( $edited_Country );
			forget_param( 'ctry_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=countries', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Country->check_delete( sprintf( T_('Cannot delete country &laquo;%s&raquo;'), $edited_Country->dget('name') ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

}

if( empty( $action ) )
{ // JS to edit status of countries from list view
	require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
}

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('Regional'), $admin_url.'?ctrl=locales' );
$AdminUI->breadcrumbpath_add( T_('Countries'), $admin_url.'?ctrl=countries' );


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
		$edited_Country->confirm_delete(
				sprintf( T_('Delete country &laquo;%s&raquo;?'), $edited_Country->dget('name') ),
				'country', $action, get_memorized( 'action' ) );
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':
		$AdminUI->disp_view( 'regional/views/_country.form.php' );
		break;

	default:
		// No specific request, list all countries:
		// Cleanup context:
		forget_param( 'ctry_ID' );
		// Display country list:
		$AdminUI->disp_view( 'regional/views/_country_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>