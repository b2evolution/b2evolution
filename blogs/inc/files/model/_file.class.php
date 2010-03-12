<?php
/**
 * This file implements the File class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_files'] = false;


load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Represents a file or folder on disk. Optionnaly stores meta data from DB.
 *
 * Use {@link FileCache::get_by_root_and_path()} to create an instance.
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
	 * Recursive directory size in bytes.
	 * @var integer
	 * @see get_recursive_size()
	 * @access protected
	 */
	var $_recursive_size;

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
	 * Is the File an audio file? NULL if unknown
	 * @var boolean
	 * @see is_audio()
	 * @access protected
	 */
	var $_is_audio;

	/**
	 * Extension, Mime type, icon, viewtype and 'allowed extension' of the file
	 * @access protected
	 * @see File::get_Filetype
	 * @var Filetype
	 */
	var $Filetype;


	/**
	 * Constructor, not meant to be called directly. Use {@link FileCache::get_by_root_and_path()}
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
				array( 'table'=>'T_users', 'fk'=>'user_avatar_file_ID', 'msg'=>T_('%d linked users (avatars)') ),
			);

		// Memorize filepath:
		$FileRootCache = & get_FileRootCache();
		$this->_FileRoot = & $FileRootCache->get_by_type_and_ID( $root_type, $root_ID );

		// If there's a valid file root, handle extra stuff. This should not get done when the FileRoot is invalid.
		if( $this->_FileRoot )
		{
			$this->_rdfp_rel_path = no_trailing_slash(str_replace( '\\', '/', $rdfp_rel_path ));
			$this->_adfp_full_path = $this->_FileRoot->ads_path.$this->_rdfp_rel_path;
			$this->_name = basename( $this->_adfp_full_path );
			$this->_dir = dirname( $this->_adfp_full_path ).'/';
			$this->_md5ID = md5( $this->_adfp_full_path );

			// Initializes file properties (type, size, perms...)
			$this->load_properties();

			if( $load_meta )
			{ // Try to load DB meta info:
				$this->load_meta();
			}
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
				$FileCache = & get_FileCache();
				$FileCache->add( $this );
			}
			else
			{ // No meta data...
				$Debuglog->add( sprintf('No metadata could be loaded for %d:%s', $this->_FileRoot ? $this->_FileRoot->ID : 'FALSE', $this->_rdfp_rel_path), 'files' );
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
		$this->_is_audio = NULL;
		$this->_lastmod_ts = NULL;
		$this->_exists = NULL;
		$this->_perms = NULL;
		$this->_size = NULL;
		$this->_recursive_size = NULL;

		if( is_dir( $this->_adfp_full_path ) )
		{	// The File is a directory:
			$this->_is_dir = true;
		}
		else
		{	// The File is a regular file:
			$this->_is_dir = false;
		}
	}


	/**
	 * Does the File/folder exist on disk?
	 *
	 * @return boolean true, if the file or dir exists; false if not
	 */
	function exists()
	{
		if( ! isset($this->_exists) )
		{
			$this->_exists = file_exists( $this->_adfp_full_path );
		}
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
	 * Is the File an audio file?
	 *
	 * Tries to determine if it is and caches the info.
	 *
	 * @return boolean true if the object is an audio file, false if not
	 */
	function is_audio()
	{
		if ( is_null( $this->_is_audio ) )
		{
			$this->_is_audio = in_array($this->get_ext(), array('mp3', 'oga'));
		}
		return $this->_is_audio;
	}


	/**
	 * Is the file editable?
	 *
	 * @param boolean allow locked file types?
	 */
	function is_editable( $allow_locked = false )
	{
		if( $this->is_dir() )
		{ // we cannot edit dirs
			return false;
		}

		$Filetype = & $this->get_Filetype();
		if( empty($Filetype) || $this->Filetype->viewtype != 'text' )	// we can only edit text files
		{
			return false;
		}

		if( ! $Filetype->allowed && ! $allow_locked )
		{	// We cannot edit locked file types:
			return false;
		}

		return true;
	}


	/**
	 * Get the File's Filetype object (or NULL).
	 *
	 * @return Filetype The Filetype object or NULL
	 */
	function & get_Filetype()
	{
		if( ! isset($this->Filetype) )
		{
			// Create the filetype with the extension of the file if the extension exist in database
			if( $ext = $this->get_ext() )
			{ // The file has an extension, load filetype object
				$FiletypeCache = & get_FiletypeCache();
				$this->Filetype = & $FiletypeCache->get_by_extension( strtolower( $ext ), false );
			}

			if( ! $this->Filetype )
			{ // remember as being retrieved.
				$this->Filetype = false;
			}
		}
		$r = $this->Filetype ? $this->Filetype : NULL;
		return $r;
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
				$url = $this->_FileRoot->ads_url.$this->get_rdfs_rel_path().'?mtime='.$this->get_lastmod_ts();
			}
			else
			{ // No Access
				// TODO: dh> why can't this go through the FM, preferably opening in a popup, if the user has access?!
				//           (see get_view_url)
				// fp> the FM can do anything as long as this function does not send back an URL to something that is actually private.
				debug_die( 'Private directory! ');
			}
		}
		else
		{ // File
			if( $public_access_to_media )
			{ // Public Access : full path
				$url = $this->_FileRoot->ads_url.no_leading_slash($this->_rdfp_rel_path).'?mtime='.$this->get_lastmod_ts();
			}
			else
			{ // Private Access: doesn't show the full path
				$url = $this->get_getfile_url();
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

		$Filetype = & $this->get_Filetype();
		if( isset( $Filetype->mimetype ) )
		{
			$this->_type = $Filetype->name;
			return $this->_type;
		}

		$this->_type = T_('Unknown');
		return $this->_type;
	}


	/**
	 * Get file/dir size in bytes.
	 *
	 * For the recursive size of a directory see {@link get_recursive_size()}.
	 *
	 * @return integer bytes
	 */
	function get_size()
	{
		if( ! isset($this->_size) )
		{
			$this->_size = @filesize( $this->_adfp_full_path );
		}
		return $this->_size;
	}


	/**
	 * Get timestamp of last modification.
	 *
	 * @return integer Timestamp
	 */
	function get_lastmod_ts()
	{
		if( ! isset($this->_lastmod_ts) )
		{
			$this->_lastmod_ts = @filemtime( $this->_adfp_full_path );
		}
		return $this->_lastmod_ts;
	}


	/**
	 * Get date/time of last modification, formatted.
	 *
	 * @param string date format or 'date' or 'time' for default locales.
	 * @return string locale formatted date/time
	 */
	function get_lastmod_formatted( $format = '#' )
	{
		global $localtimenow;

		$lastmod_ts = $this->get_lastmod_ts();

		switch( $format )
		{
			case 'date':
				return date_i18n( locale_datefmt(), $lastmod_ts );

			case 'time':
				return date_i18n( locale_timefmt(), $lastmod_ts );

			case 'compact':
				$age = $localtimenow - $lastmod_ts;
				if( $age < 3600 )
				{	// Less than 1 hour: return full time
					return date_i18n( 'H:i:s', $lastmod_ts );
				}
				if( $age < 86400 )
				{	// Less than 24 hours: return compact time
					return date_i18n( 'H:i', $lastmod_ts );
				}
				if( $age < 31536000 )
				{	// Less than 365 days: Month and day
					return date_i18n( 'M, d', $lastmod_ts );
				}
				// Older: return yeat
				return date_i18n( 'Y', $lastmod_ts );
				break;

			case '#':
				default:
				$format = locale_datefmt().' '.locale_timefmt();
				return date_i18n( $format, $lastmod_ts );
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
		if( ! isset($this->_perms) )
		{
			$this->_perms = @fileperms( $this->_adfp_full_path );
		}
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
		else
		{
			$Filetype = & $this->get_Filetype();
			if( isset( $Filetype->icon ) && $Filetype->icon )
			{ // Return icon for known type of the file
					return $Filetype->get_icon();
			}
			else
			{ // Icon for unknown file type:
				$icon = 'file_unknown';
			}
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
		if( $this->is_dir() )
		{
			return /* TRANS: short for '<directory>' */ T_('&lt;dir&gt;');
		}
		else
		{
			return bytesreadable( $this->get_size() );
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

			$img = '<img'.get_field_attribs_as_string($this->get_img_attribs($size_name)).' />';

			if( $image_link_to == 'original' )
			{	// special case
				$image_link_to = $this->get_url();
			}
			if( !empty( $image_link_to ) )
			{
				$img = '<a href="'.$image_link_to.'">'.$img.'</a>';
			}
			$r .= $img;

			$desc = $this->dget('desc');
			if( !empty($desc) && !is_null($before_image_legend) )
			{
				$r .= $before_image_legend
							.$this->dget('desc')		// If this needs to be changed, please document.
							.$after_image_legend;
			}
			$r .= $after_image;
		}
		else
		{	// Make an A HREF link:
			$r = '<a href="'.$this->get_url().'"'
						// title
						.( $this->get('desc') ? ' title="'.$this->dget('desc', 'htmlattr').'"' : '' ).'>'
						// link text
						.( $this->get('title') ? $this->dget('title') : $this->dget('name') ).'</a>';
		}

		return $r;
	}


	/**
	 * Get the "full" size of a file/dir (recursive for directories).
	 * This is used by the FileList.
	 * @return integer Recursive size of the dir or the size alone for a file.
	 */
	function get_recursive_size()
	{
		if( ! isset($this->_recursive_size) )
		{
			if( $this->is_dir() )
				$this->_recursive_size = get_dirsize_recursive( $this->get_full_path() );
			else
				$this->_recursive_size = $this->get_size();
		}
		return $this->_recursive_size;
	}


	/**
	 * Rewrite the file paths, because one the parent folder name was changed - recursive function
	 *
	 * This function should be used just after a folder rename
	 *
	 * @access should be private
	 * @param string relative path for this file's parent directory
	 * @param string full path for this file's parent directory 
	 */
	function modify_path ( $rel_dir, $full_dir )
	{
		if( $this->is_dir() )
		{
			$new_rel_dir = $rel_dir.$this->_name.'/';
			$new_full_dir = $full_dir.$this->_name.'/';
			
			$temp_Filelist = new Filelist( $this->_FileRoot, $this->_adfp_full_path );
			$temp_Filelist->load();
			
			while ( $temp_File = $temp_Filelist->get_next() )
			{
				$temp_File->modify_path( $new_rel_dir, $new_full_dir );
			}
		}

		$this->load_meta();
		$this->_rdfp_rel_path = $rel_dir.$this->_name;
		$this->_dir = $full_dir;
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
			$this->load_meta();
		}
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
		// rename() will fail if newname already exists on windows
		// if it doesn't work that way on linux we need the extra check below
		// but then we have an integrity issue!! :(
		if( file_exists($this->_dir.$newname) )
		{
			return false;
		}
		
		global $DB;
		$DB->begin();
		
		if( $this->is_dir() )
		{ // modify folder content file paths in db 
			$rel_dir = dirname( $this->_rdfp_rel_path ).'/';
			if( $rel_dir == './' )
			{
				$rel_dir = '';
			}
			$rel_dir = $rel_dir.$newname.'/';
			$full_dir = $this->_dir.$newname.'/';
			
			$temp_Filelist = new Filelist( $this->_FileRoot, $this->_adfp_full_path );
			$temp_Filelist->load();
			
			while ( $temp_File = $temp_Filelist->get_next() )
			{
				$temp_File->modify_path ( $rel_dir, $full_dir, $paths );
			}
		}
		
		if( ! @rename( $this->_adfp_full_path, $this->_dir.$newname ) )
		{ // Rename will fail if $newname already exists (at least on windows)
// fp>asimo why is there no DB commit or rollback here???
			$DB->rollback();
			return false;
		}

		// Delete thumb caches for old name:
		// Note: new name = new usage : there is a fair chance we won't need the same cache sizes in the new loc.
		$this->rm_cache();

		// Get Meta data (before we change name) (we may need to update it later):
		$this->load_meta();

		$this->_name = $newname;
		$this->Filetype = NULL; // depends on name

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
			if ( ! $this->dbupdate() )
			{
// fp>asimo: the file has already been renamed on disk. I'm not sure what DB changes took place at this point. Can you confirm it's better to roll back everything rather than committing what has already been done?
				$DB->rollback();
				return false;
			}
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
		
		$DB->commit();

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
		$FileRootCache = & get_FileRootCache();

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
		$this->Filetype = NULL; // depends on name
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

		// TODO: fp> what happens if someone else creates the destination file right at this moment here?
		//       dh> use a locking mechanism.

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

		// Remove thumb cache:
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
		$this->set_param( 'root_ID', 'number', $this->_FileRoot->in_type_ID );
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
			$Filetype = & $this->get_Filetype();
			if( !isset( $Filetype->viewtype ) )
			{
				return NULL;
			}
			switch( $Filetype->viewtype )
			{
				case 'image':
					return  $htsrv_url.'viewfile.php?root='.$root_ID.'&amp;path='.$this->_rdfp_rel_path.'&amp;viewtype=image';

				case 'text':
					return $htsrv_url.'viewfile.php?root='.$root_ID.'&amp;path='.$this->_rdfp_rel_path.'&amp;viewtype=text';

				case 'download':	 // will NOT open a popup and will insert a Content-disposition: attachment; header
					return $this->get_getfile_url();

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

		$Filetype = & $this->get_Filetype();
		if( $Filetype && in_array( $Filetype->viewtype, array( 'external', 'download' ) ) )
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
											$actionurl = '#', $target = '' )
	{
		global $dispatcher;

		if( $actionurl == '#' )
		{
			$actionurl = $dispatcher.'?ctrl=files';
		}

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
	function get_linkedit_url( $link_itm_ID = NULL, $actionurl = '#' )
	{
		global $dispatcher;

		if( $actionurl == '#' )
		{
			$actionurl = $dispatcher.'?ctrl=files';
		}

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

		// Add param to make the file list highlight this (via JS).
		$url_params .= '&amp;fm_highlight='.rawurlencode($this->_name);

		$url = url_add_param( $actionurl, $url_params );

		return $url;
	}


	/**
	 * Get the thumbnail URL for this file
	 *
	 * @param string
	 */
	function get_thumb_url( $size_name = 'fit-80x80', $glue = '&amp;' )
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
					global $Settings;
					$url = $this->_FileRoot->ads_url.dirname($this->_rdfp_rel_path).'/'.$Settings->get( 'evocache_foldername' ).'/'.$this->_name.'/'.$size_name.'.'.$this->get_ext().'?mtime='.$this->get_lastmod_ts();
					return $url;
				}
			}
		}

		// No thumbnail available (at least publicly), we need to go through getfile.php!
		$url = $this->get_getfile_url($glue).$glue.'size='.$size_name;

		return $url;
	}


	/**
	 * Get the URL to access a file through getfile.php.
	 * @return string
	 */
	function get_getfile_url( $glue = '&amp;' )
	{
		global $htsrv_url;
		return $htsrv_url.'getfile.php/'
			// This is for clean 'save as':
			.rawurlencode( $this->_name )
			// This is for locating the file:
			.'?root='.$this->_FileRoot->ID.$glue.'path='.$this->_rdfp_rel_path
			.$glue.'mtime='.$this->get_lastmod_ts(); // TODO: dh> use salt here?!
	}


	/**
	 * Generate the IMG THUMBNAIL tag with all the alt & title if available.
	 * @return string
	 */
	function get_thumb_imgtag( $size_name = 'fit-80x80', $class = '', $align = '' )
	{
		global $use_strict;

		if( ! $this->is_image() )
		{ // Not an image
			return '';
		}

		$img_attribs = $this->get_img_attribs($size_name);
		// pre_dump( $img_attribs );

		if( $class )
		{ // add class
			$img_attribs['class'] = $class;
		}

		if( !$use_strict && $align )
		{ // add align
			$img_attribs['align'] = $align;
		}

		return '<img'.get_field_attribs_as_string($img_attribs).' />';
	}


	/**
	 * Returns an array of things like:
	 * - src
	 * - title
	 * - alt
	 * - width
	 * - height
	 *
	 * @param string what size do we want src to link to, can be "original" or a thumnbail size
	 * @param string
	 * @param string
	 * @return array List of HTML attributes for the image.
	 */
	function get_img_attribs( $size_name = 'fit-80x80', $title = NULL, $alt = NULL )
	{
		$img_attribs = array(
				'title' => isset($title) ? $title : $this->get('title'),
				'alt'   => isset($alt) ? $alt : $this->get('alt'),
			);

		if( ! isset($img_attribs['alt']) )
		{ // use title for alt, too
			$img_attribs['alt'] = $img_attribs['title'];
		}
		if( ! isset($img_attribs['alt']) )
		{ // always use empty alt
			$img_attribs['alt'] = '';
		}

		if( $size_name == 'original' )
		{	// We want src to link to the original file
			$img_attribs['src'] = $this->get_url();
			if( ( $size_arr = $this->get_image_size('widthheight_assoc') ) )
			{
				$img_attribs += $size_arr;
			}
		}
		else
		{ // We want src to link to a thumbnail
			$img_attribs['src'] = $this->get_thumb_url( $size_name, '&' );
			$thumb_path = $this->get_af_thumb_path($size_name, NULL, true);
			if( substr($thumb_path, 0, 1) != '!'
				&& ( $size_arr = imgsize($thumb_path, 'widthheight_assoc') ) )
			{ // no error, add width and height attribs
				$img_attribs += $size_arr;
			}
		}

		return $img_attribs;
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
			$type = $this->get_type();
			$img_attribs = $this->get_img_attribs( 'fit-80x80', $type, $type );
			$img = '<img'.get_field_attribs_as_string($img_attribs).' />';

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
		global $Settings;
		if( strpos( $this->_dir, '/'.$Settings->get( 'evocache_foldername' ).'/' ) !== false )
		{	// We are already in an evocache folder: refuse to go further!
			return '!Recursive caching not allowed';
		}

		$adp_evocache = $this->_dir.$Settings->get( 'evocache_foldername' ).'/'.$this->_name;

		if( $create_if_needed && !is_dir( $adp_evocache ) )
		{	// Create the directory:
			if( ! mkdir_r( $adp_evocache ) )
			{	// Could not create
				return '!'.$Settings->get( 'evocache_foldername' ).' folder read/write error! Check filesystem permissions.';
			}
		}

		return $adp_evocache.'/';
	}


	/**
	 * Delete cache for a file
	 */
	function rm_cache()
	{
		global $Messages, $Settings;

		// Remove cached elts for teh current file:
		$ads_filecache = $this->get_ads_evocache( false );
		if( $ads_filecache[0] == '!' )
		{
			// This creates unwanted noise
			// $Messages->add( 'Cannot remove '.$Settings->get( 'evocache_foldername' ).' for file. - '.$ads_filecache, 'error' );
		}
		else
		{
			rmdir_r( $ads_filecache );

			// In case cache is now empty, delete the folder:
			$adp_evocache = $this->_dir.$Settings->get( 'evocache_foldername' );
			@rmdir( $adp_evocache );
		}
	}


	/**
	 * Get the full path to the thumbnail for this file.
	 *
	 * af = Absolute File
	 *
	 * @param string size name (e.g. "fit-80x80")
	 * @param string mimetype of thumbnail (NULL if we're ready to take whatever is available)
	 * @param boolean shall we create the dir if it doesn't exist?
	 * @return string absolute filename or !error
	 */
	function get_af_thumb_path( $size_name, $thumb_mimetype = NULL, $create_evocache_if_needed = false )
	{
		$Filetype = & $this->get_Filetype();
		if( isset($Filetype) )
		{
			if( empty($thumb_mimetype) )
			{
				$thumb_mimetype = $Filetype->mimetype;
			}
			elseif( $thumb_mimetype != $Filetype->mimetype )
			{
				debug_die( 'Not supported. For now, thumbnails have to have same mime type as their parent file.' );
				// TODO: extract prefered extension of filetypes config
			}
		}
		elseif( !empty($thumb_mimetype) )
		{
			debug_die( 'Not supported. Can\'t generate thumbnail for unknow parent file.' );
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
	 * @param string mimetype of thumbnail
	 * @param int Modified time of the file (should have been provided as GET param)
	 * @return mixed NULL on success, otherwise string ("!Error code")
	 */
	function output_cached_thumb( $size_name, $thumb_mimetype, $mtime = NULL )
	{
		$af_thumb_path = $this->get_af_thumb_path( $size_name, $thumb_mimetype, false );
		//pre_dump($af_thumb_path);
		if( $af_thumb_path[0] != '!' )
		{	// We obtained a path for the thumbnail to be saved:
			if( ! file_exists( $af_thumb_path ) )
			{	// The thumbnail was not found...
				global $Settings;
				return '!Thumbnail not found in'.$Settings->get( 'evocache_foldername' ); // WARNING: exact wording match on return
			}

			if( ! is_readable( $af_thumb_path ) )
			{
				return '!Thumbnail read error! Check filesystem permissions.';
			}

			header('Content-Type: '.$thumb_mimetype );
			header('Content-Length: '.filesize( $af_thumb_path ) );

			// dh> if( $mtime && $mtime == $this->get_lastmod_ts() )
			// fp> I don't think mtime changes anything to the cacheability of the data
			header_noexpire(); // Static image

			// Output the content of the file
			readfile( $af_thumb_path );
			return NULL;
		}

		return $af_thumb_path;	// !Error code
	}


	/**
	 * @param Item
	 */
	function link_to_Item( & $edited_Item )
	{
		global $DB;

		// Automatically determine default position.
		// First image becomes "teaser", otherwise "aftermore".
		$LinkCache = & get_LinkCache();
		$existing_Links = $LinkCache->get_by_item_ID($edited_Item->ID);

		$order = 1;

		if( $existing_Links )
		{
			$position = NULL;
			$last_Link = array_pop($existing_Links);
			$last_File = & $last_Link->get_File();

			if( $last_File && $this->is_image() && $last_File->is_image() && ! count($existing_Links) )
			{ // there's only one image attached yet, the second becomes "aftermore"
				$position = 'aftermore';
			}
			else
			{ // default: use position of previous link/attachment
				$position = $last_Link->get('position');
			}

			// Re-add popped link.
			$existing_Links[] = $last_Link;
		}
		else
		{ // no attachment yet
			$position = $this->is_image() ? 'teaser' : 'aftermore';
		}

		// Find highest order
		foreach( $existing_Links as $loop_Link )
		{
			$existing_order = $loop_Link->get('order');
			if( $existing_order >= $order )
				$order = $existing_order+1;
		}

		$DB->begin();

		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$this->load_meta( true );

		// Let's make the link!
		$edited_Link = new Link();
		$edited_Link->set( 'itm_ID', $edited_Item->ID );
		$edited_Link->set( 'file_ID', $this->ID );
		$edited_Link->set( 'position', $position );
		$edited_Link->set( 'order', $order );
		$edited_Link->dbinsert();

		$DB->commit();
	}
}


/*
 * $Log$
 * Revision 1.84  2010/03/12 10:52:53  efy-asimo
 * Set EvoCache  folder names - task
 *
 * Revision 1.83  2010/03/08 21:06:27  fplanque
 * minor/doc
 *
 * Revision 1.82  2010/03/06 12:53:40  efy-asimo
 * Replace existing file bugfix
 *
 * Revision 1.81  2010/03/05 13:38:31  fplanque
 * doc/todo
 *
 * Revision 1.80  2010/02/08 17:52:15  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.79  2010/01/30 18:55:25  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.78  2010/01/30 09:55:34  efy-asimo
 * return to the properties form after file rename error + user transaction during file rename
 *
 * Revision 1.77  2010/01/24 14:47:25  efy-asimo
 * Update file paths after folder rename
 *
 * Revision 1.76  2009/12/12 19:14:10  fplanque
 * made avatars optional + fixes on img props
 *
 * Revision 1.75  2009/12/06 22:55:20  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.74  2009/12/04 23:27:50  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.73  2009/12/02 01:00:07  fplanque
 * header_nocache & header_noexpire
 *
 * Revision 1.72  2009/11/30 00:22:05  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.70  2009/11/11 20:16:14  fplanque
 * doc
 *
 * Revision 1.69  2009/11/11 19:12:56  fplanque
 * Inproved actions after uploaded
 *
 * Revision 1.68  2009/10/13 22:36:01  blueyed
 * Highlight files and directories in the filemanager when opened via 'Locate this' link. Adds scrollTo jQuery plugin.
 *
 * Revision 1.67  2009/10/12 21:32:13  blueyed
 * File: get_tag: use get_img_attribs; get_img_attribs: always use empty alt, if nothing else is provided.
 *
 * Revision 1.66  2009/10/12 18:54:18  blueyed
 * link_to_Item: Fix off-by-one error with order generation
 *
 * Revision 1.65  2009/10/11 03:12:53  blueyed
 * Properly init order of new links.
 *
 * Revision 1.64  2009/10/11 03:06:26  blueyed
 * Fix install
 *
 * Revision 1.63  2009/10/11 03:00:10  blueyed
 * Add "position" and "order" properties to attachments.
 * Position can be "teaser" or "aftermore" for now.
 * Order defines the sorting of attachments.
 * Needs testing and refinement. Upgrade might work already, be careful!
 *
 * Revision 1.62  2009/10/07 23:43:25  fplanque
 * doc
 *
 * Revision 1.61  2009/10/05 23:21:32  blueyed
 * todo/question
 *
 * Revision 1.60  2009/09/30 20:04:43  blueyed
 * Add mtime param to results from get_url, too.
 *
 * Revision 1.59  2009/09/30 17:39:51  blueyed
 * Add mtime param to .evocache files. This allows to cache them to the max on the client.
 *
 * Revision 1.58  2009/09/30 00:38:15  sam2kb
 * Space is not needed before get_field_attribs_as_string()
 *
 * Revision 1.57  2009/09/27 21:00:54  blueyed
 * get_img_attribs: fix default for size_name, allow passing of title and alt. get_preview_thumb uses it now, too, which adds width/height params to the icons in the file manager.
 *
 * Revision 1.56  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.55  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.54  2009/09/20 23:54:24  blueyed
 * File::output_cached_thumb handles mtime param, and uses it to send a
 * far in the future Expires header.
 * mtime param gets forwarded from getfile.php.
 * This makes browsers finally cache files served through getfile.php.
 *
 * Revision 1.53  2009/09/20 16:55:57  tblue246
 * Prevent PHP error
 *
 * Revision 1.52  2009/09/20 13:45:19  blueyed
 * Re-add get_img_attribs, fix class issue and use it where it has been factored out, too.
 *
 * Revision 1.51  2009/09/20 01:28:35  fplanque
 * reverted broken stuff (first bug: the class is no longer added to img, I tried to fixed that and entered a rathole with no end)
 * dh> the problem has been just that get_img_attribs was called again, and not just once. Fixed.
 *
 * Revision 1.50  2009/09/20 00:19:31  blueyed
 * Add width/height attribs to get_thumb_imgtag generated images. Refactor used method into get_img_attribs.
 *
 * Revision 1.49  2009/09/16 21:23:09  blueyed
 * File class derives from DataObject. Load this.
 *
 * Revision 1.48  2009/09/16 20:33:40  tblue246
 * Fix fatal error ("unsupported operand types").
 *
 * Revision 1.47  2009/09/11 19:36:58  blueyed
 * File::get_tag: fix width/height attribs for "original" size and add the attribs for thumbs, using "widthheight_assoc"
 *
 * Revision 1.46  2009/09/09 19:05:39  blueyed
 * is_audio: fix doc, add oga to audio file extensions.
 *
 * Revision 1.45  2009/09/08 13:51:01  tblue246
 * phpdoc fixes
 *
 * Revision 1.44  2009/09/04 17:07:18  waltercruz
 * Showing a player when the attachment is a mp3
 *
 * Revision 1.43  2009/08/31 19:19:24  blueyed
 * File::get_tag: use title for alt tag, if the latter info is not provided. Refactor it using get_field_attribs_as_string.
 *
 * Revision 1.42  2009/08/06 14:55:45  fplanque
 * doc
 *
 * Revision 1.41  2009/07/31 00:17:20  blueyed
 * Move File::thumbnail to getfile.php, where it gets used exclusively. ACKed by FP.
 *
 * Revision 1.40  2009/07/31 00:14:00  blueyed
 * File class: indent, minor
 *
 * Revision 1.39  2009/07/19 21:00:19  fplanque
 * minor
 *
 * Revision 1.38  2009/07/18 18:43:50  tblue246
 * DataObject::set_param() does not accept "integer" as the 2nd param (has to be "number").
 *
 * Revision 1.37  2009/07/16 13:55:35  tblue246
 * - Do not allow modification of "Post" post type (ID 1).
 * - When deleting files, check for linked users.
 *
 * Revision 1.36  2009/07/06 23:52:24  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.35  2009/05/25 19:47:45  fplanque
 * better linking of files
 *
 * Revision 1.34  2009/05/20 17:26:22  yabs
 * minor bug fixes
 *
 * Revision 1.33  2009/05/19 14:34:31  fplanque
 * Category, tag, archive and serahc page snow only display post excerpts by default. (Requires a 3.x skin; otherwise the skin will display full posts as before). This can be controlled with the ''content_mode'' param in the skin tags.
 *
 * Revision 1.32  2009/03/26 22:45:29  blueyed
 * File class: handle invalid Fileroot more gracefully, without throwing E_NOTICEs. This happened when disabling user media dirs and the avatar thingy kicked in. See http://forums.b2evolution.net/viewtopic.php?p=89531#89531
 *
 * Revision 1.31  2009/03/26 22:23:36  blueyed
 * Fix doc
 *
 * Revision 1.30  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.29  2009/02/19 04:48:13  blueyed
 * Lazy-instantiate Filetype of a file, moved to get_Filetype. Bugfix: unset Filetype if name changes.
 *
 * Revision 1.28  2009/02/10 23:28:59  blueyed
 * Add mtime-Expires caching to getfile.php.
 *  - getfile.php links have a mtime param to make the URLs unique
 *  - Add File::get_getfile_url
 *  - getfile.php sends "Expires: 'in 10 years'" (not for thumbs yet, see
 *    TODO)
 *
 * Revision 1.27  2009/02/10 22:38:57  blueyed
 *  - Handle more File properties in File class lazily.
 *  - Cleanup recursive size handling:
 *    - Add Filelist::get_File_size
 *    - Add Filelist::get_File_size_formatted
 *    - Add File::_recursive_size/get_recursive_size
 *    - Drop File::setSize
 *    - get_dirsize_recursive: includes size of directories (e.g. 4kb here)
 *
 * Revision 1.26  2009/02/10 21:27:59  blueyed
 * File: invalidate _lastmod_ts in load_properties, where it has been set before
 *
 * Revision 1.25  2009/02/10 21:23:43  blueyed
 * File: lazy-fill _lastmod_ts through getter
 *
 * Revision 1.24  2009/02/10 21:11:24  blueyed
 * typo, indent
 *
 * Revision 1.23  2009/02/10 21:08:50  blueyed
 * doc, indent
 *
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
