<?php
/**
 * This file implements the UI controller for file management.
 *
 * NOTE: $fm_mode gets used for modes, that allow browsing to some other place or
 *       take other actions. A good example is "upload" - you can delete other files while
 *       in upload mode.
 *       "edit_perms" for example is not a mode, but a action.
 *
 * fp>> There should actually be no modes. Only geeks can understand & use them. And not all geeks might actually ever find an opportunity to want to use them. All we need is a dir selection tree inside of upload and move.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 * Parts of this file are copyright (c)2005 by The University of North Carolina at Charlotte
 * as contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * The University of North Carolina at Charlotte grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jwedgeco: Jason EDGECOMBE (for hire by UNC-Charlotte)
 * @author edgester: Jason EDGECOMBE (personal contributions, not for hire)
 *
 * @version $Id$
 *
 * @todo thumbnail view
 * @todo PHPInfo (special permission)
 * @todo directly run PHP-code (eval)
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

require_once $model_path.'files/_filelist.class.php';

/**
 * @var User
 */
global $current_User;

// Check global access permissions:
if( ! $Settings->get( 'fm_enabled' ) )
{
	die( 'The filemanager is disabled.' );
}

// Check permission:
$current_User->check_perm( 'files', 'view', true );


$AdminUI->set_path( 'files' );


// INIT params:
if( param( 'root_and_path', 'string', '', false ) /* not memorized (default) */ && strpos( $root_and_path, '::' ) )
{ // root and path together: decode and override (used by "radio-click-dirtree")
	list( $root, $path ) = explode( '::', $root_and_path, 2 );
	// Memorize new root:
	memorize_param( 'root', 'string', NULL );
	memorize_param( 'path', 'string', NULL );
}
else
{
	param( 'root', 'string', NULL, true ); // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
	param( 'path', 'string', '/', true );  // the path relative to the root dir
	if( param( 'new_root', 'string', '' )
		&& $new_root != $root )
	{ // We have changed root in the select list
		$root = $new_root;
		$path = '';
	}
}

/**
 * @global string The file manager mode we're in ('fm_upload', 'fm_cmr')
 */
$fm_mode = param( 'fm_mode', 'string', NULL, true );

$action = $Request->param_action();


// Get root:
$ads_list_path = false; // false by default, gets set if we have a valid root
$fm_FileRoot = NULL;

$available_Roots = get_available_FileRoots();

if( ! empty($root) )
{ // We have requested a root folder by string:
	$fm_FileRoot = & $FileRootCache->get_by_ID($root);

	if( ! $fm_FileRoot || ! isset( $available_Roots[$fm_FileRoot->ID] ) )
	{ // Root not found or not in list of available ones
		$Messages->add( T_('You don\'t have access to the requested root directory.'), 'error' );
		$fm_FileRoot = false;
	}
}

if( ! $fm_FileRoot )
{ // No root requested (or the requested is invalid), get the first one available:
	if( $available_Roots
	    && ( $tmp_keys = array_keys( $available_Roots ) )
	    && $first_Root = & $available_Roots[ $tmp_keys[0] ] )
	{ // get the first one
		$fm_FileRoot = & $first_Root;
	}
	else
	{
		$Messages->add( T_('You don\'t have access to any root directory.'), 'error' );
	}
}

if( $fm_FileRoot )
{ // We have access to a file root:
	if( empty($fm_FileRoot->ads_path) )
	{	// Not sure it's possible to get this far, but just in case...
		$Messages->add( sprintf( T_('The root directory &laquo;%s&raquo; does not exist.'), $fm_FileRoot->ads_path ), 'error' );
	}
	else
	{ // Root exists
		// Let's get into requested list dir...
		$ads_list_path = trailing_slash( $fm_FileRoot->ads_path.$path );

		// Dereference any /../ just to make sure, and CHECK if directory exists:
		$ads_list_path = get_ads_canonical_path( $ads_list_path );

		if( !is_dir( $ads_list_path ) )
		{ // This should never happen, but just in case the diretcoty does not exist:
			$Messages->add( sprintf( T_('The directory &laquo;%s&raquo; does not exist.'), $path ), 'error' );
			$ads_list_path = NULL;
		}
		elseif( ! preg_match( '#^'.preg_quote($fm_FileRoot->ads_path, '#').'#', $ads_list_path ) )
		{ // cwd is OUTSIDE OF root!
			$Messages->add( T_( 'You are not allowed to go outside your root directory!' ), 'error' );
			$ads_list_path = $fm_FileRoot->ads_path;
		}
	}
}


/**
 * Filelist
 * fp>> TODO: When the user is viewing details for a file he should (by default) not be presented with the filelist in addition to the file properties
 * In cases like that, we should try to avoid instanciating a Filelist.
 */
$fm_Filelist = new Filelist( $fm_FileRoot, $ads_list_path );

if( $UserSettings->param_Request( 'fm_dirsnotattop', 'integer', 0 ) )
{
	$fm_Filelist->_dirs_not_at_top = true;
}
if( $UserSettings->param_Request( 'fm_recursivedirsize', 'integer', 0 ) ) // TODO: check for permission? (Server load)
{
	$fm_Filelist->_use_recursive_dirsize = true;
}
if( $UserSettings->param_Request( 'fm_showhidden', 'integer', 0 ) )
{
	$fm_Filelist->_show_hidden_files = true;
}
if( param( 'fm_flatmode', '', NULL, true ) )
{
	$fm_Filelist->flatmode = true;
}

param( 'fm_filter', '', NULL, true );
param( 'fm_filter_regex', 'integer', 0, true );

if( ! empty($fm_filter) )
{
	$fm_Filelist->set_filter( $fm_filter, $fm_filter_regex );
}

$Debuglog->add( 'FM root: '.var_export( $fm_FileRoot, true ), 'files' );
$Debuglog->add( 'FM _ads_list_path: '.var_export( $ads_list_path, true ), 'files' );
$Debuglog->add( 'FM _rds_list_path: '.var_export( $fm_Filelist->_rds_list_path, true ), 'files' );


// For modes build $fm_source_Filelist
if( $fm_mode && $fm_sources = param( 'fm_sources', 'array', array(), true ) )
{
	$fm_sources_root = param( 'fm_sources_root', 'string', '', true );

	$sources_Root = & $FileRootCache->get_by_ID( $fm_sources_root );

	if( $sources_Root )
	{ // instantiate the source list for the selected sources
		$fm_source_Filelist = & new Filelist( $sources_Root );
	}
	else
	{ // Fallback: source files are considered to be in the current root
		$fm_source_Filelist = & new Filelist( $fm_Filelist->get_FileRoot() );
		$Debuglog->add( 'SourceList without explicit root!', 'error' );
	}

	if( $fm_source_Filelist )
	{
		// TODO: should fail for non-existant sources, or sources where no read-perm
		foreach( $fm_sources as $l_source_path )
		{
			// echo '<br>'.$lSourcePath;
			$fm_source_Filelist->add_by_subpath( urldecode($l_source_path), true );
		}
	}
	else
	{ // Without SourceList there's no mode
		$fm_mode = false;
	}
}
else
{
	$fm_source_Filelist = false;
	$fm_sources = NULL;
	$fm_sources_root = NULL;
}


if( $action == 'update_settings' )
{ // Updating user settings from options list
	$UserSettings->set( 'fm_dirsnotattop',   1-param( 'option_dirsattop',        'integer', 0 ) );
	$UserSettings->set( 'fm_permlikelsl',      param( 'option_permlikelsl',      'integer', 0 ) );
	$UserSettings->set( 'fm_getimagesizes',    param( 'option_getimagesizes',    'integer', 0 ) );
	$UserSettings->set( 'fm_recursivedirsize', param( 'option_recursivedirsize', 'integer', 0 ) );
	$UserSettings->set( 'fm_showtypes',        param( 'option_showtypes',        'integer', 0 ) );
	$UserSettings->set( 'fm_showfsperms',      param( 'option_showfsperms',      'integer', 0 ) );
	$UserSettings->set( 'fm_showfsowner',      param( 'option_showfsowner',      'integer', 0 ) );
	$UserSettings->set( 'fm_showfsgroup',      param( 'option_showfsgroup',      'integer', 0 ) );
	$UserSettings->set( 'fm_showhidden',       param( 'option_showhidden',       'integer', 0 ) );
	$UserSettings->set( 'fm_forceFM',          param( 'option_forceFM',          'integer', 0 ) );

	if( $UserSettings->dbupdate() )
	{
		$Messages->add( T_('Your user settings have been updated.'), 'success' );
	}

	$action = '';
}

/**
 * @global boolean We set this to true to force displaying of the FM (without toggle option!)
 */
$fm_forceFM = NULL;


param( 'fm_disp_browser', 'integer', 0, true );
$UserSettings->param_Request( 'fm_hide_dirtree', 'integer', 0, true ); // The directory tree next to the files table


/*
 * Load editable objects:
 */
if( param( 'link_ID', 'integer', NULL, false, false, false ) )
{
	if( ($edited_Link = & $LinkCache->get_by_ID( $link_ID, false )) === false )
	{	// We could not find the link to edit:
		$Messages->head = T_('Cannot edit link!');
		$Messages->add( T_('Requested link does not exist any longer.'), 'error' );
		unset( $edited_Link );
		unset( $link_ID );
	}
}


/*
 * Load linkable objects:
 */
if( param( 'item_ID', 'integer', NULL, true, false, false ) )
{ // Load Requested iem:
	if( ($edited_Item = & $ItemCache->get_by_ID( $item_ID, false )) === false )
	{	// We could not find the contact to link:
		$Messages->head = T_('Cannot link Item!');
		$Messages->add( T_('Requested item does not exist any longer.'), 'error' );
		unset( $edited_Item );
		unset( $item_ID );
	}
}


if( empty($ads_list_path) )
{ // We have no Root / list path, there was an error. Unset any action or mode.
	$action = '';
	$fm_mode = NULL;
}

// Check actions that toggle Filelist properties or delegate to other actions:
if( ! empty($action) )
{
	switch( $action )
	{
		case 'filter_unset':
			forget_param( 'fm_filter' );
			$fm_filter = '';
			$action = '';
			break;

		case 'createnew':
			// Check permission:
			$current_User->check_perm( 'files', 'add', true );

			// create new file/dir
			param( 'create_type', 'string', '' ); // 'file', 'dir'
			param( 'create_name', 'string', '' );

			$action = ( $create_type == 'file' ? 'createnew_file' : 'createnew_dir'  );
			break;
	}
}

$fm_Filelist->set_Filter( $fm_filter, $fm_filter_regex );

// Load Filelist (with meta data):
$fm_Filelist->load();

// Sort Filelist
param( 'fm_order', 'string', NULL, true );
if( ! in_array( $fm_order, array( 'name', 'path', 'type', 'size', 'lastmod', 'perms', 'fsowner', 'fsgroup' ) ) )
{
	$fm_order = NULL;
}
param( 'fm_orderasc', '', NULL, true );
$fm_Filelist->sort( $fm_order, $fm_orderasc );


/**
 * @var Filelist The selected files
 */
$selected_Filelist = & new Filelist( $fm_Filelist->get_FileRoot(), false );

/**
 * @global array A list of files which are selected in the FM list.
 */
$fm_selected = param( 'fm_selected', 'array', array(), true );

$Debuglog->add( count($fm_selected).' selected files/directories', 'files' );

foreach( $fm_selected as $l_source_path )
{
	// echo '<br>'.$l_source_path;
	$selected_Filelist->add_by_subpath( urldecode($l_source_path), true );
}


switch( $action )
{
	case 'open_in_new_windows':
		// catch JS-only actions (happens when Javascript is disabled on the browser)
		$Messages->add( T_('You have to enable JavaScript to use this feature.'), 'error' );
		break;

	case 'createnew_dir':
		if( ! $Settings->get( 'fm_enable_create_dir' ) )
		{ // Directory creation is gloablly disabled:
			$Messages->add( T_('Directory creation is disabled.'), 'error' );
			break;
		}
		if( empty($create_name) )
		{ // No name was supplied:
			$Messages->add( T_('Cannot create a directory without name.'), 'error' );
			break;
		}
		if( $error_dirname = validate_dirname( $create_name ) )
		{ // Not valid dirname
			$Messages->add( $error_dirname, 'error' );
			break;
		}

		// Try to get File object:
		$newFile = & $FileCache->get_by_root_and_path( $fm_Filelist->_FileRoot->type, $fm_Filelist->_FileRoot->in_type_ID, $fm_Filelist->_rds_list_path.$create_name );

		if( $newFile->exists() )
		{
			$Messages->add( sprintf( T_('The file &laquo;%s&raquo; already exists.'), $create_name ), 'error' );
			break;
		}

		if( $newFile->create( $create_type ) )
		{
			$Messages->add( sprintf( T_('The directory &laquo;%s&raquo; has been created.'), $create_name ), 'success' );

			$fm_Filelist->add( $newFile );
			$fm_Filelist->sort();
		}
		else
		{
			$Messages->add( sprintf( T_('Could not create directory &laquo;%s&raquo; in &laquo;%s&raquo;.'), $create_name, $fm_Filelist->_rds_list_path ), 'error' );
		}
		break;


	case 'createnew_file':
		if( ! $Settings->get( 'fm_enable_create_file' ) )
		{ // File creation is gloablly disabled:
			$Messages->add( T_('File creation is disabled.'), 'error' );
			break;
		}
		if( empty($create_name) )
		{ // No name was supplied:
			$Messages->add( T_('Cannot create a file without name.'), 'error' );
			break;
		}
		if( $error_filename = validate_filename( $create_name ) )
		{ // Not valid filename or extension
			$Messages->add( $error_filename, 'error' );
			break;
		}

		// Try to get File object:
		$newFile = & $FileCache->get_by_root_and_path( $fm_Filelist->_FileRoot->type, $fm_Filelist->_FileRoot->in_type_ID, $fm_Filelist->_rds_list_path.$create_name );

		if( $newFile->exists() )
		{
			$Messages->add( sprintf( T_('The file &laquo;%s&raquo; already exists.'), $create_name ), 'error' );
			break;
		}

		if( $newFile->create( $create_type ) )
		{
			$Messages->add( sprintf( T_('The file &laquo;%s&raquo; has been created.'), $create_name ), 'success' );

			$fm_Filelist->add( $newFile );
			$fm_Filelist->sort();
		}
		else
		{
			$Messages->add( sprintf( T_('Could not create file &laquo;%s&raquo; in &laquo;%s&raquo;.'), $create_name, $fm_Filelist->_rds_list_path ), 'error' );
		}
		break;


	/*
	case 'send_by_mail':
	{{{ not implemented yet
		// TODO: implement
		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			break;
		}

		echo 'TODO: Send selected by mail, query email address..';
		break;
	}}}
	*/


	case 'download':
	{{{ // TODO: provide optional zip formats (tgz, ..) - the used lib provides more..
		$action_title = T_('Download');

		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			break;
		}

		param( 'zipname', 'string', '' );
		param( 'exclude_sd', 'integer', 0 );

		if( empty($zipname) )
		{
			if( param( 'action_invoked', 'integer', 0 ) )
			{ // Action was invoked, add "hint"
				$Request->param_error( 'zipname', T_('Please provide the name of the archive.') );
			}
			if( $selected_Filelist->count() == 1 )
			{
				$only_File = $selected_Filelist->get_array();
				$only_File = $only_File[0];

				// TODO: once we support additional formats, use the default extension here:
				$zipname = $only_File->get_name().'.zip';
			}
			break;
		}

		// Downloading
		require_once $inc_path.'_misc/ext/_zip_archives.php';

		$arraylist = $selected_Filelist->get_array( 'get_rdfs_rel_path' );

		$options = array (
			'basedir' => $fm_Filelist->get_ads_list_path(),
			'inmemory' => 1,
			'recurse' => (1 - $exclude_sd),
		);

		$zipfile = & new zip_file( $zipname );
		$zipfile->set_options( $options );
		$zipfile->add_files( $arraylist );
		$zipfile->create_archive();
		$zipfile->download_file();
		exit;
	}}}


	case 'rename':
		// Rename a file:
		// This will not allow to overwrite existing files, the same way Windows and MacOS do not allow it. Adding an option will only clutter the interface and satisfy geeks only.
		if( ! $current_User->check_perm( 'files', 'edit' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$action = 'list';
			break;
		}

		if( ! $selected_Filelist->count() )
		{ // There is nothing to rename
			$Messages->add( T_('Nothing selected.'), 'error' );
			$action = 'list';
			break;
		}

		param( 'confirmed', 'integer', 0 );
		param( 'new_names', 'array', array() );

		// Check params for each file to rename:
		while( $loop_src_File = & $selected_Filelist->get_next() )
		{
			if( ! isset( $new_names[$loop_src_File->get_md5_ID()] ) )
			{ // We have not yet provided a name to rename to...
				$confirmed = 0;
				$new_names[$loop_src_File->get_md5_ID()] = $loop_src_File->get_name();
				continue;
			}

			// Check if provided name is okay:
			$new_names[$loop_src_File->get_md5_ID()] = trim(strip_tags($new_names[$loop_src_File->get_md5_ID()]));

			if( !$loop_src_File->is_dir() )
			{
				if( $error_filename = validate_filename( $new_names[$loop_src_File->get_md5_ID()] ) )
				{ // Not a file name or not an allowed extension
					$confirmed = 0;
					$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', $error_filename );
					continue;
				}
			}
			elseif( $error_dirname = validate_dirname( $new_names[$loop_src_File->get_md5_ID()] ) )
			{ // Not a directory name
				$confirmed = 0;
				$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', $error_dirname );
				continue;
			}
		}

		if( $confirmed )
		{ // Rename is confirmed, let's proceed:
			$selected_Filelist->restart();
			while( $loop_src_File = & $selected_Filelist->get_next() )
			{
				$old_name = $loop_src_File->get_name();
				$new_name = $new_names[$loop_src_File->get_md5_ID()];

				if( $new_name == $old_name )
				{ // Name has not changed...
					$Messages->add( sprintf( T_('&laquo;%s&raquo; has not been renamed'), $old_name ), 'note' );
					continue;
				}
				// Perform rename:
				if( ! $loop_src_File->rename_to( $new_name ) )
				{
					$Messages->add( sprintf( T_('&laquo;%s&raquo; could not be renamed to &laquo;%s&raquo;'),
						$old_name, $new_name ), 'error' );
					continue;
				}

				// We have moved in same dir, update caches:
				$fm_Filelist->update_caches();

				if( $fm_Filelist->contains( $loop_src_File ) === false )
				{ // File not in filelist (expected if not same dir)
					$fm_Filelist->add( $File );
				}


				$Messages->add( sprintf( T_('&laquo;%s&raquo; has been successfully renamed to &laquo;%s&raquo;'),
						$old_name, $new_name ), 'success' );
			}

			$action = 'list';
		}

		break;


	case 'delete':
		// Delete a file or directory: {{{
		if( ! $current_User->check_perm( 'files', 'edit' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$action = 'list';
			break;
		}

		if( ! $selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			$action = 'list';
			break;
		}

		param( 'confirmed', 'integer', 0 );
		// fplanque>> We cannot actually offer to delete subdirs since we cannot pre-check DB

		$selected_Filelist->restart();
		if( $confirmed )
		{ // Unlink files:
			while( $l_File = & $selected_Filelist->get_next() )
			{
				if( $l_File->unlink() )
				{
					$Messages->add( sprintf( ( $l_File->is_dir() ? T_('The directory &laquo;%s&raquo; has been deleted.') : T_('The file &laquo;%s&raquo; has been deleted.') ), $l_File->get_name() ), 'success' );
					$fm_Filelist->remove( $l_File );
				}
				else
				{
					$Messages->add( sprintf( ( $l_File->is_dir() ? T_('Could not delete the directory &laquo;%s&raquo; (not empty?).') : T_('Could not delete the file &laquo;%s&raquo;.') ), $l_File->get_name() ), 'error' );
				}
			}
			$action = 'list';
		}
		else
		{
			// make sure we have loaded metas for all files in selection!
			$selected_Filelist->load_meta();

			// Check if there are delete restrictions on the files:
			while( $l_File = & $selected_Filelist->get_next() )
			{
				// Check if there are delete restrictions on this file:
				$l_File->check_relations( 'delete_restrictions' );

				if( $Messages->count('restrict') )
				{ // There are restrictions:
					$Messages->add( $l_File->get_prefixed_name().': '.T_('cannot be deleted because of the following relations')
						.$Messages->display( NULL, NULL, false, 'restrict', '', 'ul', false ) );
					$Messages->clear( 'restrict' );

					// remove it from the list of selected files (that will be offered to delete):
					$selected_Filelist->remove( $l_File );
				}
			}

			if( ! $selected_Filelist->count() )
			{ // no files left in list, cancel action
				$action = 'list';
			}
		}
		// }}}
		break;


	case 'edit_properties':
		// Edit File properties (Meta Data); this starts the file_properties mode:
		$fm_mode = 'file_properties';
		break;


	case 'update_properties':
		// Update File properties (Meta Data); on success this ends the file_properties mode: {{{

		if( ! $current_User->check_perm( 'files', 'edit' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$action = 'list';
			break;
		}

		$edit_File = & $selected_Filelist->get_by_idx(0);
		// Load meta data:
		$edit_File->load_meta();

		$edit_File->set( 'title', param( 'title', 'string', '' ) );
		$edit_File->set( 'alt', param( 'alt', 'string', '' ) );
		$edit_File->set( 'desc', param( 'desc', 'string', '' ) );

		// Store File object into DB:
		if( $edit_File->dbsave() )
		{
			$Messages->add( sprintf( T_( 'File properties for &laquo;%s&raquo; have been updated.' ), $edit_File->get_name() ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_( 'File properties for &laquo;%s&raquo; have not changed.' ), $edit_File->get_name() ), 'note' );
		}

		// Leave special display mode:
		$fm_mode = NULL;
		// }}}
		break;


	case 'link':
		// Link File to Item (or other object if extended below):

		// Note: we are not modifying any file here, we're just linking it
		// we only need read perm on file, but we'll need write perm on destination object (to be checked below)

		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			break;
		}

		$edit_File = & $selected_Filelist->get_by_idx(0);

		if( isset($edited_Item) )
		{
			// TODO: check item EDIT permissions!
			$DB->begin();

			// Load meta data AND MAKE SURE IT IS CREATED IN DB:
			$edit_File->load_meta( true );

			// Let's make the link!
			$edited_Link = & new Link();
			$edited_Link->set( 'itm_ID', $edited_Item->ID );
			$edited_Link->set( 'file_ID', $edit_File->ID );
			$edited_Link->dbinsert();

			$DB->commit();

			$Messages->add( T_('Selected file has been linked to item.'), 'success' );
		}
		// Plug extensions/hacks here!
		else
		{	// No Item to link to - end link_item mode.
			$fm_mode = NULL;
		}
		break;


	case 'unlink':
		// Unlink File from Item (or other object if extended):

		// Note: we are not modifying any file here, we're just linking it
		// we only need read perm on file, but we'll need write perm on destination object (to be checked below)

		if( !isset( $edited_Link ) )
		{
			break;
		}

		// TODO: get Item (or other object) from Link to check perm

		// TODO: check item/object EDIT permissions!
		// Check that we have permission to edit item:
		// $current_User->check_perm( $perm_name, 'edit', true, $edited_Item->ID );

		// Delete from DB:
		$msg = sprintf( T_('Link from &laquo;%s&raquo; deleted.'), $edited_Link->Item->dget('title') );
		$edited_Link->dbdelete( true );
		unset( $edited_Link );
		$Messages->add( $msg, 'success' );
		break;


	case 'edit_perms':
		// Edit file or directory permissions: {{{
		if( ! $current_User->check_perm( 'files', 'edit' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$action = 'list';
			break;
		}

		if( ! $selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			$action = 'list';
			break;
		}


		param( 'perms', 'array', array() );
		param( 'edit_perms_default' ); // default value when multiple files are selected
		param( 'use_default_perms', 'array', array() ); // array of file IDs that should be set to default

		if( count( $use_default_perms ) && $edit_perms_default === '' )
		{
			$Request->param_error( 'edit_perms_default', T_('You have to give a default permission!') );
			break;
		}

		// form params
		$perms_read_readonly = is_windows();
		$field_options_read_readonly = array(
				array( 'value' => 444, 'label' => T_('Read-only') ),
				array( 'value' => 666, 'label' => T_('Read and write') ) );
		$more_than_one_selected_file = ( $selected_Filelist->count() > 1 );

		if( count( $perms ) || count( $use_default_perms ) )
		{ // New permissions given, change them
			$selected_Filelist->restart();
			while( $l_File = & $selected_Filelist->get_next() )
			{
				if( in_array( $l_File->get_md5_ID(), $use_default_perms ) )
				{ // use default
					$chmod = $edit_perms_default;
				}
				elseif( !isset($perms[ $l_File->get_md5_ID() ]) )
				{ // happens for an empty text input or when no radio option is selected
					$Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; have not been changed.'), $l_File->get_name() ), 'note' );
					continue;
				}
				else
				{ // provided for this file
					$chmod = $perms[ $l_File->get_md5_ID() ];
				}

				$oldperms = $l_File->get_perms( 'raw' );
				$newperms = $l_File->chmod( $chmod );

				if( $newperms === false )
				{
					$Messages->add( sprintf( T_('Failed to set permissions on &laquo;%s&raquo; to &laquo;%s&raquo;.'), $l_File->get_name(), $chmod ), 'error' );
				}
				else
				{
					// Success, remove the file from the list of selected files:
					$selected_Filelist->remove( $l_File );

					if( $newperms === $oldperms )
					{
						$Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; have not been changed.'), $l_File->get_name() ), 'note' );
					}
					else
					{
						$Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; changed to &laquo;%s&raquo;.'), $l_File->get_name(), $l_File->get_perms() ), 'success' );
					}
				}
			}
		}

		if( !$selected_Filelist->count() )
		{ // No file left selected... (everything worked fine)
			$action = 'list';
		}
		// }}}
		break;
}


// pre_dump( 'fm mode:'.$fm_mode );

switch( $fm_mode )
{ // handle modes {{{

	case 'file_properties':
		$edit_File = & $selected_Filelist->get_by_idx(0);
		// Load meta data:
		$edit_File->load_meta();
		break;


	case 'file_upload':
		// {{{
		/*
		 * upload mode
		 */
		// Check permissions:
		if( ! $Settings->get('upload_enabled') )
		{ // Upload is globally disabled
			$Messages->add( T_('Upload is disabled.'), 'error' );
			$fm_mode = NULL;
			break;
		}

		if( ! $current_User->check_perm( 'files', 'add' ) )
		{ // We do not have permission to add files
			$Messages->add( T_('You have no permission to add/upload files.'), 'error' );
			$fm_mode = NULL;
			break;
		}

		// Quick mode means "just upload and leave mode when successful"
		$Request->param( 'upload_quickmode', 'integer', 0 );

		/**
		 * @var array Remember failed files (and the error messages)
		 */
		$failedFiles = array();

		// Process uploaded files:
		if( isset($_FILES) && count( $_FILES ) )
		{ // Some files have been uploaded:
			param( 'uploadfile_title', 'array', array() );
			param( 'uploadfile_alt', 'array', array() );
			param( 'uploadfile_desc', 'array', array() );
			param( 'uploadfile_name', 'array', array() );

			foreach( $_FILES['uploadfile']['name'] as $lKey => $lName )
			{
				if( empty( $lName ) )
				{ // No file name
					if( $upload_quickmode )
					{
						$Messages->add( T_( 'Please select a local file to upload.' ) );
					}
					elseif( !empty( $uploadfile_title[$lKey] )
					     || !empty( $uploadfile_alt[$lKey] )
					     || !empty( $uploadfile_desc[$lKey] )
					     || !empty( $uploadfile_name[$lKey] ) )
					{ // User specified params but NO file!!!
						// Remember the file as failed when additional info provided.
						$failedFiles[$lKey] = T_( 'Please select a local file to upload.' );
					}
					// Abort upload for this file:
					continue;
				}

				if( $Settings->get( 'upload_maxkb' )
				    && $_FILES['uploadfile']['size'][$lKey] > $Settings->get( 'upload_maxkb' )*1024 )
				{ // bigger than defined by blog
					$failedFiles[$lKey] = sprintf(
							/* TRANS: %s will be replaced by the difference */ T_('The file is %s too large. Maximum allowed is: %s.'),
							bytesreadable( $_FILES['uploadfile']['size'][$lKey] - $Settings->get( 'upload_maxkb' ) ),
							bytesreadable($Settings->get( 'upload_maxkb' )*1024) );
					// Abort upload for this file:
					continue;
				}

				if( $_FILES['uploadfile']['error'][$lKey] )
				{ // PHP has detected an error!:
					switch( $_FILES['uploadfile']['error'][$lKey] )
					{
						case UPLOAD_ERR_FORM_SIZE:
							// The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.

							// This can easily be changed, so we do not use it.. file size gets checked for real just above.
							break;

						case UPLOAD_ERR_INI_SIZE: // bigger than allowed in php.ini
							$failedFiles[$lKey] = T_('The file exceeds the upload_max_filesize directive in php.ini.');
							// Abort upload for this file:
							continue;

						case UPLOAD_ERR_PARTIAL:
							$failedFiles[$lKey] = T_('The file was only partially uploaded.');
							// Abort upload for this file:
							continue;

						case UPLOAD_ERR_NO_FILE:
							// Is probably the same as empty($lName) before.
							$failedFiles[$lKey] = T_('No file was uploaded.');
							// Abort upload for this file:
							continue;

						case 6: // numerical value of UPLOAD_ERR_NO_TMP_DIR
						# (min_php: 4.3.10, 5.0.3) case UPLOAD_ERR_NO_TMP_DIR:
							// Missing a temporary folder.
							$failedFiles[$lKey] = T_('Missing a temporary folder (upload_tmp_dir in php.ini).');
							// Abort upload for this file:
							continue;

						default:
							$failedFiles[$lKey] = T_('Unknown error.').' #'.$_FILES['uploadfile']['error'][$lKey];
							// Abort upload for this file:
							continue;
					}
				}

				if( !is_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey] ) )
				{ // Ensure that a malicious user hasn't tried to trick the script into working on files upon which it should not be working.
					$failedFiles[$lKey] = T_('The file does not seem to be a valid upload!');
					// Abort upload for this file:
					continue;
				}

				// Use new name on server if specified:
				$newName = !empty( $uploadfile_name[ $lKey ] ) ? $uploadfile_name[ $lKey ] : $lName;

				if( $error_filename = validate_filename( $newName ) )
				{ // Not a file name or not an allowed extension
					$failedFiles[$lKey] = $error_filename;
					// Abort upload for this file:
					continue;
				}

				// Get File object for requested target location:
				$newFile = & $FileCache->get_by_root_and_path( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID(), $fm_Filelist->get_rds_list_path().$newName, true );

				if( $newFile->exists() )
				{ // The file already exists in the target location!
					// TODO: Rename/Overwriting (save as filename_<numeric_extension> and provide interface to confirm, rename or overwrite)
					$failedFiles[$lKey] = sprintf( T_('The file &laquo;%s&raquo; already exists.'), $newFile->get_name() );
					// Abort upload for this file:
					continue;
				}

				// Attempt to move the uploaded file to the requested target location:
				if( !move_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey], $newFile->get_full_path() ) )
				{
					$failedFiles[$lKey] = T_('An unknown error occurred when moving the uploaded file on the server.');
					// Abort upload for this file:
					continue;
				}

				// change to default chmod settings
				if( $newFile->chmod( NULL ) === false )
				{ // add a note, this is no error!
					$Messages->add( sprintf( T_('Could not change permissions of &laquo;%s&raquo; to default chmod setting.'), $newFile->get_name() ), 'note' );
				}

				$success_msg = sprintf( T_('The file &laquo;%s&raquo; has been successfully uploaded.'), $newFile->get_name() );
				if( $mode == 'upload' )
				{
					// TODO: Add plugin hook to allow generating JS insert code(s)
					$img_tag = format_to_output( $newFile->get_tag(), 'formvalue' );
					$success_msg .=
						'<ul>'
							.'<li>'.T_("Here's the code to display it:").' <input type="text" value="'.$img_tag.'" /></li>'
							.'<li><a href="#" onclick="if( window.focus && window.opener ){ window.opener.focus(); textarea_replace_selection( window.opener.document.item_checkchanges.itemform_post_content, \''.format_to_output( $newFile->get_tag(), 'formvalue' ).'\', window.opener.document ); } return false;">'.T_('Add the code to your post !').'</a></li>'
						.'</ul>';
				}

				$Messages->add( $success_msg, 'success' );

				// Refreshes file properties (type, size, perms...)
				$newFile->load_properties();

				// Store extra info about the file into File Object:
				if( isset( $uploadfile_title[$lKey] ) )
				{ // If a title text has been passed... (does not happen in quick upload mode)
					$newFile->set( 'title', trim( strip_tags($uploadfile_title[$lKey])) );
				}
				if( isset( $uploadfile_alt[$lKey] ) )
				{ // If an alt text has been passed... (does not happen in quick upload mode)
					$newFile->set( 'alt', trim( strip_tags($uploadfile_alt[$lKey])) );
				}
				if( isset( $uploadfile_desc[$lKey] ) )
				{ // If a desc text has been passed... (does not happen in quick upload mode)
					$newFile->set( 'desc', trim( strip_tags($uploadfile_desc[$lKey])) );
				}

				// Store File object into DB:
				$newFile->dbsave();

				// Tell the filamanager about the new file:
				$fm_Filelist->add( $newFile );
			}

			if( $upload_quickmode && !$failedFiles )
			{ // we're quick uploading and have no failed files, leave the mode
				$fm_mode = NULL;
			}
		}

		// }}}
		break;


	case 'file_properties':
		if( empty($edit_File) )
		{
			$fm_mode = NULL;
		}
		break;


	case 'file_copy':
	case 'file_move':
		// ------------------------
		// copy/move a file:
		// ------------------------
		/*
		 * fplanque>> This whole thing is flawed:
		 * 1) only geeks can possibly like to use the same interface for renaming, moving and copying
		 * 2) even the geeky unix commands won't pretend copying and moving are the same thing. They are not!
		 *    Only moving and renaming are similar, and again FOR GEEKS ONLY.
		 * 3) The way this works it breaks the File meta data (I'm working on it).
		 * 4) For Move and Copy, this should use a "destination directory tree" on the right (same as for upload)
		 * 5) Given all the reasons above copy, move and rename should be clearly separated into 3 different interfaces.
		 *
		 * blueyed>> it was never meant to only use a single interface. The original mode
		 *   'file_cmr' was just a mode to handle it internally easier/more central.
		 *   'copy' is just 'move and keep the source', while 'rename' is 'move in the same dir'
		 *
		 */

		/*
		TODO: On error notes use prefixed names, if the roots differ.
		      Something like $fm_Filelist->get_names_realtive_to( $a_File, $b_File, $root_type, $root_ID, $rel_path )
		      that returns an array containing the name of $a_File and $b_File relative to the Root path given as
		      param 3, 4, 5.
		      This would allow to say "Copied «users/admin/test_me.jpg» to «test_me.jpg»." rather than just
		      "Copied «test_me.jpg» to «test_me.jpg».".
			// fp>> I don't really understand this (probably missing a verb) but I do think that extending the Fileman object is not the right direction to go on the long term
			// blueyed>> Tried to make it clearer. If it wasn't a Filemanager method, it has to be a function or
			//   a method of the File class. IMHO it should be a method of the (to be killed) Filemanager object.
			// fp>> Okay. It should *definitely* be a method of the File object and we should ask for ONE file at a time. Any question about 'where is the file?' (what/where/when/who, etc) should be asked to the File object itself.
		*/

		if( ! $current_User->check_perm( 'files', 'edit' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$fm_mode = NULL;
			break;
		}

		if( ! $fm_source_Filelist || ! $fm_source_Filelist->count() )
		{
			$Messages->add( T_('No source files!'), 'error' );
			$fm_mode = NULL;
			break;
		}

		param( 'confirm', 'integer', 0 );
		param( 'new_names', 'array', array() );
		param( 'overwrite', 'array', array() );

		// Check params for each file to rename:
		while( $loop_src_File = & $fm_source_Filelist->get_next() )
		{
			if( ! $loop_src_File->exists() )
			{ // this can happen on reloading the page
				$fm_source_Filelist->remove($loop_src_File);
				continue;
			}
			if( ! isset( $new_names[$loop_src_File->get_md5_ID()] ) )
			{ // We have not yet provided a name to rename to...
				$confirm = 0;
				$new_names[$loop_src_File->get_md5_ID()] = $loop_src_File->get_name();
				continue;
			}

			// Check if provided name is okay:
			$new_names[$loop_src_File->get_md5_ID()] = trim(strip_tags($new_names[$loop_src_File->get_md5_ID()]));

			if( !$loop_src_File->is_dir() )
			{
				if( $error_filename = validate_filename( $new_names[$loop_src_File->get_md5_ID()] ) )
				{ // Not a file name or not an allowed extension
					$confirm = 0;
					$Messages->add( $error_filename , 'error' );
					continue;
				}
			}
			elseif( $error_dirname = validate_dirname( $new_names[$loop_src_File->get_md5_ID()] ) )
			{ // Not a directory name
				$confirm = 0;
				$Messages->add( $error_dirname, 'error' );
				continue;
			}

			// Check if destination file exists:
			if( ($dest_File = & $FileCache->get_by_root_and_path( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID(), $fm_Filelist->get_rds_list_path().$new_names[$loop_src_File->get_md5_ID()] ))
							&& $dest_File->exists() )
			{ // Target exists
				if( $dest_File === $loop_src_File )
				{
					$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', T_('Source and target files are the same. Please choose another name or directory.') );
					$confirm = 0;
					continue;
				}

				if( ! isset( $overwrite[$loop_src_File->get_md5_ID()] ) )
				{ // We have not yet asked to overwrite:
					$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('The file &laquo;%s&raquo; already exists.'), $dest_File->get_rdfp_rel_path() ) );
					$overwrite[$loop_src_File->get_md5_ID()] = 0;
					$confirm = 0;
					continue;
				}

				// We have asked to overwite...
				if( $fm_mode == 'file_copy' )
				{ // We are making a copy: no problem, we'll recycle the file ID anyway.
					continue;
				}

				// We are moving, we'll need to unlink the target file and drop it's meta data:
				// Check if there are delete restrictions on this file:
				$dest_File->check_relations( 'delete_restrictions' );

				if( $Messages->count('restrict') )
				{ // There are restrictions:
					// TODO: work on a better output display here...
					$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('Cannot overwrite the file &laquo;%s&raquo; because of the following relations'), $dest_File->get_rdfp_rel_path() ) );

					$confirm = 0;
					break;	// stop whole file list processing
				}
			}
		}

		if( $confirm && $fm_source_Filelist->count() )
		{ // Copy/move is confirmed (and we still have files to copy/move), let's proceed:

			// Loop through files:
			$fm_source_Filelist->restart();
			while( $loop_src_File = & $fm_source_Filelist->get_next() )
			{
				// Get a pointer on dest file
				$dest_File = & $FileCache->get_by_root_and_path( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID(), $fm_Filelist->get_rds_list_path().$new_names[$loop_src_File->get_md5_ID()] );

				if( $fm_mode == 'file_copy' )
				{ // COPY

					// Do the copy
					if( $loop_src_File->copy_to( $dest_File ) )
					{ // Success:
						$Messages->add( sprintf( T_('Copied &laquo;%s&raquo; to &laquo;%s&raquo;.'),
																		$loop_src_File->get_rdfp_rel_path(), $dest_File->get_rdfp_rel_path() ), 'success' );

						if( $fm_Filelist->contains( $dest_File ) === false )
						{
							$fm_Filelist->add( $dest_File );
						}
					}
					else
					{ // Failure:
						$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('Could not copy &laquo;%s&raquo; to &laquo;%s&raquo;.'),
																		$loop_src_File->get_rdfp_rel_path(), $dest_File->get_rdfp_rel_path() ) );
					}
				}
				elseif( $fm_mode == 'file_move' )
				{ // MOVE
					// NOTE: DB integrity is handled by the File object itself
					$DB->begin();

					if( isset( $overwrite[$loop_src_File->get_md5_ID()] )
							&& $overwrite[$loop_src_File->get_md5_ID()] )
					{ // We want to overwrite, let's unlink the old file:
						if( ! $dest_File->unlink() )
						{ // Unlink failed:
							$DB->rollback();

							$Messages->add( sprintf( ( $dest_File->is_dir() ? T_('Could not delete the directory &laquo;%s&raquo; (not empty?).') : T_('Could not delete the file &laquo;%s&raquo;.') ), $dest_File->get_name() ), 'error' );

							// Move on to next file:
							continue;
						}
					}

					// Do the move:
					$rdfp_oldpath = $loop_src_File->get_rdfp_rel_path();
					$rdfp_newpath = $fm_Filelist->get_rds_list_path().$new_names[$loop_src_File->get_md5_ID()];

					if( $loop_src_File->move_to( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID(), $rdfp_newpath ) )
					{ // successfully moved
						$Messages->add( sprintf( T_('Moved &laquo;%s&raquo; to &laquo;%s&raquo;.'), $rdfp_oldpath, $rdfp_newpath ), 'success' );

						// We may have moved in same dir, update caches:
						$fm_Filelist->update_caches();
						// We remove the file from the source list, after refreshing the cache
						$fm_source_Filelist->update_caches();
						$fm_source_Filelist->remove( $loop_src_File );

						if( $fm_Filelist->contains( $loop_src_File ) === false )
						{ // File not in filelist (expected if not same dir)
							$fm_Filelist->add( $loop_src_File );
						}
					}
					else
					{ // move failed
						$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('Could not move &laquo;%s&raquo; to &laquo;%s&raquo;.'), $rdfp_oldpath, $rdfp_newpath ) );
						// Note: we do not rollback, since unlinking is already done on disk :'(
					}

					$DB->commit();
				}
				else debug_die( 'Unhandled file copy/move mode' );
			}
		}

		if( $fm_source_Filelist->count() )
		{ // There are still uncopied/unmoved files, we want the file manager in this mode:
			$fm_forceFM = true;
		}
		else
		{
			// Leave mode:
			$fm_mode = NULL;
		}
		break;


	case 'link_item':
		// We want to link file(s) to an item:

		// TODO: maybe this should not be a mode and maybe we should handle linking as soon as we have an $edited_Item ...

		if( !isset($edited_Item) )
		{ // No Item to link to...
			$fm_mode = NULL;
			break;
		}

		// TODO: check EDIT permissions!

		// we want to display the file manager in this mode:
		$fm_forceFM = true;
		break;

} // }}}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


// Display reload-icon in the opener window if we're a popup in the same CWD and the
// Filemanager content differs.
?>

<script type="text/javascript">
	<!--
	if( opener
			&& opener.document.FilesForm
			&& typeof(opener.document.FilesForm.md5_filelist.value) != 'undefined'
			&& typeof(opener.document.FilesForm.md5_cwd.value) != 'undefined'
			&& opener.document.FilesForm.md5_cwd.value == '<?php echo md5($fm_Filelist->get_ads_list_path()); ?>'
		)
	{
		opener.document.getElementById( 'fm_reloadhint' ).style.display =
			opener.document.FilesForm.md5_filelist.value == '<?php echo $fm_Filelist->md5_checksum(); ?>'
			? 'none'
			: 'inline';
	}
	// -->
</script>


<?php
/*
 * Display payload:
 */
switch( $action )
{
	case 'rename':
		// Rename files dialog:
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'files/_files_rename.form.php' );
		$AdminUI->disp_payload_end();
		break;

	case 'delete':
		// Delete file(s). We arrive here either if not confirmed or in case of error(s).
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'files/_files_delete.form.php' );
		$AdminUI->disp_payload_end();
		break;

	case 'download':
		// Delete file(s). We arrive here either if not confirmed or in case of error(s).
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'files/_files_download.form.php' );
		$AdminUI->disp_payload_end();
		break;


	case 'edit_perms':
		// Filesystem permissions for specific files
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'files/_files_permissions.form.php' );
		$AdminUI->disp_payload_end();
		break;

	default:
		// Deferred action message:
		if( isset($action_title) )
		{
			echo "\n<h2>$action_title</h2>\n";
		}

		if( isset($action_msg) )
		{
			echo $action_msg;

			if( isset( $js_focus ) )
			{ // we want to auto-focus a field
				echo '
				<script type="text/javascript">
					<!--
					'.$js_focus.'.focus();
					// -->
				</script>';
			}
		}
}


// FM modes displays:
switch( $fm_mode )
{
	case 'file_copy':
	case 'file_move':
		// CMR dialog:
		$AdminUI->disp_view( 'files/_files_cmr.inc.php' );
		break;

	case 'file_upload':
		// Upload dialog:
		$AdminUI->disp_view( 'files/_files_upload.inc.php' );
		break;

	case 'file_properties':
		// File properties (Meta data) dialog:
		$AdminUI->disp_view( 'files/_file_properties.inc.php' );
		break;

	case 'link_item':
		// Links dialog:
		$AdminUI->disp_payload_begin();
		$AdminUI->disp_view( 'files/_files_links.inc.php' );
		$AdminUI->disp_payload_end();
		break;
}


// "Display Filemanager" link, if appropriate
// fp>> This needs SERIOUS documentation !
$disp_fm_browser = true;
$disp_fm_browser_toggle = false;

if( isset($fm_forceFM) )
{ // display FM, but no "close" icon
	$disp_fm_browser = $fm_forceFM;
}
elseif( $fm_mode && ! $UserSettings->get('fm_forceFM') )
{
	$disp_fm_browser = $fm_disp_browser;
	$disp_fm_browser_toggle = true;
}

if( $disp_fm_browser_toggle && ! $disp_fm_browser )
{ // FM browser can be toggled - link to display (link to hide it gets displayed in _files_browse view)
	echo '<div class="panelinfo" id="FM_anchor">';
	echo '[<a href="'.regenerate_url( 'fm_disp_browser', 'fm_disp_browser=1' ).'">'.T_('Display Filemanager').'</a>]';
	echo '</div>';
}


if( $disp_fm_browser )
{ // We're NOT in a mode where we want to hide the FM
	// -------------------
	// Browsing interface:
	// -------------------

	// Display VIEW:
	$AdminUI->disp_view( 'files/_files_browse.inc.php' );
}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * {{{ Revision log:
 * $Log$
 * Revision 1.20  2006/04/19 20:13:49  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.19  2006/04/14 19:33:29  fplanque
 * evocore sync
 *
 * Revision 1.18  2006/04/13 00:10:52  blueyed
 * cleanup
 *
 * Revision 1.17  2006/04/12 19:12:58  fplanque
 * partial cleanup
 *
 * Revision 1.16  2006/03/26 20:42:48  blueyed
 * Show Filelist dirtree by default and save it into UserSettings
 *
 * Revision 1.15  2006/03/26 19:44:43  blueyed
 * Filelist::include_files/include_dirs added; normalization
 *
 * Revision 1.14  2006/03/26 14:00:49  blueyed
 * Made Filelist constructor more decent
 *
 * Revision 1.13  2006/03/26 13:44:51  blueyed
 * Sort filelist after creating files/dirs; display $create_name in input box again;
 *
 * Revision 1.12  2006/03/26 03:06:24  blueyed
 * When there's only one selected file for download, use its filename as base for archive.
 *
 * Revision 1.11  2006/03/26 02:57:24  blueyed
 * Get param for dirtree early.
 *
 * Revision 1.10  2006/03/26 02:37:57  blueyed
 * Directory tree next to files list.
 *
 * Revision 1.9  2006/03/17 20:57:26  fplanque
 * just in case... (being a little paranoid with file management may save your day someday :P)
 *
 * Revision 1.8  2006/03/16 19:26:04  fplanque
 * Fixed & simplified media dirs out of web root.
 *
 * Revision 1.7  2006/03/16 18:44:39  blueyed
 * Removed redundant (never reached) permission check.
 *
 * Revision 1.6  2006/03/13 21:20:53  blueyed
 * fixed UserSettings::param_Request()
 *
 * Revision 1.5  2006/03/12 23:08:56  fplanque
 * doc cleanup
 *
 * Revision 1.4  2006/03/12 20:51:53  blueyed
 * Moved Request::param_UserSettings() to UserSettings::param_Request()
 *
 * Revision 1.2  2006/03/12 03:03:32  blueyed
 * Fixed and cleaned up "filemanager".
 *
 * Revision 1.1  2006/02/23 21:11:56  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.162  2006/02/13 20:20:09  fplanque
 * minor / cleanup
 *
 * Revision 1.161  2006/02/13 01:05:20  blueyed
 * Fix IDs to the item textarea.
 *
 * Revision 1.159  2006/02/11 21:29:46  fplanque
 * Fixed display of link_item mode
 *
 * Revision 1.158  2006/02/10 22:05:07  fplanque
 * Normalized itm links
 *
 * Revision 1.157  2006/01/20 00:39:17  blueyed
 * Refactorisation/enhancements to filemanager.
 *
 * Revision 1.156  2006/01/20 00:07:26  blueyed
 * 1-2-3-4 scheme for files.php again. Not fully tested.
 *
 * Revision 1.155  2006/01/11 22:09:29  blueyed
 * Reactive "download selected files as zip", also as a "workaround" to always have an icon next to "With selected files:".. ;)
 *
 * Revision 1.154  2006/01/11 17:32:53  fplanque
 * wording / translation
 *
 * Revision 1.153  2006/01/02 19:43:57  fplanque
 * just a little new year cleanup
 *
 * Revision 1.152  2005/12/19 04:36:16  blueyed
 * Fix using textarea_replace_selection() for IE from a popup.
 *
 * Revision 1.151  2005/12/16 16:59:12  blueyed
 * (Optional) File owner and group columns in Filemanager.
 *
 * Revision 1.150  2005/12/15 19:00:40  blueyed
 * Another hard to merge fix. When a filename is invalid handle it like all other upload errors.
 *
 * Revision 1.149  2005/12/14 19:36:15  fplanque
 * Enhanced file management
 *
 * Revision 1.148  2005/12/12 19:44:09  fplanque
 * Use cached objects by reference instead of copying them!!
 *
 * Revision 1.147  2005/12/12 17:57:22  fplanque
 * fixed bug with delete (when some links prevent deletion)
 *
 * Revision 1.146  2005/12/12 16:40:17  fplanque
 * fixed quick upload
 *
 * Revision 1.145  2005/12/10 03:02:50  blueyed
 * Quick upload mode merged from post-phoenix
 *
 * Revision 1.144  2005/12/06 00:01:56  blueyed
 * Unset action/fm_mode when no FileRoot available.
 *
 * Revision 1.142  2005/12/04 15:49:20  blueyed
 * More descriptive error message when no perms for 'files'/'view'.
 *
 * Revision 1.141  2005/11/27 08:48:41  blueyed
 * fix editing file properties (there were two 'case's)..
 *
 * Revision 1.140  2005/11/27 06:36:01  blueyed
 * Use deprecated LogUpload for phoenix, because it's not 1-2-3-4 scheme here.
 *
 * Revision 1.139  2005/11/27 06:16:02  blueyed
 * Echo code to display uploaded file in 'upload' mode and allow to insert it through JS (0.9.1 behaviour).
 *
 * Revision 1.138  2005/11/24 18:32:10  blueyed
 * Use $Fileman to get (deprecated) chmod defaults, not $Filemanager, which does not exist.. :/
 *
 * Revision 1.137  2005/11/24 17:56:20  blueyed
 * chmod() the uploaded file
 *
 * Revision 1.135  2005/11/24 13:23:57  blueyed
 * debug_die() for invalid action (only if actionArray given [and invalid])
 *
 * Revision 1.134  2005/11/24 08:54:42  blueyed
 * debug_die() for invalid action; doc
 *
 * Revision 1.131  2005/11/22 04:41:38  blueyed
 * Fix permissions editing again
 *
 * Revision 1.130  2005/11/21 18:33:19  fplanque
 * Too many undiscussed changes all around: Massive rollback! :((
 * As said before, I am only taking CLEARLY labelled bugfixes.
 *
 * Revision 1.121  2005/11/14 16:43:43  blueyed
 * FGix actionArray
 *
 * Revision 1.120  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.119  2005/10/28 02:37:37  blueyed
 * Normalized AbstractSettings API
 *
 * Revision 1.117  2005/09/22 21:35:26  blueyed
 * Fixed another "Only variables can be passed by reference" notice (php4) / fatal error (php5)
 *
 * Revision 1.116  2005/08/12 17:37:14  fplanque
 * cleanup
 *
 * Revision 1.115  2005/07/26 18:56:21  fplanque
 * minor
 *
 * Revision 1.114  2005/07/26 18:50:48  fplanque
 * enhanced attached file handling
 *
 * Revision 1.113  2005/06/03 20:14:38  fplanque
 * started input validation framework
 *
 * Revision 1.112  2005/06/03 15:12:32  fplanque
 * error/info message cleanup
 *
 * Revision 1.110  2005/05/24 15:26:51  fplanque
 * cleanup
 *
 * Revision 1.109  2005/05/17 19:26:06  fplanque
 * FM: copy / move debugging
 *
 * Revision 1.108  2005/05/16 15:17:12  fplanque
 * minor
 *
 * Revision 1.107  2005/05/13 18:41:28  fplanque
 * made file links clickable... finally ! :P
 *
 * Revision 1.106  2005/05/13 16:49:17  fplanque
 * Finished handling of multiple roots in storing file data.
 * Also removed many full paths passed through URL requests.
 * No full path should ever be seen by the user (only the admins).
 *
 * Revision 1.105  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.104  2005/05/11 15:58:30  fplanque
 * cleanup
 *
 * Revision 1.103  2005/05/10 18:38:15  fplanque
 * cleaned up log message display (part 1)
 *
 * Revision 1.102  2005/05/09 16:09:38  fplanque
 * implemented file manager permissions through Groups
 *
 * Revision 1.101  2005/05/06 20:04:47  fplanque
 * added contribs
 * fixed filemanager settings
 * Removed checking against browser provided mime types (very unsecure!)
 *
 * Revision 1.100  2005/05/04 19:40:40  fplanque
 * cleaned up file settings a little bit
 *
 * Revision 1.99  2005/04/29 18:49:32  fplanque
 * Normalizing, doc, cleanup
 *
 * Revision 1.98  2005/04/28 20:44:18  fplanque
 * normalizing, doc
 *
 * Revision 1.97  2005/04/27 19:05:44  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.94  2005/04/21 18:01:29  fplanque
 * CSS styles refactoring
 *
 * Revision 1.93  2005/04/21 12:13:42  blueyed
 * doc
 *
 * Revision 1.92  2005/04/20 20:04:12  fplanque
 * visual cleanup of FM
 *
 * Revision 1.91  2005/04/19 16:23:01  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.89  2005/04/15 18:02:58  fplanque
 * finished implementation of properties/meta data editor
 * started implementation of files to items linking
 *
 * Revision 1.88  2005/04/14 19:57:52  fplanque
 * filemanager refactoring & cleanup
 * started implementation of properties/meta data editor
 * note: the whole fm_mode thing is not really desireable...
 *
 * Revision 1.87  2005/04/14 18:34:03  fplanque
 * filemanager refactoring
 *
 * Revision 1.86  2005/04/14 13:14:03  fplanque
 * copy / move / rename is such a bad thing :'(
 *
 * Revision 1.85  2005/04/13 18:31:27  fplanque
 * tried to make copy/move/rename work ...
 *
 * Revision 1.84  2005/04/13 17:48:21  fplanque
 * File manager refactoring
 * storing of file meta data through upload
 * displaying or metadate in previews
 *
 * Revision 1.83  2005/04/12 19:36:30  fplanque
 * File manager cosmetics
 *
 * Revision 1.82  2005/04/12 19:00:22  fplanque
 * File manager cosmetics
 *
 * Revision 1.81  2005/03/15 19:19:46  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.80  2005/03/08 15:23:52  fplanque
 * no message
 *
 * Revision 1.79  2005/03/07 00:06:17  blueyed
 * admin UI refactoring, part three
 *
 * Revision 1.78  2005/02/28 09:06:39  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.77  2005/02/27 20:34:49  blueyed
 * Admin UI refactoring
 *
 * Revision 1.76  2005/02/23 04:06:16  blueyed
 * minor
 *
 * Revision 1.75  2005/02/12 00:58:13  blueyed
 * fixed preg_split() better ($allowedFileExtensions, $allowedMimeTypes)
 *
 * Revision 1.74  2005/02/11 20:30:20  jwedgeco
 * Added copyright and license info. I forgot to add it on the last commit.
 *
 * Revision 1.73  2005/02/11 20:27:51  jwedgeco
 * I added a kludge. For some reason on my setup, the empty() php funtion doesn't work on $allowedFileExtensions when $settings->get(upload_allowedext) is blank.
 *
 * Revision 1.72  2005/02/08 01:06:52  blueyed
 * fileupload reworked, fixed "Display FM" / "Leave mode" buttons
 *
 * Revision 1.71  2005/01/27 13:34:57  fplanque
 * i18n tuning
 *
 * Revision 1.69  2005/01/26 17:55:23  blueyed
 * catching up..
 *
 * Revision 1.68  2005/01/14 17:39:02  blueyed
 * layout
 *
 * Revision 1.67  2005/01/13 20:27:42  blueyed
 * $mode
 *
 * Revision 1.66  2005/01/12 20:22:51  fplanque
 * started file/dataobject linking
 *
 * Revision 1.65  2005/01/12 17:55:51  fplanque
 * extracted browsing interface into separate file to make code more readable
 *
 * Revision 1.63  2005/01/09 05:36:39  blueyed
 * fileupload
 *
 * Revision 1.62  2005/01/08 12:05:50  blueyed
 * small bugfix
 *
 * Revision 1.61  2005/01/08 01:24:19  blueyed
 * filelist refactoring
 *
 * Revision 1.60  2005/01/06 15:45:36  blueyed
 * Fixes..
 *
 * Revision 1.59  2005/01/06 11:31:46  blueyed
 * bugfixes
 *
 * Revision 1.58  2005/01/06 10:15:46  blueyed
 * FM upload and refactoring
 * }}}
 */
?>