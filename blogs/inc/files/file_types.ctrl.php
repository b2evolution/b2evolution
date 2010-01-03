<?php
/**
 * This file implements the file types.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );


$AdminUI->set_path( 'files', 'filetypes' );


param( 'action', 'string' );

if( param( 'ftyp_ID', 'integer', '', true) )
{// Load file type:
	$FiletypeCache = & get_FiletypeCache();
	if( ($edited_Filetype = & $FiletypeCache->get_by_ID( $ftyp_ID, false )) === false )
	{	// We could not find the file type to edit:
		unset( $edited_Filetype );
		forget_param( 'ftyp_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('File type') ), 'error' );
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
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$edited_Filetype = & new Filetype();
		$AdminUI->append_to_titlearea( T_('Add a file type...') );
		break;

	case 'copy':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Duplicate a file type by prefilling create form:
		param( 'ftyp_ID', 'integer', true );
		$new_Filetype = $edited_Filetype;	// COPY
		$new_Filetype->ID = 0;
		$edited_Filetype = & $new_Filetype;
		$AdminUI->append_to_titlearea( T_('Copy file type...') );
		break;

	case 'edit':
		// Edit file type form...:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'ftyp_ID', 'integer', true );
 		break;

	case 'create':
		// Insert new file type...:
		$edited_Filetype = & new Filetype();

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// load data from request
		if( $edited_Filetype->load_from_Request() )
		{	// We could load data from form without errors:
			// Insert in DB:
			$edited_Filetype->dbinsert();
			$Messages->add( T_('New file type created.'), 'success' );

			// What next?
			param( 'submit', 'string', true );
			if( $submit == T_('Record, then Create Similar') ) // TODO: do not use submit value for this!
			{
				$action = 'new';
			}
			elseif( $submit == T_('Record, then Create New') ) // TODO: do not use submit value for this!
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

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

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

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'ftyp_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('File type &laquo;%s&raquo; deleted.'), $edited_Filetype->dget('name') );
			$edited_Filetype->dbdelete( true );
			unset( $edited_Filetype );
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

file_controller_build_tabs();

// fp> TODO: this here is a bit sketchy since we have Blog & fileroot not necessarilly in sync. Needs investigation / propositions.
// Note: having both allows to post from any media dir into any blog.
$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Files'), '?ctrl=files&amp;blog=$blog$' );
$AdminUI->breadcrumbpath_add( T_('File types'), '?ctrl=filetypes' );

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
		$edited_Filetype->confirm_delete(
				sprintf( T_('Delete file type &laquo;%s&raquo;?'),  $edited_Filetype->dget('name') ),
				'filetype', $action, get_memorized( 'action' ) );
		/* no break */
	case 'new':
	case 'copy':
	case 'create':	// we return in this state after a validation error
	case 'edit':
	case 'update':	// we return in this state after a validation error
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'files/views/_filetype.form.php' );
		$AdminUI->disp_payload_end();
		break;


	default:
			// No specific request, list all file types:
			// Cleanup context:
			forget_param( 'ftype_ID' );
			// Display file types list:
			$AdminUI->disp_payload_begin();
			$AdminUI->disp_view( 'files/views/_filetype_list.view.php' );
			$AdminUI->disp_payload_end();

}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.7  2010/01/03 12:03:17  fplanque
 * More crumbs...
 *
 * Revision 1.6  2009/12/06 22:55:17  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.5  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.4  2009/08/30 19:54:24  fplanque
 * less translation messgaes for infrequent errors
 *
 * Revision 1.3  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:28  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:51  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.13  2007/04/26 00:11:13  fplanque
 * (c) 2007
 *
 * Revision 1.12  2006/11/26 01:42:08  fplanque
 * doc
 *
 */
?>