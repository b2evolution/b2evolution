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

// Load Currency class (PHP4):
load_class( 'regional/model/_currency.class.php', 'Currency' );

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
$AdminUI->set_path( 'options', 'regional', 'currencies' );

// Get action parameter from request:
param_action();

if( param( 'curr_ID', 'integer', '', true) )
{// Load currency from cache:
	$CurrencyCache = & get_CurrencyCache();
	if( ($edited_Currency = & $CurrencyCache->get_by_ID( $curr_ID, false )) === false )
	{	unset( $edited_Currency );
		forget_param( 'curr_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Currency') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'disable_currency':
	case 'enable_currency':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'currency' );

		// Disable a currency only if it is enabled, and user has edit access.
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure the currency information was loaded. If not, just exit with error.
		if( empty($edited_Currency) )
		{
			$Messages->add( sprintf( 'The currency with ID %d could not be instantiated.', $curr_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_currency' )
		{	// Disable this currency by setting flag to false.
			$edited_Currency->set( 'enabled', 0 );
			$Messages->add( sprintf( T_('Disabled currency (%s, #%d).'), $edited_Currency->name, $edited_Currency->ID ), 'success' );
		}
		elseif ( $action == 'enable_currency' )
		{	// Enable currency by setting flag to true.
			$edited_Currency->set( 'enabled', 1 );
			$Messages->add( sprintf( T_('Enabled currency (%s, #%d).'), $edited_Currency->name, $edited_Currency->ID ), 'success' );
		}

		// Update db with new flag value.
		$edited_Currency->dbupdate();

		param( 'results_curr_page', 'integer', '', true );
		param( 'results_curr_order', 'string', '', true );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url ( '', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( ! isset($edited_Currency) )
		{	// We don't have a model to use, start with blank object:
			$edited_Currency = new Currency();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_Currency = clone $edited_Currency;
			$edited_Currency->ID = 0;
		}
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an curr_ID:
		param( 'curr_ID', 'integer', true );
 		break;

	case 'create': // Record new currency
	case 'create_new': // Record currency and create new
	case 'create_copy': // Record currency and create similar
		// Insert new currency:
		$edited_Currency = new Currency();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'currency' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Load data from request
		if( $edited_Currency->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$edited_Currency->dbinsert();
			$Messages->add( T_('New currency created.'), 'success' );

			// What next?
			switch( $action )
			{
				case 'create_copy':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=currencies&action=new&curr_ID='.$edited_Currency->ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create_new':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=currencies&action=new', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=currencies', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
			}
		}
		break;

	case 'update':
		// Edit currency form:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'currency' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an curr_ID:
		param( 'curr_ID', 'integer', true );

		// load data from request
		if( $edited_Currency->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$edited_Currency->dbupdate();
			$Messages->add( T_('Currency updated.'), 'success' );

			// If no error, Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=currencies', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'delete':
		// Delete currency:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'currency' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an curr_ID:
		param( 'curr_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Currency &laquo;%s&raquo; deleted.'), $edited_Currency->dget('name') );
			$edited_Currency->dbdelete();
			unset( $edited_Currency );
			forget_param( 'curr_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=currencies', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Currency->check_delete( sprintf( T_('Cannot delete currency &laquo;%s&raquo;'), $edited_Currency->dget('name') ), array(), true ) )
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
$AdminUI->breadcrumbpath_add( T_('Currencies'), $admin_url.'?ctrl=currencies' );

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
		$AdminUI->set_page_manual_link( 'currencies-editing' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'currencies-list' );
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
		$edited_Currency->confirm_delete(
				sprintf( T_('Delete currency &laquo;%s&raquo;?'), $edited_Currency->dget('name') ),
				'currency', $action, get_memorized( 'action' ) );
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':
		$AdminUI->disp_view( 'regional/views/_currency.form.php' );
		break;

	default:
		// No specific request, list all currencies:
		// Cleanup context:
		forget_param( 'curr_ID' );
		// Display currency list:
		$AdminUI->disp_view( 'regional/views/_currency_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>