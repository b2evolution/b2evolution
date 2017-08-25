<?php
/**
 * This file implements the AJAX concurrent file uploader
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
 */

function out_echo( $message, $specialchars, $display = true )
{
	$response = array();
	$response['success'] = isset( $message['status'] ) && in_array( $message['status'], array( 'success', 'rename' ) );

	if( $message['status'] == 'error' )
	{
		$response['error'] = $message['error'];
		unset( $message['error'] );
	}

	if( isset( $message['text'] ) )
	{
		$message['text'] = base64_encode( $message['text'] );
	}

	if( $specialchars == 1 )
	{
		$response['specialchars'] = 1;
		if( isset( $message['text'] ) )
		{
			$message['text'] = htmlspecialchars( $message['text'] );
		}
	}
	else
	{
		$message['specialchars'] = 0;
	}

	$response['data'] = $message;
	if( $display )
	{
		echo evo_json_encode( $response );
	}
	else
	{
		return evo_json_encode( $response );
	}
}


$specialchars = 0;
if( isset( $_FILES['qqfile'] ) )
{
	$specialchars = 1;
}

$message = array();

require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';
require_once dirname(__FILE__).'/upload_handler.php';

// Check that post_max_size is not exceeded
if( isset( $_SERVER["CONTENT_LENGTH"] ) )
{
	if( $_SERVER["CONTENT_LENGTH"] > return_bytes( ini_get( 'post_max_size' ) ) )
	{
		$message['error'] = sprintf( T_('File cannot be uploaded because maximum post size is too small. The maximum allowed post size is %s.'), ini_get( 'post_max_size' ) );
		$message['status'] = 'error';
		out_echo( $message, $specialchars );
		exit();
	}
}

// Stop a request from the blocked IP addresses or Domains
antispam_block_request();

// Do not append Debuglog to response!
$debug = false;

// Do not append Debug JSlog to response!
$debug_jslog = false;

// Don't check new updates from b2evolution.net (@see b2evonet_get_updates()),
// in order to don't break the response data:
$allow_evo_stats = false;

global $current_User;

param( 'upload', 'boolean', true );
param( 'root_and_path', 'filepath', true );
param( 'blog', 'integer' );
param( 'link_owner', 'string' );
// Use the glyph or font-awesome icons if requested by skin
param( 'b2evo_icons_type', 'string', '' );

// Check that this action request is not a CSRF hacked request:
if( ! $Session->assert_received_crumb( 'file', false ) )
{ // Send a clear error message
	die( T_('Incorrect crumb received. Did you wait too long before uploading?') );
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
{
	$message['error'] = '#'.$root_and_path.'# Bad request. Unknown upload location!'; // NO TRANS!!
	$message['status'] = 'error';
	out_echo( $message, $specialchars );
	exit();
}

if( $upload && ( !$current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) ) )
{
	$message['error'] = T_( 'You don\'t have permission to upload on this file root.' );
	$message['status'] = 'error';
	$response = out_echo( $message, $specialchars, false );
	exit( $response );
}

if( $upload )
{ // Create the object and assign property

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
		$message['error'] = sprintf( T_( 'We cannot open the folder %s. PHP needs execute permissions on this folder.' ), '<b>'.$media_path.'</b>' );
		$message['status'] = 'error';
		out_echo( $message, $specialchars );
		exit();
	}

	$newName = $file->getName();
	$oldName = $newName;
	// validate file name
	if( $error_filename = process_filename( $newName, true, true, $fm_FileRoot, $path ) )
	{ // Not a file name or not an allowed extension
		$message['error'] = $error_filename;
		$message['status'] = 'error';
		out_echo( $message, $specialchars );
		syslog_insert( sprintf( 'The uploaded file %s has an unrecognized extension', '[['.$newName.']]' ), 'warning', 'file' );
		exit();
	}

	// Process a name of old name
	process_filename( $oldName, true );

	list( $newFile, $oldFile_thumb ) = check_file_exists( $fm_FileRoot, $path, $newName );
	$newName = $newFile->get( 'name' );

	// If everything is ok, save the file somewhere
	if( ! isset( $result['success'] ) || ! $result['success'] )
	{ // Error on upload
		$message['error'] = $result['error'];
		$message['status'] = 'error';
		out_echo( $message, $specialchars );
		exit();
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

		if( ! empty( $link_owner ) )
		{	// Try to link the uploaded file to the object Item/Comment/EmailCampaign:
			list( $link_owner_type, $link_owner_ID, $link_owner_is_temp ) = explode( '_', $link_owner, 3 );
			$link_owner_ID = intval( $link_owner_ID );
			if( $link_owner_ID > 0 )
			{
				switch( $link_owner_type )
				{
					case 'item':
						if( $link_owner_is_temp )
						{	// Get LinkOwner object of the Temporary ID for new creating Item:
							load_class( 'items/model/_item.class.php', 'Item' );
							$LinkOwner = new LinkItem( new Item(), $link_owner_ID );
						}
						else
						{	// Get LinkOwner object of the Item:
							$ItemCache = & get_ItemCache();
							if( $linked_Item = & $ItemCache->get_by_ID( $link_owner_ID, false, false ) )
							{
								$LinkOwner = new LinkItem( $linked_Item );
							}
						}
						break;

					case 'comment':
						// Get LinkOwner object of the Comment
						$CommentCache = & get_CommentCache();
						if( $linked_Comment = & $CommentCache->get_by_ID( $link_owner_ID, false, false ) )
						{
							$LinkOwner = new LinkComment( $linked_Comment );
						}
						break;

					case 'emailcampaign':
						// Get LinkOwner object of the EmailCampaign:
						$EmailCampaignCache = & get_EmailCampaignCache();
						if( $linked_EmailCampaign = & $EmailCampaignCache->get_by_ID( $link_owner_ID, false, false ) )
						{
							$LinkOwner = new LinkEmailCampaign( $linked_EmailCampaign );
						}
						break;

					case 'message':
						// Get LinkOwner object of the Message:
						load_class( 'messaging/model/_message.class.php', 'Message' );
						$LinkOwner = new LinkMessage( new Message(), $link_owner_ID );
						break;
				}
			}

			if( isset( $LinkOwner ) )
			{ // Link the uploaded file to the object only if it is found in DB
				$LinkCache = & get_LinkCache();
				do
				{
					$new_link_ID = $newFile->link_to_Object( $LinkOwner );
					// Check if Link has been created really
					$new_Link = & $LinkCache->get_by_ID( $new_link_ID, false, false );
				}
				while( empty( $new_Link ) );
				$current_File = $newFile; // $current_File is used in the function link_actions() as global var
			}
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
					'file'    => $newFile->get_preview_thumb( 'fulltype' ),
					'oldname' => $oldName,
					'oldpath' => $old_File->get_root_and_rel_path(),
				);
		}
		else
		{ // Success uploading
			$message = array(
					'text'   => $newFile->get_preview_thumb( 'fulltype' ),
					'status' => 'success',
				);
			report_user_upload( $newFile );
		}

		$creator = $newFile->get_creator();

		$message['filetype'] = $newFile->get( 'type' );
		$message['newname'] = $newName;
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

		$message['warning'] = $warning;
		$message['path'] = rawurlencode( $newFile->get_rdfp_rel_path() );
		$message['checkbox'] = '<span name="surround_check" class="checkbox_surround_init">'
				.'<input title="'.T_('Select this file').'" type="checkbox" class="checkbox"'
				.' name="fm_selected[]" value="'.format_to_output( $newFile->get_rdfp_rel_path(), 'formvalue' ).'" id="cb_filename_u'.$newFile->ID.'" />'
			.'</span>'
			.'<input type="hidden" name="img_tag_u'.$newFile->ID.'" id="img_tag_u'.$newFile->ID.'"'
				.' value="'.format_to_output( $newFile->get_tag(), 'formvalue' ).'" />';

		if( ! empty( $new_Link ) )
		{ // Send also the link data if it was created
			$message['link_ID'] = $new_Link->ID;
			$message['link_url'] = $newFile->get_view_link();
			$message['link_preview'] = $new_Link->get_preview_thumb();
			$message['link_actions'] = link_actions( $new_Link->ID, 'last', $link_owner_type );
			$message['link_order'] = $new_Link->get( 'order' );
			$mask_row = (object) array(
					'link_ID'       => $new_Link->ID,
					'file_ID'       => $newFile->ID,
					'file_type'     => $newFile->get_file_type(),
					'link_position' => $new_Link->get( 'position' ),
				);
			$message['link_position'] = display_link_position( $mask_row );
		}

		out_echo( $message, $specialchars );
		exit();
	}

	$message['error'] = T_( 'The file could not be saved!' );
	$message['status'] = 'error';
	out_echo( $message, $specialchars );
	exit();

}

$message['error'] =  'Invalid upload param';
$message['status'] = 'error';
out_echo( $message, $specialchars );
exit();

?>