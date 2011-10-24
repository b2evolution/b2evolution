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


/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if ($realSize != $this->getSize()){
            return false;
        }

        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        return true;
    }

	function get_content()
	{
        $input = fopen("php://input", "rb");
        $temp = tmpfile();
		stream_copy_to_stream($input, $temp);
        fclose($input);

		fseek($temp, 0, SEEK_SET);
		$contents = '';

		while (!feof($temp))
		{
			$contents .= fread($temp, 8192);
		}
		fclose($temp);
		return $contents;
	}



    function getName() {
        return $_GET['qqfile'];
    }

    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];
        } else {
            throw new Exception('Getting content length is not supported.');
        }
    }
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
    if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }

    function getName() {
        return $_FILES['qqfile']['name'];
    }
	
    function getSize() {
        return $_FILES['qqfile']['size'];
    }

	function get_content()
	{
		$temp = fopen($_FILES['qqfile']['tmp_name'], "rb");
		fseek($temp, 0, SEEK_SET);
		$contents = '';
		while (!feof($temp))
		{
			$contents .= fread($temp, 8192);
		}
		fclose($temp);
		return $contents;
		//return file_get_contents($_FILES['qqfile']['tmp_name']);
	}

}

function out_echo($message ,$specialchars)
{
	if ($specialchars == 1)
	{
	echo htmlspecialchars(json_encode(array('success'=>$message)));
	}
	else
	{
	echo (json_encode(array('success'=>$message)));
	}
}

$specialchars = 0;
if (isset($_FILES['qqfile']))
{
	$specialchars = 1;
}


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
	$message = '<span class="result_error">Bad request. Unknown upload location!</span>'; // NO TRANS!!
	out_echo($message, $specialchars);
	//echo htmlspecialchars(json_encode(array('success'=>$message)));
	exit();
}

if( $upload && ( !$current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) ) )
{
	$message = '<span class="result_error">'.T_( 'You don\'t have permission to upload on this file root.' ).'</span>';
	out_echo($message, $specialchars);
	//echo htmlspecialchars(json_encode(array('success'=>$message)));
	exit();
}

if( $upload )
{
		// create the object and assign property

		if (isset($_GET['qqfile']))
		{
            $file = new qqUploadedFileXhr();
        }
		elseif (isset($_FILES['qqfile']))
		{
            $file = new qqUploadedFileForm();

        }
		else
		{
            $file = false;
        }

		if( $Settings->get( 'upload_maxkb' ) && ( $file->getSize() > $Settings->get( 'upload_maxkb' )*1024 ) )
		{
			$message = '<span class="result_error">'.
			sprintf( T_('The file is too large: %s byte but the maximum allowed is %s byte.'), $file->getSize(), $Settings->get( 'upload_maxkb' )*1024 )
			. '</span>';
			//echo htmlspecialchars(json_encode(array('success'=>$message)));
			out_echo($message, $specialchars);
			exit();
		}

		$newName = $file->getName();
		if( $error_filename = validate_filename( $newName ) )
		{ // Not a file name or not an allowed extension
			$message = '<span class="result_error"> '.$error_filename.'</span>';
			//echo htmlspecialchars(json_encode(array('success'=>$message)));
			out_echo($message, $specialchars);
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
			$result = fwrite( $file_handle, $file->get_content());
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
			if( !empty( $message ) )
			{
				$message .= '<input type="hidden" name="renamedFiles['.$newFile->ID.'][newName]" value="'.$newName.'" />' .
				'<input type="hidden" name="renamedFiles['.$newFile->ID.'][oldName]" value="'.$oldName.'" />';
				//echo htmlspecialchars(json_encode(array('success'=>$message)));
				$message = array('text' => $message, 'status' => 'rename');
				out_echo($message, $specialchars);
				exit();
			}
			else
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
				
				$message = "<span class=\"result_success\"> OK </span> $newFile_thumb ";
				//echo (htmlspecialchars(json_encode(array('success'=>$message))));
				out_echo($message, $specialchars);
				exit();
			}
			
		}

		$message = '<span class="result_error">'.T_( 'The file could not be saved!' ).'</span>';
		//echo htmlspecialchars(json_encode(array('success'=>$message)));
		out_echo($message, $specialchars);
		exit();

}

$message = '<span class="error">Invalid upload param</span>';
//echo htmlspecialchars(json_encode(array('success'=>$message)));
out_echo($message, $specialchars);
exit();

/*
 * $Log$
 * Revision 1.3  2011/10/24 08:45:46  efy-vitalij
 * changed file validation
 *
 * Revision 1.2  2011/10/20 11:51:49  efy-vitalij
 * changed function get_content in classes qqUploadedFileXhr, qqUploadedFileForm, made some changes in response
 *
 * Revision 1.1  2011/10/19 14:17:33  efy-vitalij
 * add file quick_upload_new.php
 *
 * Revision 1.10  2011/09/06 00:54:38  fplanque
 * i18n update
 *
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