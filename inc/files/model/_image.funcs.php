<?php
/**
 * This file implements various Image File handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get available thumbnail sizes
 *
 * @param string The text that used for the "None" option
 * @return array 'key'=>'name'
 */
function get_available_thumb_sizes( $allow_none_text = NULL )
{
	global $thumbnail_sizes;

	$thumb_size_names = array();

	if( !empty( $allow_none_text ) )
	{	// 'None' option
		$thumb_size_names[''] = $allow_none_text;
	}

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
 * @param string align: center, top
 * @return array ( x, y, width, height )
 */
function crop_to_constraint( $src_width, $src_height, $max_width, $max_height, $align = 'center' )
{
	$src_ratio = $src_width / $src_height;
	$max_ratio = $max_width / $max_height;
	if( $max_ratio <= $src_ratio )
	{
		$y = 0;
		$x = ($src_width - ($src_width * ($max_ratio/$src_ratio))) / 2;
		$src_width = $src_width * ($max_ratio/$src_ratio);
	}
	else
	{
		$x = 0;
		if( $align == 'top' )
		{	// top - 15%
			$y = ( $src_height - $src_width ) * 0.15;
		}
		else
		{	// center
			$y = ($src_height - $src_width/$max_ratio) / 2;
		}
		$src_height = $src_width/$max_ratio;
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
	//pre_dump( $function );
	if( $function )
	{	// Call GD built-in function to load image
		// fp> Note: sometimes this GD call will die and there is no real way to recover :/
		load_funcs( 'tools/model/_system.funcs.php' );
		$memory_limit = system_check_memory_limit();
		$curr_mem_usage = memory_get_usage( true );
		// Calculate the aproximative memory size which would be required to create the image resource
		$tweakfactor = 1.8; // Or whatever works for you
		$memory_needed = round( ( $image_info[0] * $image_info[1]
				* ( isset( $image_info['bits'] ) ? $image_info['bits'] : 4 )
				* ( isset( $image_info['channels'] ) ? $image_info['channels'] / 8 : 1 )
				+ Pow( 2, 16 ) // number of bytes in 64K
			) * $tweakfactor );
		if( ( $memory_limit - $curr_mem_usage ) < $memory_needed )// ( 4 * $image_info[0] * $image_info[1] ) )
		{ // Don't try to load the image into the memory because it would cause 'Allowed memory size exhausted' error
			return array( "!Cannot resize too large image", false );
		}
		$imh = $function( $path );
	}

	if( $imh === false )
	{
		trigger_error( 'load_image failed: '.$path.' / '.$mimetype ); // DEBUG
		// e.g. "imagecreatefromjpeg(): $FILE is not a valid JPEG file"
		$err = '!load_image failed (no valid image?)';
	}
	if( $err )
	{
		error_log( 'load_image failed: '.substr($err, 1).' ('.$path.' / '.$mimetype.')' );
	}

	return array( $err, $imh );
}


/**
 * Output an image from memory to web client
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
			if( imagesx( $imh ) > 32 || imagesy( $imh ) > 32 )
			{ // Enable interlacing
				imageinterlace( $imh, 1 );
			}
			$r = @imagejpeg( $imh, $path, $quality );
			break;

		case 'image/gif':
			$r = @imagegif( $imh, $path );
			break;

		case 'image/png':
			$r = @imagepng( $imh, $path );
			break;

		default:
			// Unrecognized mime type
			$err = '!Unsupported format '.$mimetype.' (save_image)';
			break;
	}

	// Catch any errors by image* functions:
	if( ! $r )
	{
		// TODO: dh> This might become a generic function, since it's useful. Something similar is used in DB, too.
		if( isset($php_errormsg) )
			$err = '!'.$php_errormsg;
		elseif( function_exists('error_get_last') ) // PHP 5.2
		{
			$err = error_get_last();
			$err = '!'.$err['message'];
		}

		if( ! isset($err) )
			$err = '!Unknown error in save_image().';
	}

	if( empty( $err ) )
	{
		// Make sure the file has the default permissions we want:
		if( $chmod === NULL )
		{
			global $Settings;
			$chmod = $Settings->get('fm_default_chmod_file');
		}
		if( ! @chmod( $path, octdec( $chmod ) ) )
		{
			syslog_insert( sprintf( 'The permissions of file %s could not be changed to %s', '[['.$path.']]', $chmod ), 'error', 'file' );
		}
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
			if( imagesx( $imh ) > 32 || imagesy( $imh ) > 32 )
			{ // Enable interlacing
				imageinterlace( $imh, 1 );
			}
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
 * Check and fix thumbnail sizes depending on original image sizes
 * It is useful to fix thumbnail sizes for images less than requested thumbnail
 *
 * @param string Thumbnail type ('crop'|'crop-top'|'fit')
 * @param integer Thumbnail width (by reference)
 * @param integer Thumbnail height (by reference)
 * @param integer Original image width
 * @param integer Original image height
 * @return boolean TRUE if no need to resample
 */
function check_thumbnail_sizes( $thumb_type, & $thumb_width, & $thumb_height, $src_width, $src_height )
{
	if( empty( $src_width ) || empty( $src_height ) )
	{ // We don't know how this case can happen but it has been reported, so division by zero:
		return true;
	}

	if( $src_width <= $thumb_width && $src_height <= $thumb_height )
	{ // If original image sizes are less than thumbnail sizes
		if( $thumb_type == 'fit' )
		{ // There is no need to resample, use original!
			return true;
		}
		else
		{ // crop & crop-top
			$src_ratio = number_format( $src_width / $src_height, 4, '.', '' );
			$thumb_ratio = number_format( $thumb_width / $thumb_height, 4, '.', '' );
			if( $src_ratio == $thumb_ratio )
			{ // If original image has the same ratio then no need to resample
				return true;
			}
			else
			{ // Set new thumb sizes depending on thumbnail ratio
				if( $thumb_ratio == 1 )
				{ // Use min size for square size
					$thumb_width = ( $src_width > $src_height ) ? $src_height : $src_width;
					$thumb_height = $thumb_width;
				}
				elseif( $thumb_ratio > $src_ratio )
				{ // If thumbnail ratio > original image ratio
					$thumb_width = $src_width;
					$thumb_height = round( $src_width / $thumb_ratio );
				}
				else//if( $thumb_ratio < $src_ratio )
				{ // If thumbnail ratio < original image ratio
					$thumb_width = round( $src_height * $thumb_ratio );
					$thumb_height = $src_height;
				}
			}
		}
	}

	return false;
}


/**
 * Generate a thumbnail
 *
 * @param resource Image resource
 * @param string Thumbnail type ('crop'|'crop-top'|'fit')
 * @param int Thumbnail width
 * @param int Thumbnail height
 * @param int Thumbnail percent of blur effect (0 - No blur, 1% - Max blur effect, 99% - Min blur effect)
 * @param integer Ratio size, can be 1, 2 and etc.
 * @return array short error code + dest image handler
 */
function generate_thumb( $src_imh, $thumb_type, $thumb_width, $thumb_height, $thumb_percent_blur = 0, $size_x = 1 )
{
	$src_width = imagesx( $src_imh );
	$src_height = imagesy( $src_imh );

	$size_x = intval( $size_x );
	if( $size_x > 1 )
	{ // Use the expanded size
		$thumb_width = $thumb_width * $size_x;
		$thumb_height = $thumb_height * $size_x;
	}

	if( check_thumbnail_sizes( $thumb_type, $thumb_width, $thumb_height, $src_width, $src_height ) )
	{ // There is no need to resample, use original!
		return array( NULL, $src_imh );
	}

	switch( $thumb_type )
	{
		case 'crop':
		case 'crop-top':
			$align = $thumb_type == 'crop-top' ? 'top' : 'center';
			list( $src_x, $src_y, $src_width, $src_height) = crop_to_constraint( $src_width, $src_height, $thumb_width, $thumb_height, $align );
			$dest_width = $thumb_width;
			$dest_height = $thumb_height;
			break;

		case 'fit':
		default:
			list( $dest_width, $dest_height ) = scale_to_constraint( $src_width, $src_height, $thumb_width, $thumb_height );
			$src_x = $src_y = 0;
	}

	// pre_dump( $src_x, $src_y, $dest_width, $dest_height, $src_width, $src_height );

	// Create a transparent image:
	$dest_imh = imagecreatetruecolor( $dest_width, $dest_height );
	imagealphablending($dest_imh, true);
	imagefill($dest_imh, 0, 0, imagecolortransparent($dest_imh, imagecolorallocatealpha($dest_imh, 0, 0, 0, 127)));
	imagesavealpha($dest_imh, true);

	if( ! imagecopyresampled( $dest_imh, $src_imh, 0, 0, $src_x, $src_y, $dest_width, $dest_height, $src_width, $src_height ) )
	{
		return array( '!GD-library internal error (resample)', $dest_imh );
	}

	if( $thumb_percent_blur > 0 )
	{	// Apply blur effect
		$dest_imh = pixelblur( $dest_imh, $dest_width, $dest_height, $thumb_percent_blur );
	}

	// TODO: imageinterlace();

	return array( NULL, $dest_imh );
}


/**
 * Apply blur effect
 *
 * @param resource Image resource
 * @param int Source width
 * @param int Source height
 * @param int Percent of blur effect (0 - No blur, 1% - Max blur effect, 99% - Min blur effect)
 * @return resource Image resource
 */
function pixelblur( $image_source, $width_source, $height_source, $percent_blur )
{
	if( $percent_blur < 1 && $percent_blur > 99 )
	{	// Don't spend a time for processing of blur effect with bad percent request
		return $image_source;
	}

	$width_resized = ceil( $width_source * $percent_blur / 100 );
	$height_resized = ceil( $height_source * $percent_blur / 100 );

	$image_resized = imagecreatetruecolor( $width_resized, $height_resized );
	// Reduce image size by given percent
	imagecopyresampled( $image_resized, $image_source, 0, 0, 0, 0, $width_resized, $height_resized, $width_source, $height_source );
	// Apply blur effect from GD library
  if( function_exists('imagefilter') )
  {
    imagefilter( $image_resized, IMG_FILTER_GAUSSIAN_BLUR );
  }
	// Expand image to the source size
	imagecopyresampled( $image_source, $image_resized, 0, 0, 0, 0, $width_source, $height_source, $width_resized, $height_resized );

	return $image_source;
}


/**
 * Rotate image
 *
 * @param object File
 * @param integer # degrees to rotate
 * @return boolean TRUE if rotating is successful
 */
function rotate_image( $File, $degrees )
{
	$Filetype = & $File->get_Filetype();
	if( !$Filetype )
	{	// Error
		return false;
	}

	// Load image
	list( $err, $imh ) = load_image( $File->get_full_path(), $Filetype->mimetype );
	if( !empty( $err ) )
	{	// Error
		return false;
	}

	// Rotate image
	if( ! $imh = @imagerotate( $imh, (int)$degrees, 0 ) )
	{	// If func imagerorate is not defined for example:
		return false;
	}

	// Save image:
	$save_image_err = save_image( $imh, $File->get_full_path(), $Filetype->mimetype );
	if( $save_image_err !== NULL )
	{	// Some error has been detected on save image:
		syslog_insert( substr( $save_image_err, 1 ), 'error', 'file', $File->ID );
		return false;
	}

	// Remove the old thumbnails
	$File->rm_cache();

	return true;
}


/**
 * Crop image
 *
 * @param object File
 * @param integer X coordinate (in percents)
 * @param integer Y coordinate (in percents)
 * @param integer Width (in percents)
 * @param integer Height (in percents)
 * @param integer Min size of width or height (in pixels), 0 - to don't limit
 * @param integer Max size of width or height (in pixels), 0 - to don't limit
 * @return boolean TRUE if cropping is successful
 */
function crop_image( $File, $x, $y, $width, $height, $min_size = 0, $max_size = 0 )
{
	$Filetype = & $File->get_Filetype();
	if( ! $Filetype )
	{ // Error
		return false;
	}

	// Load image
	list( $err, $src_imh ) = load_image( $File->get_full_path(), $Filetype->mimetype );
	if( ! empty( $err ) )
	{ // Error
		return false;
	}

	$src_width = imagesx( $src_imh );
	$src_height = imagesy( $src_imh );
	$x = $src_width * ( $x / 100 );
	$y = $src_height * ( $y / 100 );
	$width = $src_width * ( $width / 100 );
	$height = $src_height * ( $height / 100 );
	$dest_width = $width;
	$dest_height = $height;

	if( $max_size > 0 )
	{ // Check if we should limit by max size
		$dest_width = $dest_width > $max_size ? $max_size : $dest_width;
		$dest_height = $dest_height > $max_size ? $max_size : $dest_height;
	}
	if( $min_size > 0 )
	{ // Check if we should limit by min size
		$width = $width < $min_size ? $min_size : $width;
		$height = $height < $min_size ? $min_size : $height;
	}

	if( $x + $width > $src_width )
	{ // Shift a crop X position to the left if the crop width is over image width
		$x = $src_width - $width;
	}
	if( $y + $height > $src_height )
	{ // Shift a crop Y position to the top if the crop height is over image height
		$y = $src_height - $height;
	}

	$dst_imh = imagecreatetruecolor( $dest_width, $dest_height );

	// Crop image
	if( ! @imagecopyresampled( $dst_imh, $src_imh, 0, 0, $x, $y, $dest_width, $dest_height, $width, $height ) )
	{ // If func imagecopyresampled is not defined for example:
		return false;
	}

	// Save image:
	$save_image_err = save_image( $dst_imh, $File->get_full_path(), $Filetype->mimetype );
	if( $save_image_err !== NULL )
	{	// Some error has been detected on save image:
		syslog_insert( substr( $save_image_err, 1 ), 'error', 'file', $File->ID );
		return false;
	}

	// Remove the old thumbnails
	$File->rm_cache();

	return true;
}


/**
 * Provide imagerotate for undefined cases
 *
 * Rotate an image with a given angle
 * @param resource Image: An image resource, returned by one of the image creation functions, such as imagecreatetruecolor().
 * @param integer Angle: Rotation angle, in degrees. The rotation angle is interpreted as the number of degrees to rotate the image anticlockwise.
 * @param string Bgd_color: Specifies the color of the uncovered zone after the rotation
 * @param integer Ignore_transparent: If set and non-zero, transparent colors are ignored (otherwise kept).
 * @return resource Returns an image resource for the rotated image, or FALSE on failure.
 */
if( !function_exists( 'imagerotate' ) )
{
	/**
	 * Imagerotate replacement. ignore_transparent is work for png images
	 * Also, have some standard functions for 90, 180 and 270 degrees.
	 */
	function imagerotate( $srcImg, $angle, $bgcolor, $ignore_transparent = 0 )
	{
		function rotateX( $x, $y, $theta )
		{
			return $x * cos( $theta ) - $y * sin( $theta );
		}
		function rotateY( $x, $y, $theta )
		{
			return $x * sin( $theta ) + $y * cos( $theta );
		}

		$srcw = imagesx( $srcImg );
		$srch = imagesy( $srcImg );

		//Normalize angle
		$angle %= 360;

		if( $angle == 0 )
		{
			if( $ignore_transparent == 0 )
			{
				imagesavealpha( $srcImg, true );
			}
			return $srcImg;
		}

		// Convert the angle to radians
		$theta = deg2rad( $angle );

		//Standart case of rotate
		if( ( abs( $angle ) == 90 ) || ( abs( $angle ) == 270) )
		{
			$width = $srch;
			$height = $srcw;
			if( ( $angle == 90 ) || ( $angle == -270 ) )
			{
				$minX = 0;
				$maxX = $width;
				$minY = -$height+1;
				$maxY = 1;
			}
			else if( ( $angle == -90 ) || ( $angle == 270 ) )
			{
				$minX = -$width+1;
				$maxX = 1;
				$minY = 0;
				$maxY = $height;
			}
		}
		else if( abs( $angle ) === 180 )
		{
			$width = $srcw;
			$height = $srch;
			$minX = -$width+1;
			$maxX = 1;
			$minY = -$height+1;
			$maxY = 1;
		}
		else
		{
			// Calculate the width of the destination image.
			$temp = array( rotateX( 0, 0, 0-$theta ),
					rotateX( $srcw, 0, 0-$theta ),
					rotateX( 0, $srch, 0-$theta ),
					rotateX( $srcw, $srch, 0-$theta )
				);
			$minX = floor( min( $temp ) );
			$maxX = ceil( max( $temp ) );
			$width = $maxX - $minX;

			// Calculate the height of the destination image.
			$temp = array( rotateY( 0, 0, 0-$theta ),
					rotateY( $srcw, 0, 0-$theta ),
					rotateY( 0, $srch, 0-$theta ),
					rotateY( $srcw, $srch, 0-$theta )
				);
			$minY = floor( min( $temp ) );
			$maxY = ceil( max( $temp ) );
			$height = $maxY - $minY;
		}

		$destimg = imagecreatetruecolor( $width, $height );
		if( $ignore_transparent == 0 )
		{
			imagefill( $destimg, 0, 0, imagecolorallocatealpha( $destimg, 255,255, 255, 127 ) );
			imagesavealpha( $destimg, true );
		}

		// sets all pixels in the new image
		for( $x = $minX; $x < $maxX; $x++ )
		{
			for( $y = $minY; $y < $maxY; $y++ )
			{
				// fetch corresponding pixel from the source image
				$srcX = round( rotateX( $x, $y, $theta ) );
				$srcY = round( rotateY( $x, $y, $theta ) );
				if( $srcX >= 0 && $srcX < $srcw && $srcY >= 0 && $srcY < $srch )
				{
					$color = imagecolorat( $srcImg, $srcX, $srcY );
				}
				else
				{
					$color = $bgcolor;
				}
				imagesetpixel( $destimg, $x-$minX, $y-$minY, $color );
			}
		}
		return $destimg;
	}
}

?>