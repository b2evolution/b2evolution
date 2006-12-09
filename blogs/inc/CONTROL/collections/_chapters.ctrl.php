<?php
/**
 * This file implements ther UI controler for chapters management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$current_User->check_perm( 'blog_cats', 'edit', true, $blog );



$BlogCache = & get_Cache( 'BlogCache' );
$edited_Blog = & $BlogCache->get_by_ID( $blog );
$Blog = & $edited_Blog; // used for "Exit to blogs.." link


$AdminUI->set_path( 'blogs', 'chapters' );


/**
 * Delete restrictions
 */
$delete_restrictions = array(
							array( 'table'=>'T_categories', 'fk'=>'cat_parent_ID', 'msg'=>T_('%d sub categories') ),
							array( 'table'=>'T_posts', 'fk'=>'post_main_cat_ID', 'msg'=>T_('%d posts within category through main cat') ),
							array( 'table'=>'T_postcats', 'fk'=>'postcat_cat_ID', 'msg'=>T_('%d posts within category through extra cat') ),
					);

$restrict_title = T_('Cannot delete category');	 //&laquo;%s&raquo;

// mb> Used to know if the element can be deleted, so to display or not display confirm delete dialog (true:display, false:not display)
// It must be initialized to false before checking the delete restrictions
$checked_delete = false;

load_class( '/MODEL/collections/_chaptercache.class.php' );
$GenericCategoryCache = & new ChapterCache();


/**
 * Display page header, menus & messages:
 */
$blogListButtons = $AdminUI->get_html_collection_list( 'blog_cats', '',
											'?ctrl='.$ctrl.'&amp;blog=%d',
											T_('List'), '?ctrl=collections&amp;blog=0' );

// Restrict to chapters of the specific blog:
$subset_ID = $blog;

$list_view_path = 'collections/_chapter_list.inc.php';
$permission_to_edit = $current_User->check_perm( 'blog_cats', '', false, $blog );
// fp> TODO: reinforce that control!

// The form will be on its own page:
$form_below_list = false;
$edit_view_path = 'collections/_chapter.form.php';



// ---- Below is a modified generic categtory list editor: -----


// fp> this is an example of where we could benefit from controler classes wich could be derived
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

if( param( $GenericCategoryCache->dbIDname, 'integer', NULL, true, false, false ) )
{
	if( ($edited_GenericCategory = & $GenericCategoryCache->get_by_ID( ${$GenericCategoryCache->dbIDname}, false, true, $subset_ID )) === false )
	{	// We could not find the element to edit:
		unset( $edited_GenericCategory );
		$Messages->head = T_('Cannot edit element!');
		$Messages->add( T_('Requested element does not exist any longer.'), 'error' );
		$action = 'nil';
	}
}

if( !is_null( param( $GenericCategoryCache->dbprefix.'parent_ID', 'integer', NULL ) ) )
{
	if( ( $edited_parent_GenericElement = & $GenericCategoryCache->get_by_ID( ${$GenericCategoryCache->dbprefix.'parent_ID'}, false, true, $subset_ID ) ) === false )
	{ // Parent generic category doesn't exist any longer.
		unset( $GenericCategoryCache->dbIDname );
		$Messages->head = T_('Cannot edit element!');
		$Messages->add( T_('Requested element does not exist any longer.'), 'error' );
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
 		if( ! $allow_moving_chapters )
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
				$Messages->add( T_('New element created.'), 'success' ); // TODO CHANGES THIS
				// Add the ID of the new element to the result fadeout
				$result_fadeout[] = $edited_GenericCategory->ID;
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
				// Add the ID of the updated element to the result fadeout
				$result_fadeout[] = $edited_GenericCategory->ID;
			}
			$action = 'list';
		}
		else
		{
			// Get the page number we come from:
			$previous_page = param( 'results'.$GenericCategoryCache->dbprefix.'page', 'integer', 1, true );
		}
		break;


	case 'update_move':
 		if( ! $allow_moving_chapters )
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
			$Messages->add( T_('Chapter had not been moved.'), 'note' );
			break;
		}



			echo 'TO DO';

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
			// Get the page number we come from:
			$previous_page = param( 'results_'.$GenericCategoryCache->dbprefix.'page', 'integer', 1, true );
			if( ! $edited_GenericCategory->check_delete( sprintf( T_('Cannot delete element &laquo;%s&raquo;'), $edited_GenericCategory->dget( 'name' ) ) ) )
			{	// There are restrictions:
				$action = 'edit';
			}
		}
		break;

}


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

		$AdminUI->disp_view( 'collections/_chapter_move.form.php' );

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


// Fadeout javascript
echo '<script type="text/javascript" src="'.$rsc_url.'js/fadeout.js"></script>';
echo '<script type="text/javascript">addEvent( window, "load", Fat.fade_all, false);</script>';


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.8  2006/12/09 17:59:31  fplanque
 * started "moving chapters accross blogs" feature
 *
 * Revision 1.7  2006/11/30 22:34:15  fplanque
 * bleh
 *
 * Revision 1.6  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.5  2006/09/10 19:32:32  fplanque
 * completed chapter URL name editing
 *
 * Revision 1.4  2006/09/10 17:33:02  fplanque
 * started to steam up the categories/chapters
 *
 */
?>