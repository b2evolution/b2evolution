<?php
/**
 * This file implements the file types.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );


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

if( $demo_mode && !empty($action) )
{
	$Messages->add( 'You cannot make any edits on this screen while in demo mode.', 'error' );
	$action = '';
}

switch( $action )
{
	case 'new':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$edited_Filetype = new Filetype();
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

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'filetype' );

		$edited_Filetype = new Filetype();

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
				$edited_Filetype = new Filetype();
			}
			else
			{
				$action = 'list';
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=filetypes', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}
		break;

	case 'update':
		// Edit file type form...:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'filetype' );

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
			//save fadeout item
			$Session->set('fadeout_id', $ftyp_ID);
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=filetypes', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'delete':
		// Delete file type:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'filetype' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Make sure we got an ftyp_ID:
		param( 'ftyp_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( T_('File type &laquo;%s&raquo; deleted.'), $edited_Filetype->dget('name') );
			$edited_Filetype->dbdelete();
			unset( $edited_Filetype );
			forget_param( 'ftyp_ID' );
			$Messages->add( $msg, 'success' );
			$action = 'list';
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=filetypes', 303 ); // Will EXIT
			// We have EXITed already at this point!!
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
 * We need make this call to build menu for all modules
 */
$AdminUI->set_path( 'files' );

file_controller_build_tabs();

$AdminUI->set_path( 'files', 'settings', 'filetypes' );

// fp> TODO: this here is a bit sketchy since we have Blog & fileroot not necessarilly in sync. Needs investigation / propositions.
// Note: having both allows to post from any media dir into any blog.
$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Files'), '?ctrl=files&amp;blog=$blog$' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=fileset' );
$AdminUI->breadcrumbpath_add( T_('File types'), '?ctrl=filetypes' );

// Set an url for manual page:
switch( $action )
{
	case 'delete':
	case 'new':
	case 'copy':
	case 'create':
	case 'edit':
	case 'update':
		$AdminUI->set_page_manual_link( 'file-type-editing' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'file-types-list' );
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

?>