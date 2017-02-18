<?php
/**
 * This file implements the UI controller for file management.
 *
 * NOTE: $fm_mode is used for modes. Modes stay visible when browsing to a different location.
 * Examples of modes: link item, copy file.
 * Actions disappear if you browse to a different location.
 * Examples of actions: file properties, file edit.
 *
 * fp>> Move/copy should not be a mode (too geeky). All we need is a dir selection tree inside of upload and move.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package admin
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

global $filename_max_length, $dirpath_max_length;

// Check permission:
$current_User->check_perm( 'files', 'view', true, $blog ? $blog : NULL );

$AdminUI->set_path( 'files', 'browse' );

// 1 when AJAX request, E.g. when popup is used to link a file to item/comment
param( 'ajax_request', 'integer', 0, true );

// INIT params:
if( param( 'root_and_path', 'filepath', '', false ) /* not memorized (default) */ && strpos( $root_and_path, '::' ) )
{ // root and path together: decode and override (used by "radio-click-dirtree")
	list( $root, $path ) = explode( '::', $root_and_path, 2 );
	// Memorize new root:
	memorize_param( 'root', 'string', NULL );
	memorize_param( 'path', 'string', NULL );
}
else
{
	param( 'root', 'string', NULL, true ); // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
	param( 'path', 'filepath', '/', true );  // the path relative to the root dir
	if( param( 'new_root', 'string', '' )
		&& $new_root != $root )
	{ // We have changed root in the select list
		$root = $new_root;
		$path = '';
	}
}

if( $path == './' )
{	// Fix name of root path to empty string in order to don't store this in DB before each file of root folder:
	$path = '';
}

/*
 * Load linkable objects:
 */
if( param( 'link_type', 'string', NULL, true, false, false ) && param( 'link_object_ID', 'integer', NULL, true, false, false ) )
{ // Load Requested LinkOwner object:
	if( $root == 'skins_0' )
	{	// Deny to link skin files to objects:
		debug_die( 'Skin files are not allowed to link to objects!' );
	}
	$LinkOwner = get_link_owner( $link_type, $link_object_ID );
	if( empty( $LinkOwner ) )
	{ // We could not find the owner object to link:
		$Messages->add( T_('Requested object does not exist any longer.'), 'error' );
		forget_param( 'link_type' );
		forget_param( 'link_object_ID' );
		unset( $link_type );
		unset( $link_object_ID );
	}
}

if( param( 'user_ID', 'integer', NULL, true, false, false ) )
{ // Load Requested user:
	if( $root == 'skins_0' )
	{	// Deny to link skin files to objects:
		debug_die( 'Skin files are not allowed to link to objects!' );
	}
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

/**
 * @var string Root of the source files that may be moved/copied from one root to another
 */
$fm_sources_root = param( 'fm_sources_root', 'string', '', true );

$action = param_action();
if( $action == 'group_action' )
{ // Get the real action from the select:
	$action = param( 'group_action', 'string', '' );
	// NOTE/TODO: dh> action "img_tag" appears to be unimplemented for non-JS
}

if( in_array( $action, array( 'move_copy', 'file_move', 'file_copy' ) ) )
{	// Memorize action to select another directory for moving/copying files:
	memorize_param( 'action', 'string', '', $action );
}

if( ! empty( $action ) && substr( $fm_mode, 0, 5 ) != 'link_' && $fm_mode != 'file_select' )
{	// The only modes which can tolerate simultaneous actions at this time are link_* modes (item, user...) and file_select
	$fm_mode = '';
}

// Abstract data we want to pass through:
param( 'linkctrl', 'string', '', true );
param( 'linkdata', 'string', '', true );

// Name of the iframe we want some actions to come back to:
param( 'iframe_name', 'string', '', true );
param( 'field_name', 'string', '', true );

// Get root:
$ads_list_path = false; // false by default, gets set if we have a valid root
/**
 * @var FileRoot
 */
$fm_FileRoot = NULL;

$FileRootCache = & get_FileRootCache();

$available_Roots = FileRootCache::get_available_FileRoots( get_param( 'root' ), 'favorite' );

if( ! empty( $root ) )
{ // We have requested a root folder by string:
	$fm_FileRoot = & $FileRootCache->get_by_ID( $root, true );

	if( ! $fm_FileRoot || ! isset( $available_Roots[$fm_FileRoot->ID] ) || ! $current_User->check_perm( 'files', 'view', false, $fm_FileRoot ) )
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
	if( ( $avatar_File = & $edited_User->get_avatar_File() ) && ( $current_User->check_perm( 'files', 'view', false, $avatar_File->get_FileRoot() ) ) )
	{
		$fm_FileRoot = & $avatar_File->get_FileRoot();
		$path = dirname( $avatar_File->get_rdfs_rel_path() ).'/';
	}
}
elseif( !empty($LinkOwner) )
{	// We have a post, check if it already has a linked file in a particular root, in which case we want to use that root!
	// This is useful when clicking "attach files" from the post edit screen: it takes you to the root where you have
	// already attached files from. Otherwise the next block below will default to the Blog's fileroot.
	// Get list of attached files:
	if( $FileList = $LinkOwner->get_attachment_FileList( 1 ) )
	{	// Get first file:
		/**
		 * @var File
		 */
		$File = & $FileList->get_next();
		if( !empty( $File ) && $current_User->check_perm( 'files', 'view', false, $File->get_FileRoot() ) )
		{	// Obtain and use file root of first file:
			$fm_FileRoot = & $File->get_FileRoot();
			$path = dirname( $File->get_rdfs_rel_path() ).'/';
		}
	}
}

if( $fm_FileRoot && ! $current_User->check_perm( 'files', 'view', false, $fm_FileRoot ) )
{
	$fm_FileRoot = false;
};

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
	if( $available_Roots )
	{
		foreach( $available_Roots as $l_FileRoot )
		{
			if( $current_User->check_perm( 'files', 'view', false, $l_FileRoot ) )
			{
				$fm_FileRoot = $l_FileRoot;
				break;
			}
		}
	}
	if( ! $fm_FileRoot )
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

		if( ( $Messages->count() == 0 ) && ( strlen( $ads_list_path ) > $dirpath_max_length ) && $current_User->check_perm( 'options', 'edit' ) )
		{ // This folder absolute path exceed the max allowed length, a warning message must be displayed, if there were no other message yet. ( If there are other messages then this one should have been already added )
			$Messages->add( sprintf( T_( 'This folder has an access path that is too long and cannot be properly handled by b2evolution. Please check and increase the &laquo;%s&raquo; variable.'), '$dirpath_max_length' ), 'warning' );
		}
	}
}


if( ! $ajax_request )
{ // Don't display tabs on AJAX request
	file_controller_build_tabs();
}


if( empty($ads_list_path) )
{ // We have no Root / list path, there was an error. Unset any action or mode.
	$action = 'nil';
	$fm_mode = NULL;

	$AdminUI->disp_html_head();
	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();
	// Begin payload block:
	$AdminUI->disp_payload_begin();
	// nothing!
	// End payload block:
	$AdminUI->disp_payload_end();
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
 * @todo fp> This could probably be further simplified by using "fm_sources" for selections.
 * Note: fm_sources is better because it also handles sources/selections on a different fileroot
 *
 * @global array
 */
$fm_selected = param( 'fm_selected', 'array:filepath', array(), true );
$Debuglog->add( count($fm_selected).' selected files/directories', 'files' );
/**
 * The selected files (must be within current fileroot)
 *
 * @global Filelist
 */
$selected_Filelist = new Filelist( $fm_FileRoot, $ads_list_path );

foreach( $fm_selected as $l_source_path )
{
	$selected_Filelist->add_by_subpath( $l_source_path, true );
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
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		// Check permission:
		$current_User->check_perm( 'files', 'add', true, $blog ? $blog : NULL );

		if( ! $Settings->get( 'fm_enable_create_dir' ) )
		{ // Directory creation is gloablly disabled:
			$Messages->add( T_('Directory creation is disabled.'), 'error' );
			break;
		}

		if( ! param( 'create_name', 'string', '' ) )
		{ // No name was supplied:
			$Messages->add( T_('Cannot create a directory without name.'), 'error' );
			break;
		}
		if( $error_dirname = validate_dirname( $create_name ) )
		{ // Not valid dirname
			$Messages->add( $error_dirname, 'error' );
			syslog_insert( sprintf( 'Invalid name is detected for folder %s', '[['.$create_name.']]' ), 'warning', 'file' );
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

		if( strlen( $newFile->get_full_path() ) > $dirpath_max_length )
		{
			$Messages->add( T_('The new file access path is too long, shorter folder names would be required.'), 'error' );
			break;
		}

		if( $newFile->exists() )
		{
			$Messages->add( sprintf( T_('The file &laquo;%s&raquo; already exists.'), $create_name ), 'error' );
			break;
		}

		if( ! $newFile->create( $create_type ) )
		{
			$Messages->add( sprintf( T_('Could not create directory &laquo;%s&raquo; in &laquo;%s&raquo;.'), $create_name, $path )
				.' '.get_file_permissions_message(), 'error' );
			break;
		}

		$Messages->add( sprintf( T_('The directory &laquo;%s&raquo; has been created.'), $create_name ), 'success' );

		header_redirect( regenerate_url( '', '', '', '&' ) );
		// $action = 'list';
		break;


	case 'createnew_file':
		// We are probably comming from 'createnew' but there is no guarantee!
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		// Check permission:
		$current_User->check_perm( 'files', 'add', true, $blog ? $blog : NULL );

		if( ! $Settings->get( 'fm_enable_create_file' ) )
		{ // File creation is gloablly disabled:
			$Messages->add( T_('File creation is disabled.'), 'error' );
			break;
		}

		if( ! param( 'create_name', 'string', '' ) )
		{ // No name was supplied:
			$Messages->add( T_('Cannot create a file without name.'), 'error' );
			break;
		}
		if( $error_filename = validate_filename( $create_name, $current_User->check_perm( 'files', 'all' ) ) )
		{ // Not valid filename or extension
			$Messages->add( $error_filename, 'error' );
			syslog_insert( sprintf( 'The creating file %s has an unrecognized extension', '[['.$create_name.']]' ), 'warning', 'file' );
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
			$Messages->add( sprintf( T_('Could not create file &laquo;%s&raquo; in &laquo;%s&raquo;.'), $create_name, $newFile->get_dir() )
				.' '.get_file_permissions_message(), 'error' );
			break;
		}

		$Messages->add( sprintf( T_('The file &laquo;%s&raquo; has been created.'), $create_name ), 'success' );

		header_redirect( regenerate_url( '', '', '', '&' ) );
		// $action = 'list';
		break;


  case 'update_settings':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

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
		$UserSettings->set( 'fm_showcreator',      param( 'option_showcreator',      'integer', 0 ) );
		$UserSettings->set( 'fm_showdownload',     param( 'option_showdownload',     'integer', 0 ) );

		$UserSettings->set( 'fm_showhidden',       param( 'option_showhidden',       'integer', 0 ) );
		$UserSettings->set( 'fm_showevocache',     param( 'option_showevocache',     'integer', 0 ) );
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

		# TODO: dh> Changes linebreaks! e.g. "\n" => "\r\n", when only saving again.

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		if( $demo_mode )
		{
			$Messages->add( 'Sorry, you cannot update files in demo mode!', 'error' );
			break;
		}

		// Check permission!
 		$current_User->check_perm( 'files', 'edit_allowed', true, $selected_Filelist->get_FileRoot() );

 		// Get the file we want to update:
		$edited_File = & $selected_Filelist->get_by_idx(0);

		// Check that the file is editable:
		if( ! $edited_File->is_editable( $current_User->check_perm( 'files', 'all' ) ) )
		{
			$Messages->add( sprintf( T_( 'You are not allowed to edit &laquo;%s&raquo;.' ), $edited_File->dget('name') ), 'error' );
			break;
		}

		param( 'file_content', 'html', '', false );


		$fpath = $edited_File->get_full_path();
		if( file_exists($fpath) && ! is_writeable($fpath) ) {
			$Messages->add( sprintf('The file &laquo;%s&raquo; is not writable.', rel_path_to_base($fpath)), 'error' );
			break;
		}

		if( save_to_file( $file_content, $fpath, 'w+' ) )
		{
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

param( 'fm_filter', 'string', NULL, true );
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
check_showparams( $fm_Filelist );
if( param( 'fm_flatmode', 'boolean', NULL, true ) )
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
param( 'fm_orderasc', 'boolean', NULL, true );
$fm_Filelist->sort( $fm_order, $fm_orderasc );


switch( $action )
{
	case 'download':
	case 'create_zip':
		// TODO: We don't need the Filelist, move UP!
		// TODO: provide optional zip formats (tgz, ..) - the used lib provides more..
		// TODO: use "inmemory"=>false, so that you can download bigger archives faster!

		if( $action == 'create_zip' )
		{	// Check permission for action to create new ZIP archive:
			$current_User->check_perm( 'files', 'edit_allowed', true, $selected_Filelist->get_FileRoot() );
		}

		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			$action = 'list';
			break;
		}

		param( 'zipname', 'string', '' );
		param( 'exclude_sd', 'integer', 0 );
		param( 'action_invoked', 'integer', 0 );
		param( 'delete_files', 'integer', 0 ); // Only for action "create_zip"

		if( $action_invoked )
		{	// Action was invoked, check felds:
			// ZIP file name should not be empty:
			param_check_not_empty( 'zipname', T_('Please provide the name of the archive.') );
			if( ! empty( $zipname ) )
			{ // Check extenssion of the entered file name:
				param_check_regexp( 'zipname', '#\.zip$#i', T_('File name must end with .zip') );
			}
		}

		if( empty( $zipname ) )
		{
			if( $selected_Filelist->count() == 1 )
			{	// Auto suggest ZIP file name as name of the single file:
				$only_File = $selected_Filelist->get_array();
				$only_File = $only_File[0];

				// TODO: once we support additional formats, use the default extension here:
				$zipname = $only_File->get_name().'.zip';
			}
			break;
		}

		if( param_errors_detected() )
		{	// Stop download if errors detected:
			break;
		}

		// Load class to work with ZIP files:
		load_class( '_ext/_zip_archives.php', 'zip_file' );

		$arraylist = $selected_Filelist->get_array( 'get_name' );

		$options = array (
			'basedir' => $fm_Filelist->get_ads_list_path(),
			// Keep zip archive in memory only ifor download action:
			'inmemory' => ( $action == 'download' ),
			'recurse' => (1 - $exclude_sd),
		);

		// Create ZIP archive:
		$zipfile = new zip_file( $zipname );
		$zipfile->set_options( $options );
		$zipfile->add_files( $arraylist, array( '_evocache' ) );
		$zipfile->create_archive();

		if( $zipfile->error )
		{
			foreach( $zipfile->error as $v )
			{
				$Messages->add( $v, 'error' );
			}
			break;
		}

		if( $action == 'download' )
		{	// Download ZIP archive:
			$zipfile->download_file();
			exit(0);
			/* EXITED! */
		}
		else // $action == 'create_zip'
		{	// Display a message after successful creating of ZIP archive:
			$Messages->add_to_group( sprintf( T_('ZIP archive "%s" has been created.'), $zipname ), 'success', T_('Creating ZIP file...') );

			if( $delete_files )
			{	// We should delete the files after creating ZIP archive:

				$selected_Filelist->restart();

				// Delete files, It is possible only file has no links:
				$selected_Filelist->load_meta();
				while( $l_File = & $selected_Filelist->get_next() )
				{
					// Check if there are delete restrictions on this file:
					$restriction_Messages = $l_File->check_relations( 'delete_restrictions', array(), true );

					if( $restriction_Messages->count() )
					{ // There are restrictions:
						$Messages->add_to_group( $l_File->get_prefixed_name().': '.T_('cannot be deleted because of the following relations')
							.$restriction_Messages->display( NULL, NULL, false, false ), 'warning', T_('Creating ZIP file...') );
						// Skip this file
						continue;
					}

					if( $l_File->unlink() )
					{
						$Messages->add_to_group( sprintf( ( $l_File->is_dir() ? T_('The directory &laquo;%s&raquo; has been deleted.')
										: T_('The file &laquo;%s&raquo; has been deleted.') ), $l_File->dget('name') ), 'success', T_('Creating ZIP file...') );
						$fm_Filelist->remove( $l_File );
					}
					else
					{
						$Messages->add_to_group( sprintf( ( $l_File->is_dir() ? T_('Could not delete the directory &laquo;%s&raquo; (not empty?).')
										: T_('Could not delete the file &laquo;%s&raquo;.') ), $l_File->dget('name') ), 'error', T_('Creating ZIP file...') );
					}
				}
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;


	case 'rename':
	case 'move_copy':
	case 'file_move':
	case 'file_copy':
	case 'move':
	case 'copy':
		// TODO: We don't need the Filelist, move UP!
		// Rename/Move/Copy a file:

		// This will not allow to overwrite existing files, the same way Windows and MacOS do not allow it. Adding an option will only clutter the interface and satisfy geeks only.
		if( ! $current_User->check_perm( 'files', 'edit_allowed', false, $selected_Filelist->get_FileRoot() ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$action = 'list';
			break;
		}

		$allow_locked_filetypes = $current_User->check_perm( 'files', 'all' );

		$sources_Root = & $FileRootCache->get_by_ID( $fm_sources_root );
		if( $sources_Root )
		{ // Instantiate the source list for the selected sources
			$source_Filelist = new Filelist( $sources_Root );
			foreach( $fm_selected as $l_source_path )
			{
				$source_Filelist->add_by_subpath( $l_source_path, true );
			}
		}

		if( ! $selected_Filelist->count() && ( ! $source_Filelist || ! $source_Filelist->count() ) )
		{ // There is nothing to rename
			$Messages->add( T_('Nothing selected.'), 'error' );
			$action = 'list';
			break;
		}

		param( 'confirmed', 'integer', 0 );
		param( 'new_names', 'array:filepath', array() );

		// Check params for each file to rename:
		while( $loop_src_File = & $source_Filelist->get_next() )
		{
			if( ! isset( $new_names[$loop_src_File->get_md5_ID()] ) )
			{ // We have not yet provided a name to rename to...
				$confirmed = 0;
				$new_names[$loop_src_File->get_md5_ID()] = $loop_src_File->get_name();
				continue;
			}

			// Check if provided name is okay:
			if( $check_error = check_rename( $new_names[$loop_src_File->get_md5_ID()], $loop_src_File->is_dir(), $loop_src_File->get_dir(), $allow_locked_filetypes ) )
			{
				$confirmed = 0;
				param_error( 'new_names['.$loop_src_File->get_md5_ID().']', $check_error );
				continue;
			}
		}

		if( $confirmed )
		{ // Rename is confirmed, let's proceed:
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'file' );
			$source_Filelist->restart();
			$FileCache = & get_FileCache();
			while( $loop_src_File = & $source_Filelist->get_next() )
			{
				$success_message = '';

				switch( $action )
				{
					case 'rename':
						// Rename file
						$old_name = $loop_src_File->get_name();
						$new_name = $new_names[$loop_src_File->get_md5_ID()];

						if( $new_name == $old_name )
						{ // Name has not changed...
							$Messages->add_to_group( sprintf( T_('&laquo;%s&raquo; has not been renamed'), $old_name ), 'note', T_('Renaming files:') );
							continue 2;
						}
						// Perform rename:
						if( ! $loop_src_File->rename_to( $new_name ) )
						{
							$Messages->add_to_group( sprintf( T_('&laquo;%s&raquo; could not be renamed to &laquo;%s&raquo;'),
								$old_name, $new_name ), 'error', T_('Renaming files') );
							continue 2;
						}

						$success_message = sprintf( T_('&laquo;%s&raquo; has been successfully renamed to &laquo;%s&raquo;'), $old_name, $new_name );
						$success_title = T_('Renaming files:');
						break;

					case 'copy':
						// Copy file
						$old_path = $loop_src_File->get_rdfp_rel_path();
						$new_path = $selected_Filelist->get_rds_list_path().$new_names[$loop_src_File->get_md5_ID()];

						if( $old_path == $new_path && $loop_src_File->_FileRoot->ID == $selected_Filelist->_FileRoot->ID )
						{ // File path has not changed...
							$Messages->add_to_group( sprintf( T_('&laquo;%s&raquo; has not been copied'), $old_path ), 'note', T_('Copying files:') );
							continue 2;
						}

						// Get a pointer on dest file
						$dest_File = & $FileCache->get_by_root_and_path( $selected_Filelist->get_root_type(), $selected_Filelist->get_root_ID(), $new_path );

						// Perform copy:
						if( ! $loop_src_File->copy_to( $dest_File ) )
						{ // failed
							$Messages->add_to_group( sprintf( T_('&laquo;%s&raquo; could not be copied to &laquo;%s&raquo;'), $old_path, $new_path ), 'error', T_('Copying files:') );
							continue 2;
						}

						// Use new file instead of source to update all file properties especially file root:
						// (to avoid debug die when file is moved to another root, @see Filelist::add())
						$loop_src_File = $dest_File;

						$success_message = sprintf( T_('&laquo;%s&raquo; has been successfully copied to &laquo;%s&raquo;'), $old_path, $new_path );
						$success_title = T_('Copying files:');
						break;

					case 'move':
						// Move file
						$old_path = $loop_src_File->get_rdfp_rel_path();
						$new_path = $selected_Filelist->get_rds_list_path().$new_names[$loop_src_File->get_md5_ID()];

						if( $old_path == $new_path && $loop_src_File->_FileRoot->ID == $selected_Filelist->_FileRoot->ID )
						{ // File path has not changed...
							$Messages->add_to_group( sprintf( T_('&laquo;%s&raquo; has not been moved'), $old_path ), 'note', T_('Moving files:') );
							continue 2;
						}

						// Perform move:
						if( ! $loop_src_File->move_to( $selected_Filelist->get_root_type(), $selected_Filelist->get_root_ID(), $new_path ) )
						{ // failed
							$Messages->add_to_group( sprintf( T_('&laquo;%s&raquo; could not be moved to &laquo;%s&raquo;'), $old_path, $new_path ), 'error', T_('Moving files:') );
							continue 2;
						}

						$success_message = sprintf( T_('&laquo;%s&raquo; has been successfully moved to &laquo;%s&raquo;'), $old_path, $new_path );
						$success_title = T_('Moving files:');
						break;
				}

				// We have moved in same dir, update caches:
				$fm_Filelist->update_caches();

				if( $fm_Filelist->contains( $loop_src_File ) === false )
				{ // File not in filelist (expected if not same dir)
					$fm_Filelist->add( $loop_src_File );
				}

				if( ! empty( $success_message ) )
				{
					$Messages->add_to_group( $success_message, 'success', $success_title );
				}
			}

			// Remove a memorized param to reset a source root after moving/coping
			forget_param( 'fm_sources_root' );

			// REDIRECT / EXIT
			header_redirect( regenerate_url( '', '', '', '&' ) );
			// $action = 'list';
		}
		break;


	case 'delete':
		// TODO: We don't need the Filelist, move UP!
		// Delete a file or directory:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		if( ! $current_User->check_perm( 'files', 'edit_allowed', false, $selected_Filelist->get_FileRoot() ) )
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
		{ // Delete files, It is possible only file has no links:
			$selected_Filelist->load_meta();
			while( $l_File = & $selected_Filelist->get_next() )
			{
				// Check if there are delete restrictions on this file:
				$restriction_Messages = $l_File->check_relations( 'delete_restrictions', array(), true );

				if( $restriction_Messages->count() )
				{ // There are restrictions:
					$Messages->add_to_group( $l_File->get_prefixed_name().': '.T_('cannot be deleted because of the following relations')
						.$restriction_Messages->display( NULL, NULL, false, false ), 'warning', T_('Deleting files...') );
					// Skip this file
					continue;
				}

				if( $l_File->unlink() )
				{
					$Messages->add_to_group( sprintf( ( $l_File->is_dir() ? T_('The directory &laquo;%s&raquo; has been deleted.')
									: T_('The file &laquo;%s&raquo; has been deleted.') ), $l_File->dget('name') ), 'success', T_('Deleting files...') );
					$fm_Filelist->remove( $l_File );
				}
				else
				{
					$Messages->add_to_group( sprintf( ( $l_File->is_dir() ? T_('Could not delete the directory &laquo;%s&raquo; (not empty?).')
									: T_('Could not delete the file &laquo;%s&raquo;.') ), $l_File->dget('name') ), 'error', T_('Deleting files...') );
				}
			}
			$action = 'list';
			$redirect_to = param( 'redirect_to', 'url', NULL );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( empty( $redirect_to ) ? regenerate_url( '', '', '', '&' ) : $redirect_to, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{
			// make sure we have loaded metas for all files in selection!
			$selected_Filelist->load_meta();

			$index = 0;
			// Check if there are delete restrictions on the files:
			while( $l_File = & $selected_Filelist->get_next() )
			{
				// Check if there are delete restrictions on this file:
				$restriction_Messages = $l_File->check_relations( 'delete_restrictions', array(), true );

				if( $restriction_Messages->count() )
				{ // There are restrictions:
					$Messages->add( $l_File->get_prefixed_name().': '.T_('cannot be deleted because of the following relations')
						.$restriction_Messages->display( NULL, NULL, false, false ) );

					// remove it from the list of selected files (that will be offered to delete):
					$selected_Filelist->remove( $l_File );
					unset( $fm_selected[$index] );
				}
				$index++;
			}

			if( ! $selected_Filelist->count() )
			{ // no files left in list, cancel action
				$action = 'list';
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}
		break;


	case 'make_post':
		// TODO: We don't need the Filelist, move UP!
		// Make posts with selected images:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

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
			$Collection = $Blog = & $BlogCache->get_by_ID( $blog );
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

				// Stop a request from the blocked IP addresses or Domains
				antispam_block_request();

				// Create a post:
				$edited_Item = new Item();
				$edited_Item->set( 'status', $item_status );
				$edited_Item->set( 'main_cat_ID', $Blog->get_default_cat_ID() );

				$l_File = & $selected_Filelist->get_next();

				$title = $l_File->get('title');
				if( empty($title) )
				{
					$title = $l_File->get('name');
				}
				$edited_Item->set( 'title', $title );

				$DB->begin( 'SERIALIZABLE' );

				// INSERT NEW POST INTO DB:
				if( $edited_Item->dbinsert() )
				{
					$order = 1;
					$LinkOwner = new LinkItem( $edited_Item );
					do
					{ // LOOP through files:
						// echo '<br>file meta: '.$l_File->meta;
						if(	$l_File->meta == 'notfound' )
						{	// That file has no meta data yet, create it now!
							$l_File->dbsave();
						}

						// Let's make the link!
						$LinkOwner->add_link( $l_File->ID, ( $order == 1 ? 'teaser' : 'aftermore' ), $order++ );

						$Messages->add_to_group( sprintf( T_('&laquo;%s&raquo; has been attached.'), $l_File->dget('name') ), 'success', T_('Creating post:') );

					} while( $l_File = & $selected_Filelist->get_next() );

					$DB->commit();
				}
				else
				{
					$Messages->add( T_('Couldn\'t create the new post'), 'error' );
					$DB->rollback();
				}

				header_redirect( $dispatcher.'?ctrl=items&action=edit&p='.$edited_Item->ID );	// Will save $Messages
				break;
		}

		// Note: we should have EXITED here. In case we don't (error, or sth...)

		// Reset stuff so it doesn't interfere with upcomming display
		unset( $edited_Item );
		unset( $edited_Link );
		$selected_Filelist = new Filelist( $fm_Filelist->get_FileRoot(), false );
		break;


	case 'edit_file':
		// TODO: We don't need the Filelist, move UP!
		// Edit Text File

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		// Check permission!
 		$current_User->check_perm( 'files', 'edit_allowed', true, $blog ? $blog : NULL );

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

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		// Check permission!
		$current_User->check_perm( 'files', 'edit_allowed', true, $selected_Filelist->get_FileRoot() );

		$edited_File = & $selected_Filelist->get_by_idx(0);
		$edited_File->load_meta();
		break;


	case 'update_properties':
		// TODO: We don't need the Filelist, move UP!
		// Update File properties (Meta Data); on success this ends the file_properties mode:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		// Check permission!
 		$current_User->check_perm( 'files', 'edit_allowed', true, $selected_Filelist->get_FileRoot() );

		$edited_File = & $selected_Filelist->get_by_idx(0);
		// Load meta data:
		$edited_File->load_meta();

		$edited_File->set( 'title', param( 'title', 'string', '' ) );
		$edited_File->set( 'alt', param( 'alt', 'string', '' ) );
		$edited_File->set( 'desc', param( 'desc', 'text', '' ) );

		// Store File object into DB:
		if( $edited_File->dbsave() )
		{
			$Messages->add( sprintf( T_( 'File properties for &laquo;%s&raquo; have been updated.' ), $edited_File->dget('name') ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_( 'File properties for &laquo;%s&raquo; have not changed.' ), $edited_File->dget('name') ), 'note' );
		}

		$old_name = $edited_File->get_name();
		$new_name = param( 'name', 'string', '' );
		$error_occured = false;

		if( $new_name != $old_name)
		{ // Name has changed...
			$allow_locked_filetypes = $current_User->check_perm( 'files', 'all' );
			if( $check_error = check_rename( $new_name, $edited_File->is_dir(), $edited_File->get_dir(), $allow_locked_filetypes ) )
			{
				$error_occured = true;
				param_error( 'new_name', $check_error );
			}
			else
			{ // Perform rename:
				if( $edited_File->rename_to( $new_name ) )
				{
					$Messages->add( sprintf( T_('&laquo;%s&raquo; has been successfully renamed to &laquo;%s&raquo;'),
							$old_name, $new_name ), 'success' );

					// We have renamed teh file, update caches:
					$fm_Filelist->update_caches();
				}
				else
				{
					$error_occured = true;
					$Messages->add( sprintf( T_('&laquo;%s&raquo; could not be renamed to &laquo;%s&raquo;'),
							$old_name, $new_name ), 'error' );
				}
			}
		}

		// Redirect so that a reload doesn't write to the DB twice:
		if( $error_occured )
		{
			header_redirect( regenerate_url( 'fm_selected', 'action=edit_properties&amp;fm_selected[]='.rawurlencode($edited_File->get_rdfp_rel_path() ).'&amp;'.url_crumb('file'), '', '&' ), 303 );
			// We have EXITed already, no need else.
		}
		header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;


	case 'link_user':
	case 'duplicate_user':
		// TODO: We don't need the Filelist, move UP!

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

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

		if( $action == 'duplicate_user' )
		{	// Copy file to the folder "profile_pictures" before linking:

			// Change current location to "profile_pictures" of the edited user:
			$user_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $edited_User->ID );
			$selected_Filelist = new Filelist( $user_FileRoot );

			$old_path = $edited_File->get_rdfp_rel_path();
			$new_path = $selected_Filelist->get_rds_list_path().$edited_File->get_name();

			if( $old_path == $new_path && $edited_File->_FileRoot->ID == $selected_Filelist->_FileRoot->ID )
			{	// File path has not changed:
				$Messages->add( sprintf( T_('&laquo;%s&raquo; has not been copied'), $old_path ), 'note' );
			}
			else
			{
				// Get a pointer on dest file:
				$dest_File = & $FileCache->get_by_root_and_path( $selected_Filelist->get_root_type(), $selected_Filelist->get_root_ID(), $new_path );

				// Perform copy:
				if( ! $edited_File->copy_to( $dest_File ) )
				{	// Failed copying:
					$Messages->add( sprintf( T_('&laquo;%s&raquo; could not be copied to &laquo;%s&raquo;'), $old_path, $new_path ), 'error' );
				}
				else
				{	// Success copying:
					$Messages->add( sprintf( T_('&laquo;%s&raquo; has been successfully copied to &laquo;%s&raquo;'), $old_path, $new_path ), 'success' );
					// Change old file to new copied in order to link it to profile:
					$edited_File = $dest_File;
				}
			}
		}

		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$edited_File->load_meta( true );

		// Check a file for min size
		$min_size = $Settings->get( 'min_picture_size' );
		$image_sizes = $edited_File->get_image_size( 'widthheight' );
		if( $image_sizes[0] < $min_size || $image_sizes[1] < $min_size )
		{	// Don't use this file as profile picture because it has small sizes
			$Messages->add( sprintf( T_( 'Your profile picture must have a minimum size of %dx%d pixels.' ), $min_size, $min_size ), 'error' );
			break;
		}

		// Link file to user
		$LinkOwner = get_link_owner( 'user', $edited_User->ID );
		$edited_File->link_to_Object( $LinkOwner );
		// Assign avatar:
		$edited_User->set( 'avatar_file_ID', $edited_File->ID );
		// update profileupdate_date, because a publicly visible user property was changed
		$edited_User->set_profileupdate_date();
		// Save to DB:
		$edited_User->dbupdate();

		$Messages->add( T_('Your profile picture has been changed.'), 'success' );

		// REDIRECT / EXIT
		header_redirect( $admin_url.'?ctrl=user&user_tab=avatar&user_ID='.$edited_User->ID );
		break;

	case 'link_data':
		// fp> do we need to go through this block + redirect or could the link icons link directly to $linkctrl ?

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

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
		header_redirect( $admin_url.'?ctrl='.$linkctrl.'&linkdata='.$linkdata.'&file_ID='.$edited_File->ID );

		break;

	case 'link':
	case 'link_inpost':	// In the context of a post
		// TODO: We don't need the Filelist, move UP!
		// Link File to a LinkOwner

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		// Note: we are not modifying any file here, we're just linking it
		// we only need read perm on file, but we'll need write perm on destination object (to be checked below)

		if( ! isset( $LinkOwner ) )
		{	// No Owner to link to - end link_object mode.
			$fm_mode = NULL;
			break;
		}

		// Check item EDIT permissions:
		$LinkOwner->check_perm( 'edit', true );

		// Get the file we want to link:
		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			break;
		}

		$files_count = $selected_Filelist->count();
		while( $edited_File = & $selected_Filelist->get_next() )
		{	// Let's make the link!
			$edited_File->link_to_Object( $LinkOwner );

			// Reset LinkCache to autoincrement link_order
			if( $files_count > 1 ) unset($GLOBALS['LinkCache']);
		}

		// Forget selected files
		if( $files_count > 1 ) $fm_selected = NULL;

		$Messages->add( $LinkOwner->translate( 'Selected files have been linked to xxx.' ), 'success' );

		// In case the mode had been closed, reopen it:
		$fm_mode = 'link_object';

		// REDIRECT / EXIT
		if( $action == 'link_inpost' )
		{
			header_redirect( $admin_url.'?ctrl=links&link_type='.$LinkOwner->type.'&action=edit_links&mode=iframe&link_object_ID='.$LinkOwner->get_ID().'&iframe_name='.$iframe_name );
		}
		else
		{
			header_redirect( regenerate_url( '', '', '', '&' ) );
		}
		break;


	case 'edit_perms':
		// TODO: We don't need the Filelist, move UP!
		// Edit file or directory permissions:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		if( ! $current_User->check_perm( 'files', 'edit_allowed', false, $selected_Filelist->get_FileRoot() ) )
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


		param( 'perms', 'array:integer', array() );
		param( 'edit_perms_default' ); // default value when multiple files are selected
		param( 'use_default_perms', 'array:string', array() ); // array of file IDs that should be set to default

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
	case 'link_object':
		// We want to link file(s) to an object or view linked files to an object:
		// TODO: maybe this should not be a mode and maybe we should handle linking as soon as we have an $edited_Item ...

		if( !isset( $LinkOwner ) )
		{ // No Object to link to...
			$fm_mode = NULL;
			break;
		}

		$LinkOwner->check_perm( 'view', true );
		break;
}


// fp> TODO: this here is a bit sketchy since we have Blog & fileroot not necessarilly in sync. Needs investigation / propositions.
// Note: having both allows to post from any media dir into any blog.
$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Files'), '?ctrl=files&amp;blog=$blog$' );
if( !isset($Blog) || $fm_FileRoot->type != 'collection' || $fm_FileRoot->in_type_ID != $Blog->ID )
{	// Display only if we're not browsing our home blog
	$AdminUI->breadcrumbpath_add( $fm_FileRoot->name, '?ctrl=files&amp;blog=$blog$&amp;root='.$fm_FileRoot->ID,
			(isset($Blog) && $fm_FileRoot->type == 'collection') ? sprintf( T_('You are ready to post files from %s into %s...'),
			$fm_FileRoot->name, $Blog->get('shortname') ) : '' );
}

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'browse' );

$mode = param( 'mode', 'string', '' );

if( $mode != 'modal' )
{
	// require colorbox js
	require_js_helper( 'colorbox' );
	// require File Uploader js and css
	require_js( 'multiupload/fileuploader.js' );
	require_css( 'fileuploader.css' );

	if( $mode == 'upload' || $mode == 'import' )
	{ // Add css to remove spaces around window
		require_css( 'fileadd.css', 'rsc_url' );
	}

	if( $mode == 'popup' )
	{ // Don't display navigation on popup mode
		$AdminUI->clear_menu_entries( 'files' );
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


	}

/*
 * Display payload:
 */
if( !empty($action ) && $action != 'list' && $action != 'nil' )
{

	// Action displays:
	switch( $action )
	{
		case 'move_copy':
		case 'file_move':
		case 'file_copy':
			// Copy/Move files dialog:
			$AdminUI->disp_view( 'files/views/_file_move_copy.form.php' );
			break;

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

		case 'create_zip':
			$AdminUI->disp_view( 'files/views/_file_create_zip.form.php' );
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
			if( $mode == 'modal' )
			{ // Unmemorize the mode param in order to submit form in mormal mode
				forget_param( 'mode' );
			}
			$AdminUI->disp_view( 'files/views/_file_properties.form.php' );
			break;

		case 'edit_settings':
			// Display settings dialog:
			$AdminUI->disp_view( 'files/views/_file_browse_set.form.php' );
			break;
	}
}

/*
 * Diplay mode payload:
 */
switch( $fm_mode )
{
	case 'link_object':
		// Links dialog:
		$AdminUI->disp_view( 'files/views/_file_links.view.php' );
		break;
}

if( $mode != 'modal' )
{
	// -------------------
	// Browsing interface:
	// -------------------
	// Display VIEW:
	$AdminUI->disp_view( 'files/views/_file_browse.view.php' );


	// End payload block:
	$AdminUI->disp_payload_end();

	// Display body bottom, debug info and close </html>:
	$AdminUI->disp_global_footer();
}

?>