<?php
/**
 * This file implements the File view (including resizing of images)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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


// Check permission:
if( ! $public_access_to_media )
{
	if( ! isset($current_User) )
	{
		debug_die( 'No permission to get file (not logged in)!', array('status'=>'403 Forbidden') );
	}
	$current_User->check_perm( 'files', 'view', true );
	// fp> TODO: check specific READ perm for requested fileroot
}

// Load params:
param( 'root', 'string', true );	// the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
param( 'path', 'string', true );
param( 'size', 'string', NULL );	// Can be used for images.
param( 'mtime', 'integer', 0 );     // used for unique URLs (that never expire).

if ( false !== strpos( urldecode( $path ), '..' ) )
{
	debug_die( 'Relative pathnames not allowed!' );
}

// Load fileroot info:
$FileRootCache = & get_Cache( 'FileRootCache' );
$FileRoot = & $FileRootCache->get_by_ID( $root );

// Load file object (not the file content):
$File = & new File( $FileRoot->type, $FileRoot->in_type_ID, $path );

if( !empty($size) && $File->is_image() )
{	// We want a thumbnail:
	global $thumbnail_sizes;

	load_funcs( '/files/model/_image.funcs.php' );

	$size_name = $size;
	if( ! isset($thumbnail_sizes[$size] ) )
	{ // this file size alias is not defined, use default:
		$size_name = 'fit-80x80';
	}

	// Set all params for requested size:
	list( $thumb_type, $thumb_width, $thumb_height, $thumb_quality ) = $thumbnail_sizes[$size_name];

	$Filetype = & $File->get_Filetype();
	// TODO: dh> Filetype may be NULL here! see also r1.18 (IIRC)
	$mimetype = $Filetype->mimetype;

	// Try to output the cached thumbnail:
	// TODO: dh> this should also then "Expires" header. Please refactor.
	$err = $File->output_cached_thumb( $size_name, $mimetype );

	if( $err == '!Thumbnail not found in .evocache' )
	{	// The thumbnail wasn't already in the cache, try to generate and cache it now:
		$err = NULL;		// Short error code

		list( $err, $src_imh ) = load_image( $File->get_full_path(), $mimetype );
		if( empty( $err ) )
		{
			list( $err, $dest_imh ) = generate_thumb( $src_imh, $thumb_type, $thumb_width, $thumb_height );
			if( empty( $err ) )
			{
				$err = $File->save_thumb_to_cache( $dest_imh, $size_name, $mimetype, $thumb_quality );
				if( empty( $err ) )
				{	// File was saved. Ouput that same file immediately:
					// This is probably better than recompressing the memory image..
					$err = $File->output_cached_thumb( $size_name, $mimetype );
				}
				else
				{	// File could not be saved.
					// fp> We might want to output dynamically...
					// $err = output_image( $dest_imh, $mimetype );
				}
			}
		}
	}

	// ERROR IMAGE
	if( !empty( $err ) )
	{	// Generate an error image and try to squeeze an error message inside:
		// Note: we write small and close to the upper left in order to have as much text as possible on small thumbs
		$err = substr( $err, 1 ); // crop 1st car
		$car_width = ceil( ($thumb_width-4)/6 );
		// $err = 'w='.$car_width.' '.$err;
		$err = wordwrap( $err, $car_width, "\n" );
		$err = split( "\n", $err );	// split into lines
		$im_handle = imagecreatetruecolor( $thumb_width, $thumb_height ); // Create a black image
		$text_color = imagecolorallocate( $im_handle, 255, 0, 0 );
		$y = 0;
		foreach( $err as $err_string )
		{
			imagestring( $im_handle, 2, 2, $y, $err_string, $text_color);
			$y += 11;
		}
		header('Content-type: image/png' );
		// The URL refers to this specific file, therefore we can tell the browser that
		// it does not expire anytime soon.
		if( $mtime && $mtime == $File->get_lastmod_ts() ) // TODO: dh> use salt here?!
		{
			header('Expires: '.date('r', time()+315360000)); // 86400*365*10 (10 years)
		}
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
	if( $mtime && $mtime == $File->get_lastmod_ts() ) // TODO: dh> use salt here?!
	{
		header('Expires: '.date('r', time()+315360000)); // 86400*365*10 (10 years)
	}

	// Display the content of the file
	readfile( $file_path );
}

/*
 * $Log$
 * Revision 1.30  2009/07/31 01:27:52  blueyed
 * TODO
 *
 * Revision 1.29  2009/07/31 00:17:20  blueyed
 * Move File::thumbnail to getfile.php, where it gets used exclusively. ACKed by FP.
 *
 * Revision 1.28  2009/03/08 23:57:36  fplanque
 * 2009
 *
 * Revision 1.27  2009/02/21 23:10:43  fplanque
 * Minor
 *
 * Revision 1.26  2009/02/19 04:53:21  blueyed
 * getfile.php: escape filename in Content-disposition header.
 *
 * Revision 1.25  2009/02/19 04:48:13  blueyed
 * Lazy-instantiate Filetype of a file, moved to get_Filetype. Bugfix: unset Filetype if name changes.
 *
 * Revision 1.24  2009/02/10 23:37:41  blueyed
 * Add status param to debug_die() and use it for "Forbidden" in getfile.php. This has quite some potential to get reverted, but then debug_die() should not get used there, maybe?!
 *
 * Revision 1.23  2009/02/10 23:28:59  blueyed
 * Add mtime-Expires caching to getfile.php.
 *  - getfile.php links have a mtime param to make the URLs unique
 *  - Add File::get_getfile_url
 *  - getfile.php sends "Expires: 'in 10 years'" (not for thumbs yet, see
 *    TODO)
 *
 * Revision 1.22  2009/01/19 21:50:47  fplanque
 * minor
 *
 * Revision 1.21  2009/01/17 21:09:27  blueyed
 * doc/todo
 *
 * Revision 1.20  2008/09/19 20:11:50  blueyed
 * getfile.php: fail if file is not readable and check if Filetype is set
 *
 * Revision 1.19  2008/09/15 10:35:28  fplanque
 * Fixed bug where thumbnails are only created when user is logged in
 *
 * Revision 1.18  2008/07/11 23:49:01  blueyed
 * TODO: add etag/modified-since support to getfile.php
 *
 * Revision 1.17  2008/07/07 05:59:26  fplanque
 * minor / doc / rollback of overzealous indetation "fixes"
 *
 * Revision 1.14  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.13  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.12  2006/12/13 20:10:30  fplanque
 * object responsibility delegation?
 *
 * Revision 1.11  2006/12/13 18:10:21  fplanque
 * thumbnail resampling proof of concept
 *
 * Revision 1.10  2006/12/13 03:08:28  fplanque
 * thumbnail implementation design demo
 *
 * Revision 1.9  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.8  2006/08/19 07:56:29  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.7  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.6  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>