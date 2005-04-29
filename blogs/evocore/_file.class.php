<?php
/**
 * This file implements the File class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Represents a file or folder on disk. Optionnaly stores meta data from DB.
 *
 * Use {@link FileCache::get_by_path()} to create an instance.
 * This is based on {@link DataObject} for the meta data.
 *
 * @package evocore
 */
class File extends DataObject
{
	/**
	 * Have we checked for meta data in the DB yet?
	 * @var string
	 */
	var $meta = 'unknown';

	/**
	 * Meta data: Long title
	 * @var string
	 */
	var $title;

	/**
	 * Meta data: ALT text for images
	 * @var string
	 */
	var $alt;

	/**
	 * Meta data: Description
	 * @var string
	 */
	var $desc;


	/**
	 * Full path for this file/folder, including trailing slash.
	 * @var string
	 * @see get_full_path()
	 * @access protected
	 */
	var $_full_path;

	/**
	 * Full path for this file/folder, WITHOUT trailing slash.
	 * @var string
	 * @access protected
	 */
	var $_posix_path;

	/**
	 * Directory path for this file/folder, including trailing slash.
	 * @var string
	 * @see get_dir()
	 * @access protected
	 */
	var $_dir;

	/**
	 * Name of this file/folder, without path.
	 * @var string
	 * @see get_name()
	 * @access protected
	 */
	var $_name;

	/**
	 * MD5 hash of full pathname.
	 *
	 * @todo fplanque>> the purpose of this thing isn't very clear... get rid of it?
	 *
	 * @var string
	 * @see get_md5_ID()
	 * @access protected
	 */
	var $_md5ID;

	/**
	 * does the File/folder exist on disk?
	 * @var boolean
	 * @see exists()
	 * @access protected
	 */
	var $_exists;

	/**
	 * Is the File a directory?
	 * @var boolean
	 * @see is_dir()
	 * @access protected
	 */
	var $_is_dir;

	/**
	 * file size in bytes.
	 * @var integer
	 * @see get_size()
	 * @access protected
	 */
	var $_size;

	/**
	 * UNIX timestamp of last modification on disk.
	 * @var integer
	 * @see get_lastmod_ts()
	 * @see get_lastmod_formatted()
	 * @access protected
	 */
	var $_lastmod_ts;

	/**
	 * UNIX file permissions.
	 * @var integer
	 * @see get_perms()
	 * @access protected
	 */
	var $_perms;

	/**
	 * Is the File an image?
	 * @var NULL|boolean
	 * @see is_image()
	 * @access protected
	 */
	var $_is_image;

	/**
	 * caches the icon key for this file (based on its extension)
	 * @var string
	 * @access protected
	 */
	var $_icon_key;

	/**
	 * Constructor, not meant to be called directly. Use {@link FileCache::get_by_path()}
	 * instead, which provides caching and checks that only one object for
	 * a unique file exists (references).
	 *
	 * @param string path to the file / directory (with trailing slash if directory).
	 * @param boolean check for meta data?
	 * @return mixed false on failure, File object on success
	 */
	function File( $path, $load_meta = false )
	{
		// Call parent constructor
		parent::DataObject( 'T_files', 'file_', 'file_ID', '', '', '', '' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_links', 'fk'=>'link_file_ID', 'msg'=>T_('%d linked items') ),
			);

		// Memorize filepath:
		$this->_full_path = str_replace( '\\', '/', $path );
		$this->_posix_path = no_trailing_slash( $this->_full_path );
		$this->_name = basename( $this->_posix_path );
		$this->_dir = dirname( $this->_posix_path ).'/';
		$this->_md5ID = md5( $this->_posix_path );

		// Initializes file properties (type, size, perms...)
		$this->load_properties();

		if( $load_meta )
		{ // Try to load DB meta info:
			$this->load_meta();
		}
	}


	/**
	 * Attempt to load meta data.
	 *
	 * Will attempt only once and cache the result.
	 *
	 * @param boolean create meta data in DB if it doesn't exist yet? (generates a $File->ID)
	 * @param object database row containing all fields needed to initialize meta data
	 * @return boolean true if meta data has been loaded/initialized.
	 */
	function load_meta( $force_creation = false, $row = NULL )
	{
		global $DB, $Debuglog, $FileCache;

		if( $this->meta == 'unknown' )
		{ // We haven't tried loading yet:
			if( is_null( $row )	)
			{	// No DB data has been provided:
				$row = $DB->get_row( 'SELECT * FROM T_files
																WHERE file_root_type = \'absolute\'
																	AND file_root_ID = 0
																	AND file_path = '.$DB->quote($this->_full_path),
																OBJECT, 0, 'Load file meta data' );
			}

			if( $row )
			{ // We found meta data
				$Debuglog->add('Loaded metadata for '.$this->_full_path);
				$this->meta  = 'loaded';
				$this->ID    = $row->file_ID;
				$this->title = $row->file_title;
				$this->alt   = $row->file_alt;
				$this->desc  = $row->file_desc;

				// Store this in the FileCache:
				$FileCache->add( $this );
			}
			else
			{ // No meta data...
				$this->meta = 'notfound';

				if( $force_creation )
				{	// No meta data, we have to create it now!
					$this->dbinsert();
				}
			}
		}

		return ($this->meta == 'loaded');
	}


	/**
	 * Create the file/folder on disk, if it does not exist yet.
	 *
	 * Also sets file permissions.
	 * Also inserts meta data into DB (if file/folder was successfully created).
	 *
	 * @param string type ('dir'|'file')
	 * @param string optional permissions (octal format)
	 * @return boolean true if file/folder was created, false on failure
	 */
	function create( $type = 'file', $chmod = NULL )
	{
		if( $type == 'dir' )
		{ // Create an empty directory:
			if( $chmod === NULL )
			{ // Create dir with default permissions (777)
				$success = @mkdir( $this->_posix_path );
			}
			else
			{ // Create directory with specific permissions:
				$success = @mkdir( $this->_posix_path, octdec($chmod) );
			}
		}
		else
		{ // Create an empty file:
			$success = touch( $this->_posix_path );
			if( $chmod !== NULL )
			{
				$this->chmod( $chmod );
			}
		}

		if( $success )
		{	// The file/folder has been successfully created:

			// Initializes file properties (type, size, perms...)
			$this->load_properties();

			// If there was meta data for this file in the DB:
			// (maybe the file had existed before?)
			// Let's recycle it! :
			$this->load_meta();
			// TODO: make path relative to a root.
			$this->set( 'path', $this->_full_path );
			// Record to DB:
			$this->dbsave();
		}

		return $success;
	}


	/**
	 * Initializes or refreshes file properties (type, size, perms...)
	 */
	function load_properties()
	{
		// Unset values that will be determined (and cached) upon request
		$this->_is_image = NULL;

		$this->_exists = file_exists( $this->_posix_path );

		if( is_dir( $this->_posix_path ) )
		{	// The File is a directory:
			$this->_is_dir = true;
			$this->_size = NULL;
		}
		else
		{	// The File is a regular file:
			$this->_is_dir = false;
			$this->_size = @filesize( $this->_posix_path );
		}

		// for files and dirs:
		$this->_lastmod_ts = @filemtime( $this->_posix_path );
		$this->_perms = @fileperms( $this->_posix_path );
	}


	/**
	 * Does the File/folder exist on disk?
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
	function is_dir()
	{
		return $this->_is_dir;
	}


	/**
	 * Is the File an image?
	 *
	 * Tries to determine if it is and caches the info.
	 *
	 * @return boolean true if the object is an image, false if not
	 */
	function is_image()
	{
		if( is_null( $this->_is_image ) )
		{	// We don't know yet
			$this->_is_image = ( $this->get_image_size() !== false );
		}

		return $this->_is_image;
	}


	/**
	 * Get the File's ID (MD5 of path and name)
	 *
	 * @return string
	 */
	function get_md5_ID()
	{
		return $this->_md5ID;
	}


	/**
	 * Get the File's name.
	 *
	 * @return string
	 */
	function get_name()
	{
		return $this->_name;
	}


	/**
	 * Get the name prefixed either with "Directory" or "File".
	 *
	 * Returned string is localized.
	 *
	 * @return string
	 */
	function get_prefixed_name()
	{
		if( $this->is_dir() )
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
	function get_dir()
	{
		return $this->_dir;
	}


	/**
	 * Get the full path (directory and name) to the file.
	 *
	 * If the File is a directory, the Path ends with a /
	 *
	 * @return string full path
	 */
	function get_full_path()
	{
		return $this->_full_path;
	}


	/**
	 * Get the file's extension.
	 *
	 * @return string the extension
	 */
	function get_ext()
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
	 * Get the file type as a descriptive localized string.
	 *
	 * @uses $fm_filetypes
	 * @return string localized type name or 'Directory' or 'Unknown'
	 */
	function get_type()
	{
		global $fm_filetypes;

		if( isset( $this->_type ) )
		{ // The type is already cached for this object:
			return $this->_type;
		}

		if( $this->is_dir() )
		{
			$this->_type = T_('Directory');
			return $this->_type;
		}

		foreach( $fm_filetypes as $type => $desc )
		{
			if( preg_match('/'.$type.'$/i', $this->_name) )
			{
				$this->_type = T_($desc);	// Localized type desc
				return $this->_type;
			}
		}

		$this->_type = T_('Unknown');
		return $this->_type;
	}


	/**
	 * Get file size in bytes.
	 *
	 * @return integer bytes
	 */
	function get_size()
	{
		return $this->_size;
	}


	/**
	 * Get timestamp of last modification.
	 *
	 * @return integer Timestamp
	 */
	function get_lastmod_ts()
	{
		return $this->_lastmod_ts;
	}

	/**
	 * Get date/time of last modification, formatted.
	 *
	 $ @param string date format or 'date' or 'time' for default locales.
	 * @return string locale formatted date/time
	 */
	function get_lastmod_formatted( $format = '#' )
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

		return date_i18n( $format, $this->_lastmod_ts );
	}


	/**
	 * Get permissions
	 *
	 * Possible return formats are:
	 *   - 'raw'=integer
	 *   - 'lsl'=string like 'ls -l'
	 *   - 'octal'=3 digits
	 *
	 * Default value:
	 *   - 'r'/'r+w' for windows
	 *   - 'octal' for other OS
	 *
	 * @param string type, see desc above.
	 * @return mixed permissions
	 */
	function get_perms( $type = NULL )
	{
		switch( $type )
		{
			case 'raw':
				return $this->_perms;

			case 'lsl':
				$sP = '';

				if(($this->_perms & 0xC000) == 0xC000)     // Socket
					$sP = 's';
				elseif(($this->_perms & 0xA000) == 0xA000) // Symbolic Link
					$sP = 'l';
				elseif(($this->_perms & 0x8000) == 0x8000) // Regular
					$sP = '&minus;';
				elseif(($this->_perms & 0x6000) == 0x6000) // Block special
					$sP = 'b';
				elseif(($this->_perms & 0x4000) == 0x4000) // Directory
					$sP = 'd';
				elseif(($this->_perms & 0x2000) == 0x2000) // Character special
					$sP = 'c';
				elseif(($this->_perms & 0x1000) == 0x1000) // FIFO pipe
					$sP = 'p';
				else                                   // UNKNOWN
					$sP = 'u';

				// owner
				$sP .= (($this->_perms & 0x0100) ? 'r' : '&minus;') .
								(($this->_perms & 0x0080) ? 'w' : '&minus;') .
								(($this->_perms & 0x0040) ? (($this->_perms & 0x0800) ? 's' : 'x' ) :
																				(($this->_perms & 0x0800) ? 'S' : '&minus;'));

				// group
				$sP .= (($this->_perms & 0x0020) ? 'r' : '&minus;') .
								(($this->_perms & 0x0010) ? 'w' : '&minus;') .
								(($this->_perms & 0x0008) ? (($this->_perms & 0x0400) ? 's' : 'x' ) :
																				(($this->_perms & 0x0400) ? 'S' : '&minus;'));

				// world
				$sP .= (($this->_perms & 0x0004) ? 'r' : '&minus;') .
								(($this->_perms & 0x0002) ? 'w' : '&minus;') .
								(($this->_perms & 0x0001) ? (($this->_perms & 0x0200) ? 't' : 'x' ) :
																				(($this->_perms & 0x0200) ? 'T' : '&minus;'));
				return $sP;

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
	 * Get icon for this file.
	 *
	 * Looks at the file's extension.
	 *
	 * @uses $map_iconfiles
	 * @return string img tag
	 */
	function get_icon()
	{
		global $map_iconfiles;

		if( !isset($this->_icon_key) )
		{	// We haven't cached the icon key before...
			if( $this->is_dir() )
			{ // Directory icon:
				$this->_icon_key = 'folder';
			}
			else
			{
				$this->_icon_key = 'file_unknown';

				// Loop through known file icons:
				foreach( $map_iconfiles as $lKey => $lIconfile )
				{
					if( isset( $lIconfile['ext'] )
							&& preg_match( '/'.$lIconfile['ext'].'$/i', $this->_name, $match ) )
					{
						$this->_icon_key = $lKey;
						break;
					}
				}
			}
		}

		// Return Icon for the determined key:
		return get_icon( $this->_icon_key, 'imgtag', array( 'alt'=>$this->get_ext(), 'title'=>$this->get_type() ) );
	}


	/**
	 * Get size of an image or false if not an image
	 *
	 * @todo cache this data (NOTE: we have different params here! - imgsize() does caching already!)
	 *
	 * @uses imgsize()
	 * @param string {@link imgsize()}
	 * @return false|mixed false if the File is not an image, the requested data otherwise
	 */
	function get_image_size( $param = 'widthxheight' )
	{
		return imgsize( $this->_full_path, $param );
	}


	/**
	 * Get size of the file, formatted to nearest unit (kb, mb, etc.)
	 *
	 * @uses bytesreadable()
	 * @return string size as b/kb/mb/gd; or '&lt;dir&gt;'
	 */
	function get_size_formatted()
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
	 * Internally sets the file/directory size
	 *
	 * This is used when the FileList wants to set the recursive size of a directory!
	 *
	 * @todo pass a param to the constructor telling it we want to store a recursive size for the direcrory.
	 * @todo store the recursive size separately (in another member), to avoid confusion
	 *
	 * @access public
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
		if( rename( $this->_full_path, $this->_dir.$newname ) )
		{
			$this->_name = $newname;
			$this->_posix_path = $this->_dir.$this->_name;
			$this->_full_path = $this->_posix_path.'/';
			$this->_md5ID = md5( $this->_posix_path );

			// Meta data...:
			// TODO: make path relative to a root.
			$this->set( 'path', $this->_full_path );
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
		global $DB;

		$DB->begin();

		// Check if there is meta data to be removed:
		if( $this->load_meta() )
		{ // remove meta data from DB:
			$this->dbdelete();
		}

		if( $this->is_dir() )
		{
			$unlinked =	@rmdir( $this->_posix_path );
		}
		else
		{
			$unlinked =	@unlink( $this->_posix_path );
		}

		if( !$unlinked )
		{
			$DB->rollback();

			return false;
		}

		$this->_exists = false;

		$DB->commit();

		return true;
	}


	/**
	 * Change file permissions on disk.
	 *
	 * @access public
	 * @param string chmod (octal three-digit-format, eg '777')
	 * @return mixed new permissions on success (octal format), false on failure
	 */
	function chmod( $chmod )
	{
		$chmod = octdec( $chmod );
		if( chmod( $this->_posix_path, $chmod ) )
		{
			clearstatcache();
			// update current entry
			$this->_perms = fileperms( $this->_posix_path );

			return $this->_perms;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Insert object into DB based on previously recorded changes
	 */
	function dbinsert( )
	{
		if( $this->meta == 'unknown' )
			die( 'cannot insert File if meta data has not been checked before' );

		if( ($this->ID != 0) || ($this->meta != 'notfound') ) die( 'Existing file object cannot be inserted!' );

		// We need to track filepath:
		$this->set_param( 'path', 'string', $this->_full_path );

		// Let parent do the insert:
		parent::dbinsert();

		// We can now consider the meta data has been loaded:
		$this->meta  = 'loaded';
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * {@internal DataObject::dbupdate(-)}}
	 */
	function dbupdate( )
	{
		if( $this->meta == 'unknown' )
			die( 'cannot update File if meta data has not been checked before' );

		// Let parent do the update:
		parent::dbupdate();
	}
}


/*
 * $Log$
 * Revision 1.31  2005/04/29 18:49:32  fplanque
 * Normalizing, doc, cleanup
 *
 * Revision 1.30  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.29  2005/04/27 19:05:46  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.27  2005/04/19 18:04:38  fplanque
 * implemented nested transactions for MySQL
 *
 * Revision 1.26  2005/04/19 16:23:02  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.25  2005/04/15 18:02:59  fplanque
 * finished implementation of properties/meta data editor
 * started implementation of files to items linking
 *
 * Revision 1.24  2005/04/13 17:48:22  fplanque
 * File manager refactoring
 * storing of file meta data through upload
 * displaying or metadate in previews
 *
 * Revision 1.23  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.22  2005/02/21 00:34:34  blueyed
 * check for defined DB_USER!
 *
 * Revision 1.21  2005/02/18 19:16:15  fplanque
 * started relation restriction/cascading handling
 *
 * Revision 1.20  2005/01/27 13:34:58  fplanque
 * i18n tuning
 *
 * Revision 1.18  2005/01/21 20:47:46  blueyed
 * doc, getLastMod() extended
 *
 * Revision 1.16  2005/01/16 18:32:27  blueyed
 * doc, whitespace
 *
 * Revision 1.15  2005/01/15 20:20:51  blueyed
 * $map_iconsizes merged with $map_iconfiles, removed obsolete getIconSize() (functionality moved to get_icon())
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