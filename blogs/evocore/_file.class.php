<?php
/**
 * This file implements the File class.
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
 *
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * These are the filetypes. The extension is a regular expression that must match the end of the file.
 */
$fm_filetypes = array( // {{{
	'\.ai' => T_('Adobe illustrator'),
	'\.bmp' => T_('Bmp image'),
	'\.bz'  => T_('Bz Archive'),
	'\.c' => T_('Source C '),
	'\.cgi' => T_('CGI file'),
	'\.(conf|inf)' => T_('Config file'),
	'\.cpp' => T_('Source C++'),
	'\.css' => T_('Stylesheet'),
	'\.doc' => T_('MS Office'),
	'\.exe' => T_('Executable'),
	'\.gif' => T_('Gif image'),
	'\.gz'  => T_('Gz Archive'),
	'\.h' => T_('Header file'),
	'\.hlp' => T_('Help file'),
	'\.ht(access|passwd)' => T_('Apache file'),
	'\.htm' => T_('Hyper text'),
	'\.html' => T_('Hyper text'),
	'\.htt' => T_('Windows access'),
	'\.inc' => T_('Include file'),
	'\.ini' => T_('Setting file'),
	'\.jpe?g' => T_('Jpeg Image'),
	'\.js'  => T_('JavaScript'),
	'\.log' => T_('Log file'),
	'\.mdb' => T_('Access DB'),
	'\.midi' => T_('Media file'),
	'\.p(hp[345]?|html)' => T_('PHP script'),
	'\.pl' => T_('Perl script'),
	'\.png' => T_('Png image'),
	'\.ppt' => T_('MS Power point'),
	'\.psd' => T_('Photoshop Image'),
	'\.ram?' => T_('Realmedia file'),
	'\.rar' => T_('Rar Archive'),
	'\.rtf' => T_('Rich Text Format'),
	'\.sql' => T_('SQL file'),
	'\.s[tx]w' => T_('OpenOffice file'),
	'\.te?xt' => T_('Text document'),
	'\.tgz' => T_('Tar gz archive'),
	'\.vbs' => T_('MS Vb script'),
	'\.wri' => T_('Document'),
	'\.xml' => T_('XML file'),
	'\.zip' => T_('Zip Archive'),
); // }}}


/**
 * Creates an object of the {@link File} class, while providing caching
 * and making sure that only one reference to a file exists.
 *
 * @param string name of the file or directory
 * @param string path of the file or directory
 * @return File an {@link File} object
 */
function &getFile( $name, $path = NULL )
{
	global $cache_File;

	$path = trailing_slash( $path === NULL ?
													getcwd() :
													$path );

	$cacheindex = is_windows() ?
								strtolower($path.$name) :
								$path.$name;

	if( isset( $cache_File[ $cacheindex ] ) )
	{
		#Log::display( '', '', 'File ['.$cacheindex.'] returned from cache!' );
		return $cache_File[ $cacheindex ];
	}
	else
	{
		$File =& new File( $name, $path );
		$cache_File[$cacheindex] =& $File;
		return $File;
	}
}


/**
 * Represents a file or directory. Use {@link getFile} to create an instance.
 *
 * @package evocore
 */
class File
{
	/**
	 * Cached iconfile name
	 */
	var $_iconfilename = NULL;

	/**
	 * Constructor, not to be meant to called directly. Use {@link getFile()}
	 * instead, which provides caching and checks that only one object for
	 * a unique file exists (references).
	 *
	 * @param string name of the file / directory
	 * @param string path to the file / directory
	 * @return mixed false on failure, File object on success
	 */
	function File( $name, $dir )
	{
		$this->setName( $name );
		$this->_dir = $dir;
		$this->_md5ID = md5( $this->_dir.$this->_name );

		$this->refresh();
	}


	/**
	 * Create the file, if it does not exist.
	 *
	 * @param string type ('dir' / 'file')
	 * @param string optional permissions (octal format)
	 * @return boolean true if file was created, false on failure
	 */
	function create( $type = 'file', $chmod = NULL )
	{
		if( $type == 'dir' )
		{
			$r = $chmod === NULL ?
						@mkdir( $this->_dir.$this->_name ) :
						@mkdir( $this->_dir.$this->_name, octdec($chmod) );
		}
		else
		{
			$r = touch( $this->_dir.$this->_name );
			if( $chmod !== NULL )
			{
				$this->chmod( $chmod );
			}
		}

		if( $r )
		{
			$this->refresh();
		}
		return $r;
	}


	/**
	 * Refreshes (and inits) information about the file.
	 */
	function refresh()
	{
		$this->_exists = file_exists( $this->_dir.$this->_name );

		if( is_dir( $this->_dir.$this->_name ) )
		{
			$this->_isDir = true;
			$this->_size = NULL;
		}
		else
		{
			$this->_isDir = false;
			$this->_size = @filesize( $this->_dir.$this->_name );
		}

		// for files and dirs
		$this->_lastm = @filemtime( $this->_dir.$this->_name );
		$this->_perms = @fileperms( $this->_dir.$this->_name );
	}


	/**
	 * Does the file exist?
	 *
	 * @return boolean true, if the file or dir exists; false if not
	 */
	function exists()
	{
		return $this->_exists;
	}


	/**
	 * Is the File a directory?
	 *
	 * @return boolean true if the object is a directory, false if not
	 */
	function isDir()
	{
		return $this->_isDir;
	}


	/**
	 * Get the File's ID (MD5 of path and name)
	 *
	 * @return string
	 */
	function getID()
	{
		return $this->_md5ID;
	}


	/**
	 * Get the File's name.
	 *
	 * @return string
	 */
	function getName()
	{
		return $this->_name;
	}

	/**
	 * Get the File's directory.
	 *
	 * @return string
	 */
	function getDir()
	{
		return $this->_dir;
	}


	/**
	 * Get the full path (directory and name) to the file.
	 *
	 * @param boolean full path with name?
	 */
	function getPath( $withname = true )
	{
		return $this->_dir.$this->_name
						.( $this->isDir() ?
								'/' :
								'' );
	}


	/**
	 * Get the file's extension.
	 *
	 * @return string the extension
	 */
	function getExt()
	{
		if( preg_match('/\.([^.]+)$/', $this->_name, $match) )
		{
			return $match[1];
		}
		else
		{
			return '';
		}
	}


	function getType()
	{
		if( isset( $this->_type ) )
		{
			return $this->_type;
		}


		if( $this->isDir() )
		{
			$this->_type = T_('directory');
			return $this->_type;
		}
		global $fm_filetypes;

		$filename = $this->getName();
		foreach( $fm_filetypes as $type => $desc )
		{
			if( preg_match('/'.$type.'$/i', $filename) )
			{
				$this->_type = $desc;
				return $this->_type;
			}
		}

		$this->_type = T_('unknown');
		return $this->_type;
	}


	function getSize()
	{
		return $this->_size;
	}


	function getLastMod()
	{
		return date_i18n( locale_datefmt().' '.locale_timefmt(), $this->_lastm );
	}


	/**
	 * Get permissions
	 *
	 * @param mixed type; 'raw': integer, 'lsl' string like 'ls -l',
	 *              'octal': 3 digits; default: 'r'/'r+w' for windows, 'octal'
	 *              for other OS
	 * @return string permissions
	 */
	function getPerms( $type = NULL )
	{
		switch( $type )
		{
			case 'raw':
				return $this->_perms;

			case 'lsl':
				return translatePerm( $this->_perms );

			case NULL:
				if( is_windows() )
				{
					if( $this->_perms & 0x0080 )
					{
						return 'r+w';
					}
					else return 'r';
				}

			case 'octal':
				return substr( sprintf('%o', $this->_perms), -3 );
		}

		return false;
	}


	/**
	 * Get the path of the respective icon file for this file (relative to
	 * {@link $basepath}, looks at file's extension).
	 * 'file_unknown' map entry is used if no match was found, or 'folder' if
	 * the file is a directory.
	 *
	 * @uses $map_iconfiles
	 * @return string Path to the iconfile (relative to {@link $baseurl})
	 */
	function getIconPath()
	{
		global $map_iconfiles;
		if( $this->_iconfilename !== NULL )
		{ // cached
			return $this->_iconfilename;
		}

		if( $this->isDir() )
		{
			$iconfilename = $map_iconfiles['folder']['file'];
		}
		else
		{
			$iconfilename = $map_iconfiles['file_unknown']['file'];
			foreach( $map_iconfiles as $lIconfile )
			{
				if( isset( $lIconfile['ext'] )
						&& preg_match( '/'.$lIconfile['ext'].'$/i', $this->_name, $match ) )
				{
					$iconfilename = $lIconfile['file'];
					break;
				}
			}
		}

		$this->_iconfilename = $iconfilename;

		return $this->_iconfilename;
	}


	/**
	 * get size of an image or false if not an image
	 *
	 * @param string {@link imgsize()}
	 */
	function getImageSize( $param = 'widthxheight' )
	{
		return imgsize( $this->getPath(), $param );
	}


	/**
	 * get nice size of the file
	 *
	 * @return string size as b/kb/mb/gd; or '&lt;dir&gt;'
	 */
	function getSizeNice()
	{
		if( $this->_size === NULL )
		{
			return /* TRANS: short for '<directory>' */ T_('&lt;dir&gt;');
		}
		else
		{
			return bytesreadable( $this->_size );
		}
	}


	/**
	 * Set the File's name.
	 *
	 * @param string
	 */
	function setName( $name )
	{
		$this->_name = $name;
	}


	function setSize( $bytes )
	{
		$this->_size = $bytes;
	}


	/**
	 * Rename the file.
	 *
	 * @param string new name (without path!)
	 * @return boolean true on success, false on failure
	 */
	function rename( $newname )
	{
		if( rename( $this->getPath(), $this->getDir().$newname ) )
		{
			$this->setName( $newname );
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Unlink / Delete the file or folder.
	 *
	 * @return boolean true on success, false on failure
	 */
	function unlink()
	{
		$unlinked = $this->isDir() ?
								@rmdir( $this->getPath() ) :
								@unlink( $this->getPath() );
		if( !$unlinked )
		{
			return false;
		}

		$this->_exists = false;
		return true;
	}


	/**
	 * Change permissions of the file
	 *
	 * @param string chmod (three-digit-format, eg '777')
	 * @return mixed new permissions on success (octal format), false on failure
	 */
	function chmod( $chmod )
	{
		$chmod = octdec( $chmod );
		if( chmod( $this->getPath(), $chmod) )
		{
			clearstatcache();
			// update current entry
			$this->_perms = fileperms( $this->getPath() );

			return $this->_perms;
		}
		else
		{
			return false;
		}
	}

}

/*
 * $Log$
 * Revision 1.10  2005/01/05 03:04:00  blueyed
 * refactored
 *
 * Revision 1.6  2004/11/03 00:58:02  blueyed
 * update
 *
 * Revision 1.5  2004/10/24 22:55:12  blueyed
 * upload, fixes, ..
 *
 * Revision 1.4  2004/10/23 23:07:16  blueyed
 * case-insensitive for windows!
 *
 * Revision 1.2  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.11  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>