<?php
/**
 * This file implements the slugs control.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author evfy-asimo: Attila Simo.
 *
 * @version $Id: slugs.ctrl.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'slugs/model/_slug.class.php', 'Slug' );

/**
 * @var User
 */
global $current_User;

// Check minimum permission:
$current_User->check_perm( 'slugs', 'view', true );

$AdminUI->set_path( 'options', 'slugs' );

param_action( 'list' );

param( 'slug_filter', 'string', '', true );
param( 'slug_item_ID', 'string', '', true );
// other slug object type IDs come here

if( param( 'slug_ID', 'integer', '', true) )
{// Load file type:
	$SlugCache = & get_SlugCache();
	if( ($edited_Slug = & $SlugCache->get_by_ID( $slug_ID, false )) === false )
	{	// We could not find the goal to edit:
		unset( $edited_Slug );
		forget_param( 'slug_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Slug') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'list':
		break;

	case 'new':
		$edited_Slug = new Slug();
		break;

	case 'edit':
		// Slug edit form...:
		// Make sure we got a slug_ID:
		param( 'slug_ID', 'string', true );
 		break;
 
	case 'create':
		// Create new slug...
		$edited_Slug = new Slug();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'slug' );

		// Check that current user has permission to create slugs:
		$current_User->check_perm( 'slugs', 'edit', true );

		// load data from request
		if( $edited_Slug->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_Slug->dbinsert();
			$Messages->add( T_('New slug created.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=slugs', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'new';
		break;

	case 'update':
		// Update slug...
		
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'slug' );

		// Check that current user has permission to edit slugs:
		$current_User->check_perm( 'slugs', 'edit', true );

		// Make sure we got an slug_ID:
		param( 'slug_ID', 'integer', true );

		// load data from request
		if( $edited_Slug->load_from_Request() )
		{	// We could load data from form without errors:
			// Update slug in DB:
			$edited_Slug->dbupdate();
			$Messages->add( T_('Slug updated.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=slugs', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		$action = 'edit';
		break;

	case 'delete':
		// Delete slug:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'slug' );

		// Check that current user has permission to edit slugs:
		$current_User->check_perm( 'slugs', 'edit', true );

		// Make sure we got an slug_ID:
		param( 'slug_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Slug &laquo;%s&raquo; deleted.'), $edited_Slug->dget('title') );
			$edited_Slug->dbdelete( true );
			unset( $edited_Slug );
			forget_param( 'slug_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( regenerate_url( 'action', '', '', '&' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Slug->check_delete( sprintf( T_('Cannot delete slug &laquo;%s&raquo;'), $edited_Slug->dget('title') ), array(), true ) )
			{	// There are restrictions:
				$action = 'list';
			}
		}
		break;
}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Slugs'), '?ctrl=slugs' );

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

	case 'new':
	case 'edit':
		// Display slug form
		$AdminUI->disp_view( 'slugs/views/_slug.form.php' );
		break;

	case 'delete':
		// We need to ask for confirmation:
		$edited_Slug->confirm_delete(
				sprintf( T_('Delete slug &laquo;%s&raquo;?'), $edited_Slug->dget('title') ),
				'slug', $action, get_memorized( 'action' ) );
		// NO BREAK
	case 'list':
		// list slugs:
		$AdminUI->disp_view( 'slugs/views/_slug_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>