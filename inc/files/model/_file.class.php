<?php
/**
 * This file implements the File class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
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
	 * ID of user that created/uploaded the file
	 * @var integer
	 */
	var $creator_user_ID;

	/**
	 * File type: 'image', 'audio', 'other', NULL
	 * @var string
	 */
	var $type;

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
	 * Meta data: Hash value of file content
	 * @var string
	 */
	var $hash;

	/**
	 * Meta data: Hash value of file path variables: $root_type, $root_ID, $rdfp_rel_path
	 * @var string
	 */
	var $path_hash;

	/**
	 * Meta data: 1 - if this file can be used as main profile picture, 0 - if admin disabled this file for main picture
	 * @var boolean
	 */
	var $can_be_main_profile;

	/**
	 * Meta data: Number of times the file was downloaded
	 * @var integer
	 */
	 var $download_count;

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
	function __construct( $root_type, $root_ID, $rdfp_rel_path, $load_meta = false )
	{
		global $Debuglog;

		$Debuglog->add( "new File( $root_type, $root_ID, $rdfp_rel_path, load_meta=$load_meta)", 'files' );

		// Call parent constructor
		parent::__construct( 'T_files', 'file_', 'file_ID', '', '', '', '' );

		// Memorize filepath:
		$FileRootCache = & get_FileRootCache();
		$this->_FileRoot = & $FileRootCache->get_by_type_and_ID( $root_type, $root_ID );

		// If there's a valid file root, handle extra stuff. This should not get done when the FileRoot is invalid.
		if( $this->_FileRoot )
		{
			$this->_rdfp_rel_path = trim( str_replace( '\\', '/', $rdfp_rel_path ), '/' );
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
	 * Get this class db table config params
	 *
	 * @return array
	 */
	static function get_class_db_config()
	{
		static $file_db_config;

		if( !isset( $file_db_config ) )
		{
			$file_db_config = array_merge( parent::get_class_db_config(),
				array(
					'dbtablename'        => 'T_files',
					'dbprefix'           => 'file_',
					'dbIDname'           => 'file_ID',
				)
			);
		}

		return $file_db_config;
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table' => 'T_links', 'fk' => 'link_file_ID', 'field' => 'link_itm_ID', 'msg' => T_('%d linked items'),
					'class'=>'Link', 'class_path'=>'links/model/_link.class.php' ),
				array( 'table' => 'T_links', 'fk' => 'link_file_ID', 'field' => 'link_cmt_ID', 'msg' => T_('%d linked comments'),
					'class'=>'Link', 'class_path'=>'links/model/_link.class.php' ),
				array( 'table' => 'T_links', 'fk' => 'link_file_ID', 'field' => 'link_usr_ID', 'msg' => T_('%d linked users (profile pictures)'),
					'class'=>'Link', 'class_path'=>'links/model/_link.class.php' ),
			);
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
			if( is_null( $row ) )
			{	// No DB data has been provided:
				$row = $DB->get_row( "
					SELECT * FROM T_files
					 WHERE file_path_hash = ".$DB->quote( md5( $this->_FileRoot->type.$this->_FileRoot->in_type_ID.$this->_rdfp_rel_path, true ) ),
					OBJECT, 0, 'Load file meta data' );
			}

			// We check that we got something AND that the CASE matches (because of case insensitive collations on MySQL)
			if( $row && $row->file_path == $this->_rdfp_rel_path )
			{ // We found meta data
				$Debuglog->add( "Loaded metadata for {$this->_FileRoot->ID}:{$this->_rdfp_rel_path}", 'files' );
				$this->meta  = 'loaded';
				$this->ID    = $row->file_ID;
				$this->creator_user_ID = $row->file_creator_user_ID;
				$this->type  = $row->file_type;
				$this->title = $row->file_title;
				$this->alt   = $row->file_alt;
				$this->desc  = $row->file_desc;
				if( isset( $row->file_hash ) )
				{
					$this->hash  = $row->file_hash;
				}
				$this->path_hash = $row->file_path_hash;
				if( isset( $row->file_can_be_main_profile ) )
				{
					$this->can_be_main_profile = $row->file_can_be_main_profile;
				}
				if( isset( $row->file_download_count ) )
				{
					$this->download_count = $row->file_download_count;
				}

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
			$syslog_message = $success ?
					'Folder %s was created' :
					sprintf( 'Folder %s could not be created', '<b>'.$this->_adfp_full_path.'</b>' );
		}
		else
		{ // Create an empty file:
			$success = @touch( $this->_adfp_full_path );
			$this->_is_dir = false; // used by chmod
			$syslog_message = $success ?
					'File %s was created' :
					sprintf( 'File %s could not be created', '<b>'.$this->_adfp_full_path.'</b>' );
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

		syslog_insert( sprintf( $syslog_message, '[['.$this->get_name().']]' ), 'info', 'file', $this->ID );

		return $success;
	}


	/**
	 * Initializes or refreshes file properties (type, size, perms...)
	 */
	function load_properties()
	{
		// Unset values that will be determined (and cached) upon request
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
	 * Is the File a directory or file?
	 *
	 * @param string String to return if the File is a directory
	 * @param string String to return if the File is a file
	 * @return string specified directory string if the object is a directory, specified file string if not
	 */
	function dir_or_file( $dir_string = NULL, $file_string = NULL)
	{
		if( is_null( $dir_string ) )
		{
			$dir_string = T_('directory');
		}

		if( is_null( $file_string ) )
		{
			$file_string = T_('file');
		}

		return $this->is_dir() ? $dir_string : $file_string;
	}

	/**
	 * Get file type
	 *
	 * @return string file type
	 */
	function get_file_type()
	{
		if( empty( $this->type ) )
		{ // Detect and set file type
			$this->set_file_type();
		}

		return $this->type;
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
		if( empty( $this->type ) )
		{ // Detect and set file type
			$this->set_file_type();
		}

		return ( $this->type == 'image' );
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
		if( empty( $this->type ) )
		{ // Detect and set file type
			$this->set_file_type();
		}

		return ( $this->type == 'audio' );
	}


	/**
	 * Is the File a video file?
	 *
	 * Tries to determine if it is and caches the info.
	 *
	 * @return boolean true if the object is a video file, false if not
	 */
	function is_video()
	{
		if( empty( $this->type ) )
		{
			$this->set_file_type();
		}

		return ( $this->type == 'video' );
	}


	/**
	 * Is the file editable?
	 *
	 * @param mixed true/false allow locked file types? NULL value means that FileType will decide
	 */
	function is_editable( $allow_locked = NULL )
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

		// user can edit only allowed file types
		return $Filetype->is_allowed( $allow_locked );
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
	 * Get file creator
	 *
	 * @return object User
	 */
	function get_creator()
	{
		if( $this->creator_user_ID )
		{
			$UserCache = & get_UserCache();
			$creator = $UserCache->get_by_ID( $this->creator_user_ID );

			return $creator;
		}
		else
		{
			return NULL;
		}
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
		global $public_access_to_media;

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
	 *
	 * @param array Params
	 * @return string img tag
	 */
	function get_icon( $params = array() )
	{
		$params = array_merge( array(
				'alt'   => '#',
				'title' => '#',
			), $params );

		if( $this->is_dir() )
		{ // Directory icon:
			$icon = 'folder';
		}
		else
		{
			$Filetype = & $this->get_Filetype();
			if( isset( $Filetype->icon ) && $Filetype->icon )
			{ // Return icon for known type of the file
				return $Filetype->get_icon( $params );
			}
			else
			{ // Icon for unknown file type:
				$icon = 'file_unknown';
			}
		}

		// Return Icon for a directory or unknown type file:
		return get_icon( $icon, 'imgtag', array(
				'alt'   => $params['alt'] == '#' ? $this->get_ext() : $params['alt'],
				'title' => $params['title'] == '#' ? $this->get_type() : $params['title']
			) );
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
	 * @param string
	 * @param string
	 * @param string rel attribute of link, usefull for jQuery libraries selecting on rel='...', e-g: lighbox
	 * @param string image class
	 * @param string image align
	 * @param string image rel
	 * @param string image caption/description
	 * @param integer Link ID
	 * @param integer Size multiplier, can be 1, 2 and etc. (Used for b2evonet slider for example)
	 *                Example: $image_size_x = 2 returns 2 img tags:
	 *                          <img src="crop-480x320.jpg" />
	 *                          <img src="crop-480x320.jpg" data-original="crop-960x640.jpg" class="image-2x" />
	 * @param string Override "width" & "height" attributes on img tag. Allows to increase pixel density for retina/HDPI screens.
	 *               Example: ( $tag_size = '160' ) => width="160" height="160"
	 *                        ( $tag_size = '160x320' ) => width="160" height="320"
	 *                        NULL - use size defined by the thumbnail
	 *                        'none' - don't use attributes "width" & "height"
	 */
	function get_tag( $before_image = '<div class="image_block">',
	                  $before_image_legend = '<div class="image_legend">', // can be NULL
	                  $after_image_legend = '</div>',
	                  $after_image = '</div>',
	                  $size_name = 'original',
	                  $image_link_to = 'original',
	                  $image_link_title = '',	// can be text or #title# or #desc#
	                  $image_link_rel = '',
	                  $image_class = '',
	                  $image_align = '',
	                  $image_alt = '',
	                  $image_desc = '#',
	                  $image_link_id = '',
	                  $image_size_x = 1,
	                  $tag_size = NULL )
	{
		if( $this->is_dir() )
		{ // We can't reference a directory
			return '';
		}

		$this->load_meta();

		if( $this->is_image() )
		{ // Make an IMG link:
			$r = $before_image;

			$x_sizes = array( 1 ); // Standard ratio size = 1x
			$image_size_x = intval( $image_size_x );
			if( $image_size_x > 1 )
			{ // Additional ratio size
				$x_sizes[] = $image_size_x;
			}

			$img = '';
			foreach( $x_sizes as $x_size )
			{
				$img_attribs = $this->get_img_attribs( $size_name, NULL, NULL, $x_size, $tag_size );

				if( $this->check_image_sizes( $size_name, 64, $img_attribs ) )
				{ // If image larger than 64x64 add class to display animated gif during loading
					$image_class = trim( $image_class.' loadimg' );
				}

				$image_class_attr = $image_class;
				if( $x_size > 1 )
				{ // Add class to detect what image is resized for speacial ratio
					$image_class_attr = trim( $image_class_attr.' image-'.$x_size.'x' );
				}

				if( $image_class_attr != '' )
				{ // Image class
					$img_attribs['class'] = $image_class_attr;
				}

				if( $image_align != '' )
				{ // Image align
					$img_attribs['align'] = $image_align;
				}

				if( $img_attribs['alt'] == '' )
				{ // Image alt
					$img_attribs['alt'] = $image_alt;
				}

				// Image tag
				$img .= '<img'.get_field_attribs_as_string( $img_attribs ).' />';
			}

			if( $image_link_to == 'original' )
			{ // special case
				$image_link_to = $this->get_url();
			}
			if( !empty( $image_link_to ) )
			{
				$a = '<a href="'.$image_link_to.'"';

				if( $image_link_title == '#title#' )
					$image_link_title = $this->title;
				elseif( $image_link_title == '#desc#' )
					$image_link_title = $this->desc;

				if( !empty($image_link_title) )
				{
					$a .= ' title="'.htmlspecialchars($image_link_title).'"';
				}
				if( !empty($image_link_rel) )
				{
					$a .= ' rel="'.htmlspecialchars($image_link_rel).'"';
				}
				if( !empty( $image_link_id ) )
				{ // Set attribute "id" for link
					$a .= ' id="'.$image_link_id.'"';
				}
				$img = $a.'>'.$img.'</a>';
			}
			$r .= $img;

			if( $image_desc == '#' )
			{
				$image_desc = $this->dget( 'desc' );
			}
			if( !empty( $image_desc ) && !is_null( $before_image_legend ) )
			{
				$r .= $before_image_legend
							.nl2br( $image_desc ) // If this needs to be changed, please document.
							.$after_image_legend;
			}
			$r .= $after_image;
		}
		else
		{ // Make an A HREF link:
			$r = '<a href="'.$this->get_url().'"'
						// title
						.( $this->get('desc') ? ' title="'.$this->dget('desc', 'htmlattr').'"' : '' ).'>'
						// link text
						.( $this->get('title') ? $this->dget('title') : $this->dget('name') ).'</a>';
		}

		return $r;
	}


	/*
	 * Get gallery for code for a directory
	 *
	 * @param array of params
	 * @return string gallery HTML code
	 */
	function get_gallery( $params )
	{
		$params = array_merge( array(
				'before_gallery'        => '<div class="bGallery">',
				'after_gallery'         => '</div>',
				'gallery_table_start'   => '',//'<table cellpadding="0" cellspacing="3" border="0" class="image_index">',
				'gallery_table_end'     => '',//'</table>',
				'gallery_row_start'     => '',//"\n<tr>",
				'gallery_row_end'       => '',//"\n</tr>",
				'gallery_cell_start'    => '<div class="evo_post_gallery__image">',//"\n\t".'<td valign="top"><div class="bGallery-thumbnail">',
				'gallery_cell_end'      => '</div>',//'</div></td>',
				'gallery_image_size'    => 'crop-80x80',
				'gallery_image_limit'   => 1000,
				'gallery_colls'         => 5,
				'gallery_order'         => '', // 'ASC', 'DESC', 'RAND'
				'gallery_link_rel'      => '#', // '#' - Use default 'lightbox[g'.$this->ID.']' to make one "gallery" per directory
			), $params );

		if( ! $this->is_dir() )
		{	// Not a directory
			return '';
		}
		if( ! $FileList = $this->get_gallery_images( $params['gallery_image_limit'], $params['gallery_order'] ) )
		{	// No images in this directory
			return '';
		}

		$r = $params['before_gallery'];
		$r .= $params['gallery_table_start'];

		$count = 0;
		foreach( $FileList as $l_File )
		{
			// We're linking to the original image, let lighbox (or clone) quick in:
			$link_title = '#title#'; // This title will be used by lightbox (colorbox for instance)
			if( $params['gallery_link_rel'] == '#' )
			{	// Make one "gallery" per directory:
				$params['gallery_link_rel'] = 'lightbox[g'.$this->ID.']';
			}

			$img_tag = $l_File->get_tag( '', NULL, '', '', $params['gallery_image_size'], 'original', $link_title, $params['gallery_link_rel'] );

			if( $count % $params['gallery_colls'] == 0 ) $r .= $params['gallery_row_start'];
			$count++;

			$r .= $params['gallery_cell_start'];
			$r .= $img_tag;
			$r .= $params['gallery_cell_end'];

			if( $count % $params['gallery_colls'] == 0 ) $r .= $params['gallery_row_end'];
		}
		if( $count && ( $count % $params['gallery_colls'] != 0 ) ) $r .= $params['gallery_row_end'];

		$r .= $params['gallery_table_end'];
		$r .= $params['after_gallery'];

		return $r;
	}


	/*
	 * Get all images in a directory (no recursion)
	 *
	 * @param integer how many images to return
	 * @param string filenames order ASC DESC RAND or empty string
	 * @return array of instantiated File objects or false
	 */
	function get_gallery_images( $limit = 1000, $order = '' )
	{
		if( $filenames = $this->get_directory_files('relative') )
		{
			$FileCache = & get_FileCache();

			switch( strtoupper($order) )
			{
				case 'ASC':
					sort($filenames);
					break;

				case 'DESC':
					rsort($filenames);
					break;

				case 'RAND':
					shuffle($filenames);
					break;
			}

			$i = 1;
			foreach( $filenames as $filename )
			{
				if( $i > $limit )
				{	// We've got enough images
					break;
				}

				/*
				sam2kb> TODO: we may need to filter files by extension first, it doesn't make sence
						to query the database for every single .txt or .zip file.
						The best solution would be to have file MIME type field in DB
				*/
				$l_File = & $FileCache->get_by_root_and_path( $this->_FileRoot->type, $this->_FileRoot->in_type_ID, $filename );
				$l_File->load_meta();

				if( ! $l_File->is_image() )
				{	// Not an image
					continue;
				}
				$Files[] = $l_File;

				$i++;
			}
			if( !empty($Files) )
			{
				return $Files;
			}
		}
		return false;
	}


	/*
	 * Get all files in a directory (no recursion)
	 *
	 * @param string what part of file name to return
	 *		'basename' return file name only e.g. 'bus-stop-ahead.jpg'
	 * 		'ralative' file path relative to '_adfp_full_path' e.g. 'monument-valley/bus-stop-ahead.jpg'
	 *		'absolute' full file path e.g. '/home/user/html/media/shared/global/monument-valley/bus-stop-ahead.jpg'
	 * @return array of files
	 */
	function get_directory_files( $path_type = 'relative' )
	{
		global $Settings;

		$path = trailing_slash( $this->_adfp_full_path );

		if( $dir = @opendir($path) )
		{	// Scan directory and list all files
			$filenames = array();
			while( ($file = readdir($dir)) !== false )
			{
				if( $file == '.' || $file == '..' || $file == $Settings->get('evocache_foldername') )
				{	// Invalid file
					continue;
				}

				// sam2kb> TODO: Do we need to process directories recursively?
				if( ! is_dir($path.$file) )
				{
					switch( $path_type )
					{
						case 'basename':
							$filenames[] = $file;
							break;

						case 'relative':
							$filenames[] = trailing_slash($this->_rdfp_rel_path).$file;
							break;

						case 'absolute':
							$filenames[] = $path.$file;
							break;
					}
				}
			}
			closedir($dir);

			if( !empty($filenames) )
			{
				return $filenames;
			}
		}
		return false;
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
		$old_file_name = $this->get_name();

		// rename() will fail if newname already exists on windows
		// if it doesn't work that way on linux we need the extra check below
		// but then we have an integrity issue!! :(
		if( file_exists($this->_dir.$newname) )
		{
			syslog_insert( sprintf( 'File %s could not be renamed to %s', '[['.$old_file_name.']]', '[['.$newname.']]' ), 'info', 'file', $this->ID );
			return false;
		}

		global $DB;
		$DB->begin();

		$oldname = $this->get_name();

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
				$temp_File->modify_path ( $rel_dir, $full_dir );
			}
		}

		if( ! @rename( $this->_adfp_full_path, $this->_dir.$newname ) )
		{ // Rename will fail if $newname already exists (at least on windows)
			syslog_insert( sprintf( 'File %s could not be renamed to %s', '[['.$old_file_name.']]', '[['.$newname.']]' ), 'info', 'file', $this->ID );
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
			{	// Update failed, try to rollback the rename on disk:
				if( ! @rename( $this->_adfp_full_path, $this->_dir.$oldname ) )
				{ // rename failed
					$DB->rollback();
					syslog_insert( sprintf( 'File %s could not be renamed to %s', '[['.$old_file_name.']]', '[['.$newname.']]' ), 'info', 'file', $this->ID );
					return false;
				}
				// Maybe needs a specific error message here, the db and the disk is out of sync
				syslog_insert( sprintf( 'File %s could not be renamed to %s', '[['.$old_file_name.']]', '[['.$newname.']]' ), 'info', 'file', $this->ID );
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

		syslog_insert( sprintf( 'File %s was renamed to %s', '[['.$old_file_name.']]', '[['.$newname.']]' ), 'info', 'file', $this->ID );
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
		$old_file_name = $this->get_name();
		// echo "relpath= $rel_path ";

		$rdfp_rel_path = str_replace( '\\', '/', $rdfp_rel_path );
		$FileRootCache = & get_FileRootCache();

		$new_FileRoot = & $FileRootCache->get_by_type_and_ID( $root_type, $root_ID, true );
		$adfp_posix_path = $new_FileRoot->ads_path.$rdfp_rel_path;

		if( ! @rename( $this->_adfp_full_path, $adfp_posix_path ) )
		{
			syslog_insert( sprintf( 'File %s could not be moved to %s', '[['.$old_file_name.']]', '[['.$rdfp_rel_path.']]' ), 'info', 'file', $this->ID );
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

		syslog_insert( sprintf( 'File %s was moved to %s', '[['.$old_file_name.']]', '[['.$rdfp_rel_path.']]' ), 'info', 'file', $this->ID );
		return true;
	}


 	/**
	 * Copy this file/folder to a new location
	 *
	 * Also copy meta data in Object
	 *
	 * @param object File the target file (expected to not exist)
	 * @return boolean true on success, false on failure
	 */
	function copy_to( & $dest_File )
	{
		if( ! $this->exists() || $dest_File->exists() )
		{
			syslog_insert( sprintf( 'File %s could not be copied', '[['.$this->get_name().']]' ), 'info', 'file', $this->ID );
			return false;
		}

		// TODO: fp> what happens if someone else creates the destination file right at this moment here?
		//       dh> use a locking mechanism.

		$new_folder_name = $this->is_dir() ? '' : NULL;
		if( ! copy_r( $this->get_full_path(), $dest_File->get_full_path(), $new_folder_name ) )
		{	// Note: unlike rename() (at least on Windows), copy() will not fail if destination already exists
			// this is probably a permission problem
			syslog_insert( sprintf( 'File %s could not be copied to %s', '[['.$this->get_name().']]', '[['.$dest_File->get_name().']]' ), 'info', 'file', $this->ID );
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

		syslog_insert( sprintf( 'File %s was copied to %s', '[['.$this->get_name().']]', '[['.$dest_File->get_name().']]' ), 'info', 'file', $this->ID );
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
	function unlink( $use_transactions = true )
	{
		global $DB;

		$old_file_ID = $this->ID;
		$old_file_name = $this->get_name();

		if( $use_transactions )
		{
			$DB->begin();
		}

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
			$unlinked = @rmdir( $this->_adfp_full_path );
			$syslog_message = $unlinked ?
					'Folder %s was deleted' :
					'Folder %s could not be deleted';
		}
		else
		{
			$unlinked = @unlink( $this->_adfp_full_path );
			$syslog_message = $unlinked ?
					'File %s was deleted' :
					'File %s could not be deleted';
		}

		$file_exists = file_exists( $this->_adfp_full_path );
		if( ! $unlinked && ! $file_exists )
		{ // Add additional message which shows that unlink was unsuccesful becuse the file didn't exist
			$syslog_message .= ' - not exists';
		}

		syslog_insert( sprintf( $syslog_message, '[['.$old_file_name.']]' ), 'info', 'file', $old_file_ID );

		if( $file_exists )
		{
			if( $use_transactions )
			{
				$DB->rollback();
			}
			return false;
		}

		$this->_exists = false;

		if( $use_transactions )
		{
			$DB->commit();
		}

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

			syslog_insert( sprintf( 'The permissions of file %s were changed to %s', '[['.$this->get_full_path().']]', $chmod ), 'info', 'file', $this->ID );
			return $this->_perms;
		}
		else
		{
			syslog_insert( sprintf( 'The permissions of file %s could not be changed to %s', '[['.$this->get_full_path().']]', $chmod ), 'info', 'file', $this->ID );
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
		global $Debuglog, $current_User;

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
		$this->set_param( 'creator_user_ID', 'integer', $current_User->ID );
		$this->set_param( 'root_type', 'string', $this->_FileRoot->type );
		$this->set_param( 'root_ID', 'number', $this->_FileRoot->in_type_ID );
		$this->set_param( 'path', 'string', $this->_rdfp_rel_path );
		$this->set_param( 'path_hash', 'string', md5( $this->_FileRoot->type.$this->_FileRoot->in_type_ID.$this->_rdfp_rel_path, true ) );
		if( ! $this->is_dir() )
		{ // create hash value only for files but not for folders
			$this->set_param( 'hash', 'string', md5_file( $this->get_full_path(), true ) );
		}

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
	function dbupdate()
	{
		if( $this->meta == 'unknown' )
		{
			debug_die( 'cannot update File if meta data has not been checked before' );
		}

		global $DB;

		$DB->begin();

		$file_path_hash = md5( $this->_FileRoot->type.$this->_FileRoot->in_type_ID.$this->_rdfp_rel_path, true );
		if( $file_path_hash != $this->path_hash )
		{ // The file path was changed
			$this->set_param( 'path_hash', 'string', $file_path_hash );
		}
		// Let parent do the update:
		if( ( $r = parent::dbupdate() ) !== false )
		{
			// Update field 'last_touched_ts' of each item that has a link with this edited file
			$LinkCache = & get_LinkCache();
			$links = $LinkCache->get_by_file_ID( $this->ID );
			foreach( $links as $Link )
			{
				$LinkOwner = & $Link->get_LinkOwner();
				if( $LinkOwner != NULL )
				{
					$LinkOwner->update_last_touched_date();
				}
			}

			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $r;
	}


	/**
	 * Get URL to view the file (either with viewer of with browser, etc...)
	 */
	function get_view_url( $always_open_dirs_in_fm = true )
	{
		global $public_access_to_media;

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
					return  get_htsrv_url().'viewfile.php?root='.$root_ID.'&amp;path='.$this->_rdfp_rel_path.'&amp;viewtype=image';

				case 'text':
					return get_htsrv_url().'viewfile.php?root='.$root_ID.'&amp;path='.$this->_rdfp_rel_path.'&amp;viewtype=text';

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
	 *
	 * @param string|NULL Text of the link
	 * @param string|NULL Title of the link
	 * @param string|NULL Text when user has no access for this file
	 * @param string Format for text of the link: $text$
	 * @param string Class name of the link
	 * @param string URL
	 * @return string Link tag
	 */
	function get_view_link( $text = NULL, $title = NULL, $no_access_text = NULL, $format = '$text$', $class = '', $url = NULL )
	{
		global $Blog;

		if( is_null( $text ) )
		{ // Use file root+relpath+name by default
			$text = ( $this->is_dir() ? $this->get_root_and_rel_path() : $this->get_root_and_rel_path() );
		}

		if( is_null( $title ) )
		{ // Default link title
			$this->load_meta();
			$title = $this->title;
		}

		if( is_null( $no_access_text ) )
		{ // Default text when no access:
			$no_access_text = $text;
		}

		if( is_null( $url ) )
		{ // Get the URL for viewing the file/dir:
			$url = ( $this->is_dir() ? $this->get_linkedit_url() : $this->get_view_url( false ) );
			$ignore_popup = false;
		}
		else
		{ // Ignore a popup window when URL is defined from param
			$ignore_popup = true;
		}

		if( empty( $url ) )
		{ // Display this text when current user has no access
			return $no_access_text;
		}

		// Replace a mask in the link text
		$text = str_replace( '$text$', $text, $format );

		// Init an attribute for class
		$class_attr = empty( $class ) ? '' : ' class="'.$class.'"';

		// rel="nofollow"
		$rel_attr = ( ! empty( $Blog ) && $Blog->get_setting( 'download_nofollowto' ) ) ? ' rel="nofollow"' : '';

		$Filetype = & $this->get_Filetype();
		if( $this->is_dir() || $ignore_popup || ( $Filetype && in_array( $Filetype->viewtype, array( 'external', 'download' ) ) ) )
		{ // Link to open in the curent window
			return '<a href="'.$url.'" title="'.$title.'"'.$class_attr.$rel_attr.'>'.$text.'</a>';
		}
		else
		{ // Link to open in a new window
			$target = 'evo_fm_'.$this->get_md5_ID();

			// onclick: we unset target attrib and return the return value of pop_up_window() to make the browser not follow the regular href link (at least FF 1.5 needs the target reset)
			return '<a href="'.$url.'" target="'.$target.'"
				title="'.T_('Open in a new window').'" onclick="'
				."this.target = ''; return pop_up_window( '$url', '$target', "
				.(( $width = $this->get_image_size( 'width' ) ) ? ( $width + 100 ) : 750 ).', '
				.(( $height = $this->get_image_size( 'height' ) ) ? ( $height + 150 ) : 550 ).' )"'
				.$class_attr.$rel_attr.'>'
				.$text.'</a>';
		}
	}


	/**
	 * Get link to edit linked file.
	 *
	 * @param string link type ( item, comment )
	 * @param integer ID of the object to link to => will open the FM in link mode
	 * @param string link text
	 * @param string link title
	 * @param string text to display if access denied
	 * @param string page url for the edit action
	 */
	function get_linkedit_link( $link_type = NULL, $link_obj_ID = NULL, $text = NULL, $title = NULL, $no_access_text = NULL,
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

		$url = $this->get_linkedit_url( $link_type, $link_obj_ID, $actionurl );

		if( !empty($target) )
		{
			$target = ' target="'.$target.'"';
		}

		return '<a href="'.$url.'" title="'.$title.'"'.$target.'>'.$text.'</a>';
	}


	/**
	 * Get link edit url for a link object
	 *
	 * @param string link type ( item, comment )
	 * @param integer ID of link object to link to => will open the FM in link mode
	 * @return string
	 */
	function get_linkedit_url( $link_type = NULL, $link_obj_ID = NULL, $actionurl = '#' )
	{
		global $dispatcher;

		if( $actionurl == '#' )
		{
			$actionurl = $dispatcher.'?ctrl=files';
		}

		if( $this->is_dir() )
		{
			$rdfp_path = $this->get_rdfp_rel_path();
		}
		else
		{
			$rdfp_path = dirname( $this->get_rdfp_rel_path() );
		}

		$url_params = 'root='.$this->get_FileRoot()->ID.'&amp;path='.$rdfp_path.'/';

		if( ! is_null($link_obj_ID) )
		{ // We want to open the filemanager in link mode:
			$url_params .= '&amp;fm_mode=link_object&amp;link_type='.$link_type.'&amp;link_object_ID='.$link_obj_ID;
		}

		// Add param to make the file list highlight this (via JS).
		$url_params .= '&amp;fm_highlight='.rawurlencode( $this->get_name() );

		$url = url_add_param( $actionurl, $url_params );

		return $url;
	}


	/**
	 * Get the thumbnail URL for this file
	 *
	 * @param string Thumbnail size name
	 * @param string Glue between url params
	 * @param integer Ratio size, can be 1, 2 and etc.
	 * @return string Thumbnail URL
	 */
	function get_thumb_url( $size_name = 'fit-80x80', $glue = '&amp;', $size_x = 1 )
	{
		global $public_access_to_media;

		if( ! $this->is_image() )
		{ // Not an image
			debug_die( 'Can only thumb images');
		}

		if( $public_access_to_media )
		{
			$af_thumb_path = $this->get_af_thumb_path( $size_name, NULL, false, $size_x );
			if( $af_thumb_path[0] != '!' )
			{ // If the thumbnail was already cached, we could publicly access it:
				if( @is_file( $af_thumb_path ) )
				{	// The thumb IS already in cache! :)
					// Let's point directly into the cache:
					global $Settings;
					// Get the relative dirpath of this file to use in the url
					$rdfp_dirname = dirname( $this->_rdfp_rel_path );
					$rdfp_dirpath = ( $rdfp_dirname == '.' ) ? '' : $rdfp_dirname.'/';
					$url = $this->_FileRoot->ads_url.$rdfp_dirpath.$Settings->get( 'evocache_foldername' ).'/'.$this->_name.'/'.$this->get_thumb_name( $size_name, $size_x ).'?mtime='.$this->get_lastmod_ts();
					$url = str_replace( '\/', '', $url ); // Fix incorrect path
					return $url;
				}
			}
		}

		// No thumbnail available (at least publicly), we need to go through getfile.php!
		$url = $this->get_getfile_url( $glue ).$glue.'size='.$size_name.( $size_x != 1 ? $glue.'size_x='.$size_x : '' );

		return $url;
	}


	/**
	 * Get the URL to access a file through getfile.php.
	 * @return string
	 */
	function get_getfile_url( $glue = '&amp;' )
	{
		return get_htsrv_url().'getfile.php/'
			// This is for clean 'save as':
			.rawurlencode( $this->_name )
			// This is for locating the file:
			.'?root='.$this->_FileRoot->ID.$glue.'path='.$this->_rdfp_rel_path
			.$glue.'mtime='.$this->get_lastmod_ts(); // TODO: dh> use salt here?!
	}


	/**
	 * Generate the IMG THUMBNAIL tag with all the alt & title if available.
	 *
	 * @param string Size
	 * @param string Class
	 * @param string Alignment
	 * @param string Title
	 * @param string Change size of the attributes "width" & "height".
	 *               Example: ( $tag_size = '160' ) => width="160" height="160"
	 *                        ( $tag_size = '160x320' ) => width="160" height="320"
	 *                        NULL - use real size
	 * @return string
	 */
	function get_thumb_imgtag( $size_name = 'fit-80x80', $class = '', $align = '', $title = '', $tag_size = NULL )
	{
		global $use_strict;

		if( ! $this->is_image() )
		{ // Not an image
			return '';
		}

		$img_attribs = $this->get_img_attribs( $size_name, $title, NULL, 1, $tag_size );

		if( $this->check_image_sizes( $size_name, 64, $img_attribs ) )
		{ // If image larger than 64x64 add class to display animated gif during loading
			$class = trim( $class.' loadimg' );
		}

		if( $class )
		{ // add class
			$img_attribs['class'] = $class;
		}

		if( !$use_strict && $align )
		{ // add align
			$img_attribs['align'] = $align;
		}

		return '<img'.get_field_attribs_as_string( $img_attribs ).' />';
	}


	/**
	 * Calculate what sizes are used for thumbnail really
	 *
	 * @param string Thumbnail size name
	 * @return boolean|array FALSE on wrong size name OR Array with keys: 0 - width, 1 - height
	 */
	function get_thumb_size( $size_name = 'fit-80x80' )
	{
		global $thumbnail_sizes;

		if( ! isset( $thumbnail_sizes[ $size_name ] ) )
		{ // Wrong thumbnail size name
			return false;
		}

		$thumb_type = $thumbnail_sizes[ $size_name ][0];
		$thumb_width = $thumbnail_sizes[ $size_name ][1];
		$thumb_height = $thumbnail_sizes[ $size_name ][2];

		load_funcs('files/model/_image.funcs.php');

		list( $orig_width, $orig_height ) = $this->get_image_size( 'widthheight' );

		if( check_thumbnail_sizes( $thumb_type, $thumb_width, $thumb_height, $orig_width, $orig_height ) )
		{ // Use the sizes of the original image
			$width = $orig_width;
			$height = $orig_height;
		}
		else
		{ // Calculate the sizes depending on thumbnail type
			if( $thumb_type == 'fit' )
			{
				list( $width, $height ) = scale_to_constraint( $orig_width, $orig_height, $thumb_width, $thumb_height );
			}
			else
			{ // crop & crop-top
				$width = $thumb_width;
				$height = $thumb_height;
			}
		}

		return array( $width, $height );
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
	 * @param string Title img attribute
	 * @param string Alt img attribute
	 * @param integer Ratio size, can be 1, 2 and etc.
	 * @param string Change size of the attributes "width" & "height".
	 *               Example: ( $tag_size = '160' ) => width="160" height="160"
	 *                        ( $tag_size = '160x320' ) => width="160" height="320"
	 *                        NULL - use real size
	 *                        'none' - don't use attributes "width" & "height"
	 * @return array List of HTML attributes for the image.
	 */
	function get_img_attribs( $size_name = 'fit-80x80', $title = NULL, $alt = NULL, $size_x = 1, $tag_size = NULL )
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
			if( $tag_size != 'none' )
			{	// Add attributes "width" & "height" only when they are not disabled:
				if( ( $size_arr = $this->get_image_size( 'widthheight_assoc' ) ) )
				{
					$img_attribs += $size_arr;
				}
			}
		}
		else
		{ // We want src to link to a thumbnail
			$img_attribs['src'] = $this->get_thumb_url( $size_name, '&', $size_x );
			if( $tag_size != 'none' )
			{	// Add attributes "width" & "height" only when they are not disabled:
				$thumb_path = $this->get_af_thumb_path( $size_name, NULL, true );
				if( $tag_size !== NULL )
				{ // Change size values
					$tag_size = explode( 'x', $tag_size );
					$img_attribs['width'] = $tag_size[0];
					$img_attribs['height'] = empty( $tag_size[1] ) ? $tag_size[0] : $tag_size[1];
				}
				elseif( substr( $thumb_path, 0, 1 ) != '!'
					&& ( $size_arr = imgsize( $thumb_path, 'widthheight_assoc' ) ) )
				{	// no error, add width and height attribs
					$img_attribs += $size_arr;
				}
				elseif( $thumb_sizes = $this->get_thumb_size( $size_name ) )
				{	// Get sizes of the generated thumbnail:
					$img_attribs['width'] = $thumb_sizes[0];
					$img_attribs['height'] = $thumb_sizes[1];
				}
			}
		}

		return $img_attribs;
	}


	/**
	 * Displays a preview thumbnail which is clickable and opens a view popup
	 *
	 * @param string what do do with files that are not images? 'fulltype'
	 * @param array colorbox plugin params:
	 *   - 'init': set true to init colorbox plugin for images and show preview thumb in colorbox
	 *   - 'lightbox_rel': set a specific group id string if the displayed image belongs to a group of images
	 *   - 'link_id': this must be set only if the displayed file belongs to a Link object
	 * @return string HTML to display
	 */
	function get_preview_thumb( $format_for_non_images = '', $cbox_params = array() )
	{
		if( $this->is_image() )
		{	// Ok, it's an image:
			$type = $this->get_type();
			$size_name = 'fit-80x80';
			$img_attribs = $this->get_img_attribs( $size_name, $type, $type );

			if( $this->check_image_sizes( $size_name, 64, $img_attribs ) )
			{ // If image larger than 64x64 add class to display animated gif during loading
				$img_attribs['class'] = 'loadimg';
			}

			$img = '<img'.get_field_attribs_as_string( $img_attribs ).' />';

			$cbox_params = array_merge( array(
					'init' => false,
					'lightbox_rel' => 'lightbox',
					'link_id' => 'f'.$this->ID
				), $cbox_params );
			if( $cbox_params['init'] )
			{ // Create link to preview image by colorbox plugin
				$link = '<a href="'.$this->get_url().'" rel="'.$cbox_params['lightbox_rel'].'" id="'.$cbox_params['link_id'].'">'.$img.'</a>';
			}
			else
			{ // Get link to view the file (fallback to no view link - just the img):
				$link = $this->get_view_link( $img );
			}

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
	 * Check if thumbnail of image has width & height more than $min_size param
	 *
	 * @param string Size name of thumbnail
	 * @param integer Min size
	 * @param array img attributes: 'width' 'height'
	 * @return boolean TRUE
	*/
	function check_image_sizes( $thumb_size, $min_size = 64, $img_attribs = array() )
	{
		if( ! $this->is_image() )
		{ // Only for images
			return false;
		}

		if( isset( $img_attribs['width'], $img_attribs['height'] ) )
		{ // If image larger than 64x64 add class to display animated gif during loading
			return ( $img_attribs['width'] > $min_size && $img_attribs['height'] > $min_size );
		}

		global $thumbnail_sizes;

		if( isset( $thumbnail_sizes[ $thumb_size ] ) )
		{ // If thumb size name is defined we can calculate what sizes will be of the thumbnail image
			$thumb_type = $thumbnail_sizes[ $thumb_size ][0];
			$thumb_width = $thumbnail_sizes[ $thumb_size ][1];
			$thumb_height = $thumbnail_sizes[ $thumb_size ][2];
			if( $thumb_type == 'crop' || $thumb_type == 'crop-top' )
			{ // When thumbnail has "crop" format - width and height have the same values
				if( $thumb_width > $min_size && $thumb_height > $min_size )
				{ // Only check if they are more than $min_size
					return true;
				}
			}
			elseif( $thumb_type == 'fit' )
			{ // Calculate what height will be for the generated thumbnail image
				$orig_sizes = $this->get_image_size( 'widthheight_assoc' );
				if( isset( $orig_sizes['width'], $orig_sizes['height'] ) )
				{
					$ratio = $orig_sizes['width'] / $orig_sizes['height'];
					$result_height = $thumb_height / $ratio;
					if( $thumb_width > $min_size && $result_height > $min_size )
					{ // Width & height of the generated thumbnail image will be more than $min_size
						return true;
					}
				}
			}
		}

		return false;
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
	 * @param integer Ratio size, can be 1, 2 and etc.
	 * @return string absolute filename or !error
	 */
	function get_af_thumb_path( $size_name, $thumb_mimetype = NULL, $create_evocache_if_needed = false, $size_x = 1 )
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
			return $ads_evocache.$this->get_thumb_name( $size_name, $size_x );
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
	 * @param integer JPEG image quality
	 * @param integer Ratio size, can be 1, 2 and etc.
	 */
	function save_thumb_to_cache( $thumb_imh, $size_name, $thumb_mimetype, $thumb_quality = 90, $size_x = 1 )
	{
		global $Plugins;

		$Plugins->trigger_event( 'BeforeThumbCreate', array(
			  'imh' => & $thumb_imh,
			  'size' => & $size_name,
			  'size_x' => & $size_x,
			  'mimetype' => & $thumb_mimetype,
			  'quality' => & $thumb_quality,
			  'File' => & $this,
			  'root_type' => $this->_FileRoot->type,
			  'root_type_ID' => $this->_FileRoot->in_type_ID,
		  ) );

		$af_thumb_path = $this->get_af_thumb_path( $size_name, $thumb_mimetype, true, $size_x );
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
	 * @param integer Ratio size, can be 1, 2 and etc.
	 * @return mixed NULL on success, otherwise string ("!Error code")
	 */
	function output_cached_thumb( $size_name, $thumb_mimetype, $mtime = NULL, $size_x = 1 )
	{
		global $servertimenow;

		$af_thumb_path = $this->get_af_thumb_path( $size_name, $thumb_mimetype, false, $size_x );
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
			header('Last-Modified: ' . date("r",$this->get_lastmod_ts()));

			// dh> if( $mtime && $mtime == $this->get_lastmod_ts() )
			// fp> I don't think mtime changes anything to the cacheability of the data
			//header_noexpire(); // Static image
			// attila> set expires on 30 days
			header('Expires: ' . date("r", $servertimenow + 2592000/* 60*60*24*30 = 30 days */ ));

			// Output the content of the file
			readfile( $af_thumb_path );
			return NULL;
		}

		return $af_thumb_path;	// !Error code
	}


	/**
	 * Link file to object
	 *
	 * @param object LinkOwner (can be: LinkItem, LinkComment or LinkUser)
	 * @param integer Order
	 * @param string Position
	 * @return integer Link ID
	 */
	function link_to_Object( & $LinkOwner, $set_order = 1, $position = NULL )
	{
		global $DB;

		$DB->begin();

		$order = $set_order;
		$existing_Links = & $LinkOwner->get_Links();

		// Find highest order
		foreach( $existing_Links as $loop_Link )
		{
			if( $loop_Link->file_ID == $this->ID )
			{ // The file is already linked to this owner
				return;
			}
			$existing_order = $loop_Link->get('order');
			if( $set_order == 1 && $existing_order >= $order )
			{ // Set order if $set_order is default
				$order = $existing_order + 1;
			}
		}

		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$this->load_meta( true );

		// Let's make the link!
		$link_ID = $LinkOwner->add_link( $this->ID, $position, $order );

		if( $link_ID )
		{
			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $link_ID;
	}


	/**
	 * Get link to restricted object
	 *
	 * Used when try to delete a file, which is attached to a post, or to a user
	 *
	 * @param array restriction
	 * @return string message with links to objects
	 */
	function get_restriction_link( $restriction )
	{
		global $DB, $admin_url;

		switch( $restriction['table'] )
		{ // can be restricted to different tables
			case 'T_links':
				switch( $restriction['field'] )
				{
					case 'link_itm_ID': // Items
						$object_table = 'T_items__item'; // related table
						$object_ID = 'post_ID';          // related table object ID
						$object_name = 'post_title';     // related table object name
						// link to object
						$link = '<a href="'.$admin_url.'?ctrl=items&action=edit&p=%d">%s</a>';
						break;

					case 'link_cmt_ID': // Comments
						$object_table = 'T_comments'; // related table
						$object_ID = 'comment_ID';    // related table object ID
						$object_name = 'comment_ID';  // related table object name
						// link to object
						$link = '<a href="'.$admin_url.'?ctrl=comments&action=edit&comment_ID=%d">'.T_('Comment ').'#%s</a>';
						break;

					case 'link_usr_ID': // Users
						$object_table = 'T_users';   // related table
						$object_ID = 'user_ID';      // related table object ID
						$object_name = 'user_login'; // related table object name
						// link to object
						$link = '<a href="'.$admin_url.'?ctrl=user&user_tab=avatar&user_ID=%d">%s</a>';
						break;

					default:
						// not defined restriction
						debug_die ( 'unhandled restriction field:' . htmlspecialchars ( $restriction['table'].' - '.$restriction['field'] ) );
				}
				$object_query = 'SELECT '.$object_ID.', '.$object_name.' FROM '.$object_table
									.' WHERE '.$object_ID.' IN'
									.' (SELECT '.$restriction['field']
									.' FROM '.$restriction['table']
									.' WHERE '.$restriction['fk'].' = '.$this->ID.')';
			break;

			default:
				// not defined restriction
				debug_die ( 'unhandled restriction:' . htmlspecialchars ( $restriction['table'] ) );
		}

		$result_link = '';
		$query_result = $DB->get_results( $object_query );
		foreach( $query_result as $row )
		{ // create links for each related object
			$result_link .= '<br/>'.sprintf( $link, $row->$object_ID, $row->$object_name );
		}

		if( ( $count = count($query_result) ) > 0 )
		{ // there are restrictions
			return sprintf( $restriction['msg'].$result_link, $count );
		}
		// no restriction
		return '';
	}


	/**
	 * Get icon with link to go to file browser where this file is highlighted
	 *
	 * @return string Link
	 */
	function get_target_icon()
	{
		global $current_User;

		$r = '';
		if( $current_User->check_perm( 'files', 'view', false, $this->get_FileRoot() ) )
		{	// Check permission
			if( $this->is_dir() )
			{	// Dir
				$title = T_('Locate this directory!');
			}
			else
			{	// File
				$title = T_('Locate this file!');
			}
			$url = $this->get_linkedit_url();
			$r .= '<a href="'.$url.'" title="'.$title.'">'.get_icon( 'locate', 'imgtag', array( 'title' => $title ) ).'</a> ';
		}

		return $r;
	}


	/**
	 * Detect file type by extension and update 'file_type' in DB
	 */
	function set_file_type()
	{
		if( ! empty( $this->type ) )
		{ // Don't detect file type if File type is already defined
			return;
		}

		// AUDIO:
		$file_extension = $this->get_ext();
		if( ! empty( $file_extension ) )
		{ // Set audio file type by extension:

			// Load all audio file types in cache
			$FiletypeCache = & get_FiletypeCache();
			$FiletypeCache->load_where( 'ftyp_mimetype LIKE "audio/%"' );
			if( count( $FiletypeCache->cache ) )
			{
				foreach( $FiletypeCache->cache as $Filetype )
				{

					if( preg_match( '#^audio/#', $Filetype->mimetype ) &&
					    in_array( $file_extension, $Filetype->get_extensions() ) )
					{ // This is audio file
						$this->update_file_type( 'audio' );
						return;
					}
				}
			}
		}

		// VIDEO:
		// Load all video file types in cache
		if( ! empty( $file_extension ) )
		{
			$FiletypeCache->load_where( 'ftyp_mimetype LIKE "video/%"' );
			if( count( $FiletypeCache->cache ) )
			{
				foreach( $FiletypeCache->cache as $Filetype )
				{
					if( preg_match( '#^video/#', $Filetype->mimetype ) &&
							in_array( $file_extension, $Filetype->get_extensions() ) )
					{ // This is a video file
						$this->update_file_type( 'video' );
						return;
					}
				}
			}
		}

		// IMAGE:
		// File type is still not defined, Try to detect image
		if( $this->get_image_size() !== false )
		{ // This is image file
			$this->update_file_type( 'image' );
			return;
		}

		// OTHER:
		// File type is still not detected, Use this default
		$this->update_file_type( 'other' );
		return;
	}


	/**
	 * Update file type in DB
	 *
	 * @param string File type
	 */
	function update_file_type( $file_type )
	{
		if( $file_type == $this->type )
		{ // File type is already defined
			return;
		}

		// Set new file type
		$this->set( 'type', $file_type );

		if( ! empty( $this->ID ) && ! empty( $file_type ) )
		{ // Update file type in DB
			global $DB;
			$DB->query( 'UPDATE T_files
					SET file_type = '.$DB->quote( $file_type ).'
				WHERE file_ID = '.$DB->quote( $this->ID ) );
		}
	}


	/**
	 * Get thumbnail file name
	 *
	 * @param string Thumbnail size name
	 * @param integer Ratio size, can be 1, 2 and etc.
	 * @return string Thumbnail file name
	 */
	function get_thumb_name( $size_name, $size_x = 1 )
	{
		$size_x = intval( $size_x );

		if( $size_x == 1 || $size_x != 2 )
		{ // 1x size or wrong $size_x
			return $size_name.'.'.$this->get_ext();
		}

		if( preg_match( '#^([^\d]+)(\d+)x(\d+)(.*)$#', $size_name, $size_match ) )
		{ // Modify size name by increasing width and height values, E.g. crop-32x32 will be crop-64x64 for $size_x = 2
			return $size_match[1].( $size_match[2] * $size_x ).'x'.( $size_match[3] * $size_x ).$size_match[4].'.'.$this->get_ext();
		}

		// Unknown wrong thumbnail size name
		return $size_name.'.'.$this->get_ext();
	}


	/**
	 * Get the duplicated files of this file
	 *
	 * @param array Params
	 * @return array Key = File ID, Value = File Root ID
	 */
	function get_duplicated_files( $params = array() )
	{
		$params = array_merge( array(
			'file_type' => 'image',
			'root_type' => 'user', // 'user', 'item', 'comment'
			'root_ID'   => NULL,
		), $params );

		global $DB;

		// Set link object ID filed name depending on root type
		switch( $params['root_type'] )
		{
			case 'user':
				$link_object_ID_field = 'link_usr_ID';
				break;
			case 'item':
				$link_object_ID_field = 'link_itm_ID';
				break;
			case 'comment':
				$link_object_ID_field = 'link_cmt_ID';
				break;
		}

		// Find the duplicated files
		$SQL = new SQL();
		$SQL->SELECT( 'file_ID, file_root_ID' );
		$SQL->FROM( 'T_files' );
		$SQL->FROM_add( 'INNER JOIN T_links ON link_file_ID = file_ID' );
		$SQL->WHERE( 'file_type = '.$DB->quote( $params['file_type'] ) );
		$SQL->WHERE_and( 'file_root_type = '.$DB->quote( $params['root_type'] ) );
		$SQL->WHERE_and( 'file_hash = '.$DB->quote( $this->get( 'hash' ) ) );
		$SQL->WHERE_and( 'file_ID != '.$DB->quote( $this->ID ) );
		if( ! empty( $link_object_ID_field ) )
		{ // Check to object ID field is not NULL
			$SQL->WHERE_and( $link_object_ID_field.' IS NOT NULL' );
		}
		if( $params['root_ID'] !== NULL )
		{ // Restrict by root ID
			$SQL->WHERE_and( 'file_root_ID = '.$DB->quote( $params['root_ID'] ) );
		}

		return $DB->get_assoc( $SQL->get() );
	}


	/**
	 * Get message if the duplicates exist for this file
	 *
	 * @param array Params
	 * @return string Message text
	 */
	function get_duplicated_files_message( $params = array() )
	{
		$params = array_merge( array(
			'message'   => '%s',
			'file_type' => 'image',
			'root_type' => 'user',
			'root_ID'   => NULL,
			'link_to'   => 'user',
			'use_style' => false, // Use style for gender colored user login
		), $params );

		// Find the duplicated files
		$duplicated_file_IDs = $this->get_duplicated_files( $params );

		if( empty( $duplicated_file_IDs ) )
		{ // No duplicates
			return false;
		}

		$FileCache = & get_FileCache();
		$duplicated_files = array();
		foreach( $duplicated_file_IDs as $file_ID => $file_root_ID )
		{
			if( ! ( $duplicated_File = & $FileCache->get_by_ID( $file_ID, false, false ) ) )
			{ // Broken file object
				continue;
			}

			if( $params['link_to'] == 'user' )
			{ // Link to profile picture edit form
				global $admin_url;
				$UserCache = & get_UserCache();
				$User = & $UserCache->get_by_ID( $file_root_ID, false, false );

				$link_text = $User ? $User->get_colored_login( array( 'use_style' => $params['use_style'] ) ) : T_('Deleted user');
				$link_class = $User ? $User->get_gender_class() : 'user';
				$link_url = $admin_url.'?ctrl=user&amp;user_tab=avatar&amp;user_ID='.$file_root_ID;

				$duplicated_files[] = '<a href="'.$link_url.'" class="nowrap '.$link_class.'">'
					.$duplicated_File->get_tag( '', '', '', '', 'crop-top-15x15', '', '', 'lightbox[d'.$this->ID.']', 'avatar_before_login' ).' '
					.$link_text.'</a>';
			}
			else
			{ // Default link
				$duplicated_files[] = $duplicated_File->get_tag( '', '', '', '', 'crop-top-15x15', 'original', '', 'lightbox[d'.$this->ID.']' );
			}
		}

		return sprintf( $params['message'], implode( ', ', $duplicated_files ) );
	}


	/**
	 * Get a count of social votes
	 *
	 * @param string Type of votes: 'like', 'dislike', 'inappropriate', 'spam'
	 * @param array Params
	 * @return string
	 */
	function get_votes_count_info( $type, $params = array() )
	{
		$params = array_merge( array(
				'message' => T_('%d times by %s'),
			), $params );

		if( empty( $this->ID ) )
		{ // This file is not exists in DB
			return '0';
		}

		if( ! isset( $this->votes_count ) )
		{ // Get the votes count only first time from DB
			global $DB;
			$SQL = new SQL();
			$SQL->SELECT( 'lvot_user_ID, lvot_like, lvot_inappropriate, lvot_spam' );
			$SQL->FROM( 'T_links' );
			$SQL->FROM_add( 'INNER JOIN T_links__vote ON lvot_link_ID = link_ID' );
			$SQL->WHERE( 'link_file_ID = '.$DB->quote( $this->ID ) );
			// Cache the results in this var
			$this->votes_count = $DB->get_results( $SQL->get() );
		}

		if( empty( $this->votes_count ) )
		{ // No votes yet
			return '0';
		}

		$count = 0;
		$users = array();
		$UserCache = & get_UserCache();
		foreach( $this->votes_count as $vote )
		{
			if( ( $type == 'like' && $vote->lvot_like == '1' ) ||
			    ( $type == 'dislike' && $vote->lvot_like == '-1' ) ||
			    ( $type == 'inappropriate' && $vote->lvot_inappropriate == '1' ) ||
			    ( $type == 'spam' && $vote->lvot_spam == '1' ) )
			{
				$count++;
				if( ! isset( $users[ $vote->lvot_user_ID ] ) )
				{
					if( $vote_User = & $UserCache->get_by_ID( $vote->lvot_user_ID, false, false ) )
					{
						$users[ $vote->lvot_user_ID ] = $vote_User->get_identity_link();
					}
				}
			}
		}

		if( $count == 0 )
		{ // No votes for the selected type yet
			return '0';
		}

		return sprintf( $params['message'], $count, implode( ', ', $users ) );
	}

	/**
	 * Increments the number of times the file was downloaded
	 *
	 * @param integer Amount to increment the download count
	 * @return integer Latest number download count
	 */
	function increment_download_count( $count = 1 )
	{
		$download_count = $this->download_count + $count;
		$this->set( 'download_count', $download_count );
 		$this->dbupdate();

		return $download_count;
	}

	/**
	 * Get total number of times the file was downloaded
	 *
	 * @return integer Download count
	 */
	function get_download_count()
	{
		return $this->download_count;
	}
}

?>