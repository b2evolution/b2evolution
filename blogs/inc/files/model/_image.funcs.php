<?php
/**
 * This file implements various Image File handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @return array 'key'=>'name'
 */
function get_available_thumb_sizes()
{
	global $thumbnail_sizes;

	$thumb_size_names = array();

	foreach( $thumbnail_sizes as $key=>$dummy )
	{
		$thumb_size_names[$key] = $key;
	}

	return $thumb_size_names;
}


/**
 * Crop dimensions to fit into a constrained size, while preserving aspect ratio.
 *
 * @param integer source width
 * @param integer source height
 * @param integer constrained width
 * @param integer constrained height
 * @return array ( x, y, width, height )
 */
function crop_to_constraint( $src_width, $src_height, $max_width, $max_height )
{
	$src_ratio = $src_width / $src_height;
	$max_ratio = $max_width / $max_height;
	if( $max_ratio <= $src_ratio )
	{
		$x = ($src_width - $src_height) / 2;
		$y = 0;
		$src_width = $src_height;
	}
	else
	{
		$x = 0;
		$y = ($src_height - $src_width) / 2;
		$src_height = $src_width;
	}

	return array( $x, $y, $src_width, $src_height );
}


/**
 * Scale dimensions to fit into a constrained size, while preserving aspect ratio.
 *
 * @param integer source width
 * @param integer source height
 * @param integer constrained width (might be NULL/0 to use source width)
 * @param integer constrained height (might be NULL/0 to use source height)
 * @return array (width, height)
 */
function scale_to_constraint( $src_width, $src_height, $max_width, $max_height )
{
	if( $max_width == 0 )
		$max_width = $src_width;
	if( $max_height == 0 )
		$max_height = $src_height;

	$src_ratio = $src_width / $src_height;
	if( $max_width / $max_height <= $src_ratio )
	{
		$width = $max_width;
		$height = (int)round( $max_width / $src_ratio );
	}
	else
	{
		$width = (int)round( $max_height * $src_ratio );
		$height = $max_height;
	}

	return array( $width, $height );
}


/**
 * Scale dimensions to fit into a constrained size, while preserving aspect ratio.
 * The scaling only happens if the source is larger than the constraint.
 *
 * @param integer source width
 * @param integer source height
 * @param integer constrained width
 * @param integer constrained height
 */
function fit_into_constraint( $src_width, $src_height, $max_width, $max_height )
{
	if( $src_width > $max_width || $src_height > $max_height )
	{
		return scale_to_constraint( $src_width, $src_height, $max_width, $max_height );
	}

	return array( $src_width, $src_height );
}


/**
 * Load an image from a file into memory
 *
 * @param string pathname of image file
 * @param string
 * @return array resource image handle or NULL
 */
function load_image( $path, $mimetype )
{
	// yabs> GD library uses shedloads of memory
	// fp> 256M is way too high to sneak this in here. There should be some checks in the systems page to warn against low memory conditions. Also i'm not sure it makes sense to bump memory just for images. If you allow memory you might as well allow it for anything. Anyways, this is too much to be snuk in.
	// @ini_set('memory_limit', '256M'); // artificially inflate memory if we can
	$err = NULL;
	$imh = NULL;
	$function = NULL;

	$image_info = getimagesize($path);
	if( ! $image_info || $image_info['mime'] != $mimetype )
	{
		$FiletypeCache = get_Cache('FiletypeCache');
		$correct_Filetype = $FiletypeCache->get_by_mimetype($image_info['mime']);
		$correct_extension = array_shift($correct_Filetype->get_extensions());

		$path_info = pathinfo($path);
		$wrong_extension = $path_info['extension'];

		$err = '!'.$correct_extension.' extension mismatch: use .'.$correct_extension.' instead of .'.$wrong_extension;
	}
	else
	{
		$mime_function = array(
			'image/jpeg' => 'imagecreatefromjpeg',
			'image/gif'  => 'imagecreatefromgif',
			'image/png'  => 'imagecreatefrompng',
		);
		if( isset($mime_function[$mimetype]) )
		{
			$function = $mime_function[$mimetype];
		}
		else
		{ // Unrecognized mime type
			$err = '!Unsupported format '.$mimetype.' (load_image)';
		}
	}

	if( $function )
	{
		$imh = $function( $path );
	}
	if( $imh === false )
	{
		// e.g. "imagecreatefromjpeg(): $FILE is not a valid JPEG file"
		$err = '!load_image failed (no valid image?)';
	}
	if( $err )
	{ // Trigger PHP error, but do not display it (otherwise the image containing the error would be broken)
		// Tblue> Perhaps it would be better to use error_log() here?
		$old_de = ini_set('display_errors', 0);
		trigger_error( 'load_image failed: '.substr($err, 1).' ('.$path.' / '.$mimetype.')', E_USER_WARNING );
		ini_set('display_errors', $old_de);
	}

	return array( $err, $imh );
}


/**
 * Output an image from memory to web client
 *
 * @todo dh> image* functions might fail here, e.g. no fs permission.
 *
 * @param resource image handle
 * @param string pathname of image file
 * @param string
 * @param integer
 * @param string permissions
 * @return string
 */
function save_image( $imh, $path, $mimetype, $quality = 90, $chmod = NULL )
{
	$err = NULL;

	switch( $mimetype )
	{
		case 'image/jpeg':
			imagejpeg( $imh, $path, $quality );
			break;

		case 'image/gif':
			imagegif( $imh, $path );
			break;

		case 'image/png':
			imagepng( $imh, $path );
			break;

 		default:
			// Unrecognized mime type
			$err = '!Unsupported format '.$mimetype.' (save_image)';
			break;
	}

	if( empty( $err ) )
	{
		// Make sure the file has the default permissions we want:
		if( $chmod === NULL )
		{
			global $Settings;
			$chmod = $Settings->get('fm_default_chmod_file');
		}
		chmod( $path, octdec( $chmod ) );
	}

	return $err;
}


/**
 * Output an image from memory to web client
 *
 * @param resource image handle
 * @param string
 * @return string
 */
function output_image( $imh, $mimetype )
{
	$err = NULL;

	switch( $mimetype )
	{
		case 'image/jpeg':
			header('Content-type: '.$mimetype );
			imagejpeg( $imh );
			break;

		case 'image/gif':
			header('Content-type: '.$mimetype );
			imagegif( $imh );
			break;

 		default:
			// Unrecognized mime type
			$err = 'Emime';	// Sort error code
			break;
	}

	return $err;
}




/**
 * Generate a thumbnail
 *
 * @param resource Image resource
 * @param string Thumbnail type ('crop'|'fit')
 * @param int Thumbnail width
 * @param int Thumbnail height
 * @return array short error code + dest image handler
 */
function generate_thumb( $src_imh, $thumb_type, $thumb_width, $thumb_height )
{
	$src_width = imagesx( $src_imh ) ;
	$src_height = imagesy( $src_imh );

	if( $src_width <= $thumb_width && $src_height <= $thumb_height )
	{	// There is no need to resample, use original!
		return array( NULL, $src_imh );
	}

	switch( $thumb_type )
	{
		case 'crop':
			list( $src_x, $src_y, $src_width, $src_height) = crop_to_constraint( $src_width, $src_height, $thumb_width, $thumb_height );
			$dest_width = $thumb_width;
			$dest_height = $thumb_height;
			break;

		case 'fit':
		default:
			list( $dest_width, $dest_height ) = scale_to_constraint( $src_width, $src_height, $thumb_width, $thumb_height );
			$src_x = $src_y = 0;
	}

	// pre_dump( $src_x, $src_y, $dest_width, $dest_height, $src_width, $src_height );

	$dest_imh = imagecreatetruecolor( $dest_width, $dest_height ); // Create a black image

	if( ! imagecopyresampled( $dest_imh, $src_imh, 0, 0, $src_x, $src_y, $dest_width, $dest_height, $src_width, $src_height ) )
	{
		return array( '!GD-library internal error (resample)', $dest_imh );
	}


	// TODO: imageinterlace();

	return array( NULL, $dest_imh );
}


/*
 * $Log$
 * Revision 1.13  2009/10/04 11:23:21  tblue246
 * doc
 *
 * Revision 1.12  2009/10/04 01:24:56  blueyed
 * load_image: refactored, add call to trigger_error.
 *
 * Revision 1.11  2009/10/02 20:34:32  blueyed
 * Improve handling of wrong file extensions for image.
 *  - load_image: if the wrong mimetype gets passed, return error, instead of letting imagecreatefrom* fail
 *  - upload: detect wrong extensions, rename accordingly and add a warning
 *
 * Revision 1.10  2009/09/20 01:10:36  blueyed
 * todo
 *
 * Revision 1.9  2009/09/20 00:46:28  blueyed
 * load_image: handle error case a bit better.
 *
 * Revision 1.8  2009/09/12 20:51:58  tblue246
 * phpdoc fixes
 *
 * Revision 1.7  2009/07/30 21:34:55  blueyed
 * scale_to_constraint: support NULL/0 for max_width/max_height, allowing formats like '640x'. doc.
 *
 * Revision 1.6  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.5  2009/01/13 22:51:28  fplanque
 * rollback / normalized / MFB
 *
 * Revision 1.4  2008/09/24 08:35:11  fplanque
 * Support of "cropped" thumbnails (the image will always fill the whole thumbnail area)
 * Thumbnail sizes can be configured in /conf/_advanced.php
 *
 * Revision 1.3  2008/01/21 09:35:29  fplanque
 * (c) 2008
 *
 * Revision 1.2  2008/01/06 04:23:49  fplanque
 * thumbnail enhancement
 *
 * Revision 1.1  2007/06/25 10:59:57  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.9  2007/05/28 11:28:22  fplanque
 * file perm fix / thumbnails
 *
 * Revision 1.8  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.7  2007/03/20 07:39:08  fplanque
 * filemanager fixes, including the chmod octal stuff
 *
 * Revision 1.6  2007/03/08 00:22:35  blueyed
 * TODO
 *
 * Revision 1.5  2007/01/19 08:20:36  fplanque
 * Addressed resized image quality.
 *
 * Revision 1.4  2006/12/13 22:43:24  fplanque
 * default perms for newly created thumbnails
 *
 * Revision 1.3  2006/12/13 21:23:56  fplanque
 * .evocache folders / saving of thumbnails
 *
 * Revision 1.2  2006/12/13 20:10:31  fplanque
 * object responsibility delegation?
 *
 * Revision 1.1  2006/12/13 18:10:21  fplanque
 * thumbnail resampling proof of concept
 *
 */
?>
