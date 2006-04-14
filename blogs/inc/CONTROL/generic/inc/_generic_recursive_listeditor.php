<?php
/**
 * This file implements the generic list editor recursif
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * NOTE: It uses <code>$AdminUI->get_path(1).'.php'</code> to link back to the ID of the entry.
 *       If that causes problems later, we'd probably need to set a global like $listeditor_url.
 *
 * @author mbreneau: Marc BRUNEAU.
 *
 * @version $Id
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
param( 'action', 'string', 'list' );


if( param( $GenericElementCache->dbIDname, 'integer', NULL, true, false, false ) )
{
	if( ($edited_GenericElement = & $GenericElementCache->get_by_ID( ${$GenericElementCache->dbIDname}, false )) === false )
	{	// We could not find the element to edit:
		unset( $edited_GenericElement );
		$Messages->head = T_('Cannot edit element!');
		$Messages->add( T_('Requested element does not exist any longer.'), 'error' );
		$action = 'nil';
	}
}

if ( !is_null( param( $GenericElementCache->dbprefix.'parent_ID', 'integer', NULL ) ) )
{
	if( ( $edited_parent_GenericElement = & $GenericElementCache->get_by_ID( ${$GenericElementCache->dbprefix.'parent_ID'}, false ) ) === false )
	{ // Parent generic category doesn't exist any longer.
		unset( $GenericElementCache->dbIDname );
		$Messages->head = T_('Cannot edit xxx!');
		$Messages->add( T_('Requested xxx does not exist any longer.'), 'error' );
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
		&& in_array( $$GenericElementCache->dbIDname, $locked_IDs ) )
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
		
		if( isset( $perm_name ) )
		{	// We need to Check permission:
			$current_User->check_perm( $perm_name, $perm_level, true );
		}
		
		$edited_GenericElement = & $GenericElementCache->new_obj();
		
		if( isset( $edited_parent_GenericElement ) )
		{
			$edited_GenericElement->parent_ID = $edited_parent_GenericElement->ID;
			$edited_GenericElement->parent_name = $edited_parent_GenericElement->name;
		}
		else 
		{
			$edited_GenericElement->parent_name = T_('Root');
		}
		
		break;
		
		
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
				$result_fadeout[] = $edited_GenericElement->ID;
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
				$result_fadeout[] = $edited_GenericElement->ID;
				$action = 'list';
			}
		}
		else 
		{
			// Get the page number we come from:
			$previous_page = param( 'results'.$GenericElementCache->dbprefix.'page', 'integer', 1, true );
		}
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
		{
			// Display list VIEW before form view:
			$AdminUI->disp_view( 'generic/_generic_recursive_list.inc.php' );
		}
		
		// Display form form the element object:
		$edited_GenericElement->disp_form();
		
		// End payload block:
		$AdminUI->disp_payload_end();
		break;
	
	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();
		
		// Display VIEW:
		$AdminUI->disp_view( 'generic/_generic_recursive_list.inc.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}
	

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


// Fadeout javascript
echo '<script type="text/javascript" src="'.$rsc_url.'js/fadeout.js"></script>';
echo '<script type="text/javascript">addEvent( window, "load", Fat.fade_all, false);</script>';


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>