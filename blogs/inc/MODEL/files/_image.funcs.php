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

function shrink_dimensions_to_fit( $src_width, $src_height, $max_width, $max_height )
{
	if( $src_width > $max_width || $src_height > $max_height )
	{
		return scale_dimensions_to_fit( $src_width, $src_height, $max_width, $max_height );
	}

	return array( $src_width, $src_height );
}

/**
 * @return string short error code
 */
function generate_thumb( $src_imh, $thumb_width, $thumb_height )
{
	$src_width = imagesx( $src_imh ) ;
	$src_height = imagesy( $src_imh );

	list( $dest_width, $dest_height ) = shrink_dimensions_to_fit( $src_width, $src_height, $thumb_width, $thumb_height );

	// pre_dump( $src_width, $src_height, $dest_width, $dest_height );

	$dest_imh = imagecreatetruecolor( $dest_width, $dest_height ); // Create a black image
	if( ! imagecopyresampled( $dest_imh, $src_imh, 0, 0, 0, 0, $dest_width, $dest_height, $src_width, $src_height ) )
	{
		return 'Eresample';	// Sort error code
	}

	// TODO: imageinterlace();
	header('Content-type: image/jpeg' );
	imagejpeg( $dest_imh );

	return NULL;
}

/*
 * $Log$
 * Revision 1.1  2006/12/13 18:10:21  fplanque
 * thumbnail resampling proof of concept
 *
 */
?>