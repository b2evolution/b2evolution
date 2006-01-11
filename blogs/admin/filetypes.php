<?php
/**
 * This file implements the file types.
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

/**
 * Includes:
 */
require( dirname(__FILE__).'/_header.php' );

$AdminUI->set_path( 'options', 'filetypes' );

// Check permission:
$current_User->check_perm( 'options', 'view', true );

param( 'action', 'string' );

if( param( 'ftyp_ID', 'integer', '', true) )
{// Load firm/division:
	if( ($edited_Filetype = & $FiletypeCache->get_by_ID( $ftyp_ID, false )) === false )
	{	// We could not find the file type to edit:
		unset( $edited_Filetype );
		$Messages->head = T_('Cannot edit file type!');
		$Messages->add( T_('Requested file type does not exist any longer.'), 'error' );
		$action = 'nil';
	}
}	

if( isset($edited_Filetype) && ($edited_Filetype !== false) )
{	// We are editing a division:
	$AdminUI->append_to_titlearea( '&laquo;<a href="'.regenerate_url('action','action=edit').
																	'">'.$edited_Filetype->dget('name').'</a>&raquo;' );
}
	
switch( $action )
{
	
	case 'new':
		// New contact form...:
		$edited_Filetype = & new Filetype();
		$AdminUI->append_to_titlearea( T_('Add a file type...') );
		break;
		
	case 'copy':
		// Duplicate a file type by prefilling create form:
		param( 'ftyp_ID', 'integer', true );
		$new_Filetype = $edited_Filetype;	// COPY
		$new_Filetype->ID = 0;
		$edited_Filetype = & $new_Filetype;
		$AdminUI->append_to_titlearea( T_('Copy file type...') );
		break;
	
	case 'edit':
		// Edit file type form...:
		// Make sure we got an ftyp_ID:
		param( 'ftyp_ID', 'integer', true );
 		break;
		
	case 'create':
		// Insert new file type...:
		$edited_Filetype = & new Filetype();

		// load data from request
		if( $edited_Filetype->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_Filetype->dbinsert();
			$Messages->add( T_('New file type created.'), 'success' );
			
			// What next?
	 		param( 'submit', 'string', true );
			if( $submit == T_('Record, then Create Similar') )
			{
				$action = 'new';
			}
			elseif( $submit == T_('Record, then Create New') )
			{
				$action = 'new';
				$edited_Filetype = & new Filetype();
			}
			else
			{			
				$action = 'list';
			}
		}
		break;

	case 'update':
		// Edit file type form...:
		// Make sure we got an ftyp_ID:
		param( 'ftyp_ID', 'integer', true );
		
		// load data from request
		if( $edited_Filetype->load_from_Request() )
		{	// We could load data from form without errors:
			// Update in DB:
			$edited_Filetype->dbupdate();
			$Messages->add( T_('File type updated.'), 'success' );
			$action = 'list';
		}
		break;
		
	case 'delete':
		// Delete file type:
		// Make sure we got an ftyp_ID:
		param( 'ftyp_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('File type &laquo;%s&raquo; deleted.'), $edited_Filetype->dget('name') );
			$edited_Filetype->dbdelete( true );
			unset($edited_Filetype);
			forget_param( 'ftyp_ID' );
			$Messages->add( $msg, 'success' );
			$action = 'list';
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Filetype->check_delete( sprintf( T_('Cannot delete file type &laquo;%s&raquo;'), $edited_Filetype->dget('name') ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;
		
}

/**
 * Display page header:
 */
require dirname(__FILE__).'/_menutop.php';

// Check permission to view:
$current_User->check_perm( 'options', 'view', true );


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
		$edited_Filetype->confirm_delete(
				sprintf( T_('Delete file type &laquo;%s&raquo;?'),  $edited_Filetype->dget('name') ),
				$action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'copy':
	case 'create':	// we return in this state after a validation error
	case 'edit':
	case 'update':	// we return in this state after a validation error
		// Begin payload block:
		$AdminUI->disp_payload_begin();
		require dirname(__FILE__).'/_filetype.form.php';
		// End payload block:
		$AdminUI->disp_payload_end();
		break;


	default:
			// No specific request, list all file types:
			// Cleanup context:
			forget_param( 'ftype_ID' );
			// Display file types list:
			$AdminUI->disp_payload_begin();
			require dirname(__FILE__).'/_filetype_list.inc.php';
			$AdminUI->disp_payload_end();
	
}
		
require dirname(__FILE__).'/_footer.php';
?>