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
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 *
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * These are the filetypes.
 * The extension is a regular expression that must match the end of the file.
 *
 * @todo Move it to some /conf file.
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
function & getFile( $name, $path = NULL )
{
	global $cache_File;

	$path = trailing_slash( $path === NULL ?
													getcwd() :
													$path );

	$path = str_replace( '\\', '/', trailing_slash($path) );

	$cacheindex = is_windows() ?
								strtolower($path.$name) :
								$path.$name;


	if( isset( $cache_File[ $cacheindex ] ) )
	{
		#Log::display( '', '', 'File ['.$cacheindex.'] returned from cache.' );
		return $cache_File[ $cacheindex ];
	}
	else
	{
		#Log::display( '', '', 'File ['.$cacheindex.'] not cached.' );
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
class File extends DataObject
{
	/**
	 * We haven't checked for meta data yet
	 */
	var $meta = 'unknown';

	/**#@+
	 * @access protected
	 */
	/**
	 * @var string Cached iconfile name
	 */
	var $_iconfilename = NULL;
	var $_dir;
	var $_name;
	var $_md5ID;
	var $_exists;
	var $_isDir;
	var $_size;
	var $_lastMod;
	var $_perms;
	/**
	 * @see isImage()
	 * @var boolean Is the File an image?
	 */
	var $_isImage = 1;
	/**#@-*/


	/**
	 * Constructor, not meant to be called directly. Use {@link getFile()}
	 * instead, which provides caching and checks that only one object for
	 * a unique file exists (references).
	 *
	 * @param string name of the file / directory
	 * @param string path to the file / directory
	 * @param boolean check for meta data?
	 * @return mixed false on failure, File object on success
	 */
	function File( $name, $dir, $meta = false )
	{
		// Call parent constructor
		parent::DataObject( 'T_files', 'file_', 'file_ID', '', '', '', '' );

		$this->setName( $name );
		$this->setDir( $dir );

		$this->_md5ID = md5( $this->_dir.$this->_name );

		// Get/Memorize detailed file info:
		$this->refresh();

		if( $meta )
		{ // Try to load DB meta info:
			$this->load_meta();
		}
	}


	/**
	 * Attempt to load meta data.
	 *
	 * Will attempt only once and cache the result.
	 */
	function load_meta()
	{
		global $DB, $Debuglog;

		if( $this->meta == 'unknown' )
		{ // We haven't tried loading yet:
			if( $row = $DB->get_row( 'SELECT * FROM T_files
																WHERE file_path = '.$DB->quote($this->getPath()) ) )
			{ // We found meta data
				$Debuglog->add('Loaded metadata for '.$this->getPath());
				$this->meta = 'loaded';
				$this->ID = $row->file_ID;
				$this->caption = $row->file_caption;
			}
			else
			{
				$this->meta = 'notfound';
			}
		}

		return ($this->meta == 'loaded');
	}


	/**
	 * Create the file/directory on disk, if it does not exist.
	 *
	 * Also sets file permissions.
	 * Also inserts meta data into DB.
	 *
	 * @param string type ('dir'|'file')
	 * @param string optional permissions (octal format)
	 * @return boolean true if file was created, false on failure
	 */
	function create( $type = 'file', $chmod = NULL )
	{
		if( $type == 'dir' )
		{ // Create an empty directory:
			if( $chmod === NULL )
			{ // Create dir with default permissions (777)
				$r = @mkdir( $this->_dir.$this->_name );
			}
			else
			{ // Create directory with specific permissions:
				$r = @mkdir( $this->_dir.$this->_name, octdec($chmod) );
			}
		}
		else
		{ // Create an empty file:
			$r = touch( $this->_dir.$this->_name );
			if( $chmod !== NULL )
			{
				$this->chmod( $chmod );
			}
		}

		if( $r )
		{ // Get/Memorize detailed file info:
			$this->refresh();
		}

		// If there was meta data for this file in the DB:
		// (maybe the file had existed before?)
		// Let's recycle it! :
		$this->load_meta();
		// TODO: make path relative to a root.
		$this->set( 'path', $this->getPath() );
		$this->set( 'caption', 'dummy demo text' );
		// Record to DB:
		$this->dbsave();

		return $r;
	}


	/**
	 * Refreshes (and inits) information about the file.
	 */
	function refresh()
	{
		// Unset values that will be determined (and cached) upon request
		$this->_isImage = NULL;

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
		$this->_lastMod = @filemtime( $this->_dir.$this->_name );
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
	 * Is the File an image?
	 *
	 * @return boolean true if the object is an image, false if not
	 */
	function isImage()
	{
		if( is_null( $this->_isImage ) )
		{
			$this->_isImage = ( $this->getImageSize() !== false );
		}

		return $this->_isImage;
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
	 * Get the name either prefix with "Directory" or "File".
	 *
	 * @return string
	 */
	function getNameWithType()
	{
		if( $this->isDir() )
		{
			return sprintf( T_('Directory &laquo;%s&raquo;'), $this->_name );
		}
		else
		{
			return sprintf( T_('File &laquo;%s&raquo;'), $this->_name );
		}
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
	 * If the File is a directory, the Path ends with a /
	 *
	 * @param boolean full path with name?
	 */
	function getPath( $withname = true )
	{
		return $this->_dir.$this->_name
						.( $this->isDir() ? '/' : '' );
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


	/**
	 * Get the file type as a descriptive string.
	 */
	function getType()
	{
		global $fm_filetypes;

		if( isset( $this->_type ) )
		{ // The type is already cached for this object:
			return $this->_type;
		}

		if( $this->isDir() )
		{
			$this->_type = T_('Directory');
			return $this->_type;
		}

		$filename = $this->getName();
		foreach( $fm_filetypes as $type => $desc )
		{
			if( preg_match('/'.$type.'$/i', $filename) )
			{
				$this->_type = $desc;
				return $this->_type;
			}
		}

		$this->_type = T_('Unknown');
		return $this->_type;
	}


	/**
	 * Get file size in bytes
	 *
	 * @return integer bytes
	 */
	function getSize()
	{
		return $this->_size;
	}


	/**
	 * Get date of last modification
	 *
	 $ @param string date format
	 * @return string locale formatted date
	 */
	function getLastMod( $format = '#' )
	{
		switch( $format )
		{
			case '#':
				$format = locale_datefmt().' '.locale_timefmt();
				break;

			case 'date':
				$format = locale_datefmt();
				break;

			case 'time':
				$format = locale_timefmt();
				break;
		}

		return date_i18n( $format, $this->_lastMod );
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
	 * Get the key of the respective icon file for this file (looks at file's extension).
	 *
	 *
	 *
	 * @uses $map_iconfiles
	 * @return string Key of the iconfile in {@link $map_iconfiles}.
	 *                'file_unknown' if no match was found.
	 *                'folder' if the file is a directory.
	 */
	function getIconKey()
	{
		global $map_iconfiles;

		if( !isset($this->_iconKey) )
		{
			if( $this->isDir() )
			{ // Directory icon:
				$this->_iconKey = 'folder';
			}
			else
			{
				$this->_iconKey = 'file_unknown';

				// Loop through known file icons:
				foreach( $map_iconfiles as $lKey => $lIconfile )
				{
					if( isset( $lIconfile['ext'] )
							&& preg_match( '/'.$lIconfile['ext'].'$/i', $this->_name, $match ) )
					{
						$this->_iconKey = $lKey;
						break;
					}
				}
			}
		}

		return $this->_iconKey;
	}


	/**
	 * Get size of an image or false if not an image
	 *
	 * @todo cache this data (NOTE: we have different params here! - imgsize() does already caching!)
	 *
	 * @uses imgsize()
	 * @param string {@link imgsize()}
	 * @return false|mixed false if the File is not an image, the requested data otherwise
	 */
	function getImageSize( $param = 'widthxheight' )
	{
		return imgsize( $this->getPath(), $param );
	}


	/**
	 * Get nice size of the file.
	 *
	 * @uses bytesreadable()
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
	 * Internally sets the filename.
	 *
	 * @access private
	 * @param string
	 */
	function setName( $name )
	{
		$this->_name = $name;
	}


	/**
	 * Internally sets the file path
	 *
	 * @access private
	 * @param string
	 */
	function setDir( $dir )
	{
		$this->_dir = str_replace( '\\', '/', trailing_slash( $dir ) );
	}


	/**
	 * Internally sets the file/directory size
	 *
	 * @access private
	 * @param integer
	 */
	function setSize( $bytes )
	{
		$this->_size = $bytes;
	}


	/**
	 * Rename the file in its current directoty on disk.
	 *
	 * Also update meta data in DB
	 *
	 * @access public
	 * @param string new name (without path!)
	 * @return boolean true on success, false on failure
	 */
	function rename( $newname )
	{
		if( rename( $this->getPath(), $this->getDir().$newname ) )
		{
			$this->setName( $newname );

			// Meta data...:
			// TODO: make path relative to a root.
			$this->set( 'path', $this->getPath() );
			// Record to DB:
			$this->dbupdate();

			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Unlink/Delete the file or folder from disk.
	 *
	 * Also removes meta data from DB.
	 *
	 * @access public
	 * @return boolean true on success, false on failure
	 */
	function unlink()
	{
		if( $this->isDir() )
		{
			$unlinked =	@rmdir( $this->getPath() );
		}
		else
		{
			$unlinked =	@unlink( $this->getPath() );
		}

		if( !$unlinked )
		{
			return false;
		}

		$this->_exists = false;

		// Check if there is meta data to be removed:
		if( $this->load_meta() )
		{ // remove meta data from DB:
			$this->dbdelete();
		}

		return true;
	}


	/**
	 * Change file permissions on disk.
	 *
	 * @access public
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
 * Revision 1.19  2005/01/26 23:44:34  blueyed
 * no message
 *
 * Revision 1.18  2005/01/21 20:47:46  blueyed
 * doc, getLastMod() extended
 *
 * Revision 1.16  2005/01/16 18:32:27  blueyed
 * doc, whitespace
 *
 * Revision 1.15  2005/01/15 20:20:51  blueyed
 * $map_iconsizes merged with $map_iconfiles, removed obsolete getIconSize() (functionality moved to getIcon())
 *
 * Revision 1.14  2005/01/12 20:22:51  fplanque
 * started file/dataobject linking
 *
 * Revision 1.13  2005/01/12 16:07:54  fplanque
 * documentation
 *
 * Revision 1.12  2005/01/08 01:24:18  blueyed
 * filelist refactoring
 *
 * Revision 1.11  2005/01/06 11:31:45  blueyed
 * bugfixes
 *
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