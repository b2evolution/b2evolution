<?php
/**
 * This file implements the AJAX concurrent file uploader
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id$
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

global $current_User;

param( 'upload', 'boolean', true );
param( 'root_and_path', 'string', true );

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'file' );

$upload_path = false;
if( strpos( $root_and_path, '::' ) )
{
	list( $root, $path ) = explode( '::', $root_and_path, 2 );
	$FileRootCache = & get_FileRootCache();
	$fm_FileRoot = $FileRootCache->get_by_ID( $root );
	$non_canonical_list_path = $fm_FileRoot->ads_path.$path;
	$upload_path = get_canonical_path( $non_canonical_list_path );
}

if( $upload_path === false )
{
	echo '<span class="result_error">'.T_( 'Bad request. Unknown upload location!' ).'</span>';
	exit();
}

if( $upload && ( !$current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) ) )
{
	echo '<span class="result_error">'.T_( 'You don\'t have permission to upload on this file root.' ).'</span>';
	exit();
}

if( $upload )
{
	if (!function_exists('apache_request_headers'))
	{
		function apache_request_headers() { 
			foreach( $_SERVER as $key => $value ) {
				if( substr( $key, 0, 5 ) == "HTTP_" )
				{
					$key = str_replace( " ", "-", ucwords( strtolower( str_replace( "_", " ", substr( $key, 5 ) ) ) ) ); 
					$out[$key] = $value;
				}
				else
				{
					$out[$key]=$value; 
				}
			}
			return $out; 
		}
	}
	$headers = apache_request_headers();

	// set content_type and content_length because of compatibility with different php versions
	$content_type = 'Content-Type';
	if( ( ! isset( $headers['Content-Type'] ) ) && isset( $headers['CONTENT_TYPE'] ) )
	{
		$content_type = 'CONTENT_TYPE';
	}
	$content_length = 'Content-Length';
	if( ( ! isset( $headers['Content-Length'] ) ) && isset( $headers['CONTENT_LENGTH'] ) )
	{
		$content_length = 'CONTENT_LENGTH';
	}

	// basic checks
	if( isset(
		$headers[$content_type],
		$headers[$content_length],
		$headers['X-File-Size'],
		$headers['X-File-Name']
		) && ( $headers[$content_type] === 'application/octet-stream' ) && ( $headers[$content_length] === $headers['X-File-Size'] ) )
	{
		// create the object and assign property
		$file = new stdClass;
		$file->name = basename($headers['X-File-Name']);
		$file->size = $headers['X-File-Size'];
		$file->content = file_get_contents("php://input");

		if( $Settings->get( 'upload_maxkb' ) && ( $file->size > $Settings->get( 'upload_maxkb' )*1024 ) )
		{
			echo '<span class="result_error">';
			echo sprintf( T_('The file is too large: %s but the maximum allowed is %s.'), $file->size, $Settings->get( 'upload_maxkb' )*1024 );
			echo '</span>';
			exit();
		}

		$newName = $file->name;
		if( $error_filename = validate_filename( $newName ) )
		{ // Not a file name or not an allowed extension
			echo '<span class="result_error"> '.$error_filename.'</span>';
			exit();
		}

		$oldName = $newName;
		list( $newFile, $oldFile_thumb ) = check_file_exists( $fm_FileRoot, $path, $newName );
		$newName = $newFile->get( 'name' );

		/*use $result = file_put_contents( $newFile->get_full_path(), $file->content ) in php5*/
		$file_handle = fopen( $newFile->get_full_path(), 'w' );
		$result = false;
		if( $file_handle )
		{
			$result = fwrite( $file_handle, $file->content );
			$result = $result && fclose( $file_handle );
		}

		// if everything is ok, save the file somewhere
		if( $result )
		{
			// change to default chmod settings
			$newFile->chmod( NULL );

			// Refreshes file properties (type, size, perms...)
			$newFile->load_properties();

			// save file into the db
			$newFile->dbsave();

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
				$message = '<br />';
				$message .= sprintf( T_('"%s was renamed to %s. Would you like to replace %s with the new version instead?'),
									'&laquo;'.$oldName.'&raquo;', '&laquo;'.$newName.'&raquo;', '&laquo;'.$oldName.'&raquo;' );
				$message .= '<li class="invalid" title="'.T_('File name changed.').'">';
				$message .= '<input type="radio" name="Renamed_'.$newFile->ID.'" value="Yes" id="Yes_'.$newFile->ID.'"/>';
				$message .= '<label for="Yes_'.$newFile->ID.'">';
				$message .= sprintf( T_("Replace the old version %s with the new version %s and keep old version as %s."), $oldFile_thumb, $newFile_thumb, $newName ).'</label><br />';
				$message .= '<input type="radio" name="Renamed_'.$newFile->ID.'" value="No" id="No_'.$newFile->ID.'" checked="checked"/>';
				$message .= '<label for="No_'.$newFile->ID.'">';
				$message .= sprintf( T_("Don't touch the old version and keep the new version as %s."), $newName ).'</label><br />';
				$message .= '</li>';
				echo '1';
			}
			else
			{
				echo '0';
			}
			if( !empty( $message ) )
			{
				echo $message;
				echo '<input type="hidden" name="renamedFiles['.$newFile->ID.'][newName]" value="'.$newName.'" />';
				echo '<input type="hidden" name="renamedFiles['.$newFile->ID.'][oldName]" value="'.$oldName.'" />';
			}
			exit();
		}

		echo '<span class="result_error">'.T_( 'The file could not be saved!' ).'</span>';
		exit();
	}

	// Could not find upload information
	echo '<span class="result_error">'.T_( 'Bad request. Missing header information.' ).'</span>';
	exit();
}

echo '<span class="error">Invalid upload param</span>';
exit();

/*
 * $Log$
 * Revision 1.9  2011/09/05 23:00:24  fplanque
 * minor/doc/cleanup/i18n
 *
 * Revision 1.8  2011/09/05 20:59:35  sam2kb
 * minor
 *
 * Revision 1.7  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.6  2011/09/04 20:59:40  fplanque
 * cleanup
 *
 * Revision 1.5  2011/05/06 07:04:45  efy-asimo
 * multiupload ui update
 *
 * Revision 1.4  2011/05/05 16:19:35  efy-asimo
 * "Missing boundary in multipart/form-data" warning - fix
 *
 * Revision 1.3  2011/05/05 16:06:47  efy-asimo
 * security issue - fix
 *
 * Revision 1.2  2011/05/05 15:11:02  efy-asimo
 * multifile upload - fix
 *
 * Revision 1.1  2011/04/28 14:07:59  efy-asimo
 * multiple file upload
 *
 */
?>