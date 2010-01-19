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
param( 'size', 'string', NULL );	// Can be used for images.
param( 'mtime', 'integer', 0 );     // used for unique URLs (that never expire).

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
$File = & new File( $FileRoot->type, $FileRoot->in_type_ID, $path );

if( !empty($size) && $File->is_image() )
{	// We want a thumbnail:
	// fp> TODO: for more efficient caching, this should probably redirect to the static file right after creating it (when $public_access_to_media=true OF COURSE)

	global $thumbnail_sizes;

	load_funcs( '/files/model/_image.funcs.php' );

	$size_name = $size;
	if( ! isset($thumbnail_sizes[$size] ) )
	{ // this file size alias is not defined, use default:
		// TODO: dh> this causes links for e.g. "fit-50x50" to work also, but with the drawback of images not getting served from the
		//           .evocache directory directly. I think invalid $size params should bark out here.
		// fp> ok.
		$size_name = 'fit-80x80';
	}

	// Set all params for requested size:
	list( $thumb_type, $thumb_width, $thumb_height, $thumb_quality ) = $thumbnail_sizes[$size_name];

	$Filetype = & $File->get_Filetype();
	// pre_dump( $Filetype );
	// TODO: dh> Filetype may be NULL here! see also r1.18 (IIRC)
	$mimetype = $Filetype->mimetype;
	// pre_dump( $mimetype );

	// Try to output the cached thumbnail:
	$err = $File->output_cached_thumb( $size_name, $mimetype, $mtime );
	//pre_dump( $err );

	if( $err == '!Thumbnail not found in .evocache' )
	{	// The thumbnail wasn't already in the cache, try to generate and cache it now:
		$err = NULL;		// Short error code
		
		list( $src_width, $src_height ) = imgsize( $File->get_full_path() );
		
		if( $src_width <= $thumb_width && $src_height <= $thumb_height )
		{	// There is no need to resample, use original!
			$err = $File->get_af_thumb_path( $size_name, $mimetype, true );
			
			if( $err[0] != '!' && @copy( $File->get_full_path(), $err ) )
			{	// File was saved. Ouput that same file immediately:
// fp>alex TODO: how do you know the file was saved? you put an @ in front of @copy!!
// sam2kb> @copy returns FALSE on failure, if not muted it'll print the error on screen
				$err = $File->output_cached_thumb( $size_name, $mimetype, $mtime );
			}
		}
		else
		{	// Resample
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
						$err = $File->output_cached_thumb( $size_name, $mimetype, $mtime );
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
		$err_lines = split( "\n", wordwrap( $err, $car_width, "\n", true ) );
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
				$err_lines = split( "\n", wordwrap( $err, $car_width, "\n", true ) );
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

/*
 * $Log$
 * Revision 1.49  2010/01/19 19:14:20  sam2kb
 * doc
 *
 * Revision 1.48  2010/01/19 19:03:06  fplanque
 * doc
 *
 * Revision 1.47  2010/01/16 06:05:42  sam2kb
 * Copy original image to .evocache if its size is lower or equal to requested thumb size
 *
 * Revision 1.46  2009/12/04 23:27:49  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.45  2009/12/02 01:00:07  fplanque
 * header_nocache & header_noexpire
 *
 * Revision 1.44  2009/11/30 22:17:38  blueyed
 * Improve error messages in images. save_image: catch errors. getfile: shorten errors, if required.
 *
 * Revision 1.43  2009/11/29 23:55:08  fplanque
 * leave pre_dumps! This has a tendency to crash a lot these days. prolly some faulty GD or PHP version. i'm not sure.
 *
 * Revision 1.41  2009/11/11 20:16:15  fplanque
 * doc
 *
 * Revision 1.40  2009/09/29 02:52:20  fplanque
 * doc
 *
 * Revision 1.39  2009/09/27 19:09:20  blueyed
 * todo
 *
 * Revision 1.38  2009/09/25 07:32:51  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.37  2009/09/20 23:54:24  blueyed
 * File::output_cached_thumb handles mtime param, and uses it to send a
 * far in the future Expires header.
 * mtime param gets forwarded from getfile.php.
 * This makes browsers finally cache files served through getfile.php.
 *
 * Revision 1.36  2009/09/19 23:34:58  fplanque
 * security risk
 *
 * Revision 1.35  2009/09/19 21:54:08  blueyed
 * Fix getfile.php for files containing multiple dots.
 *
 * Revision 1.34  2009/09/01 16:10:29  tblue246
 * minor
 *
 * Revision 1.33  2009/08/31 21:55:52  fplanque
 * no message
 *
 * Revision 1.32  2009/08/29 12:23:55  tblue246
 * - SECURITY:
 * 	- Implemented checking of previously (mostly) ignored blog_media_(browse|upload|change) permissions.
 * 	- files.ctrl.php: Removed redundant calls to User::check_perm().
 * 	- XML-RPC APIs: Added missing permission checks.
 * 	- items.ctrl.php: Check permission to edit item with current status (also checks user levels) for update actions.
 * - XML-RPC client: Re-added check for zlib support (removed by update).
 * - XML-RPC APIs: Corrected method signatures (return type).
 * - Localization:
 * 	- Fixed wrong permission description in blog user/group permissions screen.
 * 	- Removed wrong TRANS comment
 * 	- de-DE: Fixed bad translation strings (double quotes + HTML attribute = mess).
 * - File upload:
 * 	- Suppress warnings generated by move_uploaded_file().
 * 	- File browser: Hide link to upload screen if no upload permission.
 * - Further code optimizations.
 *
 * Revision 1.31  2009/08/06 14:55:45  fplanque
 * doc
 *
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