<?php
/**
 * This file implements the UI controller for file upload.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * (dh please re-add)
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
 * (dh please re-add)
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * (dh please re-add)
 *
 * @version $Id: upload.ctrl.php 6334 2014-03-25 13:11:30Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Filelist
 * fp>> TODO: When the user is viewing details for a file he should (by default) not be presented with the filelist in addition to the file properties
 * In cases like that, we should try to avoid instanciating a Filelist.
 */
load_class( 'files/model/_filelist.class.php', 'FileList' );

global $current_User, $Plugins;

global $dispatcher;

global $blog;

global $item_ID, $iframe_name;


// Check permission:
$current_User->check_perm( 'files', 'add', true, $blog ? $blog : NULL );

$AdminUI->set_path( 'files' );

// 1 when AJAX request, E.g. when popup is used to link a file to item/comment
param( 'ajax_request', 'integer', 0, true );

// Save the number of already existing error messages
$initial_error_count = $Messages->count( 'error' );

// Params that may need to be passed through:
param( 'fm_mode', 'string', NULL, true );
param( 'link_type', 'string', NULL, true );
param( 'link_object_ID', 'integer', NULL, true );
param( 'user_ID', 'integer', NULL, true );
param( 'iframe_name', 'string', '', true );

$action = param_action();

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
	param( 'path', 'string', '', true );  // the path relative to the root dir
	if( param( 'new_root', 'string', '' )
		&& $new_root != $root )
	{ // We have changed root in the select list
		$root = $new_root;
		$path = '';
	}
}

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

	if( ! $fm_FileRoot || !isset( $available_Roots[$fm_FileRoot->ID] ) || !$current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) )
	{ // Root not found or not in list of available ones. If avatar upload is in progress, the edited user root doesn't have to be available.
		$Messages->add( T_('You don\'t have upload permission to the requested root directory.'), 'warning' );
		$fm_FileRoot = false;
	}
}

if( ! $fm_FileRoot )
{ // No root requested (or the requested is invalid), get the first one available:
	if( $available_Roots )
	{
		foreach( $available_Roots as $l_FileRoot )
		{
			if( $current_User->check_perm( 'files', 'add', false, $l_FileRoot ) )
			{ // select
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


file_controller_build_tabs();


// If there were errors, display them and exit (especially in case there's no valid FileRoot ($fm_FileRoot)):
// TODO: dh> this prevents users from uploading if _any_ blog media directory is not writable.
//           See http://forums.b2evolution.net/viewtopic.php?p=49001#49001
// Exit only if new error messages were added in this file
if( $Messages->count( 'error' ) > $initial_error_count )
{
	$AdminUI->set_path( 'files', 'upload' );

	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();

	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();
	$AdminUI->disp_payload_begin();
	$AdminUI->disp_payload_end();

	$AdminUI->disp_global_footer();
	exit(0);
}


$Debuglog->add( 'FM root: '.var_export( $fm_FileRoot, true ), 'files' );
$Debuglog->add( 'FM _ads_list_path: '.var_export( $ads_list_path, true ), 'files' );


if( empty($ads_list_path) )
{ // We have no Root / list path, there was an error. Unset any action.
	$action = '';
}




// Check permissions:
// Tblue> Note: Perm 'files' (level 'add') gets checked above with $assert = true.
if( ! $Settings->get('upload_enabled') )
{ // Upload is globally disabled
	$Messages->add( T_('Upload is disabled.'), 'error' );
}


// If there were errors, display them and exit (especially in case there's no valid FileRoot ($fm_FileRoot)):
// Exit only if new error messages were added in this file
if( $Messages->count( 'error' ) > $initial_error_count )
{
	$AdminUI->set_path( 'files', 'upload' );

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


// Quick mode means "just upload and leave mode when successful"
param( 'upload_quickmode', 'integer', 0 );

/**
 * Remember failed files (and the error messages)
 * @var array
 */
$failedFiles = array();

/**
 * Remember renamed files (and the messages)
 * @var array
 */
param( 'renamedFiles', 'array/array/string', array(), true );
$renamedMessages = array();

// Process files we want to get from an URL:
param( 'uploadfile_url', 'array/string', array() );
param( 'uploadfile_source', 'array/string', array() );
if( ( $action != 'switchtab' ) && $uploadfile_url )
{
	// Check that this action request is not a CSRF hacked request:
	$Session->assert_received_crumb( 'file' );

	foreach($uploadfile_url as $k => $url)
	{
		if( ! isset($uploadfile_source[$k]) || $uploadfile_source[$k] != 'upload' )
		{ // upload by URL has not been selected
			continue;
		}
		if( strlen($url) )
		{
			// Validate URL and parse it for the file name
			if( ! is_absolute_url($url)
				|| ! ($parsed_url = parse_url($url))
				|| empty($parsed_url['scheme']) || empty($parsed_url['host'])
				|| empty($parsed_url['path']) || $parsed_url['path'] == '/' )
			{	// Includes forbidding getting the root of a server
				$failedFiles[$k] = T_('The URL must start with <code>http://</code> or <code>https://</code> and point to a valid file!');
				continue;
			}

			$file_contents = fetch_remote_page($url, $info, NULL, $Settings->get('upload_maxkb'));

			if( $file_contents !== false )
			{
				// Create temporary file and insert contents into it.
				$tmpfile_name = tempnam(sys_get_temp_dir(), 'fmupload');
				if( ! $tmpfile_name )
				{
					$failedFiles[$k] = 'Failed to find temporary directory.'; // no trans: very unlikely
					continue;
				}

				if( ! save_to_file( $file_contents, $tmpfile_name, 'w' ) )
				{
					unlink($tmpfile_name);
					$failedFiles[$k] = sprintf( 'Could not write to temporary file (%s).', $tmpfile_name );
					continue;
				}

				// Fake/inject info into PHP's array of uploaded files.
				// fp> TODO! This is a nasty dirty hack. That kind of stuff always breaks somewhere down the line. Needs cleanup.
				// This allows us to treat it (nearly) the same way as regular uploads, apart from
				// is_uploaded_file(), which we skip and move_uploaded_file() (where we use rename()).
				$_FILES['uploadfile']['name'][$k] = rawurldecode(basename($parsed_url['path']));
				$_FILES['uploadfile']['size'][$k] = evo_bytes($file_contents);
				$_FILES['uploadfile']['error'][$k] = 0;
				$_FILES['uploadfile']['tmp_name'][$k] = $tmpfile_name;
				$_FILES['uploadfile']['_evo_fetched_url'][$k] = $url; // skip is_uploaded_file and keep info
				unset($file_contents);
			}
			else
			{
				$failedFiles[$k] = sprintf(
					'Could not retrieve file. Error: %s (status %s). Used method: %s.',
					$info['error'],
					isset($info['status']) ? $info['status'] : '-',
					isset($info['used_method']) ? $info['used_method'] : '-');
			}
		}
	}
}

// Process renaming/replacing of old versions:
if( ! empty($renamedFiles) )
{
	foreach( $renamedFiles as $rKey => $rData )
	{
		$replace_old = param( 'Renamed_'.$rKey, 'string', null );
		if( $replace_old == "Yes" )
		{ // replace the old file with the new one
			replace_old_file_with_new( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, $path, $renamedFiles[$rKey]['newName'], $renamedFiles[$rKey]['oldName'] );
		}
	}
	forget_param( 'renamedFiles' );
	unset( $renamedFiles );

	if( $upload_quickmode )
	{
		header_redirect( regenerate_url( 'ctrl', 'ctrl=files', '', '&' ) );
	}
}

// Process uploaded files:
if( ( $action != 'switchtab' ) && isset($_FILES) && count( $_FILES ) )
{
	// Stop a request from the blocked IP addresses or Domains
	antispam_block_request();

	// Check that this action request is not a CSRF hacked request:
	$Session->assert_received_crumb( 'file' );

	$upload_result = process_upload( $fm_FileRoot->ID, $path, false, false, $upload_quickmode );
	if( isset( $upload_result ) )
	{
		$failedFiles = $upload_result['failedFiles'];
		$uploadedFiles = $upload_result['uploadedFiles'];
		$renamedFiles = $upload_result['renamedFiles'];
		$renamedMessages = $upload_result['renamedMessages'];

		foreach( $uploadedFiles as $uploadedFile )
		{
			$success_msg = sprintf( T_('The file &laquo;%s&raquo; has been successfully uploaded to the server.'), $uploadedFile->dget('name') );

			// Allow to insert/link new upload into currently edited link object:
			if( $mode == 'upload' && !empty( $link_object_ID ) && !empty( $link_type ) )
			{	// The filemanager has been opened from a link owner object, offer to insert an img tag into original object.
				$LinkOwner = get_link_owner( $link_type, $link_object_ID );
				// TODO: Add plugin hook to allow generating JS insert code(s)
				$img_tag = format_to_output( $uploadedFile->get_tag(), 'formvalue' );
				if( $uploadedFile->is_image() )
				{
					$link_msg = $LinkOwner->translate( 'Link this image to your owner' );
					$link_note = T_('recommended - allows automatic resizing');
				}
				else
				{
					$link_msg = $LinkOwner->translate( 'Link this file to your owner' );
					$link_note = $LinkOwner->translate( 'The file will be linked for download at the end of the owner' );
				}
				$success_msg .= '<ul>'
						.'<li>'.action_icon( T_('Link this file!'), 'link',
									regenerate_url( 'fm_selected,ctrl', 'ctrl=files&amp;action=link_inpost&amp;fm_selected[]='.rawurlencode($uploadedFile->get_rdfp_rel_path()).'&amp;'.url_crumb('file') ),
									' '.$link_msg, 5, 5, array( 'target' => $iframe_name ) )
						.' ('.$link_note.')</li>'

						.'<li>'.T_('or').' <a href="#" onclick="if( window.focus && window.opener ){'
						.'window.opener.focus(); textarea_wrap_selection( window.opener.document.getElementById(\''.$LinkOwner->type.'form_post_content\'), \''
						.format_to_output( $uploadedFile->get_tag(), 'formvalue' ).'\', \'\', 1, window.opener.document ); } return false;">'
						.$LinkOwner->translate( 'Insert the following code snippet into your owner' ).'</a> : <input type="text" value="'.$img_tag.'" size="60" /></li>'
						// fp> TODO: it would be supacool to have an ajaxy "tumbnail size selector" here that generates a thumnail of requested size on server and then changes the code in the input above
					.'</ul>';
			}

			$Messages->add( $success_msg, 'success' );
		}
	}

	if( $upload_quickmode && !empty($failedFiles) )
	{	// Transmit file error to next page!
		$Messages->add( $failedFiles[0], 'error' );
		unset($failedFiles);
	}

	if( empty($failedFiles) && empty($renamedFiles) )
	{ // quick mode or no failed files, Go back to Browsing
		// header_redirect( $dispatcher.'?ctrl=files&root='.$fm_FileRoot->ID.'&path='.rawurlencode($path) );
		header_redirect( regenerate_url( 'ctrl', 'ctrl=files', '', '&' ) );
	}
}


file_controller_build_tabs();

$AdminUI->set_path( 'files', 'upload' );

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
$AdminUI->breadcrumbpath_add( /* TRANS: noun */ T_('Advanced Upload'), '?ctrl=upload&amp;blog=$blog$&amp;root='.$fm_FileRoot->ID );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


/*
 * Display payload:
 */
$AdminUI->disp_view( 'files/views/_file_upload.view.php' );

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>