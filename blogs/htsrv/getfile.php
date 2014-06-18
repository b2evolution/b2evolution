<?php
/**
 * This file implements the File view (including resizing of images)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * @todo dh> Add support for ETag / If-Modified-Since. Maybe send "Expires", too? (to "force" caching)
 *       fp> for more efficient caching (like creating a thumbnail on view 1 then displaying the thumbnail again on view 2), this should probably redirect to the static file right after creating it (when $public_access_to_media=true OF COURSE)
 *       dh> this would add another redirect/HTTP request and no cache handling, assuming
 *           that the server is not configured for smart caching.
 *           Additionally, it does not help for non-public access, which is the meat of this file.
 *           I've added "Expires: in ten years" now, but not for thumbs (see comment there).
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 */


/**
 * Load config, init and get the {@link $mode mode param}.
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'/_main.inc.php';

if( ! isset($GLOBALS['files_Module']) )
{
	debug_die( 'Files module is disabled or missing!' );
}

// We need this param early to check blog perms, if possible
param( 'root', 'string', true ); // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))

// Check permission:
if( ! $public_access_to_media )
{
	if( ! isset($current_User) )
	{
		debug_die( 'No permission to get file (not logged in)!', array('status'=>'403 Forbidden') );
	}


	// fp> I don't think we need the following if public_access_to_media
	if( preg_match( '/^collection_(\d+)$/', $root, $perm_blog ) )
	{	// OK, we got a blog ID:
		$perm_blog = $perm_blog[1];
	}
	else
	{	// No blog ID, we will check the global group perm
		$perm_blog = NULL;
	}
	//pre_dump( $perm_blog );

	// Check permission (#2):
	$current_User->check_perm( 'files', 'view', true, $perm_blog );
}

// Load the other params:
param( 'path', 'string', true );
param( 'size', 'string', NULL ); // Can be used for images.
param( 'size_x', 'integer', 1 ); // Ratio size, can be 1, 2 and etc.
param( 'mtime', 'integer', 0 );  // used for unique URLs (that never expire).

if( $size_x != 1 && $size_x != 2 )
{ // Allow only 1x and 2x sizes, in order to avoid hack that creates many x versions
	$size_x = 1;
}

// TODO: dh> this failed with filenames containing multiple dots!
if ( false !== strpos( urldecode( $path ), '..' ) )
// TODO: dh> fix this better. by adding is_relative_path()?
// fp> the following doesn't look secure. I can't take the risk. What if the path ends with or is just '..' ? I don't want to allow this to go through.
// if( preg_match( '~\.\.[/\\\]~', urldecode( $path ) ) )
{
	debug_die( 'Relative pathnames not allowed!' );
}

// Load fileroot info:
$FileRootCache = & get_FileRootCache();
$FileRoot = & $FileRootCache->get_by_ID( $root );

// Load file object (not the file content):
$File = new File( $FileRoot->type, $FileRoot->in_type_ID, $path );

// Check if the request has an If-Modified-Since date
if( array_key_exists( 'HTTP_IF_MODIFIED_SINCE', $_SERVER ) )
{
	$if_modified_since = strtotime( preg_replace('/;.*$/','',$_SERVER['HTTP_IF_MODIFIED_SINCE']) );
	$file_lastmode_ts = $File->get_lastmod_ts();
	if( $file_lastmode_ts <= $if_modified_since )
	{ // file was not modified since if_modified_since ts
		header_http_response( '304 Not Modified' );
		exit(0);
	}
}

if( ! empty( $size ) && $File->is_image() )
{	// We want a thumbnail:
	// fp> TODO: for more efficient caching, this should probably redirect to the static file right after creating it (when $public_access_to_media=true OF COURSE)

	global $thumbnail_sizes;

	load_funcs( '/files/model/_image.funcs.php' );

	$size_name = $size;
	if( ! isset( $thumbnail_sizes[$size] ) )
	{ // this file size alias is not defined, use default:
		// TODO: dh> this causes links for e.g. "fit-50x50" to work also, but with the drawback of images not getting served from the
		//           .evocache directory directly. I think invalid $size params should bark out here.
		// fp> ok.
		$size_name = 'fit-80x80';
	}

	if( ! isset ( $thumbnail_sizes[$size_name][4] ) )
	{	// Set blur percent in 0 by default
		$thumbnail_sizes[$size_name][4] = 0;
	}
	// Set all params for requested size:
	list( $thumb_type, $thumb_width, $thumb_height, $thumb_quality, $thumb_percent_blur ) = $thumbnail_sizes[$size_name];

	$Filetype = & $File->get_Filetype();
	// pre_dump( $Filetype );
	// TODO: dh> Filetype may be NULL here! see also r1.18 (IIRC)
	$mimetype = $Filetype->mimetype;
	// pre_dump( $mimetype );

	// Try to output the cached thumbnail:
	$err = $File->output_cached_thumb( $size_name, $mimetype, $mtime, $size_x );
	//pre_dump( $err );

	if( $err == '!Thumbnail not found in'.$Settings->get( 'evocache_foldername' ) )
	{	// The thumbnail wasn't already in the cache, try to generate and cache it now:
		$err = NULL;		// Short error code

		list( $src_width, $src_height ) = imgsize( $File->get_full_path() );

		if( ! $resample_all_images && $src_width <= $thumb_width && $src_height <= $thumb_height )
		{	// There is no need to resample, use original!
			$err = $File->get_af_thumb_path( $size_name, $mimetype, true, $size_x );

			if( $err[0] != '!' && @copy( $File->get_full_path(), $err ) )
			{	// File was saved. Ouput that same file immediately:
				// note: @copy returns FALSE on failure, if not muted it'll print the error on screen
				$err = $File->output_cached_thumb( $size_name, $mimetype, $mtime, $size_x );
			}
		}
		else
		{	// Resample
			list( $err, $src_imh ) = load_image( $File->get_full_path(), $mimetype );

			if( empty( $err ) )
			{
				list( $err, $dest_imh ) = generate_thumb( $src_imh, $thumb_type, $thumb_width, $thumb_height, $thumb_percent_blur, $size_x );
				if( empty( $err ) )
				{
					$err = $File->save_thumb_to_cache( $dest_imh, $size_name, $mimetype, $thumb_quality, $size_x );
					if( empty( $err ) )
					{	// File was saved. Ouput that same file immediately:
						// This is probably better than recompressing the memory image..
						$err = $File->output_cached_thumb( $size_name, $mimetype, $mtime, $size_x );
					}
					else
					{	// File could not be saved.
						// fp> We might want to output dynamically...
						// $err = output_image( $dest_imh, $mimetype );
					}
				}
			}
		}
	}

	// ERROR IMAGE
	if( !empty( $err ) )
	{	// Generate an error image and try to squeeze an error message inside:
		// Note: we write small and close to the upper left in order to have as much text as possible on small thumbs
		$line_height = 11;
		$err = substr( $err, 1 ); // crop 1st car
		$car_width = ceil( ($thumb_width-4)/6 );
		// $err = 'w='.$car_width.' '.$err;

		// Wrap error message and split it into lines:
		$err_lines = preg_split( '~\n~', wordwrap( $err, $car_width, "\n", true ) );
		$im_handle = imagecreatetruecolor( $thumb_width, $thumb_height ); // Create a black image
		if( count($err_lines)*$line_height > $thumb_height )
		{ // Message does not fit into picture:
		  // Rewrite error messages, so they fit better into the generated images.
			$rewritten = true;
			if( preg_match('~Unable to open \'.*?\' for writing: Permission denied~', $err) )
				$err = 'Cannot write: permission denied';
			else
				$rewritten = false;
			// Recreate error lines, if it has been rewritten/shortened.
			if( $rewritten )
			{
				$err_lines = preg_split( '~\n~', wordwrap( $err, $car_width, "\n", true ) );
			}
		}

		$text_color = imagecolorallocate( $im_handle, 255, 0, 0 );
		$y = 0;
		foreach( $err_lines as $err_string )
		{
			imagestring( $im_handle, 2, 2, $y, $err_string, $text_color);
			$y += $line_height;
		}

		header('Content-type: image/png' );
		header_nocache();	// Do NOT cache errors! People won't see they have fixed them!!

		imagepng( $im_handle );
	}
}
else
{	// We want the regular file:
	// Headers to display the file directly in the browser
	if( ! is_readable($File->get_full_path()) )
	{
		debug_die( sprintf('File "%s" is not readable!', rel_path_to_base($File->get_full_path())) );
	}

	$Filetype = & $File->get_Filetype();
	if( ! empty($Filetype) )
	{
		header('Content-type: '.$Filetype->mimetype );
		if( $Filetype->viewtype == 'download' )
		{
			header('Content-disposition: attachment; filename="'
				.addcslashes($File->get_name(), '\\"').'"' ); // escape quotes and slashes, according to RFC
		}
	}
	$file_path = $File->get_full_path();
	header('Content-Length: '.filesize( $file_path ) );

	// The URL refers to this specific file, therefore we can tell the browser that
	// it does not expire anytime soon.
	// fp> I don't think mtime changes anything to the cacheability of the data
	// if( $mtime && $mtime == $File->get_lastmod_ts() ) // TODO: dh> use salt here?! fp>what for?
	header_noexpire();	// static file

	// Display the content of the file
	readfile( $file_path );
}

?>