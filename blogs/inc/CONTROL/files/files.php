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
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Filelist
 * fp>> TODO: When the user is viewing details for a file he should (by default) not be presented with the filelist in addition to the file properties
 * In cases like that, we should try to avoid instanciating a Filelist.
 */
require_once $model_path.'files/_filelist.class.php';

/**
 * @var User
 */
global $current_User;

// Check global access permissions:
if( ! $Settings->get( 'fm_enabled' ) )
{
	bad_request_die( 'The filemanager is disabled.' );
}

// Check permission:
$current_User->check_perm( 'files', 'view', true );


$AdminUI->set_path( 'files', 'browse' );


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

$action = param_action();


// Get root:
$ads_list_path = false; // false by default, gets set if we have a valid root
$fm_FileRoot = NULL;

$FileRootCache = & get_Cache( 'FileRootCache' );

$available_Roots = $FileRootCache->get_available_FileRoots();

if( ! empty($root) )
{ // We have requested a root folder by string:
	$fm_FileRoot = & $FileRootCache->get_by_ID($root, true);

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
		$non_canonical_list_path = $fm_FileRoot->ads_path.$path;

		// Dereference any /../ just to make sure, and CHECK if directory exists:
		$ads_list_path = get_canonical_path( $non_canonical_list_path );

		if( !is_dir( $ads_list_path ) )
		{ // This should never happen, but just in case the diretory does not exist:
			$Messages->add( sprintf( T_('The directory &laquo;%s&raquo; does not exist.'), $path ), 'error' );
			$path = '';		// fp> added
			$ads_list_path = NULL;
		}
		elseif( ! preg_match( '#^'.preg_quote($fm_FileRoot->ads_path, '#').'#', $ads_list_path ) )
		{ // cwd is OUTSIDE OF root!
			$Messages->add( T_( 'You are not allowed to go outside your root directory!' ), 'error' );
			$path = '';		// fp> added
			$ads_list_path = $fm_FileRoot->ads_path;
		}
		elseif( $ads_list_path != $non_canonical_list_path )
		{	// We have reduced the absolute path, we should also reduce the relative $path (used in urls params)
			$path = get_canonical_path( $path );
		}
	}
}


// If there were errors, display them and exit (especially in case there's no valid FileRoot ($fm_FileRoot)):
if( $Messages->count('error') )
{
	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();

	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();
	$AdminUI->disp_payload_begin();
	$AdminUI->disp_payload_end();

	$AdminUI->disp_global_footer();
	exit();
}


/**
 * Filelist
 * fp>> TODO: When the user is viewing details for a file he should (by default) not be presented with the filelist in addition to the file properties
 * In cases like that, we should try to avoid instanciating a Filelist.
 */
$fm_Filelist = new Filelist( $fm_FileRoot, $ads_list_path );

if( $UserSettings->param_Request( 'fm_dirsnotattop', 'fm_dirsnotattop', 'integer', 0 ) )
{
	$fm_Filelist->_dirs_not_at_top = true;
}
if( $UserSettings->param_Request( 'fm_recursivedirsize', 'fm_recursivedirsize', 'integer', 0 ) ) // TODO: check for permission? (Server load)
{
	$fm_Filelist->_use_recursive_dirsize = true;
}
if( $UserSettings->param_Request( 'fm_showhidden', 'fm_showhidden', 'integer', 0 ) )
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
$Debuglog->add( 'path: '.var_export( $path, true ), 'files' );
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
	$UserSettings->set( 'fm_imglistpreview',   param( 'option_imglistpreview',   'integer', 0 ) );
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
$UserSettings->param_Request( 'fm_hide_dirtree', 'fm_hide_dirtree', 'integer', 0, true ); // The directory tree next to the files table


/*
 * Load editable objects:
 */
if( param( 'link_ID', 'integer', NULL, false, false, false ) )
{
	$LinkCache = & get_Cache( 'LinkCache' );
	if( ($edited_Link = & $LinkCache->get_by_ID( $link_ID, false )) === false )
	{	// We could not find the link to edit:
		$Messages->head = T_('Cannot edit link!');
		$Messages->add( T_('Requested link does not exist any longer.'), 'error' );
		unset( $edited_Link );
		forget_param( 'link_ID' );
		unset( $link_ID );
	}
}


/*
 * Load linkable objects:
 */
if( param( 'item_ID', 'integer', NULL, true, false, false ) )
{ // Load Requested iem:
	$ItemCache = & get_Cache( 'ItemCache' );
	if( ($edited_Item = & $ItemCache->get_by_ID( $item_ID, false )) === false )
	{	// We could not find the contact to link:
		$Messages->head = T_('Cannot link Item!');
		$Messages->add( T_('Requested item does not exist any longer.'), 'error' );
		unset( $edited_Item );
		forget_param( 'item_ID' );
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
 * The selected files
 * @var Filelist
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
		/**
		 * @var FileCache
		 */
		$FileCache = & get_Cache( 'FileCache' );
		/**
		 * @var File
		 */
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
		if( $error_filename = validate_filename( $create_name, $current_User->check_perm( 'files', 'all' ) ) )
		{ // Not valid filename or extension
			$Messages->add( $error_filename, 'error' );
			break;
		}

		// Try to get File object:
		$FileCache = & get_Cache( 'FileCache' );
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


	case 'download':
	{ // TODO: provide optional zip formats (tgz, ..) - the used lib provides more..
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
				param_error( 'zipname', T_('Please provide the name of the archive.') );
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
	}


	case 'rename':
		// Rename a file:
		// This will not allow to overwrite existing files, the same way Windows and MacOS do not allow it. Adding an option will only clutter the interface and satisfy geeks only.
		if( ! $current_User->check_perm( 'files', 'edit' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$action = 'list';
			break;
		}

		$allow_locked_filetypes = $current_User->check_perm( 'files', 'all' );

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
				if( $error_filename = validate_filename( $new_names[$loop_src_File->get_md5_ID()], $allow_locked_filetypes ) )
				{ // Not a file name or not an allowed extension
					$confirmed = 0;
					param_error( 'new_names['.$loop_src_File->get_md5_ID().']', $error_filename );
					continue;
				}
			}
			elseif( $error_dirname = validate_dirname( $new_names[$loop_src_File->get_md5_ID()], $allow_locked_filetypes ) )
			{ // directory name
				$confirmed = 0;
				param_error( 'new_names['.$loop_src_File->get_md5_ID().']', $error_dirname );
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
					$Messages->add( sprintf( ( $l_File->is_dir() ? T_('The directory &laquo;%s&raquo; has been deleted.') : T_('The file &laquo;%s&raquo; has been deleted.') ), $l_File->dget('name') ), 'success' );
					$fm_Filelist->remove( $l_File );
				}
				else
				{
					$Messages->add( sprintf( ( $l_File->is_dir() ? T_('Could not delete the directory &laquo;%s&raquo; (not empty?).') : T_('Could not delete the file &laquo;%s&raquo;.') ), $l_File->dget('name') ), 'error' );
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


	case 'make_posts':
		// Make posts with selected images:
		if( ! $selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			$action = 'list';
			break;
		}

		// fp> TODO: this block should move to a general level
		// Try to go to the right blog:
		if( $fm_Filelist->get_root_type() == 'collection' )
		{
			set_working_blog( $fm_Filelist->get_root_ID() );
      // Load the blog we're in:
			$Blog = & $BlogCache->get_by_ID( $blog );
		}
		// ---


		if( empty( $Blog ) )
		{
			$Messages->add( T_('No destination blog is selected.'), 'error' );
			break;
		}
		//$Blog->disp('name');

		// Get default status (includes PERM CHECK):
		$item_status = $Blog->get_allowed_item_status();
		if( empty($item_status) )
		{
			$Messages->add( T_('Sorry, you have no permission to post into this blog.'), 'error' );
			break;
		}

		// make sure we have loaded metas for all files in selection!
		$selected_Filelist->load_meta();

		// Ready to create posts:
		load_class( 'MODEL/items/_item.class.php' );
		while( $l_File = & $selected_Filelist->get_next() )
		{
			if( ! $l_File->is_image() )
			{
				$Messages->add( sprintf( T_('Cannot post &laquo;%s&raquo; because it is not an image.'), $l_File->dget('name') ), 'error' );
				continue;
			}

			// Create a post:
			$edited_Item = & new Item();

			$edited_Item->blog_ID = $blog;

			$edited_Item->set( 'status', $item_status );

			$edited_Item->set( 'main_cat_ID', $Blog->get_default_cat_ID() );

			$title = $l_File->get('title');
			if( empty($title) )
			{
				$title = $l_File->get('name');
			}
			$edited_Item->set( 'title', $title );

			$DB->begin();

			// INSERT NEW POST INTO DB:
			$edited_Item->dbinsert();

			// echo '<br>file meta: '.$l_File->meta;
			if(	$l_File->meta == 'notfound' )
			{	// That file has no meta data yet, create it now!
				$l_File->dbsave();
			}

			// Let's make the link!
			$edited_Link = & new Link();
			$edited_Link->set( 'itm_ID', $edited_Item->ID );
			$edited_Link->set( 'file_ID', $l_File->ID );
			$edited_Link->dbinsert();

			$DB->commit();

			$Messages->add( sprintf( T_('&laquo;%s&raquo; has been posted.'), $l_File->dget('name') ), 'success' );
		}

		// Note: we redirect without restoring filter. This should allow to see the new files.
		// &filter=restore
		header_redirect( 'admin.php?ctrl=items&blog='.$blog );	// Will save $Messages

		// Note: we have EXITED here. In case we remove the redir, we need this:

		// Reset stuff so it doesn't interfere with upcomming display
		unset( $edited_Item );
		unset( $edited_Link );
		$selected_Filelist = & new Filelist( $fm_Filelist->get_FileRoot(), false );
		break;


	case 'edit':
		// Edit Text File; this starts the file_edit mode:
		// fp> Note: This probably should not be a mode.
		$fm_mode = 'file_edit';
		break;


	case 'update_file':
		// Update File (Meta Data); on success this ends the file_edit mode:

		if( $demo_mode )
		{
			$Messages->add( 'Sorry, you cannot update files in demo mode!', 'error' );
			break;
		}

		// Check permission!
 		$current_User->check_perm( 'files', 'edit', true );

		$edit_File = & $selected_Filelist->get_by_idx(0);

		// Check that the file is editable:
		if( ! $edit_File->is_editable( $current_User->check_perm( 'files', 'all' ) ) )
		{
			$Messages->add( sprintf( T_( 'You are not allowed to edit &laquo;%s&raquo;.' ), $edit_File->dget('name') ), 'error' );
	 		// Leave special display mode:
			$fm_mode = NULL;
			break;
		}

		param( 'file_content', 'html', '', false );


    $full_path = $edit_File->get_full_path();
		if( $rsc_handle = fopen( $full_path, 'w+') )
		{
			fwrite( $rsc_handle, $file_content );
			fclose( $rsc_handle );
			$Messages->add( sprintf( T_( 'The file &laquo;%s&raquo; has been updated.' ), $edit_File->dget('name') ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_( 'The file &laquo;%s&raquo; could not be updated.' ), $edit_File->dget('name') ), 'error' );
		}

		// Leave special display mode:
		$fm_mode = NULL;
		break;


	case 'edit_properties':
		// Edit File properties (Meta Data); this starts the file_properties mode:
		// fp> Note: This probably should not be a mode.
		$fm_mode = 'file_properties';
		break;


	case 'update_properties':
		// Update File properties (Meta Data); on success this ends the file_properties mode: {{{

		// Check permission!
 		$current_User->check_perm( 'files', 'edit', true );

		$edit_File = & $selected_Filelist->get_by_idx(0);
		// Load meta data:
		$edit_File->load_meta();

		$edit_File->set( 'title', param( 'title', 'string', '' ) );
		$edit_File->set( 'alt', param( 'alt', 'string', '' ) );
		$edit_File->set( 'desc', param( 'desc', 'string', '' ) );

		// Store File object into DB:
		if( $edit_File->dbsave() )
		{
			$Messages->add( sprintf( T_( 'File properties for &laquo;%s&raquo; have been updated.' ), $edit_File->dget('name') ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_( 'File properties for &laquo;%s&raquo; have not changed.' ), $edit_File->dget('name') ), 'note' );
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
			// check item EDIT permissions:
			$current_User->check_perm( 'item', 'edit', true, $edited_Item );

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

		// get Item (or other object) from Link to check perm
		$edited_Item = & $edited_Link->Item;

		// Check that we have permission to edit item:
		$current_User->check_perm( 'item', 'edit', true, $edited_Item );

		// Delete from DB:
		$msg = sprintf( T_('Link from &laquo;%s&raquo; deleted.'), $edited_Link->Item->dget('title') );
		$edited_Link->dbdelete( true );
		unset( $edited_Link );
		forget_param( 'link_ID' );

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
			param_error( 'edit_perms_default', T_('You have to give a default permission!') );
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
					$Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; have not been changed.'), $l_File->dget('name') ), 'note' );
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
					$Messages->add( sprintf( T_('Failed to set permissions on &laquo;%s&raquo; to &laquo;%s&raquo;.'), $l_File->dget('name'), $chmod ), 'error' );
				}
				else
				{
					// Success, remove the file from the list of selected files:
					$selected_Filelist->remove( $l_File );

					if( $newperms === $oldperms )
					{
						$Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; have not been changed.'), $l_File->dget('name') ), 'note' );
					}
					else
					{
						$Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; changed to &laquo;%s&raquo;.'), $l_File->dget('name'), $l_File->get_perms() ), 'success' );
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

	case 'file_edit':
		// Check permission!
 		$current_User->check_perm( 'files', 'edit', true );

		$edit_File = & $selected_Filelist->get_by_idx(0);

		// Check that the file is editable:
		if( ! $edit_File->is_editable( $current_User->check_perm( 'files', 'all' ) ) )
		{
			$Messages->add( sprintf( T_( 'You are not allowed to edit &laquo;%s&raquo;.' ), $edit_File->dget('name') ), 'error' );
	 		// Leave special display mode:
			$fm_mode = NULL;
			break;
		}

		$full_path = $edit_File->get_full_path();
		if( $size = filesize($full_path) )
		{
			$rsc_handle = fopen( $full_path, 'r');
			$edit_File->content = fread( $rsc_handle, $size );
			fclose( $rsc_handle );
		}
		else
		{	// Empty file
			$edit_File->content = '';
		}
		break;

	case 'file_properties':
		// Check permission!
 		$current_User->check_perm( 'files', 'edit', true );

		$edit_File = & $selected_Filelist->get_by_idx(0);
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
		param( 'upload_quickmode', 'integer', 0 );

		/**
		 * Remember failed files (and the error messages)
		 * @var array
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
				$FileCache = & get_Cache( 'FileCache' );
				$newFile = & $FileCache->get_by_root_and_path( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID(), $fm_Filelist->get_rds_list_path().$newName, true );

				if( $newFile->exists() )
				{ // The file already exists in the target location!
					// TODO: Rename/Overwriting (save as filename_<numeric_extension> and provide interface to confirm, rename or overwrite)
					$failedFiles[$lKey] = sprintf( T_('The file &laquo;%s&raquo; already exists.'), $newFile->dget('name') );
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
					$Messages->add( sprintf( T_('Could not change permissions of &laquo;%s&raquo; to default chmod setting.'), $newFile->dget('name') ), 'note' );
				}

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

				$success_msg = sprintf( T_('The file &laquo;%s&raquo; has been successfully uploaded.'), $newFile->dget('name') );
				if( $mode == 'upload' )
				{
					// TODO: Add plugin hook to allow generating JS insert code(s)
					$img_tag = format_to_output( $newFile->get_tag(), 'formvalue' );
					$success_msg .=
						'<ul>'
							.'<li>'.T_("Here's the code to display it:").' <input type="text" value="'.$img_tag.'" /></li>'
							.'<li><a href="#" onclick="if( window.focus && window.opener ){ window.opener.focus(); textarea_replace_selection( window.opener.document.getElementById(\'itemform_post_content\'), \''.format_to_output( $newFile->get_tag(), 'formvalue' ).'\', window.opener.document ); } return false;">'.T_('Add the code to your post !').'</a></li>'
						.'</ul>';
				}

				$Messages->add( $success_msg, 'success' );

				// Store File object into DB:
				$newFile->dbsave();

				// Tell the filemanager about the new file:
				$fm_Filelist->add( $newFile );
			}

			if( $upload_quickmode && !$failedFiles )
			{ // we're quick uploading and have no failed files, leave the mode
				$fm_mode = NULL;
			}
		}

		// }}}
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
				$new_names[$loop_src_File->get_md5_ID()] = $loop_src_File->get('name');
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
			$FileCache = & get_Cache( 'FileCache' );
			if( ($dest_File = & $FileCache->get_by_root_and_path( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID(), $fm_Filelist->get_rds_list_path().$new_names[$loop_src_File->get_md5_ID()] ))
							&& $dest_File->exists() )
			{ // Target exists
				if( $dest_File === $loop_src_File )
				{
					param_error( 'new_names['.$loop_src_File->get_md5_ID().']', T_('Source and target files are the same. Please choose another name or directory.') );
					$confirm = 0;
					continue;
				}

				if( ! isset( $overwrite[$loop_src_File->get_md5_ID()] ) )
				{ // We have not yet asked to overwrite:
					param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('The file &laquo;%s&raquo; already exists.'), $dest_File->get_rdfp_rel_path() ) );
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
					param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('Cannot overwrite the file &laquo;%s&raquo; because of the following relations'), $dest_File->get_rdfp_rel_path() ) );

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
				$FileCache = & get_Cache( 'FileCache' );
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
						param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('Could not copy &laquo;%s&raquo; to &laquo;%s&raquo;.'),
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

							$Messages->add( sprintf( ( $dest_File->is_dir() ? T_('Could not delete the directory &laquo;%s&raquo; (not empty?).') : T_('Could not delete the file &laquo;%s&raquo;.') ), $dest_File->dget('name') ), 'error' );

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
						param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('Could not move &laquo;%s&raquo; to &laquo;%s&raquo;.'), $rdfp_oldpath, $rdfp_newpath ) );
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

	case 'file_edit':
		// File Edit dialog:
		$AdminUI->disp_view( 'files/_file_edit.form.php' );
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
 * $Log$
 * Revision 1.46  2007/01/07 05:28:15  fplanque
 * i18n wording
 *
 * Revision 1.45  2006/12/24 00:52:57  fplanque
 * Make posts with images - Proof of concept
 *
 * Revision 1.44  2006/12/23 22:53:11  fplanque
 * extra security
 *
 * Revision 1.43  2006/12/22 01:09:30  fplanque
 * cleanup
 *
 * Revision 1.42  2006/12/22 00:58:02  fplanque
 * fix
 *
 * Revision 1.40  2006/12/22 00:50:33  fplanque
 * improved path cleaning
 *
 * Revision 1.39  2006/12/12 19:39:07  fplanque
 * enhanced file links / permissions
 *
 * Revision 1.38  2006/12/07 23:13:10  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.37  2006/12/07 20:03:32  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.36  2006/12/07 15:23:42  fplanque
 * filemanager enhanced, refactored, extended to skins directory
 *
 * Revision 1.35  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.34  2006/09/30 16:55:57  blueyed
 * $create param for media dir handling, which allows to just get the dir, without creating it.
 *
 * Revision 1.33  2006/08/20 22:25:20  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.32  2006/08/20 20:12:32  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.31  2006/08/19 10:57:40  blueyed
 * doc fixes.
 *
 * Revision 1.30  2006/08/19 07:56:29  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.29  2006/07/28 18:27:10  blueyed
 * Basic image preview for image files in the file list
 *
 * Revision 1.28  2006/07/28 17:30:30  blueyed
 * Refer to itemform_post_content field by ID, as its form has no name anymore
 *
 * Revision 1.27  2006/07/17 01:53:12  blueyed
 * added param to UserSettings::param_Request
 *
 * Revision 1.26  2006/07/07 18:42:37  blueyed
 * After upload: First save the file properties, before generating the code to display it.
 *
 * Revision 1.25  2006/06/19 20:59:37  fplanque
 * noone should die anonymously...
 *
 * Revision 1.24  2006/06/13 21:49:15  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.21.2.3  2006/06/12 20:00:33  fplanque
 * one too many massive syncs...
 *
 * Revision 1.23  2006/05/30 22:36:05  blueyed
 * doc
 *
 * Revision 1.22  2006/05/29 19:30:13  fplanque
 * no message
 *
 * Revision 1.21  2006/05/12 21:53:37  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.20  2006/04/19 20:13:49  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.19  2006/04/14 19:33:29  fplanque
 * evocore sync
 */
?>