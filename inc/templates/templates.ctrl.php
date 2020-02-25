<?php
/**
 * This file implements the templates control.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'templates/model/_template.class.php', 'Template' );
load_class( 'template/model/_templatecache.class.php', 'TemplateCache' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

param_action( 'list' );

if( param( 'tpl_ID', 'integer', '', true ) )
{	// Load menu:
	$TemplateCache = & get_TemplateCache();
	if( ( $edited_Template = & $TemplateCache->get_by_ID( $tpl_ID, false ) ) === false )
	{	// We could not find the goal to edit:
		unset( $edited_Template );
		forget_param( 'tpl_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Template') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'list':
		break;

	case 'new':
		$edited_Template = new Template();
		break;

	case 'copy':
		// Set parent menu:
		if( isset( $tpl_ID ) && empty( $edited_Template->parent_tpl_ID ) )
		{
			$edited_Template->set( 'parent_tpl_ID', $tpl_ID );
		}
	case 'edit':
		// Menu edit form:
		// Make sure we got a menu_ID:
		param( 'tpl_ID', 'integer', true );
		break;

	case 'duplicate':
		// Duplicate menu
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'template' );

		// Check that current user has permission to create menus:
		$current_User->check_perm( 'options', 'edit', true );

		if( $edited_Template && $edited_Template->duplicate() )
		{
			$Messages->add( sprintf( TB_('The %s has been duplicated.'), T_('Template') ), 'success' );
			header_redirect( $admin_url.'?ctrl=templates&action=edit&tpl_ID='.$edited_Template->ID ); // will save $Messages into Session
			// We have EXITed already at this point!!
		}
		break;

	case 'create':
	case 'create_edit':
		// Create new Menu:
		$edited_Template = new Template();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'template' );

		// Check that current user has permission to create menus:
		$current_User->check_perm( 'options', 'edit', true );

		// Load data from request:
		if( $edited_Template->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_Template->dbinsert();
			$Messages->add( sprintf( TB_('New %s created.'), T_('Template') ), 'success' );

			if( $action == 'create_edit' )
			{	// Redirect back to edit form:
				$redirect_to = $admin_url.'?ctrl=templates&action=edit&tpl_ID='.$edited_Template->ID;
			}
			else
			{
				$redirect_to = $admin_url.'?ctrl=templates';
			}

			header_redirect( $redirect_to, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'new';
		break;

	case 'update':
	case 'update_edit':
		// Update menu:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'template' );

		// Check that current user has permission to edit menus:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an menu_ID:
		param( 'tpl_ID', 'integer', true );

		// load data from request
		if( $edited_Template->load_from_Request() )
		{	// We could load data from form without errors:
			// Update Menu in DB:
			$edited_Template->dbupdate();
			$Messages->add( sprintf( TB_('%s updated.'), T_('Template')  ), 'success' );

			if( $action == 'update_edit' )
			{	// Redirect back to edit form:
				$redirect_to = $admin_url.'?ctrl=templates&action=edit&tpl_ID='.$edited_Template->ID;
			}
			else
			{	// Redirect to template list:
				$redirect_to = $admin_url.'?ctrl=templates';
			}

			header_redirect( $redirect_to, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'edit';
		break;

	case 'delete':
		// Delete menu:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'template' );

		// Check that current user has permission to delete menus:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an menu_ID:
		param( 'tpl_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( TB_('Template &laquo;%s&raquo; deleted.'), $edited_Template->dget( 'name' ) );
			$edited_Template->dbdelete();
			unset( $edited_Template );
			forget_param( 'tpl_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( regenerate_url( 'action', '', '', '&' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Template->check_delete( sprintf( TB_('Cannot delete %s &laquo;%s&raquo;'), T_('Template'), $edited_Template->dget( 'name' ) ), array(), true ) )
			{	// There are restrictions:
				$action = 'list';
			}
		}
		break;
}

// We should activate toolbar menu items for this controller
$activate_collection_toolbar = true;

$AdminUI->set_path( 'site', 'templates' );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Site'), $admin_url.'?ctrl=dashboard' );
$AdminUI->breadcrumbpath_add( T_('Templates'), $admin_url.'?ctrl=templates' );

// Set an url for manual page:
if( $action == 'new' || $action == 'edit' )
{
	$AdminUI->set_page_manual_link( 'template-form' );
}
else
{
	$AdminUI->set_page_manual_link( 'templates-list' );
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
		$edited_Template->confirm_delete(
				sprintf( TB_('Delete %s &laquo;%s&raquo;?'), T_('Template'), $edited_Template->dget( 'name' ) ),
				'template', $action, get_memorized( 'action' ) );
		// NO BREAK
	case 'new':
	case 'edit':
	case 'copy':
		// Display menu form:
		$AdminUI->disp_view( 'templates/views/_template.form.php' );
		break;

	case 'list':
		// list templates:
		$AdminUI->disp_view( 'templates/views/_templates.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>
