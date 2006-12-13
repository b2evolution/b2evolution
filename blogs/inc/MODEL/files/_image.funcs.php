<?php
/**
 * This file implements various Image File handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * Scale dimensions to fit into a constrained size, while preserving aspect ratio.
 *
 * @param integer source width
 * @param integer source height
 * @param integer constrained width
 * @param integer constrained height
 */
function scale_dimensions_to_fit( $src_width, $src_height, $max_width, $max_height )
{
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
function shrink_dimensions_to_fit( $src_width, $src_height, $max_width, $max_height )
{
	if( $src_width > $max_width || $src_height > $max_height )
	{
		return scale_dimensions_to_fit( $src_width, $src_height, $max_width, $max_height );
	}

	return array( $src_width, $src_height );
}


/**
 * Load an image from a file into memory
 *
 * @param string
 * @param string
 * @return array resource image handle or NULL
 */
function load_image( $path, $mimetype )
{
	$err = NULL;
	$err_info = NULL;
	$imh = NULL;

	switch( $mimetype )
	{
		case 'image/jpeg':
			$imh = imagecreatefromjpeg( $path );
			break;

		case 'image/gif':
			$imh = imagecreatefromgif( $path );
			break;

 		default:
			// Unrecognized mime type
			$err = 'Emime';	// Sort error code
			$err_info = $mimetype;
			break;
	}

	return array( $err, $err_info, $imh );
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
 * @return array short error code + dest image handler
 */
function generate_thumb( $src_imh, $thumb_width, $thumb_height )
{
	$src_width = imagesx( $src_imh ) ;
	$src_height = imagesy( $src_imh );

	if( $src_width <= $thumb_width && $src_height <= $thumb_height )
	{	// There is no need to resample, use original!
		return array( NULL, $src_imh );
	}

	list( $dest_width, $dest_height ) = scale_dimensions_to_fit( $src_width, $src_height, $thumb_width, $thumb_height );

	// pre_dump( $src_width, $src_height, $dest_width, $dest_height );

	$dest_imh = imagecreatetruecolor( $dest_width, $dest_height ); // Create a black image
	if( ! imagecopyresampled( $dest_imh, $src_imh, 0, 0, 0, 0, $dest_width, $dest_height, $src_width, $src_height ) )
	{
		return array( 'Eresample', $dest_imh );
	}

	// TODO: imageinterlace();

	return array( NULL, $dest_imh );
}

/*
 * $Log$
 * Revision 1.2  2006/12/13 20:10:31  fplanque
 * object responsibility delegation?
 *
 * Revision 1.1  2006/12/13 18:10:21  fplanque
 * thumbnail resampling proof of concept
 *
 */
?>