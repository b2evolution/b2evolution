<?php
/**
 * This file implements the UI controller for file management.
 *
 * NOTE: $fm_mode is used for modes. Modes stay visible when browsing to a different location.
 * Examples of modes: link item, copy file.
 * Actions disappear if you browse to a different location.
 * Examples of actions: file properties, file edit.
 *
 * fp>> Movr/copy should not be a mode (too geeky). All we need is a dir selection tree inside of upload and move.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Filelist
 * fp>> TODO: When the user is viewing details for a file he should (by default) not be presented with the filelist in addition to the file properties
 * In cases like that, we should try to avoid instanciating a Filelist.
 */
load_class( 'files/model/_filelist.class.php', 'FileList' );

/**
 * @var User
 */
global $current_User;

global $dispatcher;

global $blog;

// Check global access permissions:
if( ! $Settings->get( 'fm_enabled' ) )
{
	bad_request_die( 'The filemanager is disabled.' );
}

// Check permission:
$current_User->check_perm( 'files', 'view', true, $blog ? $blog : NULL );

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


/*
 * Load linkable objects:
 */
if( param( 'item_ID', 'integer', NULL, true, false, false ) )
{ // Load Requested iem:
	$ItemCache = & get_ItemCache();
	if( ($edited_Item = & $ItemCache->get_by_ID( $item_ID, false )) === false )
	{	// We could not find the contact to link:
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Item') ), 'error' );
		forget_param( 'item_ID' );
		unset( $item_ID );
	}
}

if( param( 'user_ID', 'integer', NULL, true, false, false ) )
{ // Load Requested user:
	$UserCache = & get_UserCache();
	if( ($edited_User = & $UserCache->get_by_ID( $user_ID, false )) === false )
	{	// We could not find the contact to link:
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('User') ), 'error' );
		unset( $edited_User );
		forget_param( 'user_ID' );
		unset( $user_ID );
	}
	else
	{	// Found User, check perm:
		if( $edited_User->ID != $current_User->ID )
		{	// if not editing himself, must have user edit permission:
			if( ! $current_User->check_perm( 'users', 'edit' ) )
			{
				$Messages->add( T_('No permission to edit this user.'), 'error' );
				unset( $edited_User );
				forget_param( 'user_ID' );
				unset( $user_ID );
			}
		}

	}
}


/**
 * @global string The file manager mode we're in ('fm_upload', 'fm_move')
 */
$fm_mode = param( 'fm_mode', 'string', NULL, true );

$action = param_action();
if( $action == 'group_action' )
{ // Get the real action from the select:
	$action = param( 'group_action', 'string', '' );
	// NOTE/TODO: dh> action "img_tag" appears to be unimplemented for non-JS
}

if( !empty($action) && substr( $fm_mode, 0, 5 ) != 'link_' )
{	// The only modes which can tolerate simultaneous actions at this time are link_* modes (item, user...)
	// file_move & file_copy shouldn't actually be modes
	$fm_mode = '';
}

// Name of the iframe we want some actions to come back to:
param( 'iframe_name', 'string', '', true );

// Get root:
$ads_list_path = false; // false by default, gets set if we have a valid root
/**
 * @var FileRoot
 */
$fm_FileRoot = NULL;

$FileRootCache = & get_FileRootCache();

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
elseif( !empty($edited_User) )
{	// We have a user, check if it already has a linked file in a particular root, in which case we want to use that root!
	// This is useful so users can find their existing avatar
	// Get list of attached files:
	/**
	 * @var File
	 */
	if( $avatar_File = & $edited_User->get_avatar_File() )
	{
		$fm_FileRoot = & $avatar_File->get_FileRoot();
		$path = dirname( $avatar_File->get_rdfs_rel_path() ).'/';
	}
}
elseif( !empty($edited_Item) )
{	// We have a post, check if it already has a linked file in a particular root, in which case we want to use that root!
	// This is useful when whlicking "attach files" from teh post edit screen: it takes you to the root where you have
	// already attached files from. Otherwise the next block below will default to the Blog's fileroot.
	// Get list of attached files:
	$FileList = $edited_Item->get_attachment_FileList( 1 );
	// Get first file:
	/**
	 * @var File
	 */
	$File = & $FileList->get_next();
	if( !empty( $File ) )
	{	// Obtain and use file root of first file:
		$fm_FileRoot = & $File->get_FileRoot();
		$path = dirname( $File->get_rdfs_rel_path() ).'/';
	}
}


if( empty($fm_FileRoot) && !empty($edited_User) )
{	// Still not set a root, try to get it for the edited User
	$fm_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $edited_User->ID );
	if( ! $fm_FileRoot || ! isset( $available_Roots[$fm_FileRoot->ID] ) )
	{ // Root not found or not in list of available ones
		$fm_FileRoot = false;
	}
}

if( empty($fm_FileRoot) && !empty($Blog) )
{	// Still not set a root, try to get it for the current Blog
	$fm_FileRoot = & $FileRootCache->get_by_type_and_ID( 'collection', $Blog->ID );
	if( ! $fm_FileRoot || ! isset( $available_Roots[$fm_FileRoot->ID] ) )
	{ // Root not found or not in list of available ones
		$fm_FileRoot = false;
	}
}


if( ! $fm_FileRoot )
{ // No root requested (or the requested is invalid),
	// get the first one available:
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

		// Make sure $root is set:
		$root = $fm_FileRoot->ID;

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


if( empty($ads_list_path) )
{ // We have no Root / list path, there was an error. Unset any action or mode.
	$action = 'nil';
	$fm_mode = NULL;

	$AdminUI->disp_html_head();
	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();
	$AdminUI->disp_global_footer();
	exit(0);
}

$Debuglog->add( 'root: '.var_export( $root, true ), 'files' );
$Debuglog->add( 'FM root: '.var_export( $fm_FileRoot, true ), 'files' );
$Debuglog->add( 'FM _ads_list_path: '.var_export( $ads_list_path, true ), 'files' );
$Debuglog->add( 'path: '.var_export( $path, true ), 'files' );


/**
 * A list of filepaths which are selected in the FM list.
 *
 * @todo fp> This could probably be further simpplified by using "fm_sources" for selections.
 * Note: fm_sources is better because it also handles sources/selections on a different fileroot
 *
 * @global array
 */
$fm_selected = param( 'fm_selected', 'array', array(), true );
$Debuglog->add( count($fm_selected).' selected files/directories', 'files' );
/**
 * The selected files (must be within current fileroot)
 *
 * @global Filelist
 */
$selected_Filelist = & new Filelist( $fm_FileRoot, false );
foreach( $fm_selected as $l_source_path )
{
	// echo '<br>'.$l_source_path;
	$selected_Filelist->add_by_subpath( urldecode($l_source_path), true );
}



/*
 * Load editable objects:
 */
if( param( 'link_ID', 'integer', NULL, false, false, false ) )
{
	$LinkCache = & get_LinkCache();
	if( ($edited_Link = & $LinkCache->get_by_ID( $link_ID, false )) === false )
	{	// We could not find the link to edit:
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Link') ), 'error' );
		unset( $edited_Link );
		forget_param( 'link_ID' );
		unset( $link_ID );
	}
}



// Check actions that need early processing:
if( $action == 'createnew' )
{
	// Check permission:
	$current_User->check_perm( 'files', 'add', true, $blog ? $blog : NULL );

	// create new file/dir
	param( 'create_type', 'string', true ); // 'file', 'dir'

	$action = ( $create_type == 'file' ? 'createnew_file' : 'createnew_dir'  );
}

switch( $action )
{
	case 'filter':
		$action = 'list';
		break;

	case 'filter_unset':
		// Clear filters!
		$fm_filter = '';
		$action = 'list';
		break;

	case 'createnew_dir':
		// We are probably comming from 'createnew' but there is no guarantee!
		// Check permission:
		$current_User->check_perm( 'files', 'add', true, $blog ? $blog : NULL );

		if( ! $Settings->get( 'fm_enable_create_dir' ) )
		{ // Directory creation is gloablly disabled:
			$Messages->add( T_('Directory creation is disabled.'), 'error' );
			break;
		}

		if( !	param( 'create_name', 'string', '' ) )
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
		$FileCache = & get_FileCache();
		/**
		 * @var File
		 */
		$newFile = & $FileCache->get_by_root_and_path( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, $path.$create_name );

		if( $newFile->exists() )
		{
			$Messages->add( sprintf( T_('The file &laquo;%s&raquo; already exists.'), $create_name ), 'error' );
			break;
		}

		if( ! $newFile->create( $create_type ) )
		{
			$Messages->add( sprintf( T_('Could not create directory &laquo;%s&raquo; in &laquo;%s&raquo;.'), $create_name, $fm_Filelist->_rds_list_path ), 'error' );
		}

		$Messages->add( sprintf( T_('The directory &laquo;%s&raquo; has been created.'), $create_name ), 'success' );

		header_redirect( regenerate_url( '', '', '', '&' ) );
		// $action = 'list';
		break;


	case 'createnew_file':
		// We are probably comming from 'createnew' but there is no guarantee!
		// Check permission:
		$current_User->check_perm( 'files', 'add', true, $blog ? $blog : NULL );

		if( ! $Settings->get( 'fm_enable_create_file' ) )
		{ // File creation is gloablly disabled:
			$Messages->add( T_('File creation is disabled.'), 'error' );
			break;
		}

		if( !	param( 'create_name', 'string', '' ) )
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
		$FileCache = & get_FileCache();
		$newFile = & $FileCache->get_by_root_and_path( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, $path.$create_name );

		if( $newFile->exists() )
		{
			$Messages->add( sprintf( T_('The file &laquo;%s&raquo; already exists.'), $create_name ), 'error' );
			break;
		}

		if( ! $newFile->create( $create_type ) )
		{
			$Messages->add( sprintf( T_('Could not create file &laquo;%s&raquo; in &laquo;%s&raquo;.'), $create_name, $fm_Filelist->_rds_list_path ), 'error' );
		}

		$Messages->add( sprintf( T_('The file &laquo;%s&raquo; has been created.'), $create_name ), 'success' );

		header_redirect( regenerate_url( '', '', '', '&' ) );
		// $action = 'list';
		break;


  case 'update_settings':
		// Update settings NOW since they may affect the FileList
		$UserSettings->set( 'fm_dirsnotattop',   1-param( 'option_dirsattop',        'integer', 0 ) );
		$UserSettings->set( 'fm_permlikelsl',      param( 'option_permlikelsl',      'integer', 0 ) );
		$UserSettings->set( 'fm_imglistpreview',   param( 'option_imglistpreview',   'integer', 0 ) );
		$UserSettings->set( 'fm_getimagesizes',    param( 'option_getimagesizes',    'integer', 0 ) );

		$UserSettings->set( 'fm_showtypes',        param( 'option_showtypes',        'integer', 0 ) );
		$UserSettings->set( 'fm_showdate',         param( 'option_showdate',         'string', 'compact' ) );
		$UserSettings->set( 'fm_showfsperms',      param( 'option_showfsperms',      'integer', 0 ) );
		$UserSettings->set( 'fm_showfsowner',      param( 'option_showfsowner',      'integer', 0 ) );
		$UserSettings->set( 'fm_showfsgroup',      param( 'option_showfsgroup',      'integer', 0 ) );

		$UserSettings->set( 'fm_showhidden',       param( 'option_showhidden',       'integer', 0 ) );
		$UserSettings->set( 'fm_recursivedirsize', param( 'option_recursivedirsize', 'integer', 0 ) );
		$UserSettings->set( 'fm_allowfiltering',   param( 'option_allowfiltering', 'string', 'simple' ) );

		if( $UserSettings->dbupdate() )
		{
			$Messages->add( T_('Your user settings have been updated.'), 'success' );
		}

		header_redirect( regenerate_url( '', '', '', '&' ) );
		// $action = 'list';
		break;

	case 'update_file':
		// Update File:

		if( $demo_mode )
		{
			$Messages->add( 'Sorry, you cannot update files in demo mode!', 'error' );
			break;
		}

		// Check permission!
 		$current_User->check_perm( 'files', 'edit', true, $blog ? $blog : NULL );

 		// Get the file we want to update:
		$edited_File = & $selected_Filelist->get_by_idx(0);

		// Check that the file is editable:
		if( ! $edited_File->is_editable( $current_User->check_perm( 'files', 'all' ) ) )
		{
			$Messages->add( sprintf( T_( 'You are not allowed to edit &laquo;%s&raquo;.' ), $edited_File->dget('name') ), 'error' );
			break;
		}

		param( 'file_content', 'html', '', false );


    $full_path = $edited_File->get_full_path();
		if( $rsc_handle = fopen( $full_path, 'w+') )
		{
			fwrite( $rsc_handle, $file_content );
			fclose( $rsc_handle );
			$Messages->add( sprintf( T_( 'The file &laquo;%s&raquo; has been updated.' ), $edited_File->dget('name') ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_( 'The file &laquo;%s&raquo; could not be updated.' ), $edited_File->dget('name') ), 'error' );
		}

		header_redirect( regenerate_url( '', '', '', '&' ) );
		// $action = 'list';
		break;
}


// Do we want to display the directory tree next to the files table
$UserSettings->param_Request( 'fm_hide_dirtree', 'fm_hide_dirtree', 'integer', 0, true );


/**
 * Filelist
 */
$fm_Filelist = new Filelist( $fm_FileRoot, $ads_list_path );
$Debuglog->add( 'FM _rds_list_path: '.var_export( $fm_Filelist->_rds_list_path, true ), 'files' );

param( 'fm_filter', '', NULL, true );
param( 'fm_filter_regex', 'integer', 0, true );
$fm_Filelist->set_Filter( $fm_filter, $fm_filter_regex );

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

/*
 * Load Filelist (with meta data):
 */
$fm_Filelist->load();

// Sort Filelist
param( 'fm_order', 'string', NULL, true );
if( ! in_array( $fm_order, array( 'name', 'path', 'type', 'size', 'lastmod', 'perms', 'fsowner', 'fsgroup' ) ) )
{
	$fm_order = NULL;
}
param( 'fm_orderasc', '', NULL, true );
$fm_Filelist->sort( $fm_order, $fm_orderasc );


switch( $action )
{
	case 'download':
		// TODO: We don't need the Filelist, move UP!
		// TODO: provide optional zip formats (tgz, ..) - the used lib provides more..
		// TODO: use "inmemory"=>false, so that you can download bigger archives faster!

		$action_title = T_('Download');

		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			$action = 'list';
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
		load_class( '_ext/_zip_archives.php', 'zip_file' );

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

		if( $zipfile->error )
		{
			foreach($zipfile->error as $v)
			{
				$Messages->add( $v, 'error' );
			}
			break;
		}

		$zipfile->download_file();
		exit(0);
		/* EXITED! */


	case 'rename':
		// TODO: We don't need the Filelist, move UP!
		// Rename a file:

		// This will not allow to overwrite existing files, the same way Windows and MacOS do not allow it. Adding an option will only clutter the interface and satisfy geeks only.
		if( ! $current_User->check_perm( 'files', 'edit', false, $blog ? $blog : NULL ) )
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
			elseif( $error_dirname = validate_dirname( $new_names[$loop_src_File->get_md5_ID()] ) )
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

			// REDIRECT / EXIT
 			header_redirect( regenerate_url( '', '', '', '&' ) );
 		  // $action = 'list';
		}
		break;


	case 'delete':
		// TODO: We don't need the Filelist, move UP!
		// Delete a file or directory:

		if( ! $current_User->check_perm( 'files', 'edit', false, $blog ? $blog : NULL ) )
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
					$Messages->add( sprintf( ( $l_File->is_dir() ? T_('The directory &laquo;%s&raquo; has been deleted.')
									: T_('The file &laquo;%s&raquo; has been deleted.') ), $l_File->dget('name') ), 'success' );
					$fm_Filelist->remove( $l_File );
				}
				else
				{
					$Messages->add( sprintf( ( $l_File->is_dir() ? T_('Could not delete the directory &laquo;%s&raquo; (not empty?).')
									: T_('Could not delete the file &laquo;%s&raquo;.') ), $l_File->dget('name') ), 'error' );
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
		break;


	case 'make_post':
	case 'make_posts':
		// TODO: We don't need the Filelist, move UP!
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

		// Ready to create post(s):
		load_class( 'items/model/_item.class.php', 'Item' );

		switch( $action )
		{
			case 'make_post':
				// SINGLE POST:
				// Create a post:
				$edited_Item = & new Item();
				$edited_Item->set( 'status', $item_status );
				$edited_Item->set( 'main_cat_ID', $Blog->get_default_cat_ID() );

				$l_File = & $selected_Filelist->get_next();

				$title = $l_File->get('title');
				if( empty($title) )
				{
					$title = $l_File->get('name');
				}
				$edited_Item->set( 'title', $title );

				$DB->begin();

				// INSERT NEW POST INTO DB:
				$edited_Item->dbinsert();

				do
				{	// LOOP Through images:
					if( ! $l_File->is_image() )
					{
						$Messages->add( sprintf( T_('Cannot post &laquo;%s&raquo; because it is not an image.'), $l_File->dget('name') ), 'error' );
						continue;
					}

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

					$Messages->add( sprintf( T_('&laquo;%s&raquo; has been attached.'), $l_File->dget('name') ), 'success' );

				} while( $l_File = & $selected_Filelist->get_next() );

				$DB->commit();

				header_redirect( $dispatcher.'?ctrl=items&action=edit&p='.$edited_Item->ID );	// Will save $Messages
				break;

			case 'make_posts':
				// MULTIPLE POST (1 per image):
				while( $l_File = & $selected_Filelist->get_next() )
				{
					if( ! $l_File->is_image() )
					{
						$Messages->add( sprintf( T_('Cannot post &laquo;%s&raquo; because it is not an image.'), $l_File->dget('name') ), 'error' );
						continue;
					}

					// Create a post:
					$edited_Item = & new Item();
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
				header_redirect( $dispatcher.'?ctrl=items&blog='.$blog );	// Will save $Messages

				break;
		}

		// Note: we should have EXITED here. In case we don't (error, or sth...)

		// Reset stuff so it doesn't interfere with upcomming display
		unset( $edited_Item );
		unset( $edited_Link );
		$selected_Filelist = & new Filelist( $fm_Filelist->get_FileRoot(), false );
		break;


	case 'edit_file':
		// TODO: We don't need the Filelist, move UP!
		// Edit Text File

		// Check permission!
 		$current_User->check_perm( 'files', 'edit', true, $blog ? $blog : NULL );

 		// Get the file we want to edit:
		$edited_File = & $selected_Filelist->get_by_idx(0);

		// Check that the file is editable:
		if( ! $edited_File->is_editable( $current_User->check_perm( 'files', 'all' ) ) )
		{
			$Messages->add( sprintf( T_( 'You are not allowed to edit &laquo;%s&raquo;.' ), $edited_File->dget('name') ), 'error' );
	 		// Leave special display mode:
			$action = 'list';
			break;
		}

		$full_path = $edited_File->get_full_path();
		if( $size = filesize($full_path) )
		{
			$rsc_handle = fopen( $full_path, 'r');
			$edited_File->content = fread( $rsc_handle, $size );
			fclose( $rsc_handle );
		}
		else
		{	// Empty file
			$edited_File->content = '';
		}
		break;


	case 'edit_properties':
		// TODO: We don't need the Filelist, move UP!
		// Edit File properties (Meta Data)

		// Check permission!
 		$current_User->check_perm( 'files', 'edit', true, $blog ? $blog : NULL );

		$edited_File = & $selected_Filelist->get_by_idx(0);
		$edited_File->load_meta();
		break;


	case 'update_properties':
		// TODO: We don't need the Filelist, move UP!
		// Update File properties (Meta Data); on success this ends the file_properties mode:

		// Check permission!
 		$current_User->check_perm( 'files', 'edit', true, $blog ? $blog : NULL );

		$edited_File = & $selected_Filelist->get_by_idx(0);
		// Load meta data:
		$edited_File->load_meta();

		$edited_File->set( 'title', param( 'title', 'string', '' ) );
		$edited_File->set( 'alt', param( 'alt', 'string', '' ) );
		$edited_File->set( 'desc', param( 'desc', 'string', '' ) );

		// Store File object into DB:
		if( $edited_File->dbsave() )
		{
			$Messages->add( sprintf( T_( 'File properties for &laquo;%s&raquo; have been updated.' ), $edited_File->dget('name') ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_( 'File properties for &laquo;%s&raquo; have not changed.' ), $edited_File->dget('name') ), 'note' );
		}
		break;


	case 'link_user':
		// TODO: We don't need the Filelist, move UP!
		// Link File to User:
		if( ! isset($edited_User) )
 		{	// No User to link to
			$fm_mode = NULL;	// not really needed but just  n case...
			break;
		}

		// Permission HAS been checked on top of controller!

 		// Get the file we want to link:
		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			break;
		}
		$edited_File = & $selected_Filelist->get_by_idx(0);

		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$edited_File->load_meta( true );

		// Assign avatar:
		$edited_User->set( 'avatar_file_ID', $edited_File->ID );
		// Save to DB:
 		$edited_User->dbupdate();

		$Messages->add( T_('User avatar modified.'), 'success' );

		// REDIRECT / EXIT
		header_redirect( $admin_url.'?ctrl=users&user_ID='.$edited_User->ID );
		break;

	case 'link_db':
		// efy-maxim> TODO: temporary hook for DB module (EXPERIMENTAL)

		// Get the file we want to link:
		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			break;
		}
		$edited_File = & $selected_Filelist->get_by_idx(0);

		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$edited_File->load_meta( true );

		// REDIRECT / EXIT
		header_redirect( $admin_url.'?ctrl=dbdata&action='.param( 'db_action', 'string' ).'&db_ID='.param( 'db_ID', 'string' ).'&field_name='.param( 'field_name', 'string' ).'&file_ID='.$edited_File->ID.'&file_root='.$edited_File->_FileRoot->ads_url.'&file_path='.$edited_File->_rdfp_rel_path );

		break;

	case 'link':
	case 'link_inpost':	// In the context of a post
		// TODO: We don't need the Filelist, move UP!
		// Link File to Item

		// Note: we are not modifying any file here, we're just linking it
		// we only need read perm on file, but we'll need write perm on destination object (to be checked below)

		if( ! isset($edited_Item) )
 		{	// No Item to link to - end link_item mode.
			$fm_mode = NULL;
			break;
		}

		// Check item EDIT permissions:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

 		// Get the file we want to link:
		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			break;
		}
		$edited_File = & $selected_Filelist->get_by_idx(0);

		$DB->begin();

		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$edited_File->load_meta( true );

		// Let's make the link!
		$edited_Link = & new Link();
		$edited_Link->set( 'itm_ID', $edited_Item->ID );
		$edited_Link->set( 'file_ID', $edited_File->ID );
		$edited_Link->dbinsert();

		$DB->commit();

		$Messages->add( T_('Selected file has been linked to item.'), 'success' );

		// In case the mode had been closed, reopen it:
		$fm_mode = 'link_item';


		// REDIRECT / EXIT
		if( $action == 'link_inpost' )
		{
 			header_redirect( $admin_url.'?ctrl=items&action=edit_links&mode=iframe&item_ID='.$edited_Item->ID );
		}
		else
		{
 			header_redirect( regenerate_url( '', '', '', '&' ) );
		}
		break;


	case 'unlink':
		// TODO: We don't need the Filelist, move UP!
		// Unlink File from Item (or other object if extended):

		// Note: we are not modifying any file here, we're just linking it
		// we only need read perm on file, but we'll need write perm on destination object (to be checked below)

		if( !isset( $edited_Link ) )
		{
			$action = 'list';
			break;
		}

		// get Item (or other object) from Link to check perm
		$edited_Item = & $edited_Link->Item;

		// Check that we have permission to edit item:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		// Delete from DB:
		$msg = sprintf( T_('Link from &laquo;%s&raquo; deleted.'), $edited_Link->Item->dget('title') );
		$edited_Link->dbdelete( true );
		unset( $edited_Link );
		forget_param( 'link_ID' );

		$Messages->add( $msg, 'success' );
		$action = 'list';
		// REDIRECT / EXIT
 		header_redirect( regenerate_url( '', '', '', '&' ) );
		break;


	case 'edit_perms':
		// TODO: We don't need the Filelist, move UP!
		// Edit file or directory permissions:

		if( ! $current_User->check_perm( 'files', 'edit', false, $blog ? $blog : NULL ) )
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
				$newperms = $l_File->chmod( octdec( $chmod ) );

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
		break;
}


/*
 * Prepare for modes:
 */
switch( $fm_mode )
{
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

		if( ! $current_User->check_perm( 'files', 'edit', false, $blog ? $blog : NULL ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$fm_mode = NULL;
			break;
		}

		// Get the source list
		if( $fm_sources = param( 'fm_sources', 'array', array(), true ) )
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
			$FileCache = & get_FileCache();
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
				$FileCache = & get_FileCache();
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

			// EXIT MODE:
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
		break;

}


// Update sub-menu:
if( $current_User->check_perm( 'files', 'add', false, $blog ? $blog : NULL ) )
{ // Permission to upload: (no subtabs needed otherwise)
	$AdminUI->add_menu_entries(
			'files',
			array(
					'browse' => array(
						'text' => T_('Browse'),
						'href' => regenerate_url( 'ctrl', 'ctrl=files' ) ),
					'upload' => array(
						'text' => T_('Upload'),
						'href' => regenerate_url( 'ctrl', 'ctrl=upload' ) ),
				)
		);
}


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

$AdminUI->disp_payload_begin();

/*
 * Display payload:
 */
if( !empty($action ) && $action != 'list' && $action != 'nil' )
{

	// Action displays:
	switch( $action )
	{
		case 'rename':
			// Rename files dialog:
			$AdminUI->disp_view( 'files/views/_file_rename.form.php' );
			break;

		case 'delete':
			// Delete file(s). We arrive here either if not confirmed or in case of error(s).
			$AdminUI->disp_view( 'files/views/_file_delete.form.php' );
			break;

		case 'download':
			$AdminUI->disp_view( 'files/views/_file_download.form.php' );
			break;

		case 'edit_perms':
			// Filesystem permissions for specific files
			$AdminUI->disp_view( 'files/views/_file_permissions.form.php' );
			break;

		case 'edit_file':
			// File Edit dialog:
			$AdminUI->disp_view( 'files/views/_file_edit.form.php' );
			break;

		case 'edit_properties':
			// File properties (Meta data) dialog:
			$AdminUI->disp_view( 'files/views/_file_properties.form.php' );
			break;

		case 'edit_settings':
			// Display settings dialog:
			$AdminUI->disp_view( 'files/views/_file_browse_set.form.php' );
			break;

		case 'download':
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
}


/*
 * Diplay mode payload:
 */
switch( $fm_mode )
{
	case 'file_copy':
	case 'file_move':
		// CMR dialog:
		$AdminUI->disp_view( 'files/views/_file_copy_move.form.php' );
		break;

	case 'link_item':
		// Links dialog:
		$AdminUI->disp_view( 'files/views/_file_links.view.php' );
		break;
}


// -------------------
// Browsing interface:
// -------------------
// Display VIEW:
$AdminUI->disp_view( 'files/views/_file_browse.view.php' );


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.38  2009/09/29 03:14:22  fplanque
 * doc
 *
 * Revision 1.37  2009/09/26 15:18:12  efy-maxim
 * temporary solution for file/image types
 *
 * Revision 1.36  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.35  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.34  2009/09/15 19:31:55  fplanque
 * Attempt to load classes & functions as late as possible, only when needed. Also not loading module specific stuff if a module is disabled (module granularity still needs to be improved)
 * PHP 4 compatible. Even better on PHP 5.
 * I may have broken a few things. Sorry. This is pretty hard to do in one swoop without any glitch.
 * Thanks for fixing or reporting if you spot issues.
 *
 * Revision 1.33  2009/09/14 13:01:31  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.32  2009/08/30 22:25:08  blueyed
 * note/todo
 *
 * Revision 1.31  2009/08/30 19:54:24  fplanque
 * less translation messgaes for infrequent errors
 *
 * Revision 1.30  2009/08/29 12:23:56  tblue246
 * - SECURITY:
 * 	- Implemented checking of previously (mostly) ignored blog_media_(browse|upload|change) permissions.
 * 	- files.ctrl.php: Removed redundant calls to User::check_perm().
 * 	- XML-RPC APIs: Added missing permission checks.
 * 	- items.ctrl.php: Check permission to edit item with current status (also checks user levels) for update actions.
 * - XML-RPC client: Re-added check for zlib support (removed by update).
 * - XML-RPC APIs: Corrected method signatures (return type).
 * - Localization:
 * 	- Fixed wrong permission description in blog user/group permissions screen.
 * 	- Removed wrong TRANS comment
 * 	- de-DE: Fixed bad translation strings (double quotes + HTML attribute = mess).
 * - File upload:
 * 	- Suppress warnings generated by move_uploaded_file().
 * 	- File browser: Hide link to upload screen if no upload permission.
 * - Further code optimizations.
 *
 * Revision 1.29  2009/07/06 23:52:24  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.28  2009/04/15 13:17:19  tblue246
 * bugfixes
 *
 * Revision 1.27  2009/04/08 11:33:55  tblue246
 * Renaming file _zip_archives.class.php back to _zip_archives.php to prevent inclusion into the autogenerated __autoload() list (when it gets regenerated).
 *
 * Revision 1.26  2009/04/06 20:20:11  sam2kb
 * class renamed, see http://forums.b2evolution.net/viewtopic.php?t=18455
 *
 * Revision 1.25  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.24  2009/02/25 22:17:53  blueyed
 * ItemLight: lazily load blog_ID and main_Chapter.
 * There is more, but I do not want to skim the diff again, after
 * "cvs ci" failed due to broken pipe.
 *
 * Revision 1.23  2008/09/29 08:30:43  fplanque
 * Avatar support
 *
 * Revision 1.22  2008/09/29 04:23:50  fplanque
 * Nasty bug fix where you would quick upload to the wrong location!
 *
 * Revision 1.21  2008/09/29 03:52:47  fplanque
 * bugfix - iframe name passthrough
 *
 * Revision 1.20  2008/09/23 07:56:47  fplanque
 * Demo blog now uses shared files folder for demo media + more images in demo posts
 *
 * Revision 1.19  2008/09/23 05:26:37  fplanque
 * Handle attaching files when multiple posts are edited simultaneously
 *
 * Revision 1.18  2008/05/12 20:33:53  fplanque
 * fix
 *
 * Revision 1.17  2008/04/14 19:50:51  fplanque
 * enhanced attachments handling in post edit mode
 *
 * Revision 1.16  2008/04/14 17:39:55  fplanque
 * create 1 post with all images attached
 *
 * Revision 1.15  2008/04/14 17:03:53  fplanque
 * "with selected files" cleanup
 *
 * Revision 1.14  2008/04/03 22:03:10  fplanque
 * added "save & edit" and "publish now" buttons to edit screen.
 *
 * Revision 1.13  2008/02/19 11:11:17  fplanque
 * no message
 *
 * Revision 1.12  2008/02/04 13:57:50  fplanque
 * wording
 *
 * Revision 1.11  2008/01/21 09:35:28  fplanque
 * (c) 2008
 *
 * Revision 1.10  2008/01/14 00:00:53  fplanque
 * removed extra param
 *
 * Revision 1.9  2008/01/06 05:16:33  fplanque
 * enhanced upload
 *
 * Revision 1.8  2007/11/29 19:47:41  fplanque
 * minor
 *
 * Revision 1.7  2007/11/29 00:00:46  blueyed
 * Fix E_NOTICE + "requested blog without ID" fatal error
 *
 * Revision 1.6  2007/11/25 20:03:12  fplanque
 * if no fileroot requested, try to select the one matching the current blog
 *
 * Revision 1.5  2007/11/01 01:41:00  fplanque
 * fixed : dir creation was losing item_ID
 *
 * Revision 1.4  2007/09/26 23:32:39  fplanque
 * upload context saving
 *
 * Revision 1.3  2007/09/26 21:53:24  fplanque
 * file manager / file linking enhancements
 */
?>
