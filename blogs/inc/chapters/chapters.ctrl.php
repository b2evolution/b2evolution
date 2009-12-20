<?php
/**
 * This file implements ther UI controler for chapters management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( valid_blog_requested() )
{
	$current_User->check_perm( 'blog_cats', 'edit', true, $blog );
	$edited_Blog = & $Blog;
}
else
{
	$action = 'nil';
}

/**
 * Delete restrictions
 */
$delete_restrictions = array(
		array( 'table'=>'T_categories', 'fk'=>'cat_parent_ID', 'msg'=>T_('%d sub categories') ),
		array( 'table'=>'T_items__item', 'fk'=>'post_main_cat_ID', 'msg'=>T_('%d posts within category through main cat') ),
		array( 'table'=>'T_postcats', 'fk'=>'postcat_cat_ID', 'msg'=>T_('%d posts within category through extra cat') ),
	);

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


/**
 * Perform action:
 */
switch( $action )
{
	case 'new':
		// New action

		if( ! $permission_to_edit )
		{
			debug_die( 'No permission to edit' );
		}

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

		if( ! $permission_to_edit )
		{
			debug_die( 'No permission to edit' );
		}

		// Get the page number we come from:
		$previous_page = param( 'results'.$GenericCategoryCache->dbprefix.'page', 'integer', 1, true );

		break;


	case 'create':
		// Insert new element...:

		if( ! $permission_to_edit )
		{
			debug_die( 'No permission to edit' );
		}

		$edited_GenericCategory = & $GenericCategoryCache->new_obj( NULL, $subset_ID );

		// load data from request
		if( $edited_GenericCategory->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			if( $edited_GenericCategory->dbinsert() !== false )
			{
				$Messages->add( T_('New category created.'), 'success' );
				// Add the ID of the new element to the result fadeout
				$result_fadeout[$edited_GenericCategory->dbIDname][] = $edited_GenericCategory->ID;
				$action = 'list';
			}
		}
		break;


	case 'update':
		// Make sure we got an ID:
		param( $GenericCategoryCache->dbIDname, 'integer', true );

		if( ! $permission_to_edit )
		{
			debug_die( 'No permission to edit' );
		}

		// LOAD FORM DATA:
		if( $edited_GenericCategory->load_from_Request() )
		{	// We could load data from form without errors:
			// Update in DB:
			if( $edited_GenericCategory->dbupdate() !== false )
			{
				$Messages->add( T_('Element updated.'), 'success' ); //ToDO change htis
			}
			// Add the ID of the updated element to the result fadeout
			$result_fadeout[$edited_GenericCategory->dbIDname][] = $edited_GenericCategory->ID;
			$action = 'list';
		}
		else
		{
			// Get the page number we come from:
			$previous_page = param( 'results'.$GenericCategoryCache->dbprefix.'page', 'integer', 1, true );
		}
		break;


	case 'update_move':
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

		if( ! $permission_to_edit )
		{
			debug_die( 'No permission to edit' );
		}

		// Set restrictions for element
		$edited_GenericCategory->delete_restrictions = $delete_restrictions;

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Element &laquo;%s&raquo; deleted.'), $edited_GenericCategory->dget( 'name' ) );
			$GenericCategoryCache->dbdelete_by_ID( $edited_GenericCategory->ID );
			unset($edited_GenericCategory);
			forget_param( $GenericCategoryCache->dbIDname );
			$Messages->add( $msg, 'success' );
			$action = 'list';
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
		if( ! $permission_to_edit )
		{
			debug_die( 'No permission to edit' );
		}

		$edited_Blog->set_setting( 'default_cat_ID', $edited_GenericCategory->ID );
		$edited_Blog->dbsave();

		break;
}

/**
 * Display page header, menus & messages:
 */
$AdminUI->set_coll_list_params( 'blog_cats', 'edit',
		array( 'ctrl' => $ctrl ),	NULL );


/**
 * We need make this call to build menu for all modules
 */
$AdminUI->set_path( 'items' );

/*
 * Add sub menu entries:
 * We do this here instead of _header because we need to include all filter params into regenerate_url()
 */
attach_browse_tabs();

$AdminUI->set_path( 'items', 'settings', 'chapters' );

$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Contents'), '?ctrl=items&amp;blog=$blog$&amp;tab=full&amp;filter=restore' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=chapters&amp;blog=$blog$' );
$AdminUI->breadcrumbpath_add( T_('Categories'), '?ctrl=chapters&amp;blog=$blog$' );

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

		if( $action == 'delete' )
		{	// We need to ask for confirmation:
			$edited_GenericCategory->confirm_delete(
					sprintf( T_('Delete element &laquo;%s&raquo;?'),  $edited_GenericCategory->dget( 'name' ) ),
					$action, get_memorized( 'action' ) );
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
			$AdminUI->disp_view( 'generic/_generic_category.form.php' );
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


/*
 * $Log$
 * Revision 1.17  2009/12/20 22:12:16  fplanque
 * doc
 *
 * Revision 1.16  2009/12/18 23:32:30  blueyed
 * Typos, trans todo nuked
 *
 * Revision 1.15  2009/12/12 01:13:08  fplanque
 * A little progress on breadcrumbs on menu structures alltogether...
 *
 * Revision 1.14  2009/09/14 11:26:19  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.13  2009/09/05 13:51:11  efy-maxim
 * pass correct Blog_ID in categories
 *
 * Revision 1.12  2009/08/30 19:54:25  fplanque
 * less translation messgaes for infrequent errors
 *
 * Revision 1.11  2009/05/17 18:09:09  tblue246
 * Update POT file
 *
 * Revision 1.10  2009/04/21 20:52:49  blueyed
 * trans comment
 *
 * Revision 1.9  2009/03/08 23:57:41  fplanque
 * 2009
 *
 * Revision 1.8  2009/01/28 22:34:21  fplanque
 * Default cat for each blog can now be chosen explicitely
 *
 * Revision 1.7  2009/01/28 21:23:23  fplanque
 * Manual ordering of categories
 *
 * Revision 1.6  2008/01/21 09:35:26  fplanque
 * (c) 2008
 *
 * Revision 1.5  2008/01/05 02:28:17  fplanque
 * enhanced blog selector (bloglist_buttons)
 */
?>