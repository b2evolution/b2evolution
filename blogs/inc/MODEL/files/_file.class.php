<?php
/**
 * This file implements the File class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
				$row = $DB->get_row( '
					SELECT * FROM T_files
					 WHERE file_root_type = "'.$this->_FileRoot->type.'"
					   AND file_root_ID = '.$this->_FileRoot->in_type_ID.'
					   AND file_path = '.$DB->quote($this->_rdfp_rel_path),
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
				|| !isset($this->Filetype)
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
	 */
	function get_tag( $before_image = '<div class="image_block">',
	                  $before_image_legend = '<div class="image_legend">',
	                  $after_image_legend = '</div>',
	                  $after_image = '</div>',
	                  $size_name = 'original' )
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
					$r .= '<img src="'.$this->get_url().'" '
								.'alt="'.$this->dget('alt', 'htmlattr').'" '
								.'title="'.$this->dget('title', 'htmlattr').'" '
								.$this->get_image_size( 'string' ).' />';
			}
			else
			{
					$r .= '<img src="'.$this->get_thumb_url( $size_name ).'" '
								.'alt="'.$this->dget('alt', 'htmlattr').'" '
								.'title="'.$this->dget('title', 'htmlattr').'" />';
					// TODO: size
			}
			$desc = $this->dget('desc');
			if( !empty($desc) )
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
	 * @param string|NULL chmod (octal three-digit-format, eg '777'), uses {@link $Settings} for NULL
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

		$chmod = octdec( $chmod );
		if( @chmod( $this->_adfp_full_path, $chmod ) )
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
				."this.target = ''; return pop_up_window( '$url', '$target', '"
				.'width='.( ( $width = $this->get_image_size( 'width' ) ) ? ( $width + 100 ) : 800  ).','
				.'height='.( ( $height = $this->get_image_size( 'height' ) ) ? ( $height + 150 ) : 800  ).','
				."scrollbars=yes,status=yes,resizable=yes' );"
				.'">'.$text.'</a>';
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
											$actionurl = 'admin.php?ctrl=files' )
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

		return '<a href="'.$url.'" title="'.$title.'">'.$text.'</a>';
	}


	/**
	 * Get the thumbnail URL for this file
	 *
	 * @param string not ready for being changed yet (fp)
	 */
	function get_thumb_url( $size_name = 'fit-80x80' )
	{
		global $public_access_to_media, $htsrv_url;

		if( ! $this->is_image() )
		{ // Not an image
			debug_die( 'Can only thumb images');
		}

		if( $public_access_to_media
			&& $af_thumb_path = $this->get_af_thumb_path( $size_name, NULL, false ) )
		{ // If the thumbnail was already cached, we could publicly access it:
			if( @is_file( $af_thumb_path ) )
			{	// The thumb IS already cache! :)
				// Let's point directly into the cache:
				$url = $this->_FileRoot->ads_url.dirname($this->_rdfp_rel_path).'/.evocache/'.$this->_name.'/'.$size_name.'.'.$this->get_ext();
				return $url;
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
	 * Get the full path to the thumbnail for this file.
	 *
	 * ads = Absolute Directory Slash
	 *
	 * @param boolean shall we create the dir if it doesn't exist?
	 * @return string or NULL if can't be obtained
	 */
	function get_ads_evocache( $create_if_needed = false )
	{
		if( strpos( $this->_dir, '/.evocache/' ) !== false )
		{	// We are already in an evocahce folder: refuse to go further!
			return NULL;
		}

		$adp_evocache = $this->_dir.'.evocache/'.$this->_name;

		if( $create_if_needed && !is_dir( $adp_evocache ) )
		{	// Create the directory:
			if( ! mkdir_r( $adp_evocache ) )
			{	// Could not create
				return NULL;
			}
		}

		return $adp_evocache.'/';
	}

	/**
	 * Get the full path to the thumbanil for this file.
	 *
	 * af = Absolute File
	 *
	 * @param string size name
	 * @param string mimetype of thumbnail (NULL if we're ready to take wathever is available)
	 * @param boolean shall we create the dir if it doesn't exist?
	 * @return string or NULL if can't be obtained
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
		if( $ads_evocache = $this->get_ads_evocache( $create_evocache_if_needed ) )
		{
			return $ads_evocache.$size_name.'.'.$this->get_ext();
		}

		return NULL;
	}


	/**
	 *Save thumbnail for file
	 *
	 * @param resource
	 * @param string size name
	 * @param string miemtype of thumbnail
	 * @param string short error code
	 */
	function save_thumb_to_cache( $thumb_imh, $size_name, $thumb_mimetype, $thumb_quality = 90 )
	{
		if( $af_thumb_path = $this->get_af_thumb_path( $size_name, $thumb_mimetype, true ) )
		{	// We obtained a path for the thumbnail to be saved:
			return save_image( $thumb_imh, $af_thumb_path, $thumb_mimetype, $thumb_quality );
		}

		return 'Ewr-access';
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
		if( $af_thumb_path = $this->get_af_thumb_path( $size_name, $thumb_mimetype, false ) )
		{	// We obtained a path for the thumbnail to be saved:
			if( ! file_exists( $af_thumb_path ) )
			{	// The thumbnail was not found...
				return 'Enotcached';
			}

			if( ! is_readable( $af_thumb_path ) )
			{
				return 'Eread';
			}

 			header('Content-type: '.$thumb_mimetype );
			header('Content-Length: '.filesize( $af_thumb_path ) );
			// Output the content of the file
			readfile( $af_thumb_path );
			return NULL;
		}

		return 'Erd-access';
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
	 */
	function thumbnail( $req_size )
	{
		load_funcs( 'MODEL/files/_image.funcs.php' );

		$size_name = $req_size;
		switch( $req_size )
		{
			case 'fit-720x500';
				$thumb_width = 720;
				$thumb_height = 500;
				$thumb_quality = 90;
				break;

			case 'fit-320x320';
				$thumb_width = 320;
				$thumb_height = 320;
				$thumb_quality = 85;
				break;

			case 'fit-80x80';
			default:
				$size_name = 'fit-80x80';
				$thumb_width = 80;
				$thumb_height = 80;
				$thumb_quality = 75;
		}

		$mimetype = $this->Filetype->mimetype;

		// Try to output the cached thumbnail:
		$err = $this->output_cached_thumb( $size_name, $mimetype );

		if( $err == 'Enotcached' )
		{	// The thumbnail wasn't already in the cache, try to generate and cache it now:
			$err = NULL;		// Short error code

			list( $err, $err_info, $src_imh ) = load_image( $this->get_full_path(), $mimetype );
			if( empty( $err ) )
			{
				list( $err, $dest_imh ) = generate_thumb( $src_imh, $thumb_width, $thumb_height );
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

		if( !empty( $err ) )
		{	// Generate an error image and try to squeeze an error message inside:
			// Note: we write small and close to the upper left in order to have as much text as possible on small thumbs
  		$im_handle = imagecreatetruecolor( $thumb_width, $thumb_height ); // Create a black image
			$text_color = imagecolorallocate( $im_handle, 255, 0, 0 );
			imagestring( $im_handle, 2, 2, 1, $err, $text_color);
			if( !empty( $err_info ) )
			{	// Additional info
				$text_color = imagecolorallocate( $im_handle, 255, 255, 255 );
				imagestring( $im_handle, 2, 2, 12, $err_info, $text_color);
			}
			header('Content-type: image/png' );
			imagepng( $im_handle );
		}
	}
}


/*
 * $Log$
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
 *
 * Revision 1.20  2006/09/30 16:55:58  blueyed
 * $create param for media dir handling, which allows to just get the dir, without creating it.
 *
 * Revision 1.19  2006/09/10 14:50:48  fplanque
 * minor / doc
 *
 * Revision 1.18  2006/09/08 15:33:43  blueyed
 * minor
 *
 * Revision 1.17  2006/08/19 08:50:26  fplanque
 * moved out some more stuff from main
 *
 * Revision 1.16  2006/08/19 07:56:30  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.15  2006/08/05 17:17:59  blueyed
 * todo for windows
 *
 * Revision 1.14  2006/07/12 20:17:13  fplanque
 * minor
 *
 * Revision 1.13  2006/07/07 22:48:26  blueyed
 * Fixed get_tag() to include meta data.
 *
 * Revision 1.12  2006/06/19 20:59:37  fplanque
 * noone should die anonymously...
 *
 * Revision 1.11  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.10  2006/04/12 19:40:37  fplanque
 * minor fixes
 *
 * Revision 1.9  2006/03/29 23:24:01  blueyed
 * Fixed linking of files.
 *
 * Revision 1.8  2006/03/20 20:06:02  fplanque
 * fixed IMG button, again :(
 *
 * Revision 1.7  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.6  2006/03/12 20:07:00  blueyed
 * re-adding re-moved todo
 *
 * Revision 1.4  2006/03/12 03:03:32  blueyed
 * Fixed and cleaned up "filemanager".
 *
 * Revision 1.2  2006/02/27 23:58:01  blueyed
 * todo
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.63  2006/02/13 21:40:30  fplanque
 * fixed memorizing of the mode when uploading/inserting IMGs into posts.
 *
 * Revision 1.62  2006/02/10 22:05:07  fplanque
 * Normalized itm links
 *
 * Revision 1.61  2006/01/26 19:27:58  fplanque
 * no message
 *
 * Revision 1.60  2006/01/20 16:40:56  blueyed
 * Cleanup
 *
 * Revision 1.59  2006/01/10 10:36:31  blueyed
 * Suppress warnings for dangling symlinks
 *
 * Revision 1.58  2006/01/09 21:57:26  blueyed
 * get_fsgroup_name(), get_fsowner_name(): fix for root (ID 0)
 *
 * Revision 1.57  2005/12/19 16:42:03  fplanque
 * minor
 *
 * Revision 1.56  2005/12/16 16:59:13  blueyed
 * (Optional) File owner and group columns in Filemanager.
 *
 * Revision 1.55  2005/12/16 14:57:18  blueyed
 * Valid target for popup link
 *
 * Revision 1.54  2005/12/14 19:33:10  fplanque
 * more responsibility given to the file class, but the file class still can work standalone (without a filemanager)
 *
 * Revision 1.53  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.52  2005/12/10 02:54:33  blueyed
 * Default chmod moved to $Settings again
 *
 * Revision 1.51  2005/11/24 08:43:34  blueyed
 * doc
 *
 * Revision 1.50  2005/11/22 13:43:33  fplanque
 * doc
 *
 * Revision 1.49  2005/11/22 04:47:59  blueyed
 * rename_to(): return false if file exists!
 *
 * Revision 1.48  2005/11/22 04:15:58  blueyed
 * doc; dbupdate()/dbinsert(): return value
 *
 * Revision 1.47  2005/11/21 18:33:19  fplanque
 * Too many undiscussed changes all around: Massive rollback! :((
 * As said before, I am only taking CLEARLY labelled bugfixes.
 *
 * Revision 1.43  2005/11/18 07:53:05  blueyed
 * use $_FileRoot / $FileRootCache for absolute path, url and name of roots.
 *
 * Revision 1.42  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.41  2005/08/12 17:41:10  fplanque
 * cleanup
 *
 * Revision 1.40  2005/08/08 18:30:49  fplanque
 * allow inserting of files as IMG or A HREFs from the filemanager
 *
 * Revision 1.39  2005/07/29 17:56:17  fplanque
 * Added functionality to locate files when they're attached to a post.
 * permission checking remains to be done.
 *
 * Revision 1.38  2005/07/26 18:50:39  fplanque
 * enhanced attached file handling
 *
 * Revision 1.37  2005/07/12 22:58:31  blueyed
 * Suppress php's chmod() warnings.
 *
 * Revision 1.36  2005/05/24 15:26:52  fplanque
 * cleanup
 *
 * Revision 1.35  2005/05/17 19:26:07  fplanque
 * FM: copy / move debugging
 *
 * Revision 1.34  2005/05/13 18:41:28  fplanque
 * made file links clickable... finally ! :P
 *
 * Revision 1.33  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.32  2005/05/11 17:53:47  fplanque
 * started multiple roots handling in file meta data
 *
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