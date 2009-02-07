<?php
/**
 * This file implements the File class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


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
	 * FileRoot of this file
	 * @var Fileroot
	 * @access protected
	 */
	var $_FileRoot;

	/**
	 * Posix subpath for this file/folder, relative the associated root (No trailing slash)
	 * @var string
	 * @access protected
	 */
	var $_rdfp_rel_path;

	/**
	 * Full path for this file/folder, WITHOUT trailing slash.
	 * @var string
	 * @access protected
	 */
	var $_adfp_full_path;

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
	 * @access protected
	 */
	var $_name;

	/**
	 * MD5 hash of full pathname.
	 *
	 * This is useful to refer to files in hidden form fields, but might be replaced by the root_ID+relpath.
	 *
	 * @todo fplanque>> get rid of it
	 *
	 * @var string
	 * @see get_md5_ID()
	 * @access protected
	 */
	var $_md5ID;

	/**
	 * Does the File/folder exist on disk?
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
	 * File size in bytes.
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
	 * Filesystem file permissions.
	 * @var integer
	 * @see get_perms()
	 * @access protected
	 */
	var $_perms;

	/**
	 * File owner. NULL if unknown
	 * @var string
	 * @see get_fsowner_name()
	 * @access protected
	 */
	var $_fsowner_name;

	/**
	 * File group. NULL if unknown
	 * @var string
	 * @see get_fsgroup_name()
	 * @access protected
	 */
	var $_fsgroup_name;

	/**
	 * Is the File an image? NULL if unknown
	 * @var boolean
	 * @see is_image()
	 * @access protected
	 */
	var $_is_image;

	/**
	 * Extension, Mime type, icon, viewtype and 'allowed extension' of the file
	 * @var Filetype
	 */
	var $Filetype;


	/**
	 * Constructor, not meant to be called directly. Use {@link FileCache::get_by_path()}
	 * instead, which provides caching and checks that only one object for
	 * a unique file exists (references).
	 *
	 * @param string Root type: 'user', 'group', 'collection' or 'absolute'
	 * @param integer ID of the user, the group or the collection the file belongs to...
	 * @param string Posix subpath for this file/folder, relative to the associated root (no trailing slash)
	 * @param boolean check for meta data?
	 * @return mixed false on failure, File object on success
	 */
	function File( $root_type, $root_ID, $rdfp_rel_path, $load_meta = false )
	{
		global $Debuglog;

		$Debuglog->add( "new File( $root_type, $root_ID, $rdfp_rel_path, load_meta=$load_meta)", 'files' );

		// Call parent constructor
		parent::DataObject( 'T_files', 'file_', 'file_ID', '', '', '', '' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_links', 'fk'=>'link_file_ID', 'msg'=>T_('%d linked items') ),
			);

		// Memorize filepath:
		$FileRootCache = & get_Cache( 'FileRootCache' );
		$this->_FileRoot = & $FileRootCache->get_by_type_and_ID( $root_type, $root_ID );
		$this->_rdfp_rel_path = no_trailing_slash(str_replace( '\\', '/', $rdfp_rel_path ));
		$this->_adfp_full_path = $this->_FileRoot->ads_path.$this->_rdfp_rel_path;
		$this->_name = basename( $this->_adfp_full_path );
		$this->_dir = dirname( $this->_adfp_full_path ).'/';
		$this->_md5ID = md5( $this->_adfp_full_path );

		// Create the filetype with the extension of the file if the extension exist in database
		if( $ext = $this->get_ext() )
		{ // The file has an extension, load filetype object
			$FiletypeCache = & get_Cache( 'FiletypeCache' );
			$this->Filetype = & $FiletypeCache->get_by_extension( strtolower( $ext ), false );
		}

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
		global $DB, $Debuglog;

		if( $this->meta == 'unknown' )
		{ // We haven't tried loading yet:
			if( is_null( $row )	)
			{	// No DB data has been provided:
				$row = $DB->get_row( "
					SELECT * FROM T_files
					 WHERE file_root_type = '".$this->_FileRoot->type."'
					   AND file_root_ID = ".$this->_FileRoot->in_type_ID."
					   AND file_path = ".$DB->quote($this->_rdfp_rel_path),
					OBJECT, 0, 'Load file meta data' );
			}

			// We check that we got something AND that the CASE matches (because of case insensitive collations on MySQL)
			if( $row && $row->file_path == $this->_rdfp_rel_path )
			{ // We found meta data
				$Debuglog->add( "Loaded metadata for {$this->_FileRoot->ID}:{$this->_rdfp_rel_path}", 'files' );
				$this->meta  = 'loaded';
				$this->ID    = $row->file_ID;
				$this->title = $row->file_title;
				$this->alt   = $row->file_alt;
				$this->desc  = $row->file_desc;

				// Store this in the FileCache:
				$FileCache = & get_Cache( 'FileCache' );
				$FileCache->add( $this );
			}
			else
			{ // No meta data...
				$Debuglog->add( "No metadata could be loaded for {$this->_FileRoot->ID}:$this->_rdfp_rel_path", 'files' );
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
	 * @param string optional permissions (octal format), otherwise the default from {@link $Settings} gets used
	 * @return boolean true if file/folder was created, false on failure
	 */
	function create( $type = 'file', $chmod = NULL )
	{
		if( $type == 'dir' )
		{ // Create an empty directory:
			$success = @mkdir( $this->_adfp_full_path );
			$this->_is_dir = true; // used by chmod
		}
		else
		{ // Create an empty file:
			$success = touch( $this->_adfp_full_path );
			$this->_is_dir = false; // used by chmod
		}
		$this->chmod( $chmod ); // uses $Settings for NULL

		if( $success )
		{	// The file/folder has been successfully created:

			// Initializes file properties (type, size, perms...)
			$this->load_properties();

			// If there was meta data for this file in the DB:
			// (maybe the file had existed before?)
			// Let's recycle it! :
			if( ! $this->load_meta() )
			{ // No meta data could be loaded, let's make sure localization info gets recorded:
				$this->set( 'root_type', $this->_FileRoot->type );
				$this->set( 'root_ID', $this->_FileRoot->in_type_ID );
				$this->set( 'path', $this->_rdfp_rel_path );
			}

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

		$this->_exists = file_exists( $this->_adfp_full_path );

		if( is_dir( $this->_adfp_full_path ) )
		{	// The File is a directory:
			$this->_is_dir = true;
			$this->_size = NULL;
		}
		else
		{	// The File is a regular file:
			$this->_is_dir = false;
			$this->_size = @filesize( $this->_adfp_full_path );
		}

		// for files and dirs:
		$this->_lastmod_ts = @filemtime( $this->_adfp_full_path );
		$this->_perms = @fileperms( $this->_adfp_full_path );
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
	 * Is the file editable?
	 *
	 * @param boolean allow locked file types?
	 */
	function is_editable( $allow_locked = false )
	{
		if( $this->is_dir()	// we cannot edit dirs
				|| empty($this->Filetype)
				|| $this->Filetype->viewtype != 'text' )	// we can only edit text files
		{
			return false;
		}

		if( ! $this->Filetype->allowed && ! $allow_locked )
		{	// We cannot edit locked file types:
			return false;
		}

		return true;
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
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'name':
				return $this->_name;

			default:
				return parent::get( $parname );
		}
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
	 * Get the file posix path relative to it's root (no trailing /)
	 *
	 * @return string full path
	 */
	function get_rdfp_rel_path()
	{
		return $this->_rdfp_rel_path;
	}


	/**
	 * Get the file path relative to it's root, WITH trailing slash.
	 *
	 * @return string full path
	 */
	function get_rdfs_rel_path()
	{
		return $this->_rdfp_rel_path.( $this->_is_dir ? '/' : '' );
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
		return $this->_adfp_full_path.( $this->_is_dir ? '/' : '' );
	}


	/**
	 * Get the absolute file url if the file is public
	 * Get the getfile.php url if we need to check permission before delivering the file
	 */
	function get_url()
	{
		global $public_access_to_media, $htsrv_url;

		if( $this->is_dir() )
		{ // Directory
			if( $public_access_to_media )
			{ // Public access: full path
				$url = $this->_FileRoot->ads_url.$this->get_rdfs_rel_path();
			}
			else
			{ // No Access
				debug_die( 'Private directory! ');
			}
		}
		else
		{ // File
			if( $public_access_to_media )
			{ // Public Access : full path
				$url = $this->_FileRoot->ads_url.$this->_rdfp_rel_path;
			}
			else
			{ // Private Access: doesn't show the full path
				$root = $this->_FileRoot->ID;
				$url = $htsrv_url.'getfile.php/'
								// This is for clean 'save as':
								.rawurlencode( $this->_name )
								// This is for locating the file:
								.'?root='.$root.'&amp;path='.$this->_rdfp_rel_path;
			}
		}
		return $url;
	}


	/**
	 * Get location of file with its root (for display)
	 */
	function get_root_and_rel_path()
	{
		return $this->_FileRoot->name.':'.$this->get_rdfs_rel_path();
	}


	/**
	 * Get the File's FileRoot.
	 *
	 * @return FileRoot
	 */
	function & get_FileRoot()
	{
		return $this->_FileRoot;
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
	 * @return string localized type name or 'Directory' or 'Unknown'
	 */
	function get_type()
	{
		if( isset( $this->_type ) )
		{ // The type is already cached for this object:
			return $this->_type;
		}

		if( $this->is_dir() )
		{
			$this->_type = T_('Directory');
			return $this->_type;
		}

		if( isset( $this->Filetype->mimetype ) )
		{
			$this->_type = $this->Filetype->name;
			return $this->_type;
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
		global $localtimenow;

		switch( $format )
		{
			case 'date':
				return date_i18n( locale_datefmt(), $this->_lastmod_ts );

			case 'time':
				return date_i18n( locale_timefmt(), $this->_lastmod_ts );

			case 'compact':
				$age = $localtimenow - $this->_lastmod_ts;
				if( $age < 3600 )
				{	// Less than 1 hour: return full time
					return date_i18n( 'H:i:s', $this->_lastmod_ts );
				}
				if( $age < 86400 )
				{	// Less than 24 hours: return compact time
					return date_i18n( 'H:i', $this->_lastmod_ts );
				}
				if( $age < 31536000 )
				{	// Less than 365 days: Month and day
					return date_i18n( 'M, d', $this->_lastmod_ts );
				}
				// Older: return yeat
				return date_i18n( 'Y', $this->_lastmod_ts );
				break;

			case '#':
				default:
				$format = locale_datefmt().' '.locale_timefmt();
				return date_i18n( $format, $this->_lastmod_ts );
		}
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
				       (($this->_perms & 0x0040) ? (($this->_perms & 0x0800) ? 's' : 'x' )
				                                 : (($this->_perms & 0x0800) ? 'S' : '&minus;'));

				// group
				$sP .= (($this->_perms & 0x0020) ? 'r' : '&minus;') .
				       (($this->_perms & 0x0010) ? 'w' : '&minus;') .
				       (($this->_perms & 0x0008) ? (($this->_perms & 0x0400) ? 's' : 'x' )
				                                 : (($this->_perms & 0x0400) ? 'S' : '&minus;'));

				// world
				$sP .= (($this->_perms & 0x0004) ? 'r' : '&minus;') .
				       (($this->_perms & 0x0002) ? 'w' : '&minus;') .
				       (($this->_perms & 0x0001) ? (($this->_perms & 0x0200) ? 't' : 'x' )
				                                 : (($this->_perms & 0x0200) ? 'T' : '&minus;'));
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
	 * Get the owner name of the file.
	 *
	 * @todo Can this be fixed for windows? filegroup() might only return 0 or 1 nad posix_getgrgid() is not available..
	 * @return NULL|string
	 */
	function get_fsgroup_name()
	{
		if( ! isset( $this->_fsgroup_name ) )
		{
			$gid = @filegroup( $this->_adfp_full_path ); // might spit a warning for a dangling symlink

			if( $gid !== false
					&& function_exists( 'posix_getgrgid' ) ) // func does not exist on windows
			{
				$posix_group = posix_getgrgid( $gid );
				if( is_array($posix_group) )
				{
					$this->_fsgroup_name = $posix_group['name'];
				}
				else
				{ // fallback to gid:
					$this->_fsgroup_name = $gid;
				}
			}
		}

		return $this->_fsgroup_name;
	}


	/**
	 * Get the owner name of the file.
	 *
	 * @todo Can this be fixed for windows? fileowner() might only return 0 or 1 nad posix_getpwuid() is not available..
	 * @return NULL|string
	 */
	function get_fsowner_name()
	{
		if( ! isset( $this->_fsowner_name ) )
		{
			$uid = @fileowner( $this->_adfp_full_path ); // might spit a warning for a dangling symlink
			if( $uid !== false
					&& function_exists( 'posix_getpwuid' ) ) // func does not exist on windows
			{
				$posix_user = posix_getpwuid( $uid );
				if( is_array($posix_user) )
				{
					$this->_fsowner_name = $posix_user['name'];
				}
				else
				{ // fallback to uid:
					$this->_fsowner_name = $uid;
				}
			}
		}

		return $this->_fsowner_name;
	}


	/**
	 * Get icon for this file.
	 *
	 * Looks at the file's extension.
	 *
	 * @uses get_icon()
	 * @return string img tag
	 */
	function get_icon()
	{
		if( $this->is_dir() )
		{ // Directory icon:
			$icon = 'folder';
		}
		elseif( isset( $this->Filetype->icon ) && $this->Filetype->icon )
		{ // Return icon for known type of the file
				return $this->Filetype->get_icon();
		}
		else
		{ // Icon for unknown file type:
			$icon = 'file_unknown';
		}
		// Return Icon for a directory or unknown type file:
		return get_icon( $icon, 'imgtag', array( 'alt'=>$this->get_ext(), 'title'=>$this->get_type() ) );
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
		return imgsize( $this->_adfp_full_path, $param );
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
	 * Get a complete tag (IMG or A HREF) pointing to this file.
	 *
	 * @param string
	 * @param string NULL for no legend
	 * @param string
	 * @param string
	 * @param string
	 */
	function get_tag( $before_image = '<div class="image_block">',
	                  $before_image_legend = '<div class="image_legend">',
	                  $after_image_legend = '</div>',
	                  $after_image = '</div>',
	                  $size_name = 'original',
	                  $image_link_to = 'original' )
	{
		if( $this->is_dir() )
		{	// We can't reference a directory
			return '';
		}

		$this->load_meta();

		if( $this->is_image() )
		{ // Make an IMG link:
			$r = $before_image;
			if( $size_name == 'original' )
			{
					$img = '<img src="'.$this->get_url().'" '
								.'alt="'.$this->dget('alt', 'htmlattr').'" '
								.'title="'.$this->dget('title', 'htmlattr').'" '
								.$this->get_image_size( 'string' ).' />';
			}
			else
			{
					$img = '<img src="'.$this->get_thumb_url( $size_name ).'" '
								.'alt="'.$this->dget('alt', 'htmlattr').'" '
								.'title="'.$this->dget('title', 'htmlattr').'" />';
					// TODO: size
			}

			if( $image_link_to == 'original' )
			{
				$img = '<a href="'.$this->get_url().'">'.$img.'</a>';
			}

			$r .= $img;

			$desc = $this->dget('desc');
			if( !empty($desc) and !is_null($before_image_legend) )
			{
				$r .= $before_image_legend
								.$this->dget('desc')
							.$after_image_legend;
			}
			$r .= $after_image;
		}
		else
		{	// Make an A HREF link:
			$r = '<a href="'.$this->get_url()
						.'" title="'.$this->dget('desc', 'htmlattr').'">'
						.$this->dget('title').'</a>';
		}

		return $r;
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
	 * Rename the file in its current directory on disk.
	 *
	 * Also update meta data in DB.
	 *
	 * @access public
	 * @param string new name (without path!)
	 * @return boolean true on success, false on failure
	 */
	function rename_to( $newname )
	{
		// echo "newname= $newname ";

		// rename() will fail if newname already exists on windows
		// if it doesn't work that way on linux we need the extra check below
		// but then we have an integrity issue!! :(
		if( file_exists($this->_dir.$newname) )
		{
			return false;
		}

		// Note: what happens if someone else creates $newname right at this moment here?

		if( ! @rename( $this->_adfp_full_path, $this->_dir.$newname ) )
		{ // Rename will fail if $newname already exists (at least on windows)
			return false;
		}

		// Delete thumb caches for old name:
		// Note: new name = new usage : there is a fair chance we won't need the same cache sizes in the new loc.
		$this->rm_cache();

		// Get Meta data (before we change name) (we may need to update it later):
		$this->load_meta();

		$this->_name = $newname;

		$rel_dir = dirname( $this->_rdfp_rel_path ).'/';
		if( $rel_dir == './' )
		{
			$rel_dir = '';
		}
		$this->_rdfp_rel_path = $rel_dir.$this->_name;

		$this->_adfp_full_path = $this->_dir.$this->_name;
		$this->_md5ID = md5( $this->_adfp_full_path );

		if( $this->meta == 'loaded' )
		{	// We have meta data, we need to deal with it:
			// unchanged : $this->set( 'root_type', $this->_FileRoot->type );
			// unchanged : $this->set( 'root_ID', $this->_FileRoot->in_type_ID );
			$this->set( 'path', $this->_rdfp_rel_path );
			// Record to DB:
			$this->dbupdate();
		}
		else
		{	// There might be some old meta data to *recycle* in the DB...
			// This can happen if there has been a file in the same location in the past and if that file
			// has been manually deleted or moved since then. When the new file arrives here, we'll recover
			// the zombie meta data and we don't reset it on purpose. Actually, we consider that the meta data
			// has been *accidentaly* lost and that the user is attempting to recover it by putting back the
			// file where it was before. Of course the logical way would be to put back the file manually, but
			// experience proves that users are inconsistent!
			$this->load_meta();
		}

		return true;
	}


	/**
	 * Move the file to another location
	 *
	 * Also updates meta data in DB
	 *
	 * @param string Root type: 'user', 'group', 'collection' or 'absolute'
	 * @param integer ID of the user, the group or the collection the file belongs to...
	 * @param string Subpath for this file/folder, relative the associated root (no trailing slash)
	 * @return boolean true on success, false on failure
	 */
	function move_to( $root_type, $root_ID, $rdfp_rel_path )
	{
		// echo "relpath= $rel_path ";

		$rdfp_rel_path = str_replace( '\\', '/', $rdfp_rel_path );
		$FileRootCache = & get_Cache( 'FileRootCache' );

		$new_FileRoot = & $FileRootCache->get_by_type_and_ID( $root_type, $root_ID, true );
		$adfp_posix_path = $new_FileRoot->ads_path.$rdfp_rel_path;

		if( ! @rename( $this->_adfp_full_path, $adfp_posix_path ) )
		{
			return false;
		}

		// Delete thumb caches from old location:
		// Note: new location = new usage : there is a fair chance we won't need the same cache sizes in the new loc.
		$this->rm_cache();

		// Get Meta data (before we change name) (we may need to update it later):
		$this->load_meta();

		// Memorize new filepath:
		$this->_FileRoot = & $new_FileRoot;
		$this->_rdfp_rel_path = $rdfp_rel_path;
		$this->_adfp_full_path = $adfp_posix_path;
		$this->_name = basename( $this->_adfp_full_path );
		$this->_dir = dirname( $this->_adfp_full_path ).'/';
		$this->_md5ID = md5( $this->_adfp_full_path );

		if( $this->meta == 'loaded' )
		{	// We have meta data, we need to deal with it:
			$this->set( 'root_type', $this->_FileRoot->type );
			$this->set( 'root_ID', $this->_FileRoot->in_type_ID );
			$this->set( 'path', $this->_rdfp_rel_path );
			// Record to DB:
			$this->dbupdate();
		}
		else
		{	// There might be some old meta data to *recycle* in the DB...
			// This can happen if there has been a file in the same location in the past and if that file
			// has been manually deleted or moved since then. When the new file arrives here, we'll recover
			// the zombie meta data and we don't reset it on purpose. Actually, we consider that the meta data
			// has been *accidentaly* lost and that the user is attempting to recover it by putting back the
			// file where it was before. Of course the logical way would be to put back the file manually, but
			// experience proves that users are inconsistent!
			$this->load_meta();
		}

		return true;
	}


 	/**
	 * Copy this file to a new location
	 *
	 * Also copy meta data in Object
	 *
	 * @param File the target file (expected to not exist)
	 * @return boolean true on success, false on failure
	 */
	function copy_to( & $dest_File )
	{
		if( ! $this->exists() || $dest_File->exists() )
		{
			return false;
		}

 		// Note: what happens if someone else creates the destination file right at this moment here?

		if( ! @copy( $this->get_full_path(), $dest_File->get_full_path() ) )
		{	// Note: unlike rename() (at least on Windows), copy() will not fail if destination already exists
			// this is probably a permission problem
			return false;
		}

		// Initializes file properties (type, size, perms...)
		$dest_File->load_properties();

		// Meta data...:
		if( $this->load_meta() )
		{	// We have source meta data, we need to copy it:
			// Try to load DB meta info for destination file:
			$dest_File->load_meta();

			// Copy meta data:
			$dest_File->set( 'title', $this->title );
			$dest_File->set( 'alt'  , $this->alt );
			$dest_File->set( 'desc' , $this->desc );

			// Save meta data:
			$dest_File->dbsave();
		}

		return true;
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

		//Remove thumb cache:
		$this->rm_cache();

		// Physically remove file from disk:
		if( $this->is_dir() )
		{
			$unlinked =	@rmdir( $this->_adfp_full_path );
		}
		else
		{
			$unlinked =	@unlink( $this->_adfp_full_path );
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
	 * @param string chmod (octal three-digit-format, eg '777'), uses {@link $Settings} for NULL
	 *                    (fm_default_chmod_dir, fm_default_chmod_file)
	 * @return mixed new permissions on success (octal format), false on failure
	 */
	function chmod( $chmod = NULL )
	{
		if( $chmod === NULL )
		{
			global $Settings;

			$chmod = $this->is_dir()
				? $Settings->get( 'fm_default_chmod_dir' )
				: $Settings->get( 'fm_default_chmod_file' );
		}

		if( @chmod( $this->_adfp_full_path, octdec( $chmod ) ) )
		{
			clearstatcache();
			// update current entry
			$this->_perms = fileperms( $this->_adfp_full_path );

			return $this->_perms;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Insert object into DB based on previously recorded changes
	 *
	 * @return boolean true on success, false on failure
	 */
	function dbinsert( )
	{
		global $Debuglog;

		if( $this->meta == 'unknown' )
		{
			debug_die( 'cannot insert File if meta data has not been checked before' );
		}

		if( ($this->ID != 0) || ($this->meta != 'notfound') )
		{
			debug_die( 'Existing file object cannot be inserted!' );
		}

		$Debuglog->add( 'Inserting meta data for new file into db', 'files' );

		// Let's make sure the bare minimum gets saved to DB:
		$this->set_param( 'root_type', 'string', $this->_FileRoot->type );
		$this->set_param( 'root_ID', 'integer', $this->_FileRoot->in_type_ID );
		$this->set_param( 'path', 'string', $this->_rdfp_rel_path );

		// Let parent do the insert:
		$r = parent::dbinsert();

		// We can now consider the meta data has been loaded:
		$this->meta  = 'loaded';

		return $r;
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true on success, false on failure / no changes
	 */
	function dbupdate( )
	{
		if( $this->meta == 'unknown' )
		{
			debug_die( 'cannot update File if meta data has not been checked before' );
		}

		// Let parent do the update:
		return parent::dbupdate();
	}


	/**
	 * Get URL to view the file (either with viewer of with browser, etc...)
	 */
	function get_view_url( $always_open_dirs_in_fm = true )
	{
		global $htsrv_url, $public_access_to_media;

		// Get root code
		$root_ID = $this->_FileRoot->ID;

		if( $this->is_dir() )
		{ // Directory
			if( $always_open_dirs_in_fm || ! $public_access_to_media )
			{ // open the dir in the filemanager:
				// fp>> Note: we MUST NOT clear mode, especially when mode=upload, or else the IMG button disappears when entering a subdir
				return regenerate_url( 'root,path', 'root='.$root_ID.'&amp;path='.$this->get_rdfs_rel_path() );
			}
			else
			{ // Public access: direct link to folder:
				return $this->get_url();
			}
		}
		else
		{ // File
			if( !isset( $this->Filetype->viewtype ) )
			{
				return NULL;
			}
			switch( $this->Filetype->viewtype )
			{
				case 'image':
					return  $htsrv_url.'viewfile.php?root='.$root_ID.'&amp;path='.$this->_rdfp_rel_path.'&amp;viewtype=image';

				case 'text':
					return $htsrv_url.'viewfile.php?root='.$root_ID.'&amp;path='.$this->_rdfp_rel_path.'&amp;viewtype=text';

				case 'download':	 // will NOT open a popup and will insert a Content-disposition: attachment; header
					return $htsrv_url.'getfile.php?root='.$root_ID.'&amp;path='.$this->_rdfp_rel_path;

				case 'browser':		// will open a popup
				case 'external':  // will NOT open a popup
				default:
					return $this->get_url();
			}
		}
	}


	/**
	 * Get Link to view the file (either with viewer of with browser, etc...)
	 */
	function get_view_link( $text = NULL, $title = NULL, $no_access_text = NULL )
	{
		if( is_null( $text ) )
		{	// Use file root+relpath+name by default
			$text = $this->get_root_and_rel_path();
		}

		if( is_null( $title ) )
		{	// Default link title
			$this->load_meta();
			$title = $this->title;
		}

		if( is_null( $no_access_text ) )
		{	// Default text when no access:
			$no_access_text = $text;
		}

		// Get the URL for viewing the file/dir:
		$url = $this->get_view_url( false );

		if( empty($url) )
		{
			return $no_access_text;
		}

		if( isset($this->Filetype) && in_array( $this->Filetype->viewtype, array( 'external', 'download' ) ) )
		{ // Link to open in the curent window
			return '<a href="'.$url.'" title="'.$title.'">'.$text.'</a>';
		}
		else
		{ // Link to open in a new window
			$target = 'evo_fm_'.$this->get_md5_ID();

			// onclick: we unset target attrib and return the return value of pop_up_window() to make the browser not follow the regular href link (at least FF 1.5 needs the target reset)
			return '<a href="'.$url.'" target="'.$target.'"
				title="'.T_('Open in a new window').'" onclick="'
				."this.target = ''; return pop_up_window( '$url', '$target', "
				.(( $width = $this->get_image_size( 'width' ) ) ? ( $width + 100 ) : 750 ).', '
				.(( $height = $this->get_image_size( 'height' ) ) ? ( $height + 150 ) : 550 ).' )">'.$text.'</a>';
		}
	}


	/**
	 * Get link to edit linked file.
	 *
	 * @param integer ID of item to link to => will open the FM in link mode
	 * @param string link text
	 * @param string link title
	 * @param string text to display if access denied
	 * @param string page url for the edit action
	 */
	function get_linkedit_link( $link_itm_ID = NULL, $text = NULL, $title = NULL, $no_access_text = NULL,
											$actionurl = 'admin.php?ctrl=files', $target = '' )
	{
		if( is_null( $text ) )
		{	// Use file root+relpath+name by default
			$text = $this->get_root_and_rel_path();
		}

		if( is_null( $title ) )
		{	// Default link title
			$this->load_meta();
			$title = $this->title;
		}

		if( is_null( $no_access_text ) )
		{	// Default text when no access:
			$no_access_text = $text;
		}

		$url = $this->get_linkedit_url( $link_itm_ID, $actionurl );

		if( !empty($target) )
		{
			$target = ' target="'.$target.'"';
		}

		return '<a href="'.$url.'" title="'.$title.'"'.$target.'>'.$text.'</a>';
	}


  /**
	 * @param integer ID of item to link to => will open the FM in link mode
	 * @return string
	 */
	function get_linkedit_url( $link_itm_ID = NULL, $actionurl = 'admin.php?ctrl=files' )
	{
		if( $this->is_dir() )
		{
			$rdfp_path = $this->_rdfp_rel_path;
		}
		else
		{
			$rdfp_path = dirname( $this->_rdfp_rel_path );
		}

		$url_params = 'root='.$this->_FileRoot->ID.'&amp;path='.$rdfp_path.'/';

		if( ! is_null($link_itm_ID) )
		{	// We want to open the filemanager in link mode:
			$url_params .= '&amp;fm_mode=link_item&amp;item_ID='.$link_itm_ID;
		}

		$url = url_add_param( $actionurl, $url_params );

		return $url;
	}


	/**
	 * Get the thumbnail URL for this file
	 *
	 * @param string
	 */
	function get_thumb_url( $size_name = 'fit-80x80' )
	{
		global $public_access_to_media, $htsrv_url;

		if( ! $this->is_image() )
		{ // Not an image
			debug_die( 'Can only thumb images');
		}

		if( $public_access_to_media )
		{
			$af_thumb_path = $this->get_af_thumb_path( $size_name, NULL, false );
			if( $af_thumb_path[0] != '!' )
			{ // If the thumbnail was already cached, we could publicly access it:
				if( @is_file( $af_thumb_path ) )
				{	// The thumb IS already in cache! :)
					// Let's point directly into the cache:
					$url = $this->_FileRoot->ads_url.dirname($this->_rdfp_rel_path).'/.evocache/'.$this->_name.'/'.$size_name.'.'.$this->get_ext();
					return $url;
				}
			}
		}

		// No thumbnail available (at least publicly), we need to go through getfile.php!
		$root = $this->_FileRoot->ID;
		$url = $htsrv_url.'getfile.php/'
						// This is for clean 'save as':
						.rawurlencode( $this->_name )
						// This is for locating the file:
						.'?root='.$root.'&amp;path='.$this->_rdfp_rel_path.'&amp;size='.$size_name;

		return $url;
	}


  /**
	 *  Generate the IMG THUMBNAIL tag with all the alt & title if available
	 */
	function get_thumb_imgtag( $size_name = 'fit-80x80', $class = '', $align = '' )
	{
		global $use_strict;

		if( ! $this->is_image() )
		{ // Not an image
			return '';
		}

		$imgtag = '<img src="'.$this->get_thumb_url($size_name).'" '
					.'alt="'.$this->dget('alt', 'htmlattr').'" '
					.'title="'.$this->dget('title', 'htmlattr').'"';

		if( $class )
		{ // add class
			$imgtag .= ' class="'.$class.'"';
		}

		if( !$use_strict && $align )
		{ // add align
			$imgtag .= ' align="'.$align.'"';
		}

		$imgtag .=' />';

		return $imgtag;
	}


 	/**
	 * Displays a preview thumbnail which is clickable and opens a view popup
	 *
	 * @param string what do do with files that are not images? 'fulltype'
	 * @return string HTML to display
 	 */
	function get_preview_thumb( $format_for_non_images = '' )
	{
		if( $this->is_image() )
		{	// Ok, it's an image:
			$img = '<img src="'.$this->get_thumb_url().'" alt="'.$this->get_type().'" title="'.$this->get_type().'" />';

			// Get link to view the file (fallback to no view link - just the img):
			$link = $this->get_view_link( $img );
			if( ! $link )
			{ // no view link available:
				$link = $img;
			}

			return $link;
		}

		// Not an image...
		switch( $format_for_non_images )
		{
			case 'fulltype':
				// Full: Icon + File type:
				return $this->get_view_link( $this->get_icon() ).' '.$this->get_type();
				break;
		}

		return '';
	}


	/**
	 * Get the full path to the thumbnail cache for this file.
	 *
	 * ads = Absolute Directory Slash
	 *
	 * @param boolean shall we create the dir if it doesn't exist?
	 * @return string absolute path or !error
	 */
	function get_ads_evocache( $create_if_needed = false )
	{
		if( strpos( $this->_dir, '/.evocache/' ) !== false )
		{	// We are already in an evocahce folder: refuse to go further!
			return '!Recursive caching not allowed';
		}

		$adp_evocache = $this->_dir.'.evocache/'.$this->_name;

		if( $create_if_needed && !is_dir( $adp_evocache ) )
		{	// Create the directory:
			if( ! mkdir_r( $adp_evocache ) )
			{	// Could not create
				return '!.evocache folder read/write error! Check filesystem permissions.';
			}
		}

		return $adp_evocache.'/';
	}


  /**
	 * Delete cache for a file
	 */
	function rm_cache()
	{
		global $Messages;

		// Remove cached elts for teh current file:
		$ads_filecache = $this->get_ads_evocache( false );
		if( $ads_filecache[0] == '!' )
		{
			// This creates unwanted noise
			// $Messages->add( 'Cannot remove .evocache for file. - '.$ads_filecache, 'error' );
		}
		else
		{
			rmdir_r( $ads_filecache );

			// In case cache is now empty, delete the folder:
			$adp_evocache = $this->_dir.'.evocache';
			@rmdir( $adp_evocache );
		}
	}


	/**
	 * Get the full path to the thumbnail for this file.
	 *
	 * af = Absolute File
	 *
	 * @param string size name
	 * @param string mimetype of thumbnail (NULL if we're ready to take wathever is available)
	 * @param boolean shall we create the dir if it doesn't exist?
	 * @return string absolte filename or !error
	 */
	function get_af_thumb_path( $size_name, $thumb_mimetype = NULL, $create_evocache_if_needed = false )
	{
		if( empty($thumb_mimetype) )
		{
			$thumb_mimetype = $this->Filetype->mimetype;
		}
		elseif( $thumb_mimetype != $this->Filetype->mimetype )
		{
			debug_die( 'Not supported. For now, thumbnails have to have same mime type as their parent file.' );
			// TODO: extract prefered extension of filetypes config
		}

		// Get the filename of the thumbnail
		$ads_evocache = $this->get_ads_evocache( $create_evocache_if_needed );
		if( $ads_evocache[0] != '!' )
		{	// Not an error
			return $ads_evocache.$size_name.'.'.$this->get_ext();
		}

		// error
		return $ads_evocache;
	}


	/**
	 * Save thumbnail for file
	 *
	 * @param resource
	 * @param string size name
	 * @param string mimetype of thumbnail
	 * @param string short error code
	 */
	function save_thumb_to_cache( $thumb_imh, $size_name, $thumb_mimetype, $thumb_quality = 90 )
	{
		$af_thumb_path = $this->get_af_thumb_path( $size_name, $thumb_mimetype, true );
		if( $af_thumb_path[0] != '!' )
		{	// We obtained a path for the thumbnail to be saved:
			return save_image( $thumb_imh, $af_thumb_path, $thumb_mimetype, $thumb_quality );
		}

		return $af_thumb_path;	// !Error code
	}


	/**
	 * Output previously saved thumbnail for file
	 *
	 * @param string size name
	 * @param string miemtype of thumbnail
	 * @param string short error code
	 */
	function output_cached_thumb( $size_name, $thumb_mimetype )
	{
		$af_thumb_path = $this->get_af_thumb_path( $size_name, $thumb_mimetype, false );
		if( $af_thumb_path[0] != '!' )
		{	// We obtained a path for the thumbnail to be saved:
			if( ! file_exists( $af_thumb_path ) )
			{	// The thumbnail was not found...
				return '!Thumbnail not found in .evocache'; // WARNING: exact wording match on return
			}

			if( ! is_readable( $af_thumb_path ) )
			{
				return '!Thumbnail read error! Check filesystem permissions.';
			}

 			header('Content-type: '.$thumb_mimetype );
			header('Content-Length: '.filesize( $af_thumb_path ) );
			// Output the content of the file
			readfile( $af_thumb_path );
			return NULL;
		}

		return $af_thumb_path;	// !Error code
	}



	/**
	 * This will spit out a content-type header followed by a thumbnail for this file.
	 *
	 * @todo a million things (fp) but you get the idea...
	 * The generated thumb will be saved to a cached file here (fp)
	 * The cache will be accessed through the File object (fp)
	 * @todo cleanup memory resources
	 *
	 * @param string requested size: 'thumbnail'
	 * @return boolean True on success, false on failure.
	 */
	function thumbnail( $req_size )
	{
		global $thumbnail_sizes;

		load_funcs( '/files/model/_image.funcs.php' );

		$size_name = $req_size;
		if( isset($thumbnail_sizes[$req_size] ) )
		{
			$size_name = $req_size;
		}
		else
		{
			$size_name = 'fit-80x80';
		}

		// Set all params for requested size:
		list( $thumb_type, $thumb_width, $thumb_height, $thumb_quality ) = $thumbnail_sizes[$size_name];

		$mimetype = $this->Filetype->mimetype;

		// Try to output the cached thumbnail:
		$err = $this->output_cached_thumb( $size_name, $mimetype );

		if( $err == '!Thumbnail not found in .evocache' )
		{	// The thumbnail wasn't already in the cache, try to generate and cache it now:
			$err = NULL;		// Short error code

			list( $err, $src_imh ) = load_image( $this->get_full_path(), $mimetype );
			if( empty( $err ) )
			{
				list( $err, $dest_imh ) = generate_thumb( $src_imh, $thumb_type, $thumb_width, $thumb_height );
				if( empty( $err ) )
				{
					$err = $this->save_thumb_to_cache( $dest_imh, $size_name, $mimetype, $thumb_quality );
					if( empty( $err ) )
					{	// File was saved. Ouput that same file immediately:
						// This is probably better than recompressing the memory image..
						$err = $this->output_cached_thumb( $size_name, $mimetype );
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
			imagepng( $im_handle );
			return false;
		}
		return true;
	}


	/**
	 * @param Item
	 */
	function link_to_Item( & $edited_Item )
	{
		global $DB;

		$DB->begin();

		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$this->load_meta( true );

		// Let's make the link!
		$edited_Link = & new Link();
		$edited_Link->set( 'itm_ID', $edited_Item->ID );
		$edited_Link->set( 'file_ID', $this->ID );
		$edited_Link->dbinsert();

		$DB->commit();
	}
}


/*
 * $Log$
 * Revision 1.22  2009/02/07 10:10:26  yabs
 * Validation
 *
 * Revision 1.21  2009/01/19 21:50:47  fplanque
 * minor
 *
 * Revision 1.20  2009/01/17 21:16:25  blueyed
 * Add return value to File::thumbnail
 *
 * Revision 1.19  2008/12/27 20:06:27  fplanque
 * rollback ( changes don't make sense to me )
 *
 * Revision 1.18  2008/11/03 21:01:56  blueyed
 * get_af_thumb_path(): Fix notice for files without Filetype
 * fp> How do we generate a thumbnail for a file with no filetype? How do we know what kind of thumbnail to generate?
 *
 * Revision 1.17  2008/10/15 22:57:14  blueyed
 * Drop support for 'clean save as'. It should be done by sending the right headers instead.
 * fp> Sorry, can't drop that if you don't replace it with alternative.
 *
 * Revision 1.16  2008/10/15 22:55:26  blueyed
 * todo/bug
 *
 * Revision 1.15  2008/10/15 22:07:54  blueyed
 * todo/bug
 *
 * Revision 1.14  2008/10/07 16:59:59  blueyed
 * todo/bug/notice
 *
 * Revision 1.13  2008/09/29 08:30:36  fplanque
 * Avatar support
 *
 * Revision 1.12  2008/09/24 08:35:11  fplanque
 * Support of "cropped" thumbnails (the image will always fill the whole thumbnail area)
 * Thumbnail sizes can be configured in /conf/_advanced.php
 *
 * Revision 1.11  2008/09/23 09:04:33  fplanque
 * moved media index to a widget
 *
 * Revision 1.10  2008/09/15 11:01:09  fplanque
 * Installer now creates a demo photoblog
 *
 * Revision 1.9  2008/07/11 23:13:05  blueyed
 * Fix possible E_NOTICE for files without Filetype in File::is_editable
 *
 * Revision 1.8  2008/05/26 19:22:00  fplanque
 * fixes
 *
 * Revision 1.7  2008/04/14 19:50:51  fplanque
 * enhanced attachments handling in post edit mode
 *
 * Revision 1.6  2008/04/14 17:52:07  fplanque
 * link images to original by default
 *
 * Revision 1.5  2008/04/13 20:40:06  fplanque
 * enhanced handlign of files attached to items
 *
 * Revision 1.4  2008/01/21 09:35:28  fplanque
 * (c) 2008
 *
 * Revision 1.3  2008/01/06 04:23:49  fplanque
 * thumbnail enhancement
 *
 * Revision 1.2  2007/11/29 00:07:19  blueyed
 * Fallback to uid/gid for file owner/group, if name is unknown
 *
 * Revision 1.1  2007/06/25 10:59:54  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.45  2007/06/23 22:05:16  fplanque
 * fixes
 *
 * Revision 1.44  2007/06/18 20:59:02  fplanque
 * minor
 *
 * Revision 1.43  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.42  2007/03/20 07:43:44  fplanque
 * .evocache cleanup triggers
 *
 * Revision 1.41  2007/03/05 02:13:26  fplanque
 * improved dashboard
 *
 * Revision 1.40  2007/02/10 18:03:03  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.39  2007/01/26 02:12:06  fplanque
 * cleaner popup windows
 *
 * Revision 1.38  2007/01/25 03:37:14  fplanque
 * made bytesreadable() really readable for average people.
 *
 * Revision 1.37  2007/01/25 03:17:00  fplanque
 * visual cleanup for average users
 * geeky stuff preserved as options
 *
 * Revision 1.36  2007/01/24 05:57:55  fplanque
 * cleanup / settings
 *
 * Revision 1.35  2007/01/19 10:45:42  fplanque
 * images everywhere :D
 * At this point the photoblogging code can be considered operational.
 *
 * Revision 1.34  2007/01/19 09:31:05  fplanque
 * Provision for case sensitive file meta data handling
 *
 * Revision 1.33  2007/01/19 08:20:36  fplanque
 * Addressed resized image quality.
 *
 * Revision 1.32  2007/01/15 20:48:20  fplanque
 * constrained photoblog image size
 * TODO: sharpness issue
 *
 * Revision 1.31  2006/12/23 22:53:10  fplanque
 * extra security
 *
 * Revision 1.30  2006/12/14 01:46:29  fplanque
 * refactoring / factorized image preview display
 *
 * Revision 1.29  2006/12/14 00:33:53  fplanque
 * thumbnails & previews everywhere.
 * this is getting good :D
 *
 * Revision 1.28  2006/12/13 22:26:27  fplanque
 * This has reached the point of a functional eternal cache.
 * TODO: handle cache on delete, upload/overwrite, rename, move, copy.
 *
 * Revision 1.27  2006/12/13 21:23:56  fplanque
 * .evocache folders / saving of thumbnails
 *
 * Revision 1.26  2006/12/13 20:10:30  fplanque
 * object responsibility delegation?
 *
 * Revision 1.25  2006/12/13 03:08:28  fplanque
 * thumbnail implementation design demo
 *
 * Revision 1.24  2006/12/07 23:13:10  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.23  2006/12/07 20:03:32  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.22  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.21  2006/11/19 23:43:04  blueyed
 * Optimized icon and $IconLegend handling
 */
?>
