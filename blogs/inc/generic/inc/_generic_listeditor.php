<?php
/**
 * This file implements ther UI controler for element list edidor management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * {@internal Open Source relicensing agreement:
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id: _generic_listeditor.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


param( 'action', 'string', 'list' );

// Init fadeout result array:
$result_fadeout = array();

if( param( $GenericElementCache->dbIDname, 'integer', NULL, true, false, false ) )
{
	if( ( $edited_GenericElement = & $GenericElementCache->get_by_ID( ${$GenericElementCache->dbIDname}, false ) ) === false )
	{	// We could not find the element to edit:
		unset( $edited_GenericElement );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Element') ), 'error' );
		$action = 'nil';
	}
}

/**
 * Check locked elements
 */
if( !empty( $locked_IDs )
		&& in_array( $action, array( 'edit', 'update', 'delete' ) )
		&& in_array( ${$GenericElementCache->dbIDname}, $locked_IDs ) )
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
		// New element

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		$edited_GenericElement = & $GenericElementCache->new_obj();
		break;


	//case 'copy':
	case 'edit':
		// Edit element form...:
		// Make sure we got an ID:
		param( $GenericElementCache->dbIDname, 'integer', true );

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		// Get the page number we come from:
		$previous_page = param( 'results'.$GenericElementCache->dbprefix.'page', 'integer', 1, true );
		break;


	case 'create':
		// Insert new element...:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'element' );

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		$edited_GenericElement = & $GenericElementCache->new_obj();

		// load data from request
		if( $edited_GenericElement->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			if( $edited_GenericElement->dbinsert() !== false )
			{
				$Messages->add( T_('New element created.'), 'success' ); // TODO CHANGES THIS
				// Add the ID of the new element to the result fadeout
				$result_fadeout[$GenericElementCache->dbIDname][] = $edited_GenericElement->ID;
				$action = 'list';
			}
		}
		break;


	case 'update':
		// Make sure we got an ID:
		param( $GenericElementCache->dbIDname, 'integer', true );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'element' );

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		// LOAD FORM DATA:
		if( $edited_GenericElement->load_from_Request() )
		{	// We could load data from form without errors:
			// Update in DB:
			if( $edited_GenericElement->dbupdate() !== false )
			{
				$Messages->add( T_('Element updated.'), 'success' ); //ToDO change htis
				// Add the ID of the updated element to the result fadeout
				$result_fadeout[$GenericElementCache->dbIDname][] = $edited_GenericElement->ID;
				unset( $edited_GenericElement );
				forget_param( $GenericElementCache->dbIDname );
				$action = 'list';
			}
		}
		else
		{
			// Get the page number we come from:
			$previous_page = param( 'results'.$GenericElementCache->dbprefix.'page', 'integer', 1, true );
		}
		break;


	case 'copy':
		// Duplicate an element by prefilling create form:
		param( $GenericElementCache->dbIDname, 'integer', true );

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		$new_Element = $edited_GenericElement;	// COPY
		$new_Element->ID = 0;
		$edited_GenericElement = & $new_Element;
		$AdminUI->append_to_titlearea( T_('Copy element...') );
		break;


	case 'move_up':
		// Move up the element order
		param( $GenericElementCache->dbIDname, 'integer', true );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'element' );

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		$GenericElementCache->move_up_by_ID( $edited_GenericElement->ID );

		break;


	case 'move_down':
		// Move down the element order
		param( $GenericElementCache->dbIDname, 'integer', true );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'element' );

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		$GenericElementCache->move_down_by_ID( $edited_GenericElement->ID );

		break;


	case 'delete':
		// Delete entry:
		param( $GenericElementCache->dbIDname, 'integer', true );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'element' );

		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}

		// Set restrictions for element
		$edited_GenericElement->delete_restrictions = $delete_restrictions;

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('Element &laquo;%s&raquo; deleted.'), $edited_GenericElement->dget( 'name' ) );
			$GenericElementCache->dbdelete_by_ID( $edited_GenericElement->ID );
			unset($edited_GenericElement);
			forget_param( $GenericElementCache->dbIDname );
			$Messages->add( $msg, 'success' );
			$action = 'list';
		}
		else
		{	// not confirmed, Check for restrictions:
			// Get the page number we come from:
			$previous_page = param( 'results_'.$GenericElementCache->dbprefix.'page', 'integer', 1, true );
			if( ! $edited_GenericElement->check_delete( sprintf( T_('Cannot delete element &laquo;%s&raquo;'), $edited_GenericElement->dget( 'name' ) ) ) )
			{	// There are restrictions:
				$action = 'edit';
			}
		}
		break;

	case 'sort_by_order':
		// The list is sorted by the order column now.
		$Results->order = '--A';
		set_param( 'results_'.$GenericElementCache->dbprefix.'order', '--A' );
		$action = 'list';
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
			$edited_GenericElement->confirm_delete(
					sprintf( T_('Delete element &laquo;%s&raquo;?'),  $edited_GenericElement->dget( 'name' ) ),
					'element', $action, get_memorized( 'action' ) );
		}

		if( $form_below_list )
		{	// Display List VIEW before form VIEW:
			if( !empty( $list_view ) )
			{
				$AdminUI->disp_view( $list_view );
			}
			else
			{
				$AdminUI->disp_view( 'generic/views/_generic_list.inc.php' );
			}
		}

		// Display form:
		$edited_GenericElement->disp_form();

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		if( !empty( $list_view ) )
		{
			$AdminUI->disp_view( $list_view );
		}
		else
		{
			$AdminUI->disp_view( 'generic/views/_generic_list.inc.php' );
		}

		if( $form_below_list )
		{	// Display form after list:
			$action = 'new';
			$edited_GenericElement = & $GenericElementCache->new_obj();
			$edited_GenericElement->disp_form();
		}

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>