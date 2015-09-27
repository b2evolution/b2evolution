<?php
/**
 * This file implements various File handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( ! function_exists('fnmatch') )
{
	/**
	 * A replacement for fnmatch() which needs PHP 4.3 and a POSIX compliant system (Windows is not).
	 *
	 * @author jk at ricochetsolutions dot com {@link http://php.net/manual/function.fnmatch.php#71725}
	 */
	function fnmatch($pattern, $string)
	{
	   return preg_match( '#^'.strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.')).'$#i', $string);
	}
}


/**
 * Converts bytes to readable bytes/kb/mb/gb, like "12.45mb"
 *
 * @param integer bytes
 * @param boolean use HTML <abbr> tags
 * @param boolean Display full text of size type when $htmlabbr == false
 * @return string bytes made readable
 */
function bytesreadable( $bytes, $htmlabbr = true, $display_size_type = true )
{
	static $types = NULL;

	if( empty($bytes) )
	{
		return T_('Empty');
	}

	if( !isset($types) )
	{ // generate once:
		$types = array(
			0 => array( 'abbr' => /* TRANS: Abbr. for "Bytes" */ T_('B.'), 'text' => T_('Bytes') ),
			1 => array( 'abbr' => /* TRANS: Abbr. for "Kilobytes" */ T_('KB'), 'text' => T_('Kilobytes') ),
			2 => array( 'abbr' => /* TRANS: Abbr. for Megabytes */ T_('MB'), 'text' => T_('Megabytes') ),
			3 => array( 'abbr' => /* TRANS: Abbr. for Gigabytes */ T_('GB'), 'text' => T_('Gigabytes') ),
			4 => array( 'abbr' => /* TRANS: Abbr. for Terabytes */ T_('TB'), 'text' => T_('Terabytes') )
		);
	}

	for( $i = 0; $bytes > 1024; $i++ )
	{
		$bytes /= 1024;
	}

	// Format to maximum of 1 digit after .
	$precision = max( 0, ( 1 -floor(log($bytes)/log(10))) );
	$r = sprintf( '%.'.$precision.'f', $bytes );

	$r .= $htmlabbr ? ( '&nbsp;<abbr title="'.$types[$i]['text'].'">' ) : ' ';
	$r .= $types[$i]['abbr'];
	$r .= $htmlabbr ? '</abbr>' : ( $display_size_type ? ' ('.$types[$i]['text'].')' : '' );

	// $r .= ' '.$precision;

	return $r;
}


/**
 * Get an array of all directories (and optionally files) of a given
 * directory, either flat (one-dimensional array) or multi-dimensional (then
 * dirs are the keys and hold subdirs/files).
 *
 * Note: there is no ending slash on dir names returned.
 *
 * @param string the path to start
 * @param array of params
 * @return false|array false if the first directory could not be accessed,
 *                     array of entries otherwise
 */
function get_filenames( $path, $params = array() )
{
	global $Settings;

	$params = array_merge( array(
			'inc_files'      => true,  // include files (not only directories)
			'inc_dirs'       => true,  // include directories (not the directory itself!)
			'flat'           => true,  // return a one-dimension-array
			'recurse'        => true,  // recurse into subdirectories
			'basename'       => false, // get the basename only
			'trailing_slash' => false, // add trailing slash
			'inc_hidden'     => true,  // inlcude hidden files, directories and content
			'inc_evocache'   => false, // exclude evocache directories and content
			'inc_temp'       => true,  // include temporary files and directories
		), $params );

	$r = array();

	$path = trailing_slash( $path );

	if( $dir = @opendir( $path ) )
	{
		while( ( $file = readdir( $dir ) ) !== false )
		{
			if( $file == '.' || $file == '..' )
			{ // Skip these reserved names
				continue;
			}

			// asimo> Also check if $Settings is not empty because this function is called from the install srcipt too, where $Settings is not initialized yet
			if( ! $params['inc_evocache'] && ! empty( $Settings ) && $file == $Settings->get( 'evocache_foldername' ) )
			{ // Do not load evocache directories
				continue;
			}

			// Check for hidden status...
			if( ( ! $params['inc_hidden'] ) && ( substr( $file, 0, 1 ) == '.' ) )
			{ // Do not load & show hidden files and folders (prefixed with .)
				continue;
			}

			// Check for temp directories/files
			if( ! $params['inc_temp'] && $file == 'upload-tmp' )
			{ // Do not load temporary files and directories
				continue;
			}

			if( is_dir( $path.$file ) )
			{
				if( $params['flat'] )
				{
					if( $params['inc_dirs'] )
					{
						$directory_name = $params['basename'] ? $file : $path.$file;
						if( $params['trailing_slash'] )
						{
							$directory_name = trailing_slash( $directory_name );
						}

						$r[] = $directory_name;
					}
					if( $params['recurse'] )
					{
						$rSub = get_filenames( $path.$file, $params );
						if( $rSub )
						{
							$r = array_merge( $r, $rSub );
						}
					}
				}
				else
				{
					$r[ $file ] = get_filenames( $path.$file, $params );
				}
			}
			elseif( $params['inc_files'] )
			{
				$r[] = $params['basename'] ? $file : $path.$file;
			}
		}
		closedir( $dir );
	}
	else
	{
		return false;
	}

	return $r;
}


/**
 * Get a list of available admin skins.
 *
 * This checks if there's a _adminUI.class.php in there.
 *
 * @return array  List of directory names that hold admin skins or false, if the admin skins driectory does not exist.
 */
function get_admin_skins()
{
	global $adminskins_path, $admin_subdir, $adminskins_subdir;

	$filename_params = array(
			'inc_files'		=> false,
			'recurse'		=> false,
			'basename'      => true,
		);
	$dirs_in_adminskins_dir = get_filenames( $adminskins_path, $filename_params );

	if( $dirs_in_adminskins_dir === false )
	{
		return false;
	}

	$r = array();
	if( $dirs_in_adminskins_dir )
	{
		foreach( $dirs_in_adminskins_dir as $l_dir )
		{
			if( !file_exists($adminskins_path.$l_dir.'/_adminUI.class.php') )
			{
				continue;
			}
			$r[] = $l_dir;
		}
	}
	return $r;
}


/**
 * Get size of a directory, including anything (especially subdirs) in there.
 *
 * @param string the dir's full path
 */
function get_dirsize_recursive( $path )
{
	$files = get_filenames( $path );
	$total = 0;

	if( !empty( $files ) )
	{
		foreach( $files as $lFile )
		{
			$total += filesize($lFile);
		}
	}

	return $total;
}


/**
 * Deletes a dir recursively, wiping out all subdirectories!!
 *
 * @param string The dir
 * @return boolean False on failure
 */
function rmdir_r( $path )
{
	$path = trailing_slash( $path );

	$r = true;

	if( ! cleardir_r( $path ) )
	{
		$r = false;
	}

	if( ! @rmdir( $path ) )
	{
		$r = false;
	}

	return $r;
}


/**
 * Clear contents of a directory, but do not delete the directory itself
 *
 * @param string the directory path
 * @return boolean False on failure (may be only partial), true on success.
 */
function cleardir_r( $path )
{
	$path = trailing_slash( $path );
	// echo "<br>rmdir_r($path)";

	$r = true; // assume success

	if( $dir = @opendir($path) )
	{
		while( ( $file = readdir($dir) ) !== false )
		{
			if( $file == '.' || $file == '..' )
				continue;

			$adfp_filepath = $path.$file;

			// echo "<br> - $os_filepath ";

			if( is_dir( $adfp_filepath ) && ! is_link($adfp_filepath) )
			{ // Note: we do NOT follow symlinks
				// echo 'D';
				if( ! rmdir_r( $adfp_filepath ) )
				{
					$r = false;
				}
			}
			else
			{ // File or symbolic link
				//echo 'F/S';
				if( ! @unlink( $adfp_filepath ) )
				{
					$r = false;
				}
			}
		}
		closedir($dir);
	}
	else
	{
		$r = false;
	}

	return $r;
}

/**
 * Get the size of an image file
 *
 * @param string absolute file path
 * @param string what property/format to get: 'width', 'height', 'widthxheight',
 *               'type', 'string' (as for img tags), 'widthheight_assoc' (array
 *               with keys "width" and "height", else 'widthheight' (numeric array)
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
	elseif( $param == 'widthheight_assoc' )
	{
		return array( 'width' => $size[0], 'height' => $size[1] );
	}
	else
	{ // default: 'widthheight'
		return array( $size[0], $size[1] );
	}
}


/**
 * Remove leading slash, if any.
 *
 * @param string
 * @return string
 */
function no_leading_slash( $path )
{
	if( isset($path[0]) && $path[0] == '/' )
	{
		return substr( $path, 1 );
	}
	else
	{
		return $path;
	}
}


/**
 * Returns canonicalized pathname of a directory + ending slash
 *
 * @param string absolute path to be reduced ending with slash
 * @return string absolute reduced path, slash terminated or NULL if the path could not get canonicalized.
 */
function get_canonical_path( $ads_path )
{
	// Remove windows backslashes:
	$ads_path = str_replace( '\\', '/', $ads_path );

	$is_absolute = is_absolute_pathname($ads_path);

	// Make sure there's a trailing slash
	$ads_path = trailing_slash($ads_path);

	while( strpos($ads_path, '//') !== false )
	{
		$ads_path = str_replace( '//', '/', $ads_path );
	}
	while( strpos($ads_path, '/./') !== false )
	{
		$ads_path = str_replace( '/./', '/', $ads_path );
	}
	$parts = explode('/', $ads_path);
	for( $i = 0; $i < count($parts); $i++ )
	{
		if( $parts[$i] != '..' )
		{
			continue;
		}
		if( $i <= 0 || $parts[$i-1] == '' || substr($parts[$i-1], -1) == ':' /* windows drive letter */ )
		{
			return NULL;
		}
		// Remove ".." and the part before it
		unset($parts[$i-1], $parts[$i]);
		// Respin array
		$parts = array_values($parts);
		$i = $i-2;
	}
	$ads_realpath = implode('/', $parts);

	// pre_dump( 'get_canonical_path()', $ads_path, $ads_realpath );

	if( strpos( $ads_realpath, '..' ) !== false )
	{	// Path malformed:
		return NULL;
	}

	if( $is_absolute && ! strlen($ads_realpath) )
	{
		return NULL;
	}

	return $ads_realpath;
}


/**
 * Fix the length of a given file name based on the global $filename_max_length setting.
 *
 * @param string the file name
 * @param string the index before we should remove the over characters
 * @return string the modified filename if the length was above the max length and the $remove_before_index param was correct. The original filename otherwie.
 */
function fix_filename_length( $filename, $remove_before_index )
{
	global $filename_max_length;

	$filename_length = strlen( $filename );
	if( $filename_length > $filename_max_length )
	{
		$difference = $filename_length - $filename_max_length;
		if( $remove_before_index > $difference )
		{ // Fix file name length only if the filename part before the 'remove index' contains more characters then what we have to remove
			$filename = substr_replace( $filename, '', $remove_before_index - $difference, $difference );
		}
	}
	return $filename;
}


/**
 * Process filename:
 *  - convert to lower case
 *  - replace consecutive dots with one dot
 *  - if force_validation is true, then replace every not valid character to '_'
 *  - check if file name is valid
 *
 * @param string file name (by reference) - this file name will be processed
 * @param boolean force validation ( replace not valid characters to '_' without warning )
 * @param boolean Crop long file name to $filename_max_length
 * @param object FileRoot
 * @param string Path in the FileRoot
 * @return error message if the file name is not valid, false otherwise
 */
function process_filename( & $filename, $force_validation = false, $autocrop_long_file = false, $FileRoot = NULL, $path = '' )
{
	if( empty( $filename ) )
	{
		return T_( 'Empty file name is not valid.' );
	}

	if( $autocrop_long_file && ! empty( $FileRoot ) )
	{ // Crop long file names
		global $filename_max_length, $Messages;

		if( ! empty( $filename_max_length ) &&
				$filename_max_length > 8 &&
				strlen( $filename ) > $filename_max_length )
		{ // Limit file name by $filename_max_length
			$new_file_ext_pos = strrpos( $filename, '.' );
			$new_file_name = substr( $filename, 0, $new_file_ext_pos );
			$new_file_name = trim( substr( $new_file_name, 0, $filename_max_length - 8 ) );
			$new_file_ext = substr( $filename, $new_file_ext_pos - strlen( $filename ) + 1 );

			$fnum = 1;
			$FileCache = & get_FileCache();
			do
			{ // Make file name unique
				$filename = $new_file_name.'-'.$fnum.'.'.$new_file_ext;
				$newFile = & $FileCache->get_by_root_and_path( $FileRoot->type, $FileRoot->in_type_ID, trailing_slash( $path ).$filename, true );
				$fnum++;
			}
			while( $newFile->exists() );
			$filename = $newFile->get( 'name' );

			$Messages->add( T_('The filename was too long. It has been shortened.'), 'warning' );
		}
	}

	if( $force_validation )
	{ // replace every not valid characters
		$filename = preg_replace( '/[^a-z0-9\-_.]+/i', '_', $filename );
		// Make sure the filename length doesn't exceed the maximum allowed. Remove characters from the end of the filename ( before the extension ) if required.
		$extension_pos = strrpos( $filename, '.' );
		$filename = fix_filename_length( $filename, strrpos( $filename, '.', ( $extension_pos ? $extension_pos : strlen( $filename ) ) ) );
	}

	// check if the file name contains consecutive dots, and replace them with one dot without warning ( keep only one dot '.' instead of '...' )
	$filename = preg_replace( '/\.(\.)+/', '.', utf8_strtolower( $filename ) );

	if( $error_filename = validate_filename( $filename ) )
	{ // invalid file name
		return $error_filename;
	}

	// on success
	return false;
}


/**
 * Check for valid filename and extension of the filename (no path allowed). (MB)
 *
 * @uses $FiletypeCache, $settings or $force_regexp_filename form _advanced.php
 *
 * @param string filename to test
 * @param mixed true/false to allow locked filetypes. NULL means that FileType will decide
 * @return nothing if the filename is valid according to the regular expression and the extension too, error message if not
 */
function validate_filename( $filename, $allow_locked_filetypes = NULL )
{
	global $Settings, $force_regexp_filename, $filename_max_length;

	if( strpos( $filename, '..' ) !== false )
	{ // consecutive dots are not allowed in file name
		return sprintf( T_('&laquo;%s&raquo; is not a valid filename.').' '.T_( 'Consecutive dots are not allowed.' ), $filename );
	}

	if( strlen( $filename ) > $filename_max_length )
	{ // filename is longer then the maximum allowed
		return sprintf( T_('&laquo;%s&raquo; is not a valid filename.').' '.sprintf( T_( 'Max %d characters are allowed on filenames.' ), $filename_max_length ), $filename );
	}

	// Check filename
	if( $force_regexp_filename )
	{ // Use the regexp from _advanced.php
		if( !preg_match( ':'.str_replace( ':', '\:', $force_regexp_filename ).':', $filename ) )
		{ // Invalid filename
			return sprintf( T_('&laquo;%s&raquo; is not a valid filename.'), $filename );
		}
	}
	else
	{	// Use the regexp from SETTINGS
		if( !preg_match( ':'.str_replace( ':', '\:', $Settings->get( 'regexp_filename' ) ).':', $filename ) )
		{ // Invalid filename
			return sprintf( T_('&laquo;%s&raquo; is not a valid filename.'), $filename );
		}
	}

	// Check extension filename
	if( preg_match( '#\.([a-zA-Z0-9\-_]+)$#', $filename, $match ) )
	{ // Filename has a valid extension
		$FiletypeCache = & get_FiletypeCache();
		if( $Filetype = & $FiletypeCache->get_by_extension( strtolower( $match[1] ) , false ) )
		{
			if( $Filetype->is_allowed( $allow_locked_filetypes ) )
			{ // Filename has an unlocked extension or we allow locked extensions
				return;
			}
			else
			{	// Filename hasn't an allowed extension
				return sprintf( T_('&laquo;%s&raquo; is a locked extension.'), htmlentities($match[1]) );
			}
		}
		else
		{ // Filename hasn't an allowed extension
			return sprintf( T_('&laquo;%s&raquo; has an unrecognized extension.'), $filename );
		}
	}
	else
	{ // Filename hasn't a valid extension
		return sprintf( T_('&laquo;%s&raquo; has not a valid extension.'), $filename );
	}
}


/**
 * Check for valid dirname (no path allowed). ( MB )
 *
 * @uses $Settings or $force_regexp_dirname form _advanced.php
 * @param string dirname to test
 * @return nothing if the dirname is valid according to the regular expression, error message if not
 */
function validate_dirname( $dirname )
{
	global $Settings, $force_regexp_dirname, $filename_max_length;

	if( $dirname != '..' )
	{
		if( strlen( $dirname ) > $filename_max_length )
		{ // Don't allow longer directory names then the max file name length
			return sprintf( T_('&laquo;%s&raquo; is not a valid directory name.'), $dirname ).' '.sprintf( T_( 'Max %d characters are allowed.' ), $filename_max_length );
		}

		if( !empty( $force_regexp_dirname ) )
		{ // Use the regexp from _advanced.php
			if( preg_match( ':'.str_replace( ':', '\:', $force_regexp_dirname ).':', $dirname ) )
			{ // Valid dirname
				return;
			}
		}
		else
		{ // Use the regexp from SETTINGS
			if( preg_match( ':'.str_replace( ':', '\:', $Settings->get( 'regexp_dirname' ) ).':', $dirname ) )
			{ // Valid dirname
				return;
			}
		}
	}

	return sprintf( T_('&laquo;%s&raquo; is not a valid directory name.'), $dirname );
}


/**
 * Check if file rename is acceptable
 *
 * used when renaming a file, File settings
 *
 * @param string the new name
 * @param boolean true if it is a directory, false if not
 * @param string the absolute path of the parent directory
 * @param boolean true if user has permission to all kind of fill types, false otherwise
 * @return mixed NULL if the rename is acceptable, error message if not
 */
function check_rename( & $newname, $is_dir, $dir_path, $allow_locked_filetypes )
{
	global $dirpath_max_length;

	// Check if provided name is okay:
	$newname = trim( strip_tags($newname) );

	if( $is_dir )
	{
		if( $error_dirname = validate_dirname( $newname ) )
		{ // invalid directory name
			syslog_insert( sprintf( 'Invalid name is detected for folder %s', '<b>'.$newname.'</b>' ), 'warning', 'file' );
			return $error_dirname;
		}
		if( $dirpath_max_length < ( strlen( $dir_path ) + strlen( $newname ) ) )
		{ // The new path length would be too long
			syslog_insert( sprintf( 'The renamed file %s is too long for the folder', '<b>'.$newname.'</b>' ), 'warning', 'file' );
			return T_('The new name is too long for this folder.');
		}
	}
	elseif( $error_filename = validate_filename( $newname, $allow_locked_filetypes ) )
	{ // Not a file name or not an allowed extension
		syslog_insert( sprintf( 'The renamed file %s has an unrecognized extension', '<b>'.$newname.'</b>' ), 'warning', 'file' );
		return $error_filename;
	}

	return NULL;
}


/**
 * Return a string with upload restrictions ( allowed extensions, max file size )
 *
 * @param array Params
 */
function get_upload_restriction( $params = array() )
{
	$params = array_merge( array(
			'block_before'       => '',
			'block_after'        => '<br />',
			'block_separator'    => '<br />',
			'title_before'       => '<strong>',
			'title_after'        => '</strong>: ',
			'ext_separator'      => ', ',
			'ext_last_separator' => ' &amp; ',
		), $params );

	global $DB, $Settings, $current_User;
	$restrictNotes = array();

	if( is_logged_in( false ) )
	{
		$condition = ( $current_User->check_perm( 'files', 'all' ) ) ? '' : 'ftyp_allowed <> "admin"';
	}
	else
	{
		$condition = 'ftyp_allowed = "any"';
	}

	if( !empty( $condition ) )
	{
		$condition = ' WHERE '.$condition;
	}

	// Get list of recognized file types (others are not allowed to get uploaded)
	// dh> because FiletypeCache/DataObjectCache has no interface for getting a list, this dirty query seems less dirty to me.
	$allowed_extensions = $DB->get_col( 'SELECT ftyp_extensions FROM T_filetypes'.$condition );
	$allowed_extensions = implode( ' ', $allowed_extensions ); // implode with space, ftyp_extensions can hold many, separated by space
	// into array:
	$allowed_extensions = preg_split( '~\s+~', $allowed_extensions, -1, PREG_SPLIT_NO_EMPTY );
	// readable:
	$allowed_extensions = implode_with_and( $allowed_extensions, $params['ext_separator'], $params['ext_last_separator'] );

	$restrictNotes[] = $params['title_before'].T_('Allowed file extensions').$params['title_after'].$allowed_extensions;

	if( $Settings->get( 'upload_maxkb' ) )
	{ // We want to restrict on file size:
		$restrictNotes[] = $params['title_before'].T_('Maximum allowed file size').$params['title_after'].bytesreadable( $Settings->get( 'upload_maxkb' ) * 1024 );
	}

	return $params['block_before'].implode( $params['block_separator'], $restrictNotes ).$params['block_after'];
}


/**
 * Return the path without the leading {@link $basepath}, or if not
 * below {@link $basepath}, just the basename of it.
 *
 * Do not use this for file handling.  JUST for displaying! (DEBUG MESSAGE added)
 *
 * @param string Path
 * @return string Relative path or even base name.
 *   NOTE: when $debug, the real path gets appended.
 */
function rel_path_to_base( $path )
{
	global $basepath, $debug;

	// Remove basepath prefix:
	if( preg_match( '~^('.preg_quote($basepath, '~').')(.+)$~', $path, $match ) )
	{
		$r = $match[2];
	}
	else
	{
		$r = basename($path).( is_dir($path) ? '/' : '' );
	}

	/* fp> The following MUST be moved to the caller if needed:
	if( $debug )
	{
		$r .= ' [DEBUG: '.$path.']';
	}
	*/
	
	return $r;
}


/**
 * Get the directories of the supplied path as a radio button tree.
 *
 * @todo fp> Make a DirTree class (those static hacks suck)
 *
 * @param FileRoot A single root or NULL for all available.
 * @param string the root path to use
 * @param boolean add radio buttons ?
 * @param string used by recursion
 * @param string what kind of action do the user ( we need this to check permission )
 * 			fp>asimo : in what case can this be something else than "view" ?
 * 			asimo>fp : on the _file_upload.view, we must show only those roots where current user has permission to add files
 * @return string
 */
function get_directory_tree( $Root = NULL, $ads_full_path = NULL, $ads_selected_full_path = NULL, $radios = false, $rds_rel_path = NULL, $is_recursing = false, $action = 'view' )
{
	static $js_closeClickIDs; // clickopen IDs that should get closed
	static $instance_ID = 0;
	static $fm_highlight;
	global $current_User;

	// A folder might be highlighted (via "Locate this directory!")
	if( ! isset($fm_highlight) )
	{
		$fm_highlight = param('fm_highlight', 'string', '');
	}


	if( ! $is_recursing )
	{	// This is not a recursive call (yet):
		// Init:
		$instance_ID++;
		$js_closeClickIDs = array();
		$ret = '<ul class="clicktree">';
	}
	else
	{
		$ret = '';
	}

	// ________________________ Handle Roots ______________________
	if( $Root === NULL )
	{ // We want to list all roots:
		$_roots = FileRootCache::get_available_FileRoots();

		foreach( $_roots as $l_Root )
		{
			if( ! $current_User->check_perm( 'files', $action, false, $l_Root ) )
			{	// current user does not have permission to "view" (or other $action) this root
				continue;
			}
			$subR = get_directory_tree( $l_Root, $l_Root->ads_path, $ads_selected_full_path, $radios, '', true );
			if( !empty( $subR['string'] ) )
			{
				$ret .= '<li>'.$subR['string'].'</li>';
			}
		}
	}
	else
	{
		// We'll go through files in current dir:
		$Nodelist = new Filelist( $Root, trailing_slash($ads_full_path) );
		check_showparams( $Nodelist );
		$Nodelist->load();
		$Nodelist->sort( 'name' );
		$has_sub_dirs = $Nodelist->count_dirs();

		$id_path = 'id_path_'.$instance_ID.md5( $ads_full_path );

		$r['string'] = '<span class="folder_in_tree"';

		if( $ads_full_path == $ads_selected_full_path )
		{ // This is the current open path
			$r['opened'] = true;

			if( $fm_highlight && $fm_highlight == substr($rds_rel_path, 0, -1) )
			{
				$r['string'] .= ' id="fm_highlighted"';
				unset($fm_highlight);
			}
		}
		else
		{
	 		$r['opened'] = NULL;
		}

		$r['string'] .= '>';

		if( $radios )
		{ // Optional radio input to select this path:
			$root_and_path = format_to_output( implode( '::', array($Root->ID, $rds_rel_path) ), 'formvalue' );

			$r['string'] .= '<input type="radio" name="root_and_path" value="'.$root_and_path.'" id="radio_'.$id_path.'"';

			if( $r['opened'] )
			{	// This is the current open path
				$r['string'] .= ' checked="checked"';
			}

			//.( ! $has_sub_dirs ? ' style="margin-right:'.get_icon( 'collapse', 'size', array( 'size' => 'width' ) ).'px"' : '' )
			$r['string'] .= ' /> &nbsp; &nbsp;';
		}

		// Folder Icon + Name:
		$url = regenerate_url( 'root,path', 'root='.$Root->ID.'&amp;path='.$rds_rel_path );
		$label = action_icon( T_('Open this directory in the file manager'), 'folder', $url )
			.'<a href="'.$url.'"
			title="'.T_('Open this directory in the file manager').'">'
			.( empty($rds_rel_path) ? $Root->name : basename( $ads_full_path ) )
			.'</a>';

		// Handle potential subdir:
		if( ! $has_sub_dirs )
		{	// No subdirs
			$r['string'] .= get_icon( 'expand', 'noimg', array( 'class'=>'' ) ).'&nbsp;'.$label.'</span>';
		}
		else
		{ // Process subdirs
			$r['string'] .= get_icon( 'collapse', 'imgtag', array( 'onclick' => 'toggle_clickopen(\''.$id_path.'\');',
						'id' => 'clickimg_'.$id_path,
						'style'=>'margin:0 2px'
					) )
				.'&nbsp;'.$label.'</span>'
				.'<ul class="clicktree" id="clickdiv_'.$id_path.'">'."\n";

			while( $l_File = & $Nodelist->get_next( 'dir' ) )
			{
				$rSub = get_directory_tree( $Root, $l_File->get_full_path(), $ads_selected_full_path, $radios, $l_File->get_rdfs_rel_path(), true );

				if( $rSub['opened'] )
				{ // pass opened status on, if given
					$r['opened'] = $rSub['opened'];
				}

				$r['string'] .= '<li>'.$rSub['string'].'</li>';
			}

			if( !$r['opened'] )
			{
				$js_closeClickIDs[] = $id_path;
			}
			$r['string'] .= '</ul>';
		}

   	if( $is_recursing )
		{
			return $r;
		}
		else
		{
			$ret .= '<li>'.$r['string'].'</li>';
		}
	}

	if( ! $is_recursing )
	{
 		$ret .= '</ul>';

		if( ! empty($js_closeClickIDs) )
		{ // there are IDs of checkboxes that we want to close
			$ret .= "\n".'<script type="text/javascript">toggle_clickopen( \''
						.implode( "' );\ntoggle_clickopen( '", $js_closeClickIDs )
						."' );\n</script>";
		}
	}

	return $ret;
}


/**
 * Create a directory recursively.
 *
 * @todo dh> simpletests for this (especially for open_basedir)
 *
 * @param string directory name
 * @param integer permissions
 * @return boolean
 */
function mkdir_r( $dirName, $chmod = NULL )
{
	return evo_mkdir( $dirName, $chmod, true );
}


/**
 * Create a directory
 *
 * @param string Directory path
 * @param integer Permissions
 * @param boolean Create a dir recursively
 * @return boolean TRUE on success
 */
function evo_mkdir( $dir_path, $chmod = NULL, $recursive = false )
{
	if( is_dir( $dir_path ) )
	{ // already exists:
		return true;
	}

	if( @mkdir( $dir_path, 0777, $recursive ) )
	{ // Directory is created succesfully
		if( $chmod === NULL )
		{ // Get default permissions
			global $Settings;
			$chmod = $Settings->get( 'fm_default_chmod_dir' );
		}

		if( ! empty( $chmod ) )
		{ // Set the dir rights by chmod() function because mkdir() doesn't provide this operation correctly
			chmod( $dir_path, is_string( $chmod ) ? octdec( $chmod ) : $chmod );
		}

		return true;
	}

	return false;
}


/**
 * Copy directory recursively or one file
 *
 * @param string Source path
 * @param string Destination path
 * @param string Name of folder name for the copied folder, Use NULL to get a folder name from source
 * @param array What directories should be excluded
 * @return boolean TRUE on success, FALSE when no permission
 */
function copy_r( $source, $dest, $new_folder_name = NULL, $exclude_dirs = array() )
{
	$result = true;

	if( is_dir( $source ) )
	{ // Copy directory recusively
		if( in_array( basename( $source ), $exclude_dirs ) )
		{ // Don't copy this folder
			return true;
		}
		if( ! ( $dir_handle = @opendir( $source ) ) )
		{ // Unable to open dir
			return false;
		}
		$source_folder = is_null( $new_folder_name ) ? basename( $source ) : $new_folder_name;
		if( ! mkdir_r( $dest.'/'.$source_folder ) )
		{ // No rights to create a dir
			return false;
		}
		while( $file = readdir( $dir_handle ) )
		{
			if( $file != '.' && $file != '..' )
			{
				if( is_dir( $source.'/'.$file ) )
				{ // Copy the files of subdirectory
					$result = copy_r( $source.'/'.$file, $dest.'/'.$source_folder, NULL, $exclude_dirs ) && $result;
				}
				else
				{ // Copy one file of the directory
					$result = @copy( $source.'/'.$file, $dest.'/'.$source_folder.'/'.$file ) && $result;
				}
			}
		}
		closedir( $dir_handle );
	}
	else
	{ // Copy a file and check destination folder for existing
		$dest_folder = preg_replace( '#(.+)/[^/]+$#', '$1', $dest );
		if( ! file_exists( $dest_folder ) )
		{ // Create destination folder recursively if it doesn't exist
			if( ! mkdir_r( $dest_folder ) )
			{ // Unable to create a destination folder
				return false;
			}
		}
		// Copy a file
		$result = @copy( $source, $dest );
	}

	return $result;
}


/**
 * Is the given path absolute (non-relative)?
 *
 * @return boolean
 */
function is_absolute_pathname($path)
{
	$pathlen = strlen($path);
	if( ! $pathlen )
	{
		return false;
	}

	if( is_windows() )
	{ // windows e-g: (note: "XY:" can actually happen as a drive ID in windows; I have seen it once in 2009 on MY XP sp3 after plugin in & plugin out an USB stick like 26 times over 26 days! (with sleep/hibernate in between)
		return ( $pathlen > 1 && $path[1] == ':' );
	}
	else
	{ // unix
		return ( $path[0] == '/' );
	}
}


/**
 * Define sys_get_temp_dir, if not available (PHP 5 >= 5.2.1)
 * @link http://us2.php.net/manual/en/function.sys-get-temp-dir.php#93390
 * @return string NULL on failure
 */
if ( !function_exists('sys_get_temp_dir'))
{
  function sys_get_temp_dir()
	{
    if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
    if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
    if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
    $tempfile=tempnam(__FILE__,'');
    if (file_exists($tempfile))
		{
      unlink($tempfile);
      return realpath(dirname($tempfile));
    }
    return null;
  }
}


/**
 * Controller helper
 */
function file_controller_build_tabs()
{
	global $AdminUI, $current_User, $blog, $admin_url;

	$AdminUI->add_menu_entries(
			'files',
			array(
					'browse' => array(
						'text' => T_('Browse'),
						'href' => $admin_url.'?ctrl=files' ),
					)
				);

	if( $current_User->check_perm( 'files', 'add', false, $blog ? $blog : NULL ) )
	{ // Permission to upload: (no subtabs needed otherwise)
		$AdminUI->add_menu_entries(
				'files',
				array(
						'upload' => array(
							'text' => /* TRANS: verb */ T_('Advanced Upload'),
							'href' => $admin_url.'?ctrl=upload' ),
					)
			);
	}

	if( $current_User->check_perm( 'options', 'view' ) )
	{	// Permission to view settings:
		$AdminUI->add_menu_entries(
			'files',
			array(
				'settings' => array(
					'text' => T_('Settings'),
					'href' => $admin_url.'?ctrl=fileset',
					)
				)
			);

		$AdminUI->add_menu_entries(
			array('files', 'settings'),
			array(
					'settings' => array(
						'text' => T_('Settings'),
						'href' => $admin_url.'?ctrl=fileset' ),
					'filetypes' => array(
						'text' => T_('File types'),
						'href' => $admin_url.'?ctrl=filetypes' ),
				)
			);
	}

	if( $current_User->check_perm( 'options', 'edit' ) )
	{ // Permission to edit settings:
		$AdminUI->add_menu_entries(
			'files',
			array(
				'moderation' => array(
					'text' => T_('Moderation'),
					'href' => $admin_url.'?ctrl=filemod',
					'entries' => array(
						'likes' => array(
							'text' => T_('Likes'),
							'href' => $admin_url.'?ctrl=filemod&amp;tab=likes' ),
						'suspicious' => array(
							'text' => T_('Suspicious'),
							'href' => $admin_url.'?ctrl=filemod&amp;tab=suspicious' ),
						'duplicates' => array(
							'text' => T_('Duplicates'),
							'href' => $admin_url.'?ctrl=filemod&amp;tab=duplicates' ),
						)
					)
				)
			);
	}

}


/**
 * Rename evocache folders after File settings update, whe evocahe folder name was chaned
 *
 * @param string old evocache folder name
 * @param string new evocache folder name
 * @return bool true on success
 */
function rename_cachefolders( $oldname, $newname )
{
	$available_Roots = FileRootCache::get_available_FileRoots();

	$slash_oldname = '/'.$oldname;

	$result = true;
	foreach( $available_Roots as $fileRoot )
	{
		$filename_params = array(
				'inc_files'		=> false,
				'inc_evocache'	=> true,
			);
		$dirpaths = get_filenames( $fileRoot->ads_path, $filename_params );

		foreach( $dirpaths as $dirpath )
		{ // search ?evocache folders
			$dirpath_length = strlen( $dirpath );
			if( $dirpath_length < 10 )
			{ // The path is to short, can not contains ?evocache folder name
				continue;
			}
			// searching at the end of the path -> '/' character + ?evocache, length = 1 + 9
			$path_end = substr( $dirpath, $dirpath_length - 10 );
			if( $path_end == $slash_oldname )
			{ // this is a ?evocache folder
				$new_dirpath = substr_replace( $dirpath, $newname, $dirpath_length - 9 );
				// result is true only if all rename call return true (success)
				$result = $result && @rename( $dirpath, $new_dirpath );
			}
		}
	}
	return $result;
}


/**
 * Delete any ?evocache folders.
 *
 * @param Log Pass a Log object here to have error messages added to it.
 * @return integer Number of deleted dirs.
 */
function delete_cachefolders( $Log = NULL )
{
	global $media_path, $Settings;

	if( !isset( $Settings ) )
	{	// This function can be called on install process before initialization of $Settings, Exit here
		return false;
	}

	// Get this, just in case someone comes up with a different naming:
	$evocache_foldername = $Settings->get( 'evocache_foldername' );

	$filename_params = array(
			'inc_files'		=> false,
			'inc_evocache'	=> true,
		);
	$dirs = get_filenames( $media_path, $filename_params );

	$deleted_dirs = 0;
	foreach( $dirs as $dir )
	{
		$basename = basename($dir);
		if( $basename == '.evocache' || $basename == '_evocache' || $basename == $evocache_foldername )
		{	// Delete .evocache directory recursively
			if( rmdir_r( $dir ) )
			{
				$deleted_dirs++;
			}
			elseif( $Log )
			{
				$Log->add( sprintf( T_('Could not delete directory: %s'), $dir ), 'error' );
			}
		}
	}
	return $deleted_dirs;
}


/**
 * Check and set the given FileList object fm_showhidden and fm_showevocache params
 */
function check_showparams( & $Filelist )
{
	global $UserSettings;

	if( $UserSettings->param_Request( 'fm_showhidden', 'fm_showhidden', 'integer', 0 ) )
	{
		$Filelist->_show_hidden_files = true;
	}

	if( $UserSettings->param_Request( 'fm_showevocache', 'fm_showevocache', 'integer', 0 ) )
	{
		$Filelist->_show_evocache = true;
	}
}


/**
 * Process file uploads (this can process multiple file uploads at once)
 *
 * @param string FileRoot id string
 * @param string the upload dir relative path in the FileRoot
 * @param boolean Shall we create path dirs if they do not exist?
 * @param boolean Shall we check files add permission for current_User?
 * @param boolean upload quick mode
 * @param boolean show warnings if filename is not valid
 * @param integer minimum size for pictures in pixels (width and height)
 * @return mixed NULL if upload was impossible to complete for some reason (wrong fileroot ID, insufficient user permission, etc.)
 * 				       array, which contains uploadedFiles, failedFiles, renamedFiles and renamedMessages
 */
function process_upload( $root_ID, $path, $create_path_dirs = false, $check_perms = true, $upload_quickmode = true, $warn_invalid_filenames = true, $min_size = 0 )
{
	global $Settings, $Plugins, $Messages, $current_User, $force_upload_forbiddenext;

	if( empty($_FILES) )
	{	// We have NO uploaded files to process...
		return NULL;
	}

	/**
	 * Remember failed files (and the error messages)
	 * @var array
	 */
	$failedFiles = array();
	/**
	 * Remember uploaded files
	 * @var array
	 */
	$uploadedFiles = array();
	/**
	 * Remember renamed files
	 * @var array
	 */
	$renamedFiles = array();
	/**
	 * Remember renamed Messages
	 * @var array
	 */
	$renamedMessages = array();

	$FileRootCache = & get_FileRootCache();
	$fm_FileRoot = & $FileRootCache->get_by_ID($root_ID, true);
	if( !$fm_FileRoot )
	{ // fileRoot not found:
		return NULL;
	}

	if( $check_perms && ( !isset( $current_User ) || $current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) ) )
	{ // Permission check required but current User has no permission to upload:
		return NULL;
	}

	// Let's get into requested list dir...
	$non_canonical_list_path = $fm_FileRoot->ads_path.$path;
	// Dereference any /../ just to make sure, and CHECK if directory exists:
	$ads_list_path = get_canonical_path( $non_canonical_list_path );

	// check if the upload dir exists
	if( !is_dir( $ads_list_path ) )
	{
		if( $create_path_dirs )
		{ // Create path
			mkdir_r( $ads_list_path );
		}
		else
		{ // This case should not happen! If it happens then there is a bug in the code where this function was called!
			return NULL;
		}
	}

	// Get param arrays for all uploaded files:
	$uploadfile_title = param( 'uploadfile_title', 'array:string', array() );
	$uploadfile_alt = param( 'uploadfile_alt', 'array:string', array() );
	$uploadfile_desc = param( 'uploadfile_desc', 'array:string', array() );
	$uploadfile_name = param( 'uploadfile_name', 'array:string', array() );

	// LOOP THROUGH ALL UPLOADED FILES AND PROCCESS EACH ONE:
	foreach( $_FILES['uploadfile']['name'] as $lKey => $lName )
	{
		if( empty( $lName ) )
		{ // No file name:
			if( $upload_quickmode
				 || !empty( $uploadfile_title[$lKey] )
				 || !empty( $uploadfile_alt[$lKey] )
				 || !empty( $uploadfile_desc[$lKey] )
				 || !empty( $uploadfile_name[$lKey] ) )
			{ // User specified params but NO file! Warn the user:
				$failedFiles[$lKey] = T_( 'Please select a local file to upload.' );
			}
			// Abort upload for this file:
			continue;
		}

		if( $Settings->get( 'upload_maxkb' )
				&& $_FILES['uploadfile']['size'][$lKey] > $Settings->get( 'upload_maxkb' )*1024 )
		{ // File is larger than allowed in settings:
			$failedFiles[$lKey] = sprintf(
					T_('The file is too large: %s but the maximum allowed is %s.'),
					bytesreadable( $_FILES['uploadfile']['size'][$lKey] ),
					bytesreadable($Settings->get( 'upload_maxkb' )*1024) );
			// Abort upload for this file:
			continue;
		}

		if( ( !( $_FILES['uploadfile']['error'][$lKey] ) ) && ( !empty( $min_size ) ) )
		{ // If there is no error and a minimum size is required, check if the uploaded picture satisfies the "minimum size" criteria
			$image_sizes = imgsize( $_FILES['uploadfile']['tmp_name'][$lKey], 'widthheight' );
			if( $image_sizes[0] < $min_size || $image_sizes[1] < $min_size )
			{	// Abort upload for this file:
				$failedFiles[$lKey] = sprintf(
					T_( 'Your profile picture must have a minimum size of %dx%d pixels.' ),
					$min_size,
					$min_size );
				continue;
			}
		}

		if( $_FILES['uploadfile']['error'][$lKey] )
		{ // PHP itself has detected an error!:
			switch( $_FILES['uploadfile']['error'][$lKey] )
			{
				case UPLOAD_ERR_FORM_SIZE:
					// The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.
					// This can easily be edited by the user/hacker, so we do not use it.. file size gets checked for real just above.
					break;

				case UPLOAD_ERR_INI_SIZE:
					// File is larger than allowed in php.ini:
					$failedFiles[$lKey] = 'The file exceeds the upload_max_filesize directive in php.ini.'; // Configuration error, no translation
					// Abort upload for this file:
					continue;

				case UPLOAD_ERR_PARTIAL:
					$failedFiles[$lKey] = T_('The file was only partially uploaded.');
					// Abort upload for this file:
					continue;

				case UPLOAD_ERR_NO_FILE:
					// Is probably the same as empty($lName) before.
					$failedFiles[$lKey] = T_('No file was uploaded.');
					// Abort upload for this file:
					continue;

				case 6: // numerical value of UPLOAD_ERR_NO_TMP_DIR
				# (min_php: 4.3.10, 5.0.3) case UPLOAD_ERR_NO_TMP_DIR:
					// Missing a temporary folder.
					$failedFiles[$lKey] = 'Temporary upload dir is missing! (upload_tmp_dir in php.ini)'; // Configuration error, no translation
					// Abort upload for this file:
					continue;

				default:
					$failedFiles[$lKey] = T_('An unknown error has occurred!').' Error code #'.$_FILES['uploadfile']['error'][$lKey];
					// Abort upload for this file:
					continue;
			}
		}

		if( ! isset($_FILES['uploadfile']['_evo_fetched_url'][$lKey]) // skip check for fetched URLs
			&& ! is_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey] ) )
		{ // Ensure that a malicious user hasn't tried to trick the script into working on files upon which it should not be working.
			$failedFiles[$lKey] = T_('The file does not seem to be a valid upload! It may exceed the upload_max_filesize directive in php.ini.');
			// Abort upload for this file:
			continue;
		}

		// Use new name on server if specified:
		$newName = !empty( $uploadfile_name[ $lKey ] ) ? $uploadfile_name[ $lKey ] : $lName;
		// validate file name
		if( $error_filename = process_filename( $newName, !$warn_invalid_filenames, true, $fm_FileRoot, $path ) )
		{ // Not a valid file name or not an allowed extension:
			$failedFiles[$lKey] = $error_filename;
			syslog_insert( sprintf( 'The uploaded file %s has an unrecognized extension', '<b>'.$newName.'</b>' ), 'warning', 'file' );
			// Abort upload for this file:
			continue;
		}

		// Check if the uploaded file type is an image, and if is an image then try to fix the file extension based on mime type
		// If the mime type is a known mime type and user has right to upload files with this kind of file type,
		// this part of code will check if the file extension is the same as admin defined for this file type, and will fix it if it isn't the same
		// Note: it will also change the jpeg extensions to jpg.
		$uploadfile_path = $_FILES['uploadfile']['tmp_name'][$lKey];
		// this image_info variable will be used again to get file thumb
		$image_info = getimagesize($uploadfile_path);
		if( $image_info )
		{ // This is an image, validate mimetype vs. extension:
			$image_mimetype = $image_info['mime'];
			$FiletypeCache = & get_FiletypeCache();
			// Get correct file type based on mime type
			$correct_Filetype = $FiletypeCache->get_by_mimetype( $image_mimetype, false, false );

			// Check if file type is known by us, and if it is allowed for upload.
			// If we don't know this file type or if it isn't allowed we don't change the extension! The current extension is allowed for sure.
			if( $correct_Filetype && $correct_Filetype->is_allowed() )
			{ // A FileType with the given mime type exists in database and it is an allowed file type for current User
				// The "correct" extension is a plausible one, proceed...
				$correct_extension = array_shift($correct_Filetype->get_extensions());
				$path_info = pathinfo($newName);
				$current_extension = $path_info['extension'];

				// change file extension to the correct extension, but only if the correct extension is not restricted, this is an extra security check!
				if( strtolower($current_extension) != strtolower($correct_extension) && ( !in_array( $correct_extension, $force_upload_forbiddenext ) ) )
				{ // change the file extension to the correct extension
					$old_name = $newName;
					$newName = $path_info['filename'].'.'.$correct_extension;
					$Messages->add( sprintf(T_('The extension of the file &laquo;%s&raquo; has been corrected. The new filename is &laquo;%s&raquo;.'), $old_name, $newName), 'warning' );
				}
			}
		}

		// Get File object for requested target location:
		$oldName = strtolower( $newName );
		list( $newFile, $oldFile_thumb ) = check_file_exists( $fm_FileRoot, $path, $newName, $image_info );
		$newName = $newFile->get( 'name' );

		// Trigger plugin event
		if( $Plugins->trigger_event_first_false( 'AfterFileUpload', array(
					'File' => & $newFile,
					'name' => & $_FILES['uploadfile']['name'][$lKey],
					'type' => & $_FILES['uploadfile']['type'][$lKey],
					'tmp_name' => & $_FILES['uploadfile']['tmp_name'][$lKey],
					'size' => & $_FILES['uploadfile']['size'][$lKey],
				) ) )
		{
			// Plugin returned 'false'.
			// Abort upload for this file:
			continue;
		}

		// Attempt to move the uploaded file to the requested target location:
		if( isset($_FILES['uploadfile']['_evo_fetched_url'][$lKey]) )
		{ // fetched remotely
			if( ! rename( $_FILES['uploadfile']['tmp_name'][$lKey], $newFile->get_full_path() ) )
			{
				$failedFiles[$lKey] = T_('An unknown error occurred when moving the uploaded file on the server.');
				// Abort upload for this file:
				continue;
			}
		}
		elseif( ! move_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey], $newFile->get_full_path() ) )
		{
			syslog_insert( sprintf( 'File %s could not be uploaded', '<b>'.$newFile->get_name().'</b>' ), 'warning', 'file', $newFile->ID );
			$failedFiles[$lKey] = T_('An unknown error occurred when moving the uploaded file on the server.');
			// Abort upload for this file:
			continue;
		}

		// change to default chmod settings
		if( $newFile->chmod( NULL ) === false )
		{ // add a note, this is no error!
			$Messages->add( sprintf( T_('Could not change permissions of &laquo;%s&raquo; to default chmod setting.'), $newFile->dget('name') ), 'note' );
		}

		// Refreshes file properties (type, size, perms...)
		$newFile->load_properties();

		if( ! empty( $oldFile_thumb ) )
		{ // The file name was changed!
			if( $image_info )
			{
				$newFile_thumb = $newFile->get_preview_thumb( 'fulltype' );
			}
			else
			{
				$newFile_thumb = $newFile->get_size_formatted();
			}
			//$newFile_size = bytesreadable ($_FILES['uploadfile']['size'][$lKey]);
			$renamedMessages[$lKey]['message'] = sprintf( T_('"%s was renamed to %s. Would you like to replace %s with the new version instead?'),
														 '&laquo;'.$oldName.'&raquo;', '&laquo;'.$newName.'&raquo;', '&laquo;'.$oldName.'&raquo;' );
			$renamedMessages[$lKey]['oldThumb'] = $oldFile_thumb;
			$renamedMessages[$lKey]['newThumb'] = $newFile_thumb;
			$renamedFiles[$lKey]['oldName'] = $oldName;
			$renamedFiles[$lKey]['newName'] = $newName;
		}

		// Store extra info about the file into File Object:
		if( isset( $uploadfile_title[$lKey] ) )
		{ // If a title text has been passed... (does not happen in quick upload mode)
			$newFile->set( 'title', trim( strip_tags($uploadfile_title[$lKey])) );
		}
		if( isset( $uploadfile_alt[$lKey] ) )
		{ // If an alt text has been passed... (does not happen in quick upload mode)
			$newFile->set( 'alt', trim( strip_tags($uploadfile_alt[$lKey])) );
		}
		if( isset( $uploadfile_desc[$lKey] ) )
		{ // If a desc text has been passed... (does not happen in quick upload mode)
			$newFile->set( 'desc', trim( strip_tags($uploadfile_desc[$lKey])) );
		}

		// Store File object into DB:
		$newFile->dbsave();
		syslog_insert( sprintf( 'File %s was uploaded', '<b>'.$newFile->get_name().'</b>' ), 'info', 'file', $newFile->ID );
		$uploadedFiles[] = $newFile;
	}

	prepare_uploaded_files( $uploadedFiles );

	return array( 'uploadedFiles' => $uploadedFiles, 'failedFiles' => $failedFiles, 'renamedFiles' => $renamedFiles, 'renamedMessages' => $renamedMessages );
}


/**
 * Prepare the uploaded files
 *
 * @param array Uploaded files
 */
function prepare_uploaded_files( $uploadedFiles )
{
	if( count( $uploadedFiles ) == 0 )
	{	// No uploaded files
		return;
	}

	foreach( $uploadedFiles as $File )
	{
		$Filetype = & $File->get_Filetype();
		if( $Filetype )
		{
			if( in_array( $Filetype->mimetype, array( 'image/jpeg', 'image/gif', 'image/png' ) ) )
			{	// Image file
				prepare_uploaded_image( $File, $Filetype->mimetype );
			}
		}
	}
}


/**
 * Prepare image file (Resize, Rotate and etc.)
 *
 * @param object File
 * @param string mimetype
 */
function prepare_uploaded_image( $File, $mimetype )
{
	global $Settings, $Messages;

	$thumb_width = $Settings->get( 'fm_resize_width' );
	$thumb_height = $Settings->get( 'fm_resize_height' );
	$thumb_quality = $Settings->get( 'fm_resize_quality' );

	$do_resize = false;
	if( $Settings->get( 'fm_resize_enable' ) &&
	    $thumb_width > 0 && $thumb_height > 0 )
	{	// Image resizing is enabled
		list( $image_width, $image_height ) = explode( 'x', $File->get_image_size() );
		if( $image_width > $thumb_width || $image_height > $thumb_height )
		{	// This image should be resized
			$do_resize = true;
		}
	}

	load_funcs( 'files/model/_image.funcs.php' );

	$resized_imh = null;
	if( $do_resize )
	{ // Resize image
		list( $err, $src_imh ) = load_image( $File->get_full_path(), $mimetype );
		if( empty( $err ) )
		{
			list( $err, $resized_imh ) = generate_thumb( $src_imh, 'fit', $thumb_width, $thumb_height );
		}

		if( empty( $err ) )
		{ // Image was rezised successfully
			$Messages->add( sprintf( T_( '%s was resized to %dx%d pixels.' ), '<b>'.$File->get('name').'</b>', imagesx( $resized_imh ), imagesy( $resized_imh ) ), 'success' );
		}
		else
		{ // Image was not rezised
			$Messages->add( sprintf( T_( '%s could not be resized to target resolution of %dx%d pixels.' ), '<b>'.$File->get('name').'</b>', $thumb_width, $thumb_height ), 'error' );
			// Error exists, exit here
			return;
		}
	}

	if( $mimetype == 'image/jpeg' )
	{	// JPEG, do autorotate if EXIF Orientation tag is defined
		$save_image = !$do_resize; // If image was be resized, we should save file only in the end of this function
		exif_orientation( $File->get_full_path(), $resized_imh, $save_image );
	}

	if( !$resized_imh )
	{	// Image resource is incorrect
		return;
	}

	if( $do_resize && empty( $err ) )
	{	// Save resized image ( and also rotated image if this operation was done )
		save_image( $resized_imh, $File->get_full_path(), $mimetype, $thumb_quality );
	}
}


/**
 * Rotate the JPEG image if EXIF Orientation tag is defined
 *
 * @param string File name (with full path)
 * @param resource Image resource ( result of the function imagecreatefromjpeg() ) (by reference)
 * @param boolean TRUE - to save the rotated image in the end of this function
 */
function exif_orientation( $file_name, & $imh/* = null*/, $save_image = false )
{
	global $Settings;

	if( !$Settings->get( 'exif_orientation' ) )
	{ // Autorotate is disabled
		return;
	}

	if( ! function_exists('exif_read_data') )
	{ // Exif extension is not loaded
		return;
	}

	$image_info = array();
	getimagesize( $file_name, $image_info );
	if( ! isset( $image_info['APP1'] ) || ( strpos( $image_info['APP1'], 'Exif' ) !== 0 ) )
	{ // This file format is not an 'Exchangeable image file format' so there are no Exif data to read
		return;
	}

	if( ( $exif_data = exif_read_data( $file_name ) ) === false )
	{ // Could not read Exif data
		return;
	}

	if( !( isset( $exif_data['Orientation'] ) && in_array( $exif_data['Orientation'], array( 3, 6, 8 ) ) ) )
	{ // Exif Orientation tag is not defined OR we don't interested in current value
		return;
	}

	load_funcs( 'files/model/_image.funcs.php' );

	if( is_null( $imh ) )
	{ // Create image resource from file name
		$imh = imagecreatefromjpeg( $file_name );
	}

	if( !$imh )
	{ // Image resource is incorrect
		return;
	}

	switch( $exif_data['Orientation'] )
	{
		case 3:	// Rotate for 180 degrees
			$imh = @imagerotate( $imh, 180, 0 );
			break;

		case 6:	// Rotate for 90 degrees to the right
			$imh = @imagerotate( $imh, 270, 0 );
			break;

		case 8:	// Rotate for 90 degrees to the left
			$imh = @imagerotate( $imh, 90, 0 );
			break;
	}

	if( !$imh )
	{	// Image resource is incorrect
		return;
	}

	if( $save_image )
	{	// Save rotated image
		save_image( $imh, $file_name, 'image/jpeg' );
	}
}


/**
 * Check if file exists in the target location with the given name. Used during file upload.
 *
 * @param FileRoot target file Root
 * @param string target path
 * @param string file name
 * @param array the new file image_info
 * @return array contains two elements
 * 			first elements is a new File object
 * 			second element is the existing file thumb, or empty string, if the file doesn't exists
 */
function check_file_exists( $fm_FileRoot, $path, $newName, $image_info = NULL )
{
	global $filename_max_length;

	// Get File object for requested target location:
	$FileCache = & get_FileCache();
	$newFile = & $FileCache->get_by_root_and_path( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, trailing_slash($path).$newName, true );

	$num_ext = 0;
	$oldName = $newName;

	$oldFile_thumb = "";
	while( $newFile->exists() )
	{ // The file already exists in the target location!
		$num_ext++;
		$ext_pos = strrpos( $newName, '.');
		if( $num_ext == 1 )
		{
			if( $image_info == NULL )
			{
				$image_info = getimagesize( $newFile->get_full_path() );
			}
			$newName = substr_replace( $newName, '-'.$num_ext.'.', $ext_pos, 1 );
			if( $image_info )
			{
				$oldFile_thumb = $newFile->get_preview_thumb( 'fulltype' );
			}
			else
			{
				$oldFile_thumb = $newFile->get_size_formatted();
			}
		}
		else
		{
			$replace_length = strlen( '-'.($num_ext-1) );
			$newName = substr_replace( $newName, '-'.$num_ext, $ext_pos-$replace_length, $replace_length );
		}
		if( strlen( $newName ) > $filename_max_length )
		{
			$newName = fix_filename_length( $newName, strrpos( $newName, '-' ) );
			if( $error_filename = process_filename( $newName, true ) )
			{ // The file name is still not valid
				syslog_insert( sprintf( 'Invalid file name %s has found during file exists check', '<b>'.$newName.'</b>' ), 'warning', 'file' );
				debug_die( 'Invalid file name has found during file exists check: '.$error_filename );
			}
		}
		$newFile = & $FileCache->get_by_root_and_path( $fm_FileRoot->type, $fm_FileRoot->in_type_ID, trailing_slash($path).$newName, true );
	}

	return array( $newFile, $oldFile_thumb );
}


/**
 * Remove files with the given ids
 *
 * @param array file ids to remove, default to remove all orphan file IDs
 * @param integer remove files older than the given hour, default NULL will remove all
 * @return integer the number of removed files
 */
function remove_orphan_files( $file_ids = NULL, $older_than = NULL, $remove_empty_comment_folders = false )
{
	global $DB, $localtimenow;
	// asimo> This SQL query should use file class delete_restrictions array (currently T_links and T_users is explicitly used)
	// select orphan comment attachment file ids
	$sql = 'SELECT file_ID FROM T_files
				WHERE file_ID NOT IN (
					SELECT * FROM (
						( SELECT DISTINCT link_file_ID FROM T_links
							WHERE link_file_ID IS NOT NULL ) UNION
						( SELECT DISTINCT user_avatar_file_ID FROM T_users
							WHERE user_avatar_file_ID IS NOT NULL ) ) AS linked_files )';

	if( $file_ids !== NULL )
	{ // Remove only from the given files
		$sql .= ' AND file_ID IN ( '.implode( ',', $file_ids ).' )';
	}
	else
	{ // Remove only the files in the comment folders
		$sql .= ' AND ( file_path LIKE "comments/p%" OR file_path LIKE "anonymous_comments/p%" )';
	}

	if( $remove_empty_comment_folders )
	{ // init "folders to delete" array
		$delete_folders = array();
	}

	$result = $DB->get_col( $sql );
	$FileCache = & get_FileCache();
	$FileCache->load_list( $result );
	$count = 0;
	foreach( $result as $file_ID )
	{
		$File = $FileCache->get_by_ID( $file_ID, false, false );
		if( $older_than != NULL && $File->exists() )
		{ // we have to check if the File is older than the given value
			$datediff = $localtimenow - filemtime( $File->_adfp_full_path );
			if( $datediff > $older_than * 3600 ) // convert hours to seconds
			{ // not older
				continue;
			}
		}

		if( $remove_empty_comment_folders )
		{
			$rel_path = $File->get_rdfp_rel_path();
			$folder_path = dirname( $File->get_full_path() );
		}

		// delete the file
		if( $File->unlink() )
		{
			$count++;
			if( $remove_empty_comment_folders && ! in_array( $folder_path, $delete_folders )
				&& preg_match( '/^(anonymous_)?comments\/p(\d+)\/.*$/', $rel_path ) )
			{ // Collect comment attachments folders to delete the empty folders later
				$delete_folders[] = $folder_path;
			}
		}
	}

	// Delete the empty folders
	if( $remove_empty_comment_folders && count( $delete_folders ) )
	{
		foreach( $delete_folders as $delete_folder )
		{
			if( file_exists( $delete_folder ) )
			{ // Delete folder only if it is empty, Hide an error if folder is not empty
				@rmdir( $delete_folder );
			}
		}
	}

	// Clear FileCache to save memory
	$FileCache->clear();

	return $count;
}


/**
 * Get available icons for file types
 *
 * @return array 'key'=>'name'
 */
function get_available_filetype_icons()
{
	$icons = array(
		''               => T_('Unknown'),
		'file_empty'     => T_('Empty'),
		'file_image'     => T_('Image'),
		'file_document'  => T_('Document'),
		'file_www'       => T_('Web file'),
		'file_log'       => T_('Log file'),
		'file_sound'     => T_('Audio file'),
		'file_video'     => T_('Video file'),
		'file_message'   => T_('Message'),
		'file_pdf'       => T_('PDF'),
		'file_php'       => T_('PHP script'),
		'file_encrypted' => T_('Encrypted file'),
		'file_zip'       => T_('Zip archive'),
		'file_tar'       => T_('Tar archive'),
		'file_tgz'       => T_('Tgz archive'),
		'file_pk'        => T_('Archive'),
		'file_doc'       => T_('Microsoft Word'),
		'file_xls'       => T_('Microsoft Excel'),
		'file_ppt'       => T_('Microsoft PowerPoint'),
		'file_pps'       => T_('Microsoft PowerPoint Slideshow'),
	);

	return $icons;
}


/**
 * Copy file from source path to destination path (Used on import)
 *
 * @param string Path of source file
 * @param string FileRoot id string
 * @param string the upload dir relative path in the FileRoot
 * @param boolean Shall we check files add permission for current_User?
 * @return mixed NULL if import was impossible to complete for some reason (wrong fileroot ID, insufficient user permission, etc.)
 *               file ID of new inserted file in DB
 */
function copy_file( $file_path, $root_ID, $path, $check_perms = true )
{
	global $current_User;

	$FileRootCache = & get_FileRootCache();
	$fm_FileRoot = & $FileRootCache->get_by_ID($root_ID, true);
	if( !$fm_FileRoot )
	{	// fileRoot not found:
		return NULL;
	}

	if( $check_perms && ( !isset( $current_User ) || $current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) ) )
	{	// Permission check required but current User has no permission to upload:
		return NULL;
	}

	// Let's get into requested list dir...
	$non_canonical_list_path = $fm_FileRoot->ads_path.$path;
	// Dereference any /../ just to make sure, and CHECK if directory exists:
	$ads_list_path = get_canonical_path( $non_canonical_list_path );

	// check if the upload dir exists
	if( !is_dir( $ads_list_path ) )
	{	// Create path
		mkdir_r( $ads_list_path );
	}

	// Get file name from full path:
	$newName = basename( $file_path );
	// validate file name
	if( $error_filename = process_filename( $newName, true ) )
	{	// Not a valid file name or not an allowed extension:
		syslog_insert( sprintf( 'File %s is not valid or not an allowed extension', '<b>'.$newName.'</b>' ), 'warning', 'file' );
		// Abort import for this file:
		return NULL;
	}

	// Check if the imported file type is an image, and if is an image then try to fix the file extension based on mime type
	// If the mime type is a known mime type and user has right to import files with this kind of file type,
	// this part of code will check if the file extension is the same as admin defined for this file type, and will fix it if it isn't the same
	// Note: it will also change the jpeg extensions to jpg.
	// this image_info variable will be used again to get file thumb
	$image_info = getimagesize( $file_path );
	if( $image_info )
	{	// This is an image, validate mimetype vs. extension:
		$image_mimetype = $image_info['mime'];
		$FiletypeCache = & get_FiletypeCache();
		// Get correct file type based on mime type
		$correct_Filetype = $FiletypeCache->get_by_mimetype( $image_mimetype, false, false );

		// Check if file type is known by us, and if it is allowed for upload.
		// If we don't know this file type or if it isn't allowed we don't change the extension! The current extension is allowed for sure.
		if( $correct_Filetype && $correct_Filetype->is_allowed() )
		{	// A FileType with the given mime type exists in database and it is an allowed file type for current User
			// The "correct" extension is a plausible one, proceed...
			$correct_extension = array_shift($correct_Filetype->get_extensions());
			$path_info = pathinfo($newName);
			$current_extension = $path_info['extension'];

			// change file extension to the correct extension, but only if the correct extension is not restricted, this is an extra security check!
			if( strtolower($current_extension) != strtolower($correct_extension) && ( !in_array( $correct_extension, $force_upload_forbiddenext ) ) )
			{	// change the file extension to the correct extension
				$old_name = $newName;
				$newName = $path_info['filename'].'.'.$correct_extension;
			}
		}
	}

	// Get File object for requested target location:
	$oldName = strtolower( $newName );
	list( $newFile, $oldFile_thumb ) = check_file_exists( $fm_FileRoot, $path, $newName, $image_info );
	$newName = $newFile->get( 'name' );

	if( ! copy( $file_path, $newFile->get_full_path() ) )
	{	// Abort import for this file:
		return NULL;
	}

	// change to default chmod settings
	$newFile->chmod( NULL );

	// Refreshes file properties (type, size, perms...)
	$newFile->load_properties();

	// Store File object into DB:
	if( $newFile->dbsave() )
	{	// Success
		return $newFile->ID;
	}
	else
	{	// Failure
		return NULL;
	}
}


/**
 * Create links between users and image files from the users profile_pictures folder
 */
function create_profile_picture_links()
{
	global $DB;

	load_class( 'files/model/_filelist.class.php', 'Filelist' );
	load_class( 'files/model/_fileroot.class.php', 'FileRoot' );
	$path = 'profile_pictures';

	$FileRootCache = & get_FileRootCache();
	$UserCache = & get_UserCache();

	// SQL query to get all users and limit by page below
	$users_SQL = new SQL();
	$users_SQL->SELECT( '*' );
	$users_SQL->FROM( 'T_users' );
	$users_SQL->ORDER_BY( 'user_ID' );

	$page = 0;
	$page_size = 100;
	while( count( $UserCache->cache ) > 0 || $page == 0 )
	{ // Load users by 100 at one time to avoid errors about memory exhausting
		$users_SQL->LIMIT( ( $page * $page_size ).', '.$page_size );
		$UserCache->clear();
		$UserCache->load_by_sql( $users_SQL );

		while( ( $iterator_User = & $UserCache->get_next(/* $user_ID, false, false */) ) != NULL )
		{ // Iterate through UserCache)
			$FileRootCache->clear();
			$user_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $iterator_User->ID );
			if( !$user_FileRoot )
			{ // User FileRoot doesn't exist
				continue;
			}

			$ads_list_path = get_canonical_path( $user_FileRoot->ads_path.$path );
			// Previously uploaded avatars
			if( !is_dir( $ads_list_path ) )
			{ // profile_picture folder doesn't exists in the user root dir
				continue;
			}

			$user_avatar_Filelist = new Filelist( $user_FileRoot, $ads_list_path );
			$user_avatar_Filelist->load();

			if( $user_avatar_Filelist->count() > 0 )
			{	// profile_pictures folder is not empty
				$info_content = '';
				$LinkOwner = new LinkUser( $iterator_User );
				while( $lFile = & $user_avatar_Filelist->get_next() )
				{ // Loop through all Files:
					$fileName = $lFile->get_name();
					if( process_filename( $fileName ) )
					{ // The file has invalid file name, don't create in the database
						syslog_insert( sprintf( 'Invalid file name %s has been found in a user folder', '<b>'.$fileName.'</b>' ), 'info', 'user', $iterator_User->ID );
						// TODO: asimo> we should collect each invalid file name here, and send an email to the admin
						continue;
					}
					$lFile->load_meta( true );
					if( $lFile->is_image() )
					{
						$lFile->link_to_Object( $LinkOwner );
					}
				}
			}
		}

		// Increase page number to get next portion of users
		$page++;
	}

	// Clear cache data
	$UserCache->clear();
	$FileRootCache->clear();
}


/**
 * Create .htaccess and sample.htaccess files with deny rules in the folder
 *
 * @param string Directory path
 * @return boolean TRUE if files have been created successfully
 */
function create_htaccess_deny( $dir )
{
	if( ! mkdir_r( $dir, NULL ) )
	{
		return false;
	}

	$htaccess_files = array(
			$dir.'.htaccess',
			$dir.'sample.htaccess'
		);

	$htaccess_content = '# We don\'t want web users to access any file in this directory'."\r\n".
		'Order Deny,Allow'."\r\n".
		'Deny from All';

	foreach( $htaccess_files as $htaccess_file )
	{
		if( file_exists( $htaccess_file ) )
		{ // File already exists
			continue;
		}

		$handle = @fopen( $htaccess_file, 'w' );

		if( !$handle )
		{ // File cannot be created
			return false;
		}

		fwrite( $handle, $htaccess_content );
		fclose( $handle );
	}

	return true;
}


/**
 * Display a button to quick upload the files by drag&drop method
 *
 * @param integer ID of FileRoot object
 */
function display_dragdrop_upload_button( $params = array() )
{
	global $htsrv_url, $blog, $current_User;

	$params = array_merge( array(
			'before'           => '',
			'after'            => '',
			'fileroot_ID'      => 0, // Root type and ID, e.g. collection_1
			'path'             => '', // Subpath for the file/folder
			'list_style'       => 'list',  // 'list' or 'table'
			'template_button'  => '<div class="qq-uploader">'
					.'<div class="qq-upload-drop-area"><span>'.TS_('Drop files here to upload').'</span></div>'
					.'<div class="qq-upload-button">#button_text#</div>'
					.'<ul class="qq-upload-list"></ul>'
				.'</div>',
			'template_filerow' => '<li>'
					.'<span class="qq-upload-file"></span>'
					.'<span class="qq-upload-spinner"></span>'
					.'<span class="qq-upload-size"></span>'
					.'<a class="qq-upload-cancel" href="#">'.TS_('Cancel').'</a>'
					.'<span class="qq-upload-failed-text">'.TS_('Failed').'</span>'
				.'</li>',
			'display_support_msg'    => true, // Display info under button about that current supports drag&drop
			'additional_dropzone'    => '', // jQuery selector of additional drop zone
			'filename_before'        => '', // Append this text before file name on success uploading of new file,
			                             // Used a mask $file_path$ to replace it with File->get_rdfp_rel_path()
			'LinkOwner'              => NULL, // Use it if you want to link a file to Item/Comment right after uploading
			'display_status_success' => true, // Display status text about successful uploading
			'status_conflict_place'  => 'default', // Where we should write a message about conflict:
																						 //    'default' - in the element ".qq-upload-status"
																						 //    'before_button' - before button to solve a conflict
			'conflict_file_format'   => 'simple', // 'simple' - file name text, 'full_path_link' - a link with text as full file path
			'resize_frame'           => false, // Resize frame on upload new image
			'table_headers'          => '', // Use this html text as table headers when first file is loaded
		), $params );

	$FileRootCache = & get_FileRootCache();
	$fm_FileRoot = $FileRootCache->get_by_ID( $params['fileroot_ID'] );

	if( ! is_logged_in() || ! $current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) )
	{	// Don't display the button if current user has no permission to upload to the selected file root:
		return;
	}

	$root_and_path = $params['fileroot_ID'].'::'.$params['path'];
	$quick_upload_url = $htsrv_url.'quick_upload.php?upload=true'.( empty( $blog ) ? '' : '&blog='.$blog );

	echo $params['before'];

	?>
	<div id="file-uploader" style="width:100%">
		<noscript>
			<p><?php echo T_('Please enable JavaScript to use file uploader.'); ?></p>
		</noscript>
	</div>
	<input id="saveBtn" type="submit" style="display:none" name="saveBtn" value="<?php echo T_('Save modified files'); ?>" class="ActionButton" />
	<script type="text/javascript">
		if( 'draggable' in document.createElement('span') )
		{
			var button_text = '<?php echo TS_('Drag & Drop files to upload here <br /><span>or click to manually select files...</span>') ?>';
			var file_uploader_note_text = '<?php echo TS_('Your browser supports full upload functionality.') ?>';
		}
		else
		{
			var button_text = '<?php echo TS_('Click to manually select files...') ?>';
			var file_uploader_note_text = '<?php echo TS_('Your browser does not support full upload functionality: You can only upload files one by one and you cannot use Drag & Drop.') ?>';
		}

		var url = <?php echo '"'.$quick_upload_url.'&'.url_crumb( 'file' ).'"'; ?>;
		var root_and_path = '<?php echo $root_and_path ?>';

		jQuery( '#fm_dirtree input[type=radio]' ).click( function()
		{
			url = "<?php echo $quick_upload_url; ?>"+"&root_and_path="+this.value+"&"+"<?php echo url_crumb( 'file' ); ?>";
			root_and_path = this.value;
			uploader.setParams({root_and_path: root_and_path});
		} );

		<?php
		if( $params['LinkOwner'] !== NULL )
		{ // Add params to link a file right after uploading
			global $b2evo_icons_type;
			$link_owner_type = $params['LinkOwner']->type;
			$link_owner_ID = ( $link_owner_type == 'item' ? $params['LinkOwner']->Item->ID : $params['LinkOwner']->Comment->ID );
			echo 'url += "&link_owner='.$link_owner_type.'_'.$link_owner_ID.'&b2evo_icons_type='.$b2evo_icons_type.'"';
		}
		?>

		jQuery( document ).ready( function()
		{
			uploader = new qq.FileUploader(
			{
				element: document.getElementById( 'file-uploader' ),
				list_style: '<?php echo $params['list_style']; ?>',
				additional_dropzone: '<?php echo $params['additional_dropzone']; ?>',
				action: url,
				debug: true,
				onSubmit: function( id, fileName )
				{
					var noresults_row = jQuery( 'tr.noresults' );
					if( noresults_row.length )
					{ // Add table headers and remove "No results" row
						<?php
						if( $params['table_headers'] != '' )
						{ // Append table headers if they are defined
						?>
						noresults_row.parent().parent().prepend( '<?php echo str_replace( array( "'", "\n" ), array( "\'", '' ), $params['table_headers'] ); ?>' );
						<?php } ?>
						noresults_row.remove();
					}
				},
				onComplete: function( id, fileName, responseJSON )
				{
					if( responseJSON.success != undefined )
					{
						if( responseJSON.success.status == 'fatal' )
						{
							var text = responseJSON.success.text;
						}
						else
						{
							var text = base64_decode( responseJSON.success.text );
							if( responseJSON.success.specialchars == 1 )
							{
								text = htmlspecialchars_decode( text );
							}
						}

						<?php
						if( $params['list_style'] == 'list' )
						{ // List view
						?>
						if( responseJSON.success.status != undefined && responseJSON.success.status == 'rename' )
						{
							jQuery('#saveBtn').show();
						}
						<?php } ?>
					}

					<?php
					if( $params['list_style'] == 'table' )
					{ // Table view
					?>
					var this_row = jQuery( 'tr[rel=file_upload_' + id + ']' );

					if( responseJSON.success == undefined || responseJSON.success.status == 'error' || responseJSON.success.status == 'fatal' )
					{ // Failed
						this_row.find( '.qq-upload-status' ).html( '<span class="red"><?php echo TS_('Upload ERROR'); ?></span>' );
						if( typeof( text ) == 'undefined' || text == '' )
						{ // Message for unknown error
							text = '<?php echo TS_('Server dropped the connection.'); ?>';
						}
						this_row.find( '.qq-upload-file' ).append( ' <span class="result_error">' + text + '</span>' );
						this_row.find( '.qq-upload-image, td.size' ).prepend( '<?php echo get_icon( 'warning_yellow' ); ?>' );
					}
					else
					{ // Success/Conflict
						var table_view = typeof( responseJSON.success.link_ID ) != 'undefined' ? 'link' : 'file';

						var filename_before = '<?php echo str_replace( "'", "\'", $params['filename_before'] ); ?>';
						if( filename_before != '' )
						{
							filename_before = filename_before.replace( '$file_path$', responseJSON.success.path );
						}

						var warning = '';
						if( responseJSON.success.warning != '' )
						{
							warning = '<div class="orange">' + responseJSON.success.warning + '</div>';
						}

						// File name or url to view file
						var file_name = ( typeof( responseJSON.success.link_url ) != 'undefined' ) ? responseJSON.success.link_url : responseJSON.success.newname;

						if( responseJSON.success.status == 'success' )
						{ // Success upload
							<?php
							if( $params['display_status_success'] )
							{ // Display this message only if it is enabled
							?>
							this_row.find( '.qq-upload-status' ).html( '<span class="green"><?php echo TS_('Upload OK'); ?></span>' );
							<?php } else { ?>
							this_row.find( '.qq-upload-status' ).html( '' );
							<?php } ?>
							this_row.find( '.qq-upload-image' ).html( text );
							this_row.find( '.qq-upload-file' ).html( filename_before
								+ '<input type="hidden" value="' + responseJSON.success.newpath + '" />'
								+ '<span class="fname">' + file_name + '</span>' + warning );
						}
						else if( responseJSON.success.status == 'rename' )
						{ // Conflict on upload
							<?php
							$status_conflict_message = '<span class="orange">'.TS_('Upload Conflict').'</span>';
							if( $params['status_conflict_place'] == 'default' )
							{ // Default place for a conflict message
							?>
							this_row.find( '.qq-upload-status' ).html( '<?php echo $status_conflict_message; ?>' );
							<?php } else { ?>
							this_row.find( '.qq-upload-status' ).html( '' );
							<?php } ?>
							this_row.find( '.qq-upload-image' ).append( htmlspecialchars_decode( responseJSON.success.file ) );
							this_row.find( '.qq-upload-file' ).html( filename_before
								+ '<input type="hidden" value="' + responseJSON.success.newpath + '" />'
								+ '<span class="fname">' + file_name + '</span>'
								<?php echo ( $params['status_conflict_place'] == 'before_button' ) ? "+ ' - ".$status_conflict_message."'" : ''; ?>
								+ ' - <a href="#" '
								+ 'class="<?php echo button_class( 'text' ); ?> roundbutton_text_noicon qq-conflict-replace" '
								+ 'old="' + responseJSON.success.oldpath + '" '
								+ 'new="' + responseJSON.success.newpath + '">'
								+ '<div><?php echo TS_('Use this new file to replace the old file'); ?></div>'
								+ '<div style="display:none"><?php echo TS_('Revert'); ?></div>'
								+ '</a>'
								+ warning );
							var old_file_obj = jQuery( 'input[type=hidden][value="' + responseJSON.success.oldpath + '"]' );
							if( old_file_obj.length > 0 )
							{
								old_file_obj.parent().append( ' <span class="orange"><?php echo TS_('(Old File)'); ?></span>' );
							}
						}

						if( table_view == 'link' )
						{ // Update the cells for link view, because these data exist in response
							this_row.find( '.qq-upload-link-id' ).html( responseJSON.success.link_ID );
							this_row.find( '.qq-upload-link-actions' ).prepend( responseJSON.success.link_actions );
							this_row.find( '.qq-upload-link-position' ).html( responseJSON.success.link_position );
						}
					}
					<?php
					}
					else
					{ // Simple list
					?>
						jQuery( uploader._getItemByFileId( id ) ).append( text );
						if( responseJSON.success == undefined && responseJSON != '' )
						{ // Disppay the fatal errors
							jQuery( uploader._getItemByFileId( id ) ).append( responseJSON );
						}
					<?php
					}

					if( $params['resize_frame'] )
					{ // Resize frame after upload new image
					?>
					update_iframe_height();
					jQuery( 'img' ).on( 'load', function() { update_iframe_height(); } );
					<?php } ?>
				},
				template: '<?php echo str_replace( '#button_text#', "' + button_text + '", $params['template_button'] ); ?>',
				fileTemplate: '<?php echo $params['template_filerow']; ?>',
				params: { root_and_path: root_and_path }
			} );
		} );

		<?php
		if( $params['resize_frame'] )
		{ // Resize frame after upload new image
		?>
		function update_iframe_height()
		{
			var wrapper_height = jQuery( 'body' ).height();
			jQuery( 'div#attachmentframe_wrapper', window.parent.document ).css( { 'height': wrapper_height, 'max-height': wrapper_height } );
		}
		<?php } ?>

		<?php
		if( $params['list_style'] == 'table' )
		{
		// A click event for button to replace old file with name
		?>
		jQuery( document ).on( 'click', '.qq-conflict-replace', function()
		{
			var this_obj = jQuery( this );

			var is_replace = this_obj.children( 'div:first' ).is( ':visible' );

			var old_file_name = this_obj.attr( 'old' );
			var old_file_obj = jQuery( 'input[type=hidden][value="' + old_file_name + '"]' );
			// Element found with old file name on the page
			var old_file_exists = ( old_file_obj.length > 0 );
			this_obj.hide();

			// Highlight the rows with new and old files
			var tr_rows = this_obj.parent().parent().children( 'td' );
			if( old_file_exists )
			{
				tr_rows = tr_rows.add( old_file_obj.parent().parent().children( 'td' ) );
			}
			tr_rows.css( 'background', '#FFFF00' );
			// Remove previous errors
			tr_rows.find( 'span.error' ).remove();

			jQuery.ajax(
			{ // Replace old file name with new
				type: 'POST',
				url: '<?php echo get_secure_htsrv_url(); ?>async.php',
				data:
				{
					action: 'conflict_files',
					fileroot_ID: '<?php echo $params['fileroot_ID']; ?>',
					path: '<?php echo $params['path']; ?>',
					oldfile: old_file_name.replace( /^(.+\/)?([^\/]+)$/, '$2' ),
					newfile: this_obj.attr( 'new' ).replace( /^(.+\/)?([^\/]+)$/, '$2' ),
					format: '<?php echo $params['conflict_file_format']; ?>',
					crumb_conflictfiles: '<?php echo get_crumb( 'conflictfiles' ); ?>'
				},
				success: function( result )
				{
					var data = jQuery.parseJSON( result );
					if( typeof data.error == 'undefined' )
					{ // Success
						this_obj.show();
						var new_filename_obj = this_obj.parent().find( 'span.fname' );
						if( is_replace )
						{ // The replacing was executed, Change data of html elements
							this_obj.children( 'div:first' ).hide();
							this_obj.children( 'div:last' ).show();
						}
						else
						{ // The replacing was reverting, Put back the data of html elements
							this_obj.children( 'div:first' ).show();
							this_obj.children( 'div:last' ).hide();
						}
						if( old_file_exists )
						{ // If old file element exists on the page, we can:
							// Swap old and new names
							var old_filename_obj = old_file_obj.parent().find( 'span.fname' );
							var old_filename_obj_html = old_filename_obj.html();
							old_filename_obj.html( new_filename_obj.html() );
							new_filename_obj.html( old_filename_obj_html );

							var old_icon_link = old_filename_obj.prev();
							if( old_icon_link.length == 0 || old_icon_link.get(0).tagName != 'A' )
							{
								old_icon_link = old_filename_obj.parent().prev();
							}
							if( old_icon_link.length > 0 && old_icon_link.get(0).tagName == 'A' )
							{ // The icons exist to link files, We should swap them
								var old_href = old_icon_link.attr( 'href' );
								old_icon_link.attr( 'href', new_filename_obj.prev().attr( 'href' ) );
								new_filename_obj.prev().attr( 'href', old_href );
							}
						}
						else
						{ // No old file element, Get data from request
							new_filename_obj.html( is_replace ? data.old : data.new );
						}
					}
					else
					{ // Failed
						this_obj.show();
						this_obj.parent().append( '<span class="error"> - ' + data.error + '</span>' );
					}
					tr_rows.css( 'background', '' );
				}
			} );

			return false;
		} );
		<?php } ?>

		<?php
		if( $params['display_support_msg'] )
		{ // Display a message about the dragdrop supproting by current browser
		?>
		document.write( '<p class="note">' + file_uploader_note_text + '</p>' );
		<?php } ?>
	</script>
	<?php

	echo $params['after'];
}


/**
 * Replace the old file with the new one
 *
 * @param string Root type: 'user', 'group' or 'collection'
 * @param integer ID of the user, the group or the collection the file belongs to...
 * @param string Subpath for this file/folder, relative the associated root, including trailing slash (if directory)
 * @param string Name of NEW file
 * @param string Name of OLD file
 * @param boolean TRUE to display message
 * @return boolean|string TRUE on success, otherwise an error message
 */
function replace_old_file_with_new( $root_type, $root_in_type_ID, $path, $new_name, $old_name, $display_message = true )
{
	$error_message = '';

	if( empty( $new_name ) )
	{
		$error_message = T_( 'The new file name is empty!' );
	}
	elseif( empty( $new_name ) )
	{
		$error_message = T_( 'The old file name is empty!' );
	}

	if( empty( $error_message ) )
	{
		$FileCache = & get_FileCache();
		$newFile = & $FileCache->get_by_root_and_path( $root_type, $root_in_type_ID, trailing_slash( $path ).$new_name, true );
		$oldFile = & $FileCache->get_by_root_and_path( $root_type, $root_in_type_ID, trailing_slash( $path ).$old_name, true );
		$new_filename = $newFile->get_name();
		$old_filename = $oldFile->get_name();
		$dir = $newFile->get_dir();
		$oldFile->rm_cache();
		$newFile->rm_cache();

		// rename new uploaded file to temp file name
		$index = 0;
		$temp_filename = 'temp'.$index.'-'.$new_filename;
		while( file_exists( $dir.$temp_filename ) )
		{ // find an unused filename
			$index++;
			$temp_filename = 'temp'.$index.'-'.$new_filename;
		}
	}

	// @rename will overwrite a file with the same name if exists. In this case it shouldn't be a problem.
	if( empty( $error_message ) && ( ! @rename( $newFile->get_full_path(), $dir.$temp_filename ) ) )
	{ // rename new file to temp file name failed
		$error_message = sprintf( T_( 'The new file could not be renamed to %s' ), $temp_filename );
	}

	if( empty( $error_message ) && ( ! @rename( $oldFile->get_full_path(), $dir.$new_filename ) ) )
	{ // rename original file to the new file name failed
		$error_message = sprintf( T_( 'The original file could not be renamed to %s. The new file is now named %s.' ), $new_filename, $temp_filename );
	}

	if( empty( $error_message ) && ( ! @rename( $dir.$temp_filename, $dir.$old_filename ) ) )
	{ // rename new file to the original file name failed
		$error_message = sprintf( T_( 'The new file could not be renamed to %s. It is now named %s.' ), $old_filename, $temp_filename );
	}

	if( $display_message )
	{
		global $Messages;
		if( empty( $error_message ) )
		{
			$Messages->add( sprintf( T_( '%s has been replaced with the new version!' ), $old_filename ), 'success' );
		}
		else
		{
			$Messages->add( $error_message, 'error' );
		}
	}

	return empty( $error_message ) ? true : $error_message;
}


/**
 * Check if directory is empty
 *
 * @param string Directory path
 * @param boolean TRUE - to decide when dir is not empty if at least one file exists in subdirectories,
 *                FALSE - to decide - if dir contains even only empty subdirectories
 * @return boolean TRUE if directory is empty
 */
function is_empty_directory( $dir, $recursive = true )
{
	$result = true;

	if( empty( $dir ) || ! file_exists( $dir ) || ! is_readable( $dir ) )
	{ // Return TRUE if dir doesn't exist or it is not readbale
		return $result;
	}

	// Fix dir path if slash is missed at the end
	$dir = rtrim( $dir, '/' ).'/';

	$handle = opendir( $dir );
	while( ( $file = readdir( $handle ) ) !== false )
	{
		if( $file != '.' && $file != '..' )
		{ // Check what is it, dir or file?
			if( $recursive && is_dir( $dir.$file ) )
			{ // It is a directory - try to find the files inside
				$result = is_empty_directory( $dir.$file, $recursive );
				if( $result === false )
				{ // A file was found inside the directory
					break;
				}
			}
			else
			{ // It is a file then directory is not empty
				$result = false;
				break; // Stop here
			}
		}
	}
	closedir( $handle );

	return $result;
}


/**
 * Open temporary file
 *
 * @param string Name of the temporary file (changed by reference)
 * @return resource|string File handle OR Error text
 */
function open_temp_file( & $temp_file_name )
{
	$temp_handle = tmpfile();

	if( $temp_handle === false )
	{ // Error on create a temp file on system temp dir
		global $media_path;

		$temp_path = $media_path.'upload-tmp/';

		$temp_folder_exists = file_exists( $temp_path );
		if( ! $temp_folder_exists )
		{ // Temp folder doesn't exist yet
			// Try to create it
			$temp_folder_exists = @mkdir( $temp_path, 0755 );
			if( $temp_folder_exists )
			{ // If temp folder has been created try to create .htaccess to prevent listing
				if( $htaccess_handle = @fopen( $temp_path.'.htaccess', 'w+' ) )
				{
					fwrite( $htaccess_handle, 'deny from all' );
					fclose( $htaccess_handle );
				}
			}
		}

		if( $temp_folder_exists )
		{ // Try to create a temp file on media temp path
			$temp_file_name = tempnam( $temp_path, 'qck_' );
		}

		if( ! $temp_folder_exists || $temp_file_name === false )
		{ // Error on create a temp file on media temp path
			return sprintf( T_( 'Unable to create temporary upload file. PHP needs write permissions on %s or %s.' ),
				'<b>'.sys_get_temp_dir().'</b>',
				'<b>'.$temp_path.'</b>' );
		}

		// Create a file handle for new temp file on media path
		$temp_handle = fopen( $temp_file_name, 'r+' );
	}

	// File handle
	return $temp_handle;
}


/**
 * Initialize JavaScript for AJAX loading of popup window to report user
 *
 * @param array Params
 */
function echo_file_properties()
{
	global $admin_url;
?>
<script type="text/javascript">
	//<![CDATA[
<?php
// Initialize JavaScript to build and open window
echo_modalwindow_js();
?>
	// Window to edit file
	function file_properties( root, path, file )
	{
		openModalWindow( '<span class="loader_img loader_file_edit absolute_center" title="<?php echo T_('Loading...'); ?>"></span>',
			'80%', '', true,
			'<?php echo TS_('File properties'); ?>',
			'<?php echo TS_('Save Changes!'); ?>', true, true );
		jQuery.ajax(
		{
			type: 'POST',
			url: '<?php echo $admin_url; ?>',
			data:
			{
				'ctrl': 'files',
				'action': 'edit_properties',
				'root': root,
				'path': path,
				'fm_selected': [ file ],
				'mode': 'modal',
				'crumb_file': '<?php echo get_crumb( 'file' ); ?>',
			},
			success: function( result )
			{
				openModalWindow( result, '80%', '',true,
					'<?php echo TS_('File properties'); ?>',
					'<?php echo TS_('Save Changes!'); ?>', false, true );
			}
		} );
		return false;
	}
	//]]>
</script>
<?php
}
?>