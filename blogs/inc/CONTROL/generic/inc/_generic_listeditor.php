<?php
/**
 * This file implements ther UI controler for element list edidor management.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package admin
 *
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
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
		unset( $GenericElementCache->dbIDname );
		$Messages->head = T_('Cannot edit element!');
		$Messages->add( T_('Requested element does not exist any longer.'), 'error' );
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
		
		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}
		
		$GenericElementCache->move_up_by_ID( $edited_GenericElement->ID );
		
		break;

	
	case 'move_down':
		// Move down the element order
		param( $GenericElementCache->dbIDname, 'integer', true );
		
		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}
		
		$GenericElementCache->move_down_by_ID( $edited_GenericElement->ID );

		break;

		
	case 'delete':
		// Delete entry:
		param( $GenericElementCache->dbIDname, 'integer', true );
		
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
		$Request->set_param( 'results_'.$GenericElementCache->dbprefix.'order', '--A' );
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
					$action, get_memorized( 'action' ) );
		}
		
		if( $form_below_list )
		{	// Display List VIEW before form VIEW:
			if( !empty( $list_view ) )
			{
				$AdminUI->disp_view( $list_view );
			}
			else 
			{
				$AdminUI->disp_view( 'generic/_generic_list.inc.php' );
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
			$AdminUI->disp_view( 'generic/_generic_list.inc.php' );
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