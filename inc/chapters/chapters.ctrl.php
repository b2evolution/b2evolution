<?php
/**
 * This file implements ther UI controler for chapters management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// We should activate toolbar menu items for this controller
$activate_collection_toolbar = true;

if( valid_blog_requested() )
{
	$current_User->check_perm( 'blog_cats', 'edit', true, $blog );
	$edited_Blog = & $Blog;
}
else
{
	$action = 'nil';
}

$restrict_title = T_('Cannot delete category');	 //&laquo;%s&raquo;

// This must be initialized to false before checking the delete restrictions
$checked_delete = false;

load_class( 'chapters/model/_chaptercache.class.php', 'ChapterCache' );
$GenericCategoryCache = new ChapterCache();


// Restrict to chapters of the specific blog:
$subset_ID = $blog;

$list_view_path = 'chapters/views/_chapter_list.view.php';
$permission_to_edit = $current_User->check_perm( 'blog_cats', '', false, $blog );

// The form will be on its own page:
$form_below_list = false;
$edit_view_path = 'chapters/views/_chapter.form.php';


// ---- Below is a modified generic category list editor: -----


// fp> this is an example of where we could benefit from controler classes which could be derived
// fp> we basically need to add a "move" action.
/*
class Controler
{
	method get_params() // and init object
	method do_action()
	method display_payload()
}
the $AdminUI->foo() structural calls would move to the dispatcher.
*/
// fp> TODO: find 4 other cases before refactoring this way. (fp)

param( 'action', 'string', 'list' );

// Init fadeout result array:
$result_fadeout = array();

if( param( $GenericCategoryCache->dbIDname, 'integer', NULL, true, false, false ) )
{
	if( ($edited_GenericCategory = & $GenericCategoryCache->get_by_ID( ${$GenericCategoryCache->dbIDname}, false, true, $subset_ID )) === false )
	{	// We could not find the element to edit:
		unset( $edited_GenericCategory );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Category') ), 'error' );
		$action = 'nil';
	}
}

if( !is_null( param( $GenericCategoryCache->dbprefix.'parent_ID', 'integer', NULL ) ) )
{
	$edited_parent_GenericElement = & $GenericCategoryCache->get_by_ID( ${$GenericCategoryCache->dbprefix.'parent_ID'}, false, true, $subset_ID );
	if( $edited_parent_GenericElement === false )
	{ // Parent generic category doesn't exist any longer.
		unset( $GenericCategoryCache->dbIDname );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Category') ), 'error' );
		$action = 'nil';
	}
}

// Init fadeout result array of IDs:
$result_fadeout = array();

/**
 * Check locked elements
 */
if( !empty( $locked_IDs )
		&& in_array( $action, array( 'edit', 'update', 'delete' ) )
		&& in_array( $$GenericCategoryCache->dbIDname, $locked_IDs ) )
{
	$Messages->add( T_('This element is locked and cannot be edited!') );
	$action = 'list';
}

// Check that action request is not a CSRF hacked request and user has permission for the action
switch( $action )
{
	case 'create':
	case 'update':
	case 'delete':
	case 'make_default':
	case 'set_meta':
	case 'unset_meta':
	case 'lock':
	case 'unlock':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'element' );
		/* NO BREAK */
	case 'new':
	case 'move':
	case 'edit':
		if( ! $permission_to_edit )
		{
			debug_die( 'No permission to edit' );
		}
		break;
}


/**
 * Get url to redirect after chapter editing
 *
 * @param string Redirect Page: 'front', 'manual', 'list'
 * @param integer Parent ID
 * @param integer Chapter ID
 * @return string URL
 */
function get_chapter_redirect_url( $redirect_page, $parent_ID, $chapter_ID = 0 )
{
	global $admin_url, $blog;

	if( $redirect_page == 'front' )
	{ // Get Chapter for front page redirect
		if( empty( $chapter_ID ) )
		{ // Chapter ID is invalid, redirect to chapters list
			$redirect_page = 'list';
		}
		else
		{
			$ChapterCache = & get_ChapterCache();
			$Chapter = & $ChapterCache->get_by_ID( $chapter_ID, false, false );
			if( $Chapter === false )
			{ // Chapter doesn't exist anymore, redirect to chapters list
				$redirect_page = 'list';
			}
		}
	}

	switch( $redirect_page )
	{
		case 'front':
			// Redirect to front-office
			$redirect_url = $Chapter->get_permanent_url( NULL, NULL, 1, NULL, '&' );
			break;

		case 'manual':
			// Redirect to manual pages
			$redirect_url = $admin_url.'?ctrl=items&blog='.$blog.'&tab=manual';
			if( !empty( $parent_ID ) )
			{ // Open parent category to display new created category
				$redirect_url .= '&cat_ID='.$parent_ID;
			}
			break;

		default: // 'list'
			// Redirect to chapters list
			$redirect_url = $admin_url.'?ctrl=chapters&blog='.$blog;
			break;
	}

	return $redirect_url;
}


/**
 * Perform action:
 */
switch( $action )
{
	case 'new':
		// New action

		$edited_GenericCategory = & $GenericCategoryCache->new_obj( NULL, $subset_ID );
		$edited_GenericCategory->blog_ID = $edited_Blog->ID;

		if( isset( $edited_parent_GenericElement ) )
		{
			$edited_GenericCategory->parent_ID = $edited_parent_GenericElement->ID;
			$edited_GenericCategory->parent_name = $edited_parent_GenericElement->name;
		}
		else
		{
			$edited_GenericCategory->parent_name = T_('Root');
		}

		break;


	case 'move': // EXTENSION
 		if( ! $Settings->get('allow_moving_chapters') )
 		{
			debug_die( 'Moving of chapters is disabled' );
		}
		/* NO BREAK */
	case 'edit':
		// Edit element form...:
		// Make sure we got an ID:
		param( $GenericCategoryCache->dbIDname, 'integer', true );

		// Get the page number we come from:
		$previous_page = param( 'results'.$GenericCategoryCache->dbprefix.'page', 'integer', 1, true );

		break;


	case 'create':
		// Insert new element...:

		$edited_GenericCategory = & $GenericCategoryCache->new_obj( NULL, $subset_ID );

		// load data from request
		if( $edited_GenericCategory->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			if( $edited_GenericCategory->dbinsert() !== false )
			{
				$Messages->add( T_('New chapter created.'), 'success' );
				// Add the ID of the new element to the result fadeout
				$result_fadeout[$edited_GenericCategory->dbIDname][] = $edited_GenericCategory->ID;
				$action = 'list';
				// We want to highlight the edited object on next list display:
				$Session->set( 'fadeout_array', array($edited_GenericCategory->ID) );

				// Redirect so that a reload doesn't write to the DB twice:
				$redirect_to = get_chapter_redirect_url( param( 'redirect_page', 'string', '' ), $edited_GenericCategory->parent_ID, $edited_GenericCategory->ID );
				header_redirect( $redirect_to, 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}
		break;


	case 'update':
		// Make sure we got an ID:

		param( $GenericCategoryCache->dbIDname, 'integer', true );

		// LOAD FORM DATA:
		if( $edited_GenericCategory->load_from_Request() )
		{	// We could load data from form without errors:
			// Update in DB:
			if( $edited_GenericCategory->dbupdate() !== false )
			{
				$Messages->add( T_('Chapter updated.'), 'success' ); //ToDO change htis
			}
			// Add the ID of the updated element to the result fadeout
			$result_fadeout[$edited_GenericCategory->dbIDname][] = $edited_GenericCategory->ID;

			// We want to highlight the edited object on next list display:
			$Session->set( 'fadeout_array', array($edited_GenericCategory->ID));

			// Redirect so that a reload doesn't write to the DB twice:
			$redirect_to = get_chapter_redirect_url( param( 'redirect_page', 'string', '' ), $edited_GenericCategory->parent_ID, $edited_GenericCategory->ID );
			header_redirect( $redirect_to, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{
			// Get the page number we come from:
			$previous_page = param( 'results'.$GenericCategoryCache->dbprefix.'page', 'integer', 1, true );
		}
		break;


	case 'update_move':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'element' );

		// EXTENSION
		if( ! $Settings->get('allow_moving_chapters') )
		{
			debug_die( 'Moving of chapters is disabled' );
		}

		// Make sure we got an ID:
		param( $GenericCategoryCache->dbIDname, 'integer', true );

		// Control permission to edit source blog:
		$edited_Blog = & $edited_GenericCategory->get_Blog();
		if( ! $current_User->check_perm( 'blog_cats', '', false, $edited_Blog->ID ) )
		{
			debug_die( 'No permission to edit source collection.' );
			/* die */
		}

		// Control permission to edit destination blog:
		param( 'cat_coll_ID', 'integer', true );
		if( ! $current_User->check_perm( 'blog_cats', '', false, $cat_coll_ID ) )
		{
			// fp> TODO: prevent move in UI.
			$Messages->add( 'No permission to edit destination blog.', 'error' );	// NO TRANS b/c temporary
			break;
		}

		if( $cat_coll_ID == $edited_Blog->ID )
		{
			$Messages->add( T_('Category has not been moved.'), 'note' );
			break;
		}

		// Do the actual move! (This WILL reset the cache!)
		$GenericCategoryCache->move_Chapter_subtree( $edited_GenericCategory->ID, $subset_ID, $cat_coll_ID );

		$dest_Blog = & $BlogCache->get_by_ID( $cat_coll_ID );
		$Messages->add( /* TRANS: first %s is the moved category's name, the second one the new parent category */ sprintf( T_('The category &laquo;%s&raquo; has been moved (with children) to &laquo;%s&raquo;\'s root. You may want to nest it in another parent category below...'), $edited_GenericCategory->dget('name'), $dest_Blog->dget( 'shortname' )  ), 'success' );

		header_redirect( url_add_param( $admin_url, 'ctrl=chapters&action=edit&blog='.$cat_coll_ID.'&cat_ID='.$cat_ID, '&' ) );	// will save $Messages
		/* EXIT */

		// In case we changed the redirect someday:
		unset($edited_GenericCategory);
		$cat_ID = NULL;
		$action = 'list';
		break;


	case 'delete':
		// Delete entry:

		param( $GenericCategoryCache->dbIDname, 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$parent_ID = $edited_GenericCategory->parent_ID;
			$msg = sprintf( T_('Chapter &laquo;%s&raquo; deleted.'), $edited_GenericCategory->dget( 'name' ) );
			$GenericCategoryCache->dbdelete_by_ID( $edited_GenericCategory->ID );
			unset($edited_GenericCategory);
			forget_param( $GenericCategoryCache->dbIDname );
			$Messages->add( $msg, 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			$redirect_to = get_chapter_redirect_url( param( 'redirect_page', 'string', '' ), $parent_ID );
			header_redirect( $redirect_to, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			// TODO: dh> allow to delete a category which has links (and unbreak those after confirmation).
			// Get the page number we come from:
			$previous_page = param( 'results_'.$GenericCategoryCache->dbprefix.'page', 'integer', 1, true );
			if( ! $edited_GenericCategory->check_delete( sprintf( T_('Cannot delete element &laquo;%s&raquo;'), $edited_GenericCategory->dget( 'name' ) ) ) )
			{	// There are restrictions:
				$action = 'edit';
			}
		}
		break;

	case 'make_default':
		// Make category as default

		$edited_Blog->set_setting( 'default_cat_ID', $edited_GenericCategory->ID );
		$edited_Blog->dbsave();
		break;

	case 'set_meta':
		// Make category as meta category

		// Start serializable transaction because a category can be meta only if it has no posts
		$DB->begin( 'SERIALIZABLE' );

		// Category can be set as meta if it has no posts
		$result = !$edited_GenericCategory->has_posts();
		$edited_GenericCategory->set( 'meta', '1' );

		// Save category
		if( $result && $edited_GenericCategory->dbsave() )
		{ // Category has no posts and it was saved successful
			$Messages->add( sprintf( T_('The category &laquo;%s&raquo; was made as meta category.'), $edited_GenericCategory->dget('name') ), 'success' );
			$DB->commit();
		}
		else
		{
			$Messages->add( sprintf( T_('The category &laquo;%s&raquo; cannot be set as meta category. You must remove the posts it contains first.'), $edited_GenericCategory->dget('name') ) );
			$DB->rollback();
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=chapters&blog='.$blog, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'unset_meta':
		// Revert to simple category

		$edited_GenericCategory->set( 'meta', '0' );
		if( $edited_GenericCategory->dbsave() )
		{
			$Messages->add( sprintf( T_('The category &laquo;%s&raquo; was reverted from meta category.'), $edited_GenericCategory->dget('name') ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_('The category &laquo;%s&raquo; couldn\'t be reverted from meta category.'), $edited_GenericCategory->dget('name') ), 'error' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=chapters&blog='.$blog, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'lock':
		// Lock category

		$edited_GenericCategory->set( 'lock', '1' );
		if( $edited_GenericCategory->dbsave() )
		{
			$Messages->add( sprintf( T_('The category &laquo;%s&raquo; was locked.'), $edited_GenericCategory->dget('name') ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_('The category &laquo;%s&raquo; couldn\'t be locked.'), $edited_GenericCategory->dget('name') ), 'error' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=chapters&blog='.$blog, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'unlock':
		// Unlock category

		$edited_GenericCategory->set( 'lock', '0' );
		if( $edited_GenericCategory->dbsave() )
		{
			$Messages->add( sprintf( T_('The category &laquo;%s&raquo; was unlocked.'), $edited_GenericCategory->dget('name') ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_('The category &laquo;%s&raquo; couldn\'t be unlocked.'), $edited_GenericCategory->dget('name') ), 'error' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=chapters&blog='.$blog, 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;
}

if( $action == 'list' )
{ // Load JS to edit chapter order inline
	require_js( 'jquery/jquery.jeditable.js', 'rsc_url' );
}

/**
 * Display page header, menus & messages:
 */
$AdminUI->set_coll_list_params( 'blog_cats', 'edit', array( 'ctrl' => $ctrl ) );

$AdminUI->set_path( 'collections', 'categories' );

$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=dashboard&amp;blog=$blog$' ) );
$AdminUI->breadcrumbpath_add( T_('Categories'), $admin_url.'?ctrl=chapters&amp;blog=$blog$' );

$AdminUI->set_page_manual_link( 'categories-tab' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;

	case 'move':
		// EXTENSION TO GENERIC:
		// Move to another blog:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		$AdminUI->disp_view( 'chapters/views/_chapter_move.form.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'new':
	case 'copy':
	case 'create':
	case 'edit':
	case 'update':
	case 'delete':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		param( 'redirect_page', 'string', '', true );

		if( $action == 'delete' )
		{	// We need to ask for confirmation:
			$edited_GenericCategory->confirm_delete(
					sprintf( T_('Delete element &laquo;%s&raquo;?'),  $edited_GenericCategory->dget( 'name' ) ),
					'element', $action, get_memorized( 'action' ) );
		}

		if( $form_below_list )
		{
			// Display list VIEW before form view:
			if( !empty( $list_view_path ) )
			{
				$AdminUI->disp_view( $list_view_path );
			}
			else
			{
				$AdminUI->disp_view( 'generic/_generic_recursive_list.inc.php' );
			}
		}

		// Display category edit form:
		if( !empty( $edit_view_path ) )
		{
			$AdminUI->disp_view( $edit_view_path );
		}
		else
		{
			$AdminUI->disp_view( 'generic/views/_generic_category.form.php' );
		}

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display list VIEW:
		if( !empty( $list_view_path ) )
		{
			$AdminUI->disp_view( $list_view_path );
		}
		else
		{
			$AdminUI->disp_view( 'generic/_generic_recursive_list.inc.php' );
		}

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>