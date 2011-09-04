<?php
/**
 * This file implements the UI controller for file upload.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id$
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

// Params that may need to be passed through:
param( 'fm_mode', 'string', NULL, true );
param( 'item_ID', 'integer', NULL, true );
param( 'user_ID', 'integer', NULL, true );
param( 'iframe_name', 'string', '', true );
param( 'tab3', 'string' );
if( empty( $tab3 ) )
{
	$tab3 = 'quick';
}

$action = param_action();

if( $tab3 == 'quick' )
{
	require_css( 'quick_upload.css' );
	require_js( 'multiupload/sendfile.js' );
	require_js( 'multiupload/quick_upload.js' );
}

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
if( $Messages->has_errors() )
{
	$AdminUI->set_path( 'files', 'upload', $tab3 );

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
if( $Messages->has_errors() )
{
	$AdminUI->set_path( 'files', 'upload', $tab3 );

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
param( 'renamedFiles', 'array', array(), true );
$renamedMessages = array();

// Process files we want to get from an URL:
param( 'uploadfile_url', 'array', array() );
param( 'uploadfile_source', 'array', array() );
if( $uploadfile_url )
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
				$tmpfile = fopen($tmpfile_name, 'w');
				if( ! fwrite($tmpfile, $file_contents) )
				{
					unlink($tmpfile);
					$failedFiles[$k] = sprintf( 'Could not write to temporary file (%s).', $tmpfile );
					continue;
				}
				fclose($tmpfile);

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
					T_('Could not retrieve file. Error: %s (status %s). Used method: %s.'),
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
			$FileCache = & get_FileCache();
			$newFile = & $FileCache->get_by_root_and_path( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, trailing_slash($path).$renamedFiles[$rKey]['newName'], true );
			$oldFile = & $FileCache->get_by_root_and_path( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, trailing_slash($path).$renamedFiles[$rKey]['oldName'], true );
			$new_filename = $newFile->get_name();
			$old_filename = $oldFile->get_name();
			$dir = $newFile->get_dir();
			$oldFile->rm_cache();
			$newFile->rm_cache();
			$error_message = '';

			// rename new uploaded file to temp file name
			$index = 0;
			$temp_filename = 'temp'.$index.'-'.$new_filename;
			while( file_exists( $dir.$temp_filename ) )
			{ // find an unused filename
				$index++;
				$temp_filename = 'temp'.$index.'-'.$new_filename;
			}

			// @rename will overwrite a file with the same name if exists. In this case it shouldn't be a problem.
			if( ! @rename( $newFile->get_full_path(), $dir.$temp_filename ) )
			{ // rename new file to temp file name failed
				$error_message = $Messages->add( sprintf( T_('The new file could not be renamed to %s'), $temp_filename ), 'error' );
			}

			if( empty( $error_message ) && ( ! @rename( $oldFile->get_full_path(), $dir.$new_filename ) ) )
			{ // rename original file to the new file name failed
				$error_message = sprintf( T_( "The original file could not be renamed to %s. The new file is now named %s." ), $new_filename, $temp_filename );
			}

			if( empty( $error_message ) && ( ! @rename( $dir.$temp_filename, $dir.$old_filename ) ) )
			{ // rename new file to the original file name failed
				$error_message = sprintf( T_( "The new file could not be renamed to %s. It is now named %s." ), $old_filename, $temp_filename );
			}

			if( empty( $error_message ) )
			{
				$Messages->add( sprintf( T_('%s has been replaced with the new version!'), $old_filename ), 'success' );
			}
			else
			{
				$Messages->add( $error_message, 'error' );
			}
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
if( isset($_FILES) && count( $_FILES ) )
{
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

			// Allow to insert/link new upload into currently edited post:
			if( $mode == 'upload' && !empty($item_ID) )
			{	// The filemanager has been opened from an Item, offer to insert an img tag into original post.
				// TODO: Add plugin hook to allow generating JS insert code(s)
				$img_tag = format_to_output( $uploadedFile->get_tag(), 'formvalue' );
				if( $uploadedFile->is_image() )
				{
					$link_msg = T_('Link this image to your post');
					$link_note = T_('recommended - allows automatic resizing');
				}
				else
				{
					$link_msg = T_('Link this file to your post');
					$link_note = T_('The file will be appended for download at the end of the post');
				}
				$success_msg .= '<ul>'
						.'<li>'.action_icon( T_('Link this file!'), 'link',
									regenerate_url( 'fm_selected,ctrl', 'ctrl=files&amp;action=link_inpost&amp;fm_selected[]='.rawurlencode($uploadedFile->get_rdfp_rel_path()).'&amp;'.url_crumb('file') ),
									' '.$link_msg, 5, 5, array( 'target' => $iframe_name ) )
						.' ('.$link_note.')</li>'

						.'<li>'.T_('or').' <a href="#" onclick="if( window.focus && window.opener ){'
						.'window.opener.focus(); textarea_wrap_selection( window.opener.document.getElementById(\'itemform_post_content\'), \''
						.format_to_output( $uploadedFile->get_tag(), 'formvalue' ).'\', \'\', 1, window.opener.document ); } return false;">'
						.T_('Insert the following code snippet into your post').'</a> : <input type="text" value="'.$img_tag.'" size="60" /></li>'
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

$AdminUI->set_path( 'files', 'upload', $tab3 );

// fp> TODO: this here is a bit sketchy since we have Blog & fileroot not necessarilly in sync. Needs investigation / propositions.
// Note: having both allows to post from any media dir into any blog.
$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Files'), '?ctrl=files&amp;blog=$blog$' );
if( !isset($Blog) || $fm_FileRoot->type != 'collection' || $fm_FileRoot->in_type_ID != $Blog->ID )
{	// Display only if we're not browsing our home blog
	$AdminUI->breadcrumbpath_add( $fm_FileRoot->name, '?ctrl=files&amp;blog=$blog$&amp;root='.$fm_FileRoot->ID,
			(isset($Blog) && $fm_FileRoot->type == 'collection') ? sprintf( T_('You are ready to post files from %s into %s...'),
			$fm_FileRoot->name, $Blog->get('shortname') ) : '' );
}
$AdminUI->breadcrumbpath_add( T_('Upload'), '?ctrl=upload&amp;blog=$blog$&amp;root='.$fm_FileRoot->ID );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


/*
 * Display payload:
 */
if( $tab3 == 'quick' )
{
	$AdminUI->disp_view( 'files/views/_file_quick_upload.view.php' );
}
else
{
	$AdminUI->disp_view( 'files/views/_file_upload.view.php' );
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.47  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.46  2011/05/06 07:04:46  efy-asimo
 * multiupload ui update
 *
 * Revision 1.45  2011/04/28 14:07:58  efy-asimo
 * multiple file upload
 *
 * Revision 1.44  2011/03/03 14:31:57  efy-asimo
 * use user.ctrl for avatar upload
 * create File object in the db if an avatar file is already on the user's profile picture folder
 *
 * Revision 1.43  2011/03/02 11:04:22  efy-asimo
 * Refactor file uploads for future use
 *
 * Revision 1.42  2011/01/18 16:23:02  efy-asimo
 * add shared_root perm and refactor file perms - part1
 *
 * Revision 1.41  2010/11/25 15:16:34  efy-asimo
 * refactor $Messages
 *
 * Revision 1.40  2010/10/27 14:56:42  efy-asimo
 * when replacing a file, keep a backup
 *
 * Revision 1.39  2010/09/16 14:12:24  efy-asimo
 * New avatar upload
 *
 * Revision 1.38  2010/07/16 08:39:26  efy-asimo
 * file rename_to and "replace existing file" - fix
 *
 * Revision 1.37  2010/04/19 17:19:02  blueyed
 * Upload controller:
 *  - rawurldecode the filename given in the URL. Especially for %20 etc. Fixes 'Invalid file name.'.
 *  - add laquo/raquo to filename in was-renamed error message. Usability nightmare there btw.
 *
 * Revision 1.36  2010/03/28 17:08:09  fplanque
 * minor
 *
 * Revision 1.35  2010/03/14 06:36:57  sam2kb
 * New plugin hooks: BeforeThumbCreate, AfterFileUpload
 *
 * Revision 1.34  2010/03/06 12:53:40  efy-asimo
 * Replace existing file bugfix
 *
 * Revision 1.33  2010/03/05 13:30:35  fplanque
 * cleanup/wording
 *
 * Revision 1.32  2010/02/17 12:59:52  efy-asimo
 * Replace existing file task
 *
 * Revision 1.31  2010/02/08 17:52:15  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.30  2010/01/16 14:27:03  efy-yury
 * crumbs, fadeouts, redirect, action_icon
 *
 * Revision 1.29  2010/01/03 13:10:58  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.28  2009/12/18 18:54:32  blueyed
 * trans/punctuation fix
 *
 * Revision 1.27  2009/12/06 22:55:18  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.26  2009/11/22 18:21:21  fplanque
 * keep cap
 *
 * Revision 1.25  2009/11/22 16:22:27  tblue246
 * Translation fixes
 *
 * Revision 1.24  2009/11/11 20:53:24  fplanque
 * restricting get from url a little bit so it doesn't allow getting the root of a website (this case was aboring silently before)
 *
 * Revision 1.23  2009/11/11 20:44:55  fplanque
 * minor/cleanup
 *
 * Revision 1.22  2009/11/11 20:16:14  fplanque
 * doc
 *
 * Revision 1.21  2009/11/11 19:25:59  fplanque
 * "after upload actions" are even better now :)
 *
 * Revision 1.20  2009/11/11 19:12:55  fplanque
 * Inproved actions after uploaded
 *
 * Revision 1.19  2009/10/29 22:17:20  blueyed
 * Filemanager upload: add "Upload by URL" fields. Cleanup/rewrite some JS on the go.
 *
 * Revision 1.18  2009/10/15 00:35:04  blueyed
 * Upload: make correction of extensions case insensitive.
 *
 * Revision 1.17  2009/10/02 20:34:31  blueyed
 * Improve handling of wrong file extensions for image.
 *  - load_image: if the wrong mimetype gets passed, return error, instead of letting imagecreatefrom* fail
 *  - upload: detect wrong extensions, rename accordingly and add a warning
 *
 * Revision 1.16  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.15  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.14  2009/09/14 13:01:31  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.13  2009/08/29 12:23:56  tblue246
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
 * Revision 1.12  2009/07/06 23:52:24  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.11  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.10  2008/09/29 08:30:43  fplanque
 * Avatar support
 *
 * Revision 1.9  2008/09/29 03:52:47  fplanque
 * bugfix - iframe name passthrough
 *
 * Revision 1.8  2008/02/20 02:48:25  blueyed
 * Fix default for "path" param, which is "" and not "/". Fixes double slashes in quick-uploaded files.
 *
 * Revision 1.7  2008/02/19 11:11:17  fplanque
 * no message
 *
 * Revision 1.6  2008/02/04 13:57:50  fplanque
 * wording
 *
 * Revision 1.5  2008/01/28 20:17:45  fplanque
 * better display of image file linking while in 'upload' mode
 *
 * Revision 1.4  2008/01/21 09:35:28  fplanque
 * (c) 2008
 *
 * Revision 1.3  2008/01/06 05:16:33  fplanque
 * enhanced upload
 *
 * Revision 1.2  2007/09/26 23:32:39  fplanque
 * upload context saving
 *
 * Revision 1.1  2007/06/25 10:59:53  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.10  2007/04/26 00:11:13  fplanque
 * (c) 2007
 *
 * Revision 1.9  2007/04/20 01:42:32  fplanque
 * removed excess javascript
 *
 * Revision 1.8  2007/02/22 18:36:57  fplanque
 * better error messages
 *
 * Revision 1.7  2007/01/24 13:44:56  fplanque
 * cleaned up upload
 *
 * Revision 1.6  2007/01/24 02:35:42  fplanque
 * refactoring
 *
 * Revision 1.5  2007/01/24 01:40:15  fplanque
 * Upload tab now stays in context
 *
 * Revision 1.4  2007/01/10 21:41:51  blueyed
 * todo: any "error" does not allow uploading and "blog media directory could not be created" is such an error, which may not be relevant
 *
 * Revision 1.3  2006/12/23 22:53:11  fplanque
 * extra security
 *
 * Revision 1.2  2006/12/22 01:09:30  fplanque
 * cleanup
 *
 * Revision 1.1  2006/12/22 00:51:34  fplanque
 * dedicated upload tab - proof of concept
 * (interlinking to be done)
 *
 */
?>
