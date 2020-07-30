<?php
/**
 * This file implements the AJAX concurrent file uploader
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
 */


/**
 * Print out result of uploader process for AJAX response in JSON format
 *
 * @param array Message data to print out
 */
function evo_uploader_result( $message )
{
	$response = array();
	$response['success'] = isset( $message['status'] ) && in_array( $message['status'], array( 'success', 'rename' ) );

	if( isset( $message['error'] ) )
	{	// If error message is passed:
		$response['error'] = $message['error'];
		unset( $message['error'] );
		if( ! isset( $message['status'] ) )
		{	// Set status to error when it is not provided:
			$message['status'] = 'error';
		}
	}

	if( isset( $message['text'] ) )
	{
		$message['text'] = base64_encode( $message['text'] );
	}

	if( isset( $_FILES['qqfile'] ) )
	{	// If file is sending to quick uploader:
		$response['specialchars'] = 1; // Used to decode chars on JS side
		if( isset( $message['text'] ) )
		{	// Convert special chars to HTML entities:
			$message['text'] = htmlspecialchars( $message['text'] );
		}
	}
	else
	{	// Don't try to encode when no file was sent:
		$message['specialchars'] = 0;
	}

	// Do not append Debuglog and JSlog to response!
	global $debug, $debug_jslog;
	$debug = false;
	$debug_jslog = false;

	$response['data'] = $message;

	// Print out message data for response:
	exit( evo_json_encode( $response ) );
}


/**
 * Print out error result of uploader process
 *
 * @param string Error message
 */
function evo_uploader_error( $error_message )
{
	// Log each error message in system:
	syslog_insert( 'Upload error: '.$error_message, 'error', 'file' );
	// Return result data with error message for AJAX response:
	evo_uploader_result( array( 'error' => $error_message ) );
	// EXIT here.
}


require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';
require_once dirname(__FILE__).'/upload_handler.php';

// Check that post_max_size is not exceeded:
if( isset( $_SERVER['CONTENT_LENGTH'] ) &&
    $_SERVER['CONTENT_LENGTH'] > return_bytes( ini_get( 'post_max_size' ) ) )
{
	evo_uploader_error( sprintf( T_('File cannot be uploaded because maximum post size is too small. The maximum allowed post size is %s.'), ini_get( 'post_max_size' ) ) );
	// EXIT here.
}

// Stop a request from the blocked IP addresses or Domains
antispam_block_request();

// Don't check new updates from b2evolution.net (@see b2evonet_get_updates()),
// in order to don't break the response data:
$allow_evo_stats = false;

global $current_User;

param( 'root_and_path', 'filepath' );
param( 'blog', 'integer' );
param( 'link_owner', 'string' );
param( 'link_position', 'string', NULL );
param( 'fm_mode', 'string' );
// Use the glyph or font-awesome icons if requested by skin
param( 'b2evo_icons_type', 'string', '' );
param( 'prefix', 'string', '' );

// Check that this action request is not a CSRF hacked request:
if( ! $Session->assert_received_crumb( 'file', false ) )
{	// Send a clear error message:
	evo_uploader_error( T_('Incorrect crumb received. Did you wait too long before uploading?') );
	// EXIT here.
}

$upload_path = false;
if( strpos( $root_and_path, '::' ) )
{
	list( $root, $path ) = explode( '::', $root_and_path, 2 );
	$FileRootCache = & get_FileRootCache();
	$fm_FileRoot = $FileRootCache->get_by_ID( $root );
	if( $fm_FileRoot )
	{
		$non_canonical_list_path = $fm_FileRoot->ads_path.$path;
		$upload_path = get_canonical_path( $non_canonical_list_path );
	}
}

if( $upload_path === false )
{	// Stop on wrong upload path:
	evo_uploader_error( 'Bad request. Unknown upload location "'.$root_and_path.'"!' ); // NO TRANS!!
	// EXIT here.
}

$link_owner_type = NULL;
$link_owner_ID = NULL;
$link_owner_is_temp = NULL;
if( ! empty( $link_owner ) )
{	// Get link owner data for Item/Comment/EmailCampaign/Message from request param:
	list( $link_owner_type, $link_owner_ID, $link_owner_is_temp ) = explode( '_', $link_owner, 3 );
	$link_owner_ID = intval( $link_owner_ID );
}
// Try to get LinkOwner by type and ID:
$LinkOwner = get_LinkOwner( ( $link_owner_is_temp ? 'temporary' : $link_owner_type ), $link_owner_ID );

if( $link_owner_type == 'comment' && $prefix == 'meta_' )
{	// Set comment type for proper permission check - meta comment attachments are always allowed:
	$LinkOwner->Comment->type = 'meta';
}

if( ! check_perm_upload_files( $LinkOwner, $fm_FileRoot ) )
{	// If current User has no permission to upload files into the file root:
	evo_uploader_error( T_('You don\'t have permission to upload on this file root.') );
	// EXIT here.
}

// Check for sensitive filetype upload:
$path_info = pathinfo( param( 'qqfilename', 'string', true ) );
$FiletypeCache = & get_FiletypeCache();
$upload_Filetype = $FiletypeCache->get_by_extension( isset( $path_info['extension'] ) ? strtolower( $path_info['extension'] ) : '', false, false );
if( ! $upload_Filetype || ! $upload_Filetype->is_allowed() )
{
	evo_uploader_error( sprintf( T_('Admins can upload/rename/edit this file type only if %s in the <a %s>configuration files</a>'),
		'<code>$admins_can_manipulate_sensitive_files = true</code>', 'href="'.get_manual_url( 'advanced-php' ).'"' ) );
	// EXIT here.
}

// Start to upload if it is allowed by all checks above:

$size_limits = array( return_bytes( ini_get( 'post_max_size' ) ), return_bytes( ini_get( 'upload_max_filesize') ) );
if( $Settings->get( 'upload_maxkb' ) )
{
	$size_limits[] = $Settings->get( 'upload_maxkb' ) * 1024;
}

$file = new UploadHandler();
// Specify the list of valid extensions, ex. array("jpeg", "xml", "bmp")
$file->allowedExtensions = array(); // all files types allowed by default
// Specify max file size in bytes.
$file->sizeLimit = min( $size_limits );
// Specify the input name set in the javascript.
$file->inputName = "qqfile"; // matches Fine Uploader's default inputName value by default
// If you want to use the chunking/resume feature, specify the folder to temporarily save parts.
$file->chunksFolder = 'chunks';
$method = $_SERVER['REQUEST_METHOD'];
if ( $method == 'POST' )
{
	header( 'Content-Type: text/plain' );
	// Assumes you have a chunking.success.endpoint set to point here with a query parameter of "done".
	// For example: /myserver/handlers/endpoint.php?done
	if ( isset( $_GET['done'] ) )
	{
		$result = $file->combineChunks();
	}
	else
	{ // Handles upload requests
		// Call handleUpload() with the name of the folder, relative to PHP's getcwd()
		$result = $file->handleUpload();
		// To return a name used for uploaded file you can use the following line.
		$result["uploadName"] = $file->getUploadName();
	}
}
else if( $method == 'DELETE' )
{ // for delete file requests
	$result = $file->handleDelete("files");
}
else
{
	header( 'HTTP/1.0 405 Method Not Allowed' );
}

if( empty( $fm_FileRoot ) )
{ // Stop when this object is NULL, it can happens when media path has no rights to write
	evo_uploader_error( sprintf( T_('We cannot open the folder %s. PHP needs execute permissions on this folder.'), '<b>'.$media_path.'</b>' ) );
	// EXIT here.
}

$newName = $file->getName();
$oldName = $newName;
// validate file name
if( $error_filename = process_filename( $newName, true, true, $fm_FileRoot, $path ) )
{ // Not a file name or not an allowed extension
	evo_uploader_error( $error_filename );
	// EXIT here.
}

// Process a name of old name
process_filename( $oldName, true );

list( $newFile, $oldFile_thumb ) = check_file_exists( $fm_FileRoot, $path, $newName );
$newName = $newFile->get( 'name' );

// If everything is ok, save the file somewhere
if( ! isset( $result['success'] ) || ! $result['success'] )
{ // Error on upload
	evo_uploader_error( $result['error'] );
	// EXIT here.
}
$file_content = $result['contents'];

if( ! file_exists( $newFile->get_dir() ) )
{ // Create a folder for new uploaded file if it doesn't exist yet
	mkdir_r( $newFile->get_dir() );
}

if( save_to_file( $file_content, $newFile->get_full_path(), 'wb' ) )
{
	// Change to default chmod settings
	$newFile->chmod( NULL );

	// Refreshes file properties (type, size, perms...)
	$newFile->load_properties();

	// save file into the db
	$newFile->dbsave();

	// Prepare the uploaded file to the final format ( E.g. Resize and Rotate images )
	prepare_uploaded_files( array( $newFile ) );

	if( isset( $LinkOwner ) )
	{ // Link the uploaded file to the object only if it is found in DB:
		$LinkCache = & get_LinkCache();
		do
		{
			$new_link_ID = $newFile->link_to_Object( $LinkOwner, 0, $link_position );
			// Check if Link has been created really
			$new_Link = & $LinkCache->get_by_ID( $new_link_ID, false, false );
		}
		while( empty( $new_Link ) );
		$current_File = $newFile; // $current_File is used in the function link_actions() as global var
	}

	$message = '';
	if( ! empty( $oldFile_thumb ) )
	{
		$image_info = getimagesize( $newFile->get_full_path() );
		if( $image_info )
		{
			$newFile_thumb = $newFile->get_preview_thumb( 'fulltype' );
		}
		else
		{
			$newFile_thumb = $newFile->get_size_formatted();
		}
		$message .= '<br />';
		$message .= sprintf( T_('%s was renamed to %s. Would you like to replace %s with the new version instead?'),
							'&laquo;'.$oldName.'&raquo;', '&laquo;'.$newName.'&raquo;', '&laquo;'.$oldName.'&raquo;' );
		$message .= '<div class="invalid" title="'.T_('File name changed.').'">';
		$message .= '<input type="radio" name="Renamed_'.$newFile->ID.'" value="Yes" id="Yes_'.$newFile->ID.'"/>';
		$message .= '<label for="Yes_'.$newFile->ID.'">';
		$message .= sprintf( T_("Replace the old version %s with the new version %s and keep old version as %s."), $oldFile_thumb, $newFile_thumb, $newName ).'</label><br />';
		$message .= '<input type="radio" name="Renamed_'.$newFile->ID.'" value="No" id="No_'.$newFile->ID.'" checked="checked"/>';
		$message .= '<label for="No_'.$newFile->ID.'">';
		$message .= sprintf( T_("Don't touch the old version and keep the new version as %s."), $newName ).'</label><br />';
		$message .= '</div>';
	}

	$warning = '';
	if( $Messages->count > 0 )
	{ // Some errors/info messages can be created during prepare_uploaded_files()
		$warning .= $Messages->display( NULL, NULL, false );
	}

	if( ! empty( $message ) )
	{ // There is an error message on uploading
		$FileCache = & get_FileCache();
		$old_File = & $FileCache->get_by_root_and_path( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, trailing_slash( $path ).$oldName, true );

		$message .= '<input type="hidden" name="renamedFiles['.$newFile->ID.'][newName]" value="'.$newName.'" />'.
			'<input type="hidden" name="renamedFiles['.$newFile->ID.'][oldName]" value="'.$oldName.'" />';

		$message = array(
				'text'    => $message,
				'status'  => 'rename',
				'file'    => $newFile->get_preview_thumb( 'fulltype', array( 'init' => true ) ),
				'old_rootrelpath' => $old_File->get_root_and_rel_path(),
				'oldpath' => $old_File->get_root_and_rel_path(),
			);
	}
	else
	{ // Success uploading
		$message = array(
				'text'   => $newFile->get_preview_thumb( 'fulltype', array( 'init' => true ) ),
				'status' => 'success',
			);
		report_user_upload( $newFile );
	}

	$creator = $newFile->get_creator();

	$message['filetype'] = $newFile->get( 'type' );
	$message['formatted_name'] = file_td_name( $newFile );
	$message['new_rootrelpath'] = $newFile->get_root_and_rel_path();
	$message['newpath'] = $newFile->get_root_and_rel_path();
	$message['filesize'] = bytesReadable( $newFile->get_size() );

	if( $UserSettings->get('fm_showtypes') )
	{
		$message['filetype'] = $newFile->get_type();
	}

	if( $UserSettings->get('fm_showcreator') )
	{
		$message['creator'] = $creator ? $creator->get( 'login' ) : T_('Unknown');
	}

	if( $UserSettings->get( 'fm_showdownload' ) )
	{
		$message['downloads'] = 0;
	}

	if( $UserSettings->get('fm_showfsowner') )
	{
		$message['owner'] = $newFile->get_fsowner_name();
	}

	if( $UserSettings->get('fm_showfsgroup') )
	{
		$message['group'] = $newFile->get_fsgroup_name();
	}

	if( $UserSettings->get( 'fm_showdate' ) != 'no' )
	{	// Get last modified datetime:
		$message['file_date'] = file_td_lastmod( $newFile );
	}

	$message['file_actions'] = file_td_actions( $newFile );

	$message['warning'] = $warning;
	$message['path'] = $newFile->get_rdfp_rel_path();
	$message['checkbox'] = '<input title="'.T_('Select this file').'" type="checkbox" class="checkbox"'
			.' name="fm_selected[]" value="'.format_to_output( $newFile->get_rdfp_rel_path(), 'formvalue' ).'" id="cb_filename_u'.$newFile->ID.'" />'
		.'<input type="hidden" name="img_tag_u'.$newFile->ID.'" id="img_tag_u'.$newFile->ID.'"'
			.' value="'.format_to_output( $newFile->get_tag(), 'formvalue' ).'" />';

	if( ! empty( $new_Link ) )
	{ // Send also the link data if it was created
		$message['link_ID'] = $new_Link->ID;
		$message['link_url'] = $newFile->get_view_link();
		$message['link_preview'] = $new_Link->get_preview_thumb();
		$message['link_actions'] = link_actions( $new_Link->ID, 'last', $link_owner_type );
		$message['link_order'] = $new_Link->get( 'order' );
		if( isset( $fm_mode ) && $fm_mode == 'file_select' )
		{
			$message['select_link_button'] = select_link_button( $new_Link->ID );
		}
		$mask_row = (object) array(
				'link_ID'       => $new_Link->ID,
				'file_ID'       => $newFile->ID,
				'file_type'     => $newFile->get_file_type(),
				'link_position' => $new_Link->get( 'position' ),
			);
		$message['link_position'] = display_link_position( $mask_row, $fm_mode != 'file_select', param( 'prefix', 'string' ) );
	}

	evo_uploader_result( $message );
	// EXIT here.
}

// Display log error message from the called function save_to_file() above:
evo_uploader_error( empty( $evo_save_file_error_msg ) ? T_('The file could not be saved!') : $evo_save_file_error_msg );
// EXIT here.
?>