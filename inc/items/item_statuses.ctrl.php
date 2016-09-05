<?php
/**
 * This file implements the controller for item statuses management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load ItemStatus class:
load_class( 'items/model/_itemstatus.class.php', 'ItemStatus' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

// We should activate toolbar menu items for this controller
$activate_collection_toolbar = true;

$tab = param( 'tab', 'string', 'settings', true );

$tab3 = param( 'tab3', 'string', 'statuses', true );

$AdminUI->set_path( 'collections', 'settings', 'statuses' );

// Get action parameter from request:
param_action();

if( param( 'pst_ID', 'integer', '', true ) )
{	// Load ItemStatus from cache::
	$ItemStatusCache = & get_ItemStatusCache();
	if( ( $edited_ItemStatus = & $ItemStatusCache->get_by_ID( $pst_ID, false ) ) === false )
	{	// We could not find the post status to edit:
		unset( $edited_ItemStatus );
		forget_param( 'pst_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Post status') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{

	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( ! isset( $edited_ItemStatus ) )
		{	// We don't have a model to use, start with blank object:
			$edited_ItemStatus = new ItemStatus();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_ItemStatus = clone $edited_ItemStatus;
			// Reset ID of new post status:
			$edited_ItemStatus->ID = 0;
		}
		break;

	case 'edit':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an pst_ID:
		param( 'pst_ID', 'integer', true );
		break;

	case 'create': // Record new ItemStatus
	case 'create_new': // Record ItemStatus and create new
	case 'create_copy': // Record ItemStatus and create similar
		// Insert new post status:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemstatus' );

		$edited_ItemStatus = new ItemStatus();

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// load data from request
		if( $edited_ItemStatus->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$edited_ItemStatus->dbinsert();

			// Update allowed item types
			$edited_ItemStatus->update_item_types_from_Request();

			$Messages->add( T_('New Post Status has been created.'), 'success' );

			switch( $action )
			{
				case 'create_copy':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $admin_url.'?ctrl=itemstatuses&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'&action=new&pst_ID='.$edited_ItemStatus->ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create_new':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $admin_url.'?ctrl=itemstatuses&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3.'&action=new', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $admin_url.'?ctrl=itemstatuses&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3, 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
			}
		}
		break;

	case 'update':
		// Edit post status:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemstatus' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an pst_ID:
		param( 'pst_ID', 'integer', true );

		// load data from request
		if( $edited_ItemStatus->load_from_Request() )
		{	// We could load data from form without errors:
			$edited_ItemStatus->update_item_types_from_Request();
			$edited_ItemStatus->dbupdate();
			$Messages->add( T_('Post Status has been updated.'), 'success' );

			header_redirect( $admin_url.'?ctrl=itemstatuses&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'delete':
		// Delete post status:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemstatus' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an pst_ID:
		param( 'pst_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Post Status &laquo;%s&raquo; has been deleted.'), $edited_ItemStatus->dget( 'name' ) );
			$edited_ItemStatus->dbdelete();
			unset( $edited_ItemStatus );
			forget_param( 'pst_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=itemstatuses&blog='.$blog.'&tab='.$tab.'&tab3='.$tab3, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_ItemStatus->check_delete( sprintf( T_('Cannot delete Post Status &laquo;%s&raquo;'), $edited_ItemStatus->dget( 'name' ) ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;
}

// Generate available blogs list:
$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'itemstatuses', 'tab' => $tab, 'tab3' => 'statuses' ) );

$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog=$blog$' ) );
$AdminUI->breadcrumbpath_add( T_('Settings'), $admin_url.'?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
$AdminUI->breadcrumbpath_add( T_('Post Statuses'), $admin_url.'?ctrl=itemstatuses&amp;blog=$blog$&amp;tab=settings&amp;tab3=statuses' );

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
		$AdminUI->set_page_manual_link( 'managing-item-statuses-form' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'managing-item-statuses' );
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
		$edited_ItemStatus->confirm_delete(
				sprintf( T_('Delete Post Status &laquo;%s&raquo;?'),  $edited_ItemStatus->dget( 'name' ) ),
				'itemstatus', $action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':	// we return in this state after a validation error
		$AdminUI->disp_view( 'items/views/_itemstatus.form.php' );
		break;


	default:
		// No specific request, list all post statuses:
		// Cleanup context:
		forget_param( 'pst_ID' );
		// Display post statuses list:
		$AdminUI->disp_view( 'items/views/_itemstatuses.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>