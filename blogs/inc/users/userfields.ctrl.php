<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
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
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author evofactory-test
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_userfield.class.php', 'Userfield' );

/**
 * Userfield Class
 *
 * @package evocore
 */

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'users', 'userfields' );

param( 'action', 'string' );

if( param( 'ufdf_ID', 'integer', '', true) )
{// Load file type:
	$UsertypeCache = & get_Cache( 'UserFieldCache' );
	if( ($edited_Userfield = & $UsertypeCache->get_by_ID( $ufdf_ID, false )) === false )
	{	// We could not find the user field to edit:
		unset( $edited_Userfield );
		forget_param( 'ufdf_ID' );
		$Messages->head = T_('Cannot edit user field!');
		$Messages->add( T_('Requested user field does not exist any longer.'), 'error' );
		$action = 'nil';
	}
}

if( isset($edited_Userfield) && ($edited_Userfield !== false) )
{	// We are editing a division:
	$AdminUI->append_to_titlearea( '&laquo;<a href="'.regenerate_url('action','action=edit').
																	'">'.$edited_Userfield->dget('name').'</a>&raquo;' );
}

switch( $action )
{

	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
		$edited_Userfield = & new Userfield();
		$new_ufdf_ID = '';
		$AdminUI->append_to_titlearea( T_('Add a user field...') );
		break;

	case 'copy':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Duplicate a user field by prefilling create form:
		param( 'ufdf_ID', 'integer', true );
		$edited_Userfield = duplicate( $edited_Userfield );
		$new_ufdf_ID = $edited_Userfield->ID;
		$edited_Userfield->ID = 0;
		$AdminUI->append_to_titlearea( T_('Copy user field...') );
		break;

	case 'edit':
		// Edit user field form...:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ufdf_ID:
		param( 'ufdf_ID', 'integer', true );
 		break;

	case 'create':
		// Insert new user field...:
		$edited_Userfield = & new Userfield();

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// load data from request
		if( $edited_Userfield->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_Userfield->dbinsert();
			$Messages->add( T_('New user field created.'), 'success' );

			// What next?
			param( 'submit', 'string', true );
			if( $submit == T_('Record, then Create Similar') ) // TODO: do not use submit value for this!
			{
				$action = 'new';
			}
			elseif( $submit == T_('Record, then Create New') ) // TODO: do not use submit value for this!
			{
				$action = 'new';
				$edited_Userfield = & new Userfield();
			}
			else
			{
				$action = 'list';
			}
		}
		break;

	case 'update':
		// Edit user field form...:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ufdf_ID:
		param( 'ufdf_ID', 'integer', true );

		// load data from request
		if( $edited_Userfield->load_from_Request() )
		{	// We could load data from form without errors:
			// Update in DB:
			$edited_Userfield->dbupdate();
			$Messages->add( T_('User field updated.'), 'success' );
			$action = 'list';
		}
		break;

	case 'delete':
		// Delete user field:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ufdf_ID:
		param( 'ufdf_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('User field &laquo;%s&raquo; deleted.'), $edited_Userfield->dget('name') );
			$edited_Userfield->dbdelete( true );
			unset( $edited_Userfield );
			forget_param( 'ufdf_ID' );
			$Messages->add( $msg, 'success' );
			$action = 'list';
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Userfield->check_delete( sprintf( T_('Cannot delete file type &laquo;%s&raquo;'), $edited_Userfield->dget('name') ) ) )
			{	// There are restrictions:
				$action = 'view';
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


	case 'delete':
		// We need to ask for confirmation:
		$edited_Userfield->confirm_delete(
				sprintf( T_('Delete file type &laquo;%s&raquo;?'),  $edited_Userfield->dget('name') ),
				$action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'copy':
	case 'create':	// we return in this state after a validation error
	case 'edit':
	case 'update':	// we return in this state after a validation error
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'users/views/_userfield.form.php' );
		$AdminUI->disp_payload_end();
		break;


	default:
		// No specific request, list all user fields:
		// Cleanup context:
		forget_param( 'ufdf_ID' );
		// Display user fields list:
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'users/views/_userfields.view.php' );
		$AdminUI->disp_payload_end();

}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.1  2009/09/11 18:34:06  fplanque
 * userfields editing module.
 * needs further cleanup but I think it works.
 *
 */
?>