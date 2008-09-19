<?php
/**
 * This file implements the File view (including resizing of images)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @todo fp> for more efficient caching, this should probably redirect to the static file right after creating it
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
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
		debug_die( 'No permission to get file (not logged in)!' );
	}
	$current_User->check_perm( 'files', 'view', true );
	// fp> TODO: check specific READ perm for requested fileroot
}

// Load params:
param( 'root', 'string', true );	// the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
param( 'path', 'string', true );
param( 'size', 'string', NULL );	// Can be used for images.

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
	// This will do all the magic:
	$File->thumbnail( $size );
	// fp> TODO: for more efficient caching, this should probably redirect to the static file right after creating it
}
else
{	// We want the regular file:
	// Headers to display the file directly in the browser
	if( ! is_readable($File->get_full_path()) )
	{
		debug_die( sprintf('File "%s" is not readable!', rel_path_to_base($File->get_full_path())) );
	}

	if( ! empty($File->Filetype) )
	{
		header('Content-type: '.$File->Filetype->mimetype );
		if( $File->Filetype->viewtype == 'download' )
		{
			header('Content-disposition: attachment; filename="'.$File->get_name().'"' );
		}
	}
	header('Content-Length: '.filesize( $File->get_full_path() ) );


	// Display the content of the file
	readfile( $File->get_full_path() );
}

/*
 * $Log$
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