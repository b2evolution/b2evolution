<?php
/**
 * This file implements various File handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * converts bytes to readable bytes/kb/mb/gb
 *
 * @param integer bytes
 * @return string bytes made readable
 */
function bytesreadable( $bytes )
{
	$type = array ('b', 'kb', 'mb', 'gb');

	for ($i = 0; $bytes > 1024; $i++)
		$bytes /= 1024;

	return str_replace(',', '.', round($bytes, 2)) . $type[$i];
}


/**
 * create crossplatform-safe filename
 * @param string filename/path
 * @return string converted path
 */
function safefilename( $path )
{
	$path = preg_replace( '/[^A-Za-z0-9]+/', '_', $path );

	// remove trailing/leading '_'
	$path = preg_replace( '/^_+/', '', $path );
	$path = preg_replace( '/_+$/', '', $path );

	return $path;
}


/**
 * get size of directory, including anything in there.
 *
 * @param string the dir's full path
 */
function get_dirsize_recursive( $path )
{
	if( !($dir = @opendir( $path )) )
	{
		return false;
	}
	$total = 0;
	while( $cur = readdir($dir) ) if( !in_array( $cur, array('.', '..')) )
	{
		if( is_dir($path.'/'.$cur) )
		{
			$total += get_dirsize_recursive($path.'/'.$cur);
		}
		else
		{
			$total += filesize($path.'/'.$cur);
		}
	}
	return $total;
}


/**
 * deletes a dir recursive, wiping out all subdirectories!!
 *
 * @param string the dir
 */
function deldir_recursive( $dir )
{
	$current_dir = opendir( $dir );
	while( $entryname = readdir($current_dir) )
	{
		if( is_dir( "$dir/$entryname" ) && ( $entryname != '.' && $entryname != '..') )
		{
			deldir( "$dir/$entryname" );
		}
		elseif( $entryname != '.' && $entryname != '..' )
		{
			unlink( "$dir/$entryname" );
		}
	}
	closedir( $current_dir );
	return rmdir( $dir );
}


/**
 * Get the size of an image file
 *
 * @param string absolute file path
 * @param string what property/format to get: 'width', 'height', 'widthxheight',
 *               'type', 'string' (as for img tags), else 'widthheight' (array)
 * @return mixed false if no image, otherwise what was requested through $param
 */
function imgsize( $path, $param = 'widthheight' )
{
	/**
	 * Cache image sizes
	 */
	global $cache_imgsize;

	if( isset($cache_imgsize[$path]) )
	{
		$size = $cache_imgsize[$path];
	}
	elseif( !($size = @getimagesize( $path )) )
	{
		return false;
	}
	else
	{
		$cache_imgsize[$path] = $size;
	}

	if( $param == 'width' )
	{
		return $size[0];
	}
	elseif( $param == 'height' )
	{
		return $size[1];
	}
	elseif( $param == 'widthxheight' )
	{
		return $size[0].'x'.$size[1];
	}
	elseif( $param == 'type' )
	{
		switch( $size[1] )
		{
			case 1: return 'gif';
			case 2: return 'jpg';
			case 3: return 'png';
			case 4: return 'swf';
			default: return 'unknown';
		}
	}
	elseif( $param == 'string' )
	{
		return $size[3];
	}
	else
	{ // default: 'widthheight'
		return array( $size[0], $size[1] );
	}
}


/**
 * Add a trailing slash, if none present
 *
 * @param string the path/url
 * @return string the path/url with trailing slash
 */
function trailing_slash( $path )
{
	if( empty($path) || substr( $path, -1 ) == '/' )
	{
		return $path;
	}
	else
	{
		return $path.'/';
	}
}


/**
 * Displays file permissions like 'ls -l'
 *
 * @author zilinex at linuxmail dot com {@link www.php.net/manual/en/function.fileperms.php}
 * @todo move out of class
 * @param string
 */
function translatePerm( $in_Perms )
{
	$sP = '';

	if(($in_Perms & 0xC000) == 0xC000)     // Socket
		$sP = 's';
	elseif(($in_Perms & 0xA000) == 0xA000) // Symbolic Link
		$sP = 'l';
	elseif(($in_Perms & 0x8000) == 0x8000) // Regular
		$sP = '&minus;';
	elseif(($in_Perms & 0x6000) == 0x6000) // Block special
		$sP = 'b';
	elseif(($in_Perms & 0x4000) == 0x4000) // Directory
		$sP = 'd';
	elseif(($in_Perms & 0x2000) == 0x2000) // Character special
		$sP = 'c';
	elseif(($in_Perms & 0x1000) == 0x1000) // FIFO pipe
		$sP = 'p';
	else                                   // UNKNOWN
		$sP = 'u';

	// owner
	$sP .= (($in_Perms & 0x0100) ? 'r' : '&minus;') .
					(($in_Perms & 0x0080) ? 'w' : '&minus;') .
					(($in_Perms & 0x0040) ? (($in_Perms & 0x0800) ? 's' : 'x' ) :
																	(($in_Perms & 0x0800) ? 'S' : '&minus;'));

	// group
	$sP .= (($in_Perms & 0x0020) ? 'r' : '&minus;') .
					(($in_Perms & 0x0010) ? 'w' : '&minus;') .
					(($in_Perms & 0x0008) ? (($in_Perms & 0x0400) ? 's' : 'x' ) :
																	(($in_Perms & 0x0400) ? 'S' : '&minus;'));

	// world
	$sP .= (($in_Perms & 0x0004) ? 'r' : '&minus;') .
					(($in_Perms & 0x0002) ? 'w' : '&minus;') .
					(($in_Perms & 0x0001) ? (($in_Perms & 0x0200) ? 't' : 'x' ) :
																	(($in_Perms & 0x0200) ? 'T' : '&minus;'));
	return $sP;
}


/**
	Does the same thing as the function realpath(), except it will
	also translate paths that don't exist on the system.

	@param string the path to be translated
	@return array [0] = the translated path (with trailing slash); [1] = TRUE|FALSE (path exists?)
*/
function str2path( $path )
{
	$path = str_replace( '\\', '/', $path );
	$pwd = realpath( $path );

	if( !empty($pwd) )
	{ // path exists
		$pwd = str_replace( '\\', '/', $pwd);
		if( substr( $pwd, -1 ) !== '/' )
		{
			$pwd .= '/';
		}
		return array( $pwd, true );
	}
	else
	{ // no realpath
		$pwd = '';
		$strArr = preg_split( '#/#', $path, -1, PREG_SPLIT_NO_EMPTY );
		$pwdArr = array();
		$j = 0;
		for( $i = 0; $i < count($strArr); $i++ )
		{
			if( $strArr[$i] != '..' )
			{
				if( $strArr[$i] != '.' )
				{
					$pwdArr[$j] = $strArr[$i];
					$j++;
				}
			}
			else
			{
				array_pop( $pwdArr );
				$j--;
			}
		}
		return array( implode('/', $pwdArr).'/', false );
	}
}


/**
 * Check a filename if it has an image extension
 *
 * @param string the filename to check
 * @return boolean true if the filename indicates an image, false otherwise
 */
function isImage( $filename )
{
	global $regexp_images;

	return (boolean)preg_match( $regexp_images, $filename );
}


/**
 * Check for valid filename (no path allowed)
 *
 * @param string filename to test
 */
function isFilename( $filename )
{
	global $regexp_filename;

	return (boolean)preg_match( $regexp_filename, $filename );
}

/*
 * $Log$
 * Revision 1.4  2004/11/05 00:36:43  blueyed
 * no message
 *
 * Revision 1.3  2004/10/21 00:14:44  blueyed
 * moved
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.10  2004/10/12 22:33:40  blueyed
 * minor doc formatation
 *
 * Revision 1.9  2004/10/12 17:22:30  fplanque
 * Edited code documentation.
 *
 */
?>