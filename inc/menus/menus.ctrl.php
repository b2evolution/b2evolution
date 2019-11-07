<?php
/**
 * This file implements the menus control.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'menus/model/_sitemenu.class.php', 'SiteMenu' );
load_class( 'menus/model/_sitemenuentry.class.php', 'SiteMenuEntry' );
load_class( 'menus/model/_sitemenuentrycache.class.php', 'SiteMenuEntryCache' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'site', 'menus' );

param_action( 'list' );

if( param( 'menu_ID', 'integer', '', true ) )
{	// Load menu:
	$SiteMenuCache = & get_SiteMenuCache();
	if( ( $edited_SiteMenu = & $SiteMenuCache->get_by_ID( $menu_ID, false ) ) === false )
	{	// We could not find the goal to edit:
		unset( $edited_SiteMenu );
		forget_param( 'menu_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Menu') ), 'error' );
		$action = 'nil';
	}
}

if( param( 'ment_ID', 'integer', '', true ) )
{	// Load menu:
	$SiteMenuEntryCache = & get_SiteMenuEntryCache();
	if( ( $edited_SiteMenuEntry = & $SiteMenuEntryCache->get_by_ID( $ment_ID, false ) ) === false )
	{	// We could not find the goal to edit:
		unset( $edited_SiteMenuEntry );
		forget_param( 'menu_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Menu Entry') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'list':
		break;

	case 'new':
		$edited_SiteMenu = new SiteMenu();
		break;

	case 'edit':
		// Menu edit form:
		// Make sure we got a menu_ID:
		param( 'menu_ID', 'integer', true );
		break;

	case 'create':
		// Create new Menu:
		$edited_SiteMenu = new SiteMenu();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'menu' );

		// Check that current user has permission to create menus:
		$current_User->check_perm( 'options', 'edit', true );

		// Load data from request:
		if( $edited_SiteMenu->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_SiteMenu->dbinsert();
			$Messages->add( T_('New menu created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=menus&action=edit&menu_ID='.$edited_SiteMenu->ID ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'new';
		break;

	case 'update':
		// Update menu:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'menu' );

		// Check that current user has permission to edit menus:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an menu_ID:
		param( 'menu_ID', 'integer', true );

		// load data from request
		if( $edited_SiteMenu->load_from_Request() )
		{	// We could load data from form without errors:
			// Update Menu in DB:
			$edited_SiteMenu->dbupdate();
			$Messages->add( T_('Menu updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=menus' ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'edit';
		break;

	case 'delete':
		// Delete menu:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'menu' );

		// Check that current user has permission to delete menus:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an menu_ID:
		param( 'menu_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Menu &laquo;%s&raquo; deleted.'), $edited_SiteMenu->dget( 'name' ) );
			$edited_SiteMenu->dbdelete();
			unset( $edited_SiteMenu );
			forget_param( 'menu_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( regenerate_url( 'action', '', '', '&' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_SiteMenu->check_delete( sprintf( T_('Cannot delete menu &laquo;%s&raquo;'), $edited_SiteMenu->dget( 'name' ) ), array(), true ) )
			{	// There are restrictions:
				$action = 'list';
			}
		}
		break;

	case 'new_entry':
		$ment_parent_ID = param( 'ment_parent_ID', 'integer', 0 );
		$edited_SiteMenuEntry = new SiteMenuEntry();
		$edited_SiteMenuEntry->set( 'menu_ID', $edited_SiteMenu->ID );
		$edited_SiteMenuEntry->set( 'parent_ID', $ment_parent_ID );
		$edited_SiteMenuEntry->set( 'order', $edited_SiteMenu->get_max_order( $ment_parent_ID ) + 10 );
		$edited_SiteMenuEntry->set( 'type', 'item' );
		break;

	case 'edit_entry':
		// Menu Entry edit form:
		// Make sure we got a ment_ID:
		param( 'ment_ID', 'integer', true );
		break;

	case 'create_entry':
		// Create new Menu Entry:
		$edited_SiteMenuEntry = new SiteMenuEntry();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'menuentry' );

		// Check that current user has permission to create menus:
		$current_User->check_perm( 'options', 'edit', true );

		// Load data from request:
		if( $edited_SiteMenuEntry->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_SiteMenuEntry->dbinsert();
			$Messages->add( T_('New menu entry created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=menus&action=edit&menu_ID='.$edited_SiteMenuEntry->get( 'menu_ID' ) ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'new_entry';
		break;

	case 'update_entry':
		// Update menu entry:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'menuentry' );

		// Check that current user has permission to edit menus:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an menu_ID:
		param( 'menu_ID', 'integer', true );

		// load data from request
		if( $edited_SiteMenuEntry->load_from_Request() )
		{	// We could load data from form without errors:
			// Update Menu in DB:
			$edited_SiteMenuEntry->dbupdate();
			$Messages->add( T_('Menu entry updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=menus&action=edit&menu_ID='.$edited_SiteMenuEntry->get( 'menu_ID' ) ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'edit';
		break;

	case 'delete_entry':
		// Delete menu entry:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'menuentry' );

		// Check that current user has permission to delete menus:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an menu_ID:
		param( 'menu_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Menu entry &laquo;%s&raquo; deleted.'), $edited_SiteMenuEntry->dget( 'text' ) );
			$edited_SiteMenuEntry->dbdelete();
			unset( $edited_SiteMenuEntry );
			forget_param( 'ment_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( regenerate_url( 'action,blog', 'action=edit', '', '&' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_SiteMenuEntry->check_delete( sprintf( T_('Cannot delete menu &laquo;%s&raquo;'), $edited_SiteMenuEntry->dget( 'text' ) ), array(), true ) )
			{	// There are restrictions:
				$action = 'list';
			}
		}
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Site'), $admin_url.'?ctrl=dashboard' );
$AdminUI->breadcrumbpath_add( T_('Menus'), $admin_url.'?ctrl=menus' );

// Set an url for manual page:
if( $action == 'new' || $action == 'edit' )
{
	$AdminUI->set_page_manual_link( 'menu-form' );
}
else
{
	$AdminUI->set_page_manual_link( 'menus-list' );
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
		$edited_SiteMenu->confirm_delete(
				sprintf( T_('Delete menu &laquo;%s&raquo;?'), $edited_SiteMenu->dget( 'name' ) ),
				'menu', $action, get_memorized( 'action' ) );
		// NO BREAK
	case 'new':
	case 'edit':
		// Display menu form:
		$AdminUI->disp_view( 'menus/views/_menu.form.php' );
		break;

	case 'delete_entry':
		// We need to ask for confirmation:
		$edited_SiteMenuEntry->confirm_delete(
				sprintf( T_('Delete menu entry &laquo;%s&raquo;?'), $edited_SiteMenuEntry->dget( 'text' ) ),
				 'menuentry', $action, array_merge( get_memorized( 'action,locale,blog,mode' ), array( 'action' => 'edit' ) ) );
		// NO BREAK
	case 'new_entry':
	case 'edit_entry':
		// Display menu entry form:
		$AdminUI->disp_view( 'menus/views/_menu_entry.form.php' );
		break;

	case 'list':
		// list menus:
		$AdminUI->disp_view( 'menus/views/_menus.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>