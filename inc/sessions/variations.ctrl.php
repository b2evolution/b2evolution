<?php
/**
 * This file implements the A/B Variation Tests.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'sessions/model/_variation_test.class.php', 'VariationTest' );

/**
 * @var User
 */
global $current_User;

$blog = 0;

// Do we have permission to view all stats (aggregated stats) ?
$current_User->check_perm( 'stats', 'view', true );

$AdminUI->set_path( 'stats', 'goals', 'variations' );

if( isset( $collections_Module ) )
{ // Display list of blogs:
	$AdminUI->set_coll_list_params( 'stats', 'view', array( 'ctrl' => 'stats', 'tab' => 'summary', 'tab3' => 'global' ), T_('All'),
					$admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog=0' );
}

param_action();

if( param( 'vtst_ID', 'integer', '', true) )
{ // Load variation test:
	$VariationTestCache = & get_VariationTestCache();
	if( ( $edited_VariationTest = & $VariationTestCache->get_by_ID( $vtst_ID, false ) ) === false )
	{ // We could not find the variation test to edit:
		unset( $edited_VariationTest );
		forget_param( 'vtst_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Variaion Test') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{

	case 'new':
	case 'copy':
		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		if( ! isset($edited_VariationTest) )
		{ // We don't have a model to use, start with blank object:
			$edited_VariationTest = new VariationTest();
		}
		else
		{ // Duplicate object in order no to mess with the cache:
			$edited_VariationTest = duplicate( $edited_VariationTest ); // PHP4/5 abstraction
			$edited_VariationTest->ID = 0;
		}
		break;

	case 'edit':
		// Edit variation test form...:

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'vtst_ID', 'integer', true );
		break;

	case 'create': // Record new variation test
	case 'create_new': // Record variation test and create new
	case 'create_copy': // Record variation test and create similar
		// Insert new variation test...:
		$edited_VariationTest = new VariationTest();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'variationtest' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// load data from request
		if( $edited_VariationTest->load_from_Request() )
		{ // We could load data from form without errors:

			// Insert in DB:
			$edited_VariationTest->dbinsert();
			$Messages->add( T_('New variation test created.'), 'success' );

			switch( $action )
			{
				case 'create_copy':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $admin_url.'?ctrl=variations&action=new&vtst_ID='.$edited_VariationTest->ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create_new':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $admin_url.'?ctrl=variations&action=new', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $admin_url.'?ctrl=variations', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
			}
		}
		break;

	case 'update':
		// Edit variation test form...:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'variationtest' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'vtst_ID', 'integer', true );

		// load data from request
		if( $edited_VariationTest->load_from_Request() )
		{ // We could load data from form without errors:

			// Update in DB:
			$edited_VariationTest->dbupdate();
			$Messages->add( T_('Variation Test updated.'), 'success' );

			if( empty($q) )
			{
				$action = 'list';
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( $admin_url.'?ctrl=variations', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}


		break;

	case 'delete':
		// Delete variation test:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'variationtest' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'vtst_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Variation Test &laquo;%s&raquo; deleted.'), $edited_VariationTest->dget('name') );
			$edited_VariationTest->dbdelete( true );
			unset( $edited_VariationTest );
			forget_param( 'vtst_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=variations', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{ // not confirmed, Check for restrictions:
			if( ! $edited_VariationTest->check_delete( sprintf( T_('Cannot delete variation test &laquo;%s&raquo;'), $edited_VariationTest->dget('name') ) ) )
			{ // There are restrictions:
				$action = 'view';
			}
		}
		break;
}

$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Analytics'), $admin_url.'?ctrl=stats' );
$AdminUI->breadcrumbpath_add( T_('Goal tracking'), $admin_url.'?ctrl=goals' );
$AdminUI->breadcrumbpath_add( T_('A/B Variation Tests'), $admin_url.'?ctrl=variations' );

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
		$edited_VariationTest->confirm_delete(
				sprintf( T_('Delete variation test &laquo;%s&raquo;?'), $edited_VariationTest->dget('name') ),
				'variationtest', $action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'create':	// we return in this state after a validation error
	case 'create_new':	// we return in this state after a validation error
	case 'create_copy':	// we return in this state after a validation error
	case 'edit':
	case 'update':	// we return in this state after a validation error
		$AdminUI->disp_view( 'sessions/views/_variation.form.php' );
		break;

	default:
		// No specific request, list all file types:
		$AdminUI->disp_view( 'sessions/views/_variation.view.php' );
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>