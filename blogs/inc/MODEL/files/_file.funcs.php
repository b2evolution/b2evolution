<?php
/**
 * This file implements various File handling functions.
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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Converts bytes to readable bytes/kb/mb/gb, like "12.45mb"
 *
 * @param integer bytes
 * @return string bytes made readable
 */
function bytesreadable( $bytes, $decimals = 2 )
{
	static $types = NULL;

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

	$r = round($bytes, $decimals).'&nbsp;';
	$r .= '<abbr title="'.$types[$i]['text'].'">';
	$r .= $types[$i]['abbr'];
	$r .= '</abbr>';

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
 * @param boolean include files (not only directories)
 * @param boolean include directories (not the directory itself!)
 * @param boolean flat (return an one-dimension-array)
 * @param boolean Recurse into subdirectories?
 * @param boolean Get the basename only.
 * @return false|array false if the first directory could not be accessed,
 *                     array of entries otherwise
 */
function get_filenames( $path, $inc_files = true, $inc_dirs = true, $flat = true, $recurse = true, $basename = false )
{
	$r = array();

	$path = trailing_slash( $path );

	if( $dir = @opendir($path) )
	{
		while( ( $file = readdir($dir) ) !== false )
		{
			if( $file == '.' || $file == '..' )
			{
				continue;
			}
			if( is_dir($path.$file) )
			{
				if( $flat )
				{
					if( $inc_dirs )
					{
						$r[] = $basename ? $file : $path.$file;
					}
					if( $recurse )
					{
						$rSub = get_filenames( $path.$file, $inc_files, $inc_dirs, $flat, $recurse, $basename );
						if( $rSub )
						{
							$r = array_merge( $r, $rSub );
						}
					}
				}
				else
				{
					$r[$file] = get_filenames( $path.$file, $inc_files, $inc_dirs, $flat, $recurse, $basename );
				}
			}
			elseif( $inc_files )
			{
				$r[] = $basename ? $file : $path.$file;
			}
		}
		closedir($dir);
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
 * @return array|false List of directory names that hold admin skins or
 *         false, if the admin skins driectory does not exist.
 */
function get_admin_skins()
{
	global $adminskins_path, $admin_subdir, $adminskins_subdir;

	$dirs_in_adminskins_dir = get_filenames( $adminskins_path, false, true, true, false, true );

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
 * Get size of a directory, including anything (especially subdirs) in there.
 *
 * @param string the dir's full path
 */
function get_dirsize_recursive( $path )
{
	$files = get_filenames( $path, true, false );
	$total = 0;

	foreach( $files as $lFile )
	{
		$total += filesize($lFile);
	}

	return $total;
}


/**
 * Deletes a dir recursive, wiping out all subdirectories!!
 *
 * @param string the dir
 */
function deldir_recursive( $dir )
{
	$toDelete = get_filenames( $dir );
	$toDelete = array_reverse( $toDelete );
	$toDelete[] = $dir;

	while( list( $lKey, $lPath ) = each( $toDelete ) )
	{
		if( is_dir( $lPath ) )
		{
			rmdir( $lPath );
		}
		else
		{
			unlink( $lPath );
		}
	}

	return true;
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
 * Remove trailing slash, if present
 *
 * @param string the path/url
 * @return string the path/url without trailing slash
 */
function no_trailing_slash( $path )
{
	if( substr( $path, -1 ) == '/' )
	{
		return substr( $path, 0, strlen( $path ) );
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
 * @return string absolute reduced path, slash terminated or NULL if the path doesn't exist
 */
function get_canonical_path( $ads_path )
{
	// Remove windows backslashes:
	$ads_path = str_replace( '\\', '/', $ads_path );

	// Make sure there's a trailing slash
	$ads_path = trailing_slash($ads_path);

	$ads_path = str_replace( '//', '/', $ads_path );
	$ads_path = str_replace( '/./', '/', $ads_path );
	while( ($ads_realpath = preg_replace( '#(^|/)([^/^.]+)/\.\./#', '$1', $ads_path )) != $ads_path )
	{ // While we find /../ back references to dereference...
		// echo '*';
		$ads_path = $ads_realpath;
	}

	// pre_dump( 'get_canonical_path()', $ads_path, $ads_realpath );

	if( strpos( $ads_realpath, '..' ) !== false )
	{	// Path malformed:
		return NULL;
	}

	return $ads_realpath;
}


/**
 * Returns canonicalized absolute pathname as with realpath(), except it will
 * also translate paths that don't exist on the system.
 *
 * @deprecated overly complex
 * @todo remove
 *
 * @param string the path to be translated
 * @return array [0] = the translated path (with trailing slash); [1] = TRUE|FALSE (path exists?)
 */
function check_canonical_path( $path )
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

		$r_path = implode('/', $pwdArr).'/';

		if( strpos( ltrim($path), '/' ) === 0 )
		{ // There was at least one slash at the beginning
			$r_path = '/'.$r_path;
		}
		return array( $r_path, false );
	}
}


/**
 * Check for valid filename and extension of the filename (no path allowed). (MB)
 *
 * @uses $FiletypeCache, $settings or $force_regexp_filename form _advanced.php
 *
 * @param string filename to test
 * @param boolean
 * @return nothing if the filename is valid according to the regular expression and the extension too, error message if not
 */
function validate_filename( $filename, $allow_locked_filetypes = false )
{
	global $Settings, $force_regexp_filename;

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
		$FiletypeCache = & get_Cache( 'FiletypeCache' );
		if( $Filetype = & $FiletypeCache->get_by_extension( strtolower( $match[1] ) , false ) )
		{
			if( $Filetype->allowed || $allow_locked_filetypes )
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
	global $Settings, $force_regexp_dirname;

	if( !empty( $force_regexp_dirname ) )
	{ // Use the regexp from _advanced.php
		if( preg_match( ':'.str_replace( ':', '\:', $force_regexp_dirname ).':', $dirname ) )
		{ // Valid dirname
			return;
		}
		else
		{ // Invalid filename
			return sprintf( T_('&laquo;%s&raquo; is not a valid directory name.'), $dirname );
		}
	}
	else
	{ // Use the regexp from SETTINGS
		if( preg_match( ':'.str_replace( ':', '\:', $Settings->get( 'regexp_dirname' ) ).':', $dirname ) )
		{ // Valid dirname
			return;
		}
		else
		{ // Invalid dirname
			return sprintf( T_('&laquo;%s&raquo; is not a valid directory name.'), $dirname );
		}
	}
}


/**
 * Return the path without the leading {@link $basepath}, or if not
 * below {@link $basepath}, just the basename of it.
 *
 *         Do not use this for file handling. but "just" displaying!
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

	if( $debug )
	{
		$r .= ' [DEBUG: '.$path.']';
	}

	return $r;
}


/**
 * Get the directories of the supplied path as a radio button tree.
 *
 * @param FileRoot A single root or NULL for all available.
 * @param string the root path to use
 * @param boolean add radio buttons ?
 * @param string used by recursion
 * @return string
 */
function get_directory_tree( $Root = NULL, $ads_full_path = NULL, $ads_selected_full_path = NULL, $radios = false, $rds_rel_path = NULL )
{
	static $js_closeClickIDs; // clickopen IDs that should get closed
	static $instance_ID = 0;

	// ________________________ Handle Roots ______________________
	if( $Root === NULL )
	{ // This is the top level call:
		$instance_ID++;
		$js_closeClickIDs = array();

		$FileRootCache = & get_Cache( 'FileRootCache' );
		$_roots = $FileRootCache->get_available_FileRoots();

		$r = '<ul class="clicktree">';
		foreach( $_roots as $l_Root )
		{
			$subR = get_directory_tree( $l_Root, $l_Root->ads_path, $ads_selected_full_path, $radios, '' );
			if( !empty( $subR['string'] ) )
			{
				$r .= '<li>'.$subR['string'].'</li>';
			}
		}

		$r .= '</ul>';

		if( ! empty($js_closeClickIDs) )
		{ // there are IDs of checkboxes that we want to close
			$r .= "\n".'<script type="text/javascript">toggle_clickopen( \''
						.implode( "' );\ntoggle_clickopen( '", $js_closeClickIDs )
						."' );\n</script>";
		}

		return $r;
	}
	// _______________________________________________________________________


	// We'll go through files in current dir:
	$Nodelist = & new Filelist( $Root, trailing_slash($ads_full_path) );
	$Nodelist->load();
	$has_sub_dirs = $Nodelist->count_dirs();

	$id_path = 'id_path_'.$instance_ID.md5( $ads_full_path );

	$r['string'] = '<span class="folder_in_tree">';

	// echo '<br />'. $rds_rel_path . ' - '.$ads_full_path;
	if( $ads_full_path == $ads_selected_full_path )
	{	// This is the current open path
	 	$r['opened'] = true;
	}
	else
	{
	 	$r['opened'] = NULL;
	}

	// Optional radio input to select this path:
	if( $radios )
	{
		$root_and_path = format_to_output( implode( '::', array($Root->ID, $rds_rel_path) ), 'formvalue' );

		$r['string'] .= '<input'
			.' type="radio"'
			.' name="root_and_path"'
			.' value="'.$root_and_path.'"'
			.' id="radio_'.$id_path.'"';

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
	{	// No subirs
		$r['string'] .= get_icon( 'expand', 'noimg', array( 'class'=>'' ) ).'&nbsp;'.$label.'</span>';
		return $r;
	}
	else
	{ // Process subdirs
		$r['string'] .= get_icon( 'collapse', 'imgtag', array(
					'onclick' => 'toggle_clickopen(\''.$id_path.'\');',
					'id' => 'clickimg_'.$id_path
				) )
			.'&nbsp;'.$label.'</span>'
			.'<ul class="clicktree" id="clickdiv_'.$id_path.'">'."\n";

		while( $l_File = & $Nodelist->get_next( 'dir' ) )
		{
			$rSub = get_directory_tree( $Root, $l_File->get_full_path(), $ads_selected_full_path, $radios, $l_File->get_rdfs_rel_path() );

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

	return $r;
}


/**
 * Create a directory recursively.
 *
 * NOTE: this can be done with the "recursive" param in PHP5
 *
 * @todo dh> simpletests for this (especially for open_basedir)
 *
 * @param string directory name
 * @param integer permissions
 * @return boolean
 */
function mkdir_r( $dirName, $chmod = NULL )
{
	if( is_dir($dirName) )
	{ // already exists:
		return true;
	}

	if( $chmod === NULL )
	{
		global $Settings;
		$chmod = $Settings->get('fm_default_chmod_dir');
	}

	/*
	if( version_compare(PHP_VERSION, 5, '>=') )
	{
		return mkdir( $dirName, $chmod, true );
	}
	*/

	$dirName = trailing_slash($dirName);

	$parts = array_reverse( explode('/', $dirName) );
	$loop_dir = $dirName;
	$create_dirs = array();
	foreach($parts as $part)
	{
		if( ! strlen($part) )
		{
			continue;
		}
		// We want to create this dir:
		array_unshift($create_dirs, $loop_dir);
		$loop_dir = substr($loop_dir, 0, 0 - strlen($part)-1);

		if( is_dir($loop_dir) )
		{ // found existing dir:
			foreach($create_dirs as $loop_dir )
			{
				if( ! @mkdir($loop_dir, octdec($chmod)) )
				{
					return false;
				}
			}
			return true;
		}
	}
	return true;
}


/*
 * {{{ Revision log:
 * $Log$
 * Revision 1.49  2007/01/24 13:44:56  fplanque
 * cleaned up upload
 *
 * Revision 1.48  2007/01/24 12:18:25  blueyed
 * Fixed PHP-fnmatch() implementation (for Windows)
 *
 * Revision 1.47  2007/01/24 06:31:09  fplanque
 * doc
 *
 * Revision 1.46  2007/01/24 05:57:55  fplanque
 * cleanup / settings
 *
 * Revision 1.45  2007/01/24 03:45:29  fplanque
 * decrap / removed a lot of bloat...
 *
 * Revision 1.44  2007/01/23 22:30:14  fplanque
 * empty icons cleanup
 *
 * Revision 1.43  2006/12/22 01:17:37  fplanque
 * fix
 *
 * Revision 1.42  2006/12/22 01:09:30  fplanque
 * cleanup
 *
 * Revision 1.41  2006/12/22 00:58:02  fplanque
 * fix
 *
 * Revision 1.39  2006/12/22 00:50:33  fplanque
 * improved path cleaning
 *
 * Revision 1.38  2006/12/22 00:17:05  fplanque
 * got rid of dirty globals
 * some refactoring
 *
 * Revision 1.36  2006/12/14 23:02:43  blueyed
 * Fixed handling of "0" as directory
 *
 * Revision 1.35  2006/12/14 22:13:05  blueyed
 * mkdir_r(): implemented suggestion from Francois, not tested with open_basedir yet
 *
 * Revision 1.34  2006/12/14 01:53:10  fplanque
 * doc
 *
 * Revision 1.33  2006/12/14 00:58:17  blueyed
 * mkdir_r(): fixed permissions with mkdir() call and handle open_basedir restrictions
 *
 * Revision 1.32  2006/12/14 00:42:04  fplanque
 * A little bit of windows detection / normalization
 *
 * Revision 1.31  2006/12/14 00:07:43  blueyed
 * Fixed mkdir_r
 *
 * Revision 1.30  2006/12/13 22:26:27  fplanque
 * This has reached the point of a functional eternal cache.
 * TODO: handle cache on delete, upload/overwrite, rename, move, copy.
 *
 * Revision 1.29  2006/12/13 21:23:56  fplanque
 * .evocache folders / saving of thumbnails
 *
 * Revision 1.28  2006/12/07 20:03:32  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.27  2006/12/07 15:23:42  fplanque
 * filemanager enhanced, refactored, extended to skins directory
 *
 * Revision 1.26  2006/12/03 18:20:29  blueyed
 * Added mkdir_r()
 *
 * Revision 1.25  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.24  2006/09/30 16:55:58  blueyed
 * $create param for media dir handling, which allows to just get the dir, without creating it.
 *
 * Revision 1.23  2006/09/30 16:41:00  blueyed
 * Reverted last rev
 *
 * Revision 1.22  2006/09/14 22:06:38  blueyed
 * get_directory_tree(): link to "fm_browser" anchor/id, at the top of the filelist
 *
 * Revision 1.21  2006/08/19 08:50:26  fplanque
 * moved out some more stuff from main
 *
 * Revision 1.20  2006/08/19 07:56:30  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.19  2006/08/03 20:50:25  blueyed
 * Did not mean to commit this (display all user dirs for admin users)
 *
 * Revision 1.17  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.16  2006/04/18 00:00:59  blueyed
 * *** empty log message ***
 *
 * Revision 1.15  2006/04/14 19:34:40  fplanque
 * folder tree reorganization
 *
 * Revision 1.14  2006/04/13 00:10:52  blueyed
 * cleanup
 *
 * Revision 1.13  2006/04/12 19:12:58  fplanque
 * partial cleanup
 *
 * Revision 1.12  2006/03/26 20:24:19  blueyed
 * doc
 *
 * Revision 1.11  2006/03/26 14:00:49  blueyed
 * Made Filelist constructor more decent
 *
 * Revision 1.10  2006/03/26 02:37:57  blueyed
 * Directory tree next to files list.
 *
 * Revision 1.9  2006/03/24 19:53:35  blueyed
 * str_replace() is not regexp..
 *
 * Revision 1.8  2006/03/24 19:38:21  fplanque
 * fixed nasty regexp
 *
 * Revision 1.7  2006/03/18 14:21:16  blueyed
 * *** empty log message ***
 *
 * Revision 1.6  2006/03/17 18:05:44  fplanque
 * bugfixes
 *
 * Revision 1.5  2006/03/16 19:26:04  fplanque
 * Fixed & simplified media dirs out of web root.
 *
 * Revision 1.4  2006/03/15 22:53:31  blueyed
 * cosmetic
 *
 * Revision 1.3  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/03/12 03:03:32  blueyed
 * Fixed and cleaned up "filemanager".
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.41  2006/01/09 19:27:57  blueyed
 * Fixed check_canonical_path() for non-existing paths with leading slash.
 *
 * Revision 1.39  2005/12/15 19:12:54  blueyed
 * Typo. consistent wording.
 *
 * Revision 1.38  2005/12/14 19:36:16  fplanque
 * Enhanced file management
 *
 * Revision 1.37  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.36  2005/12/08 22:35:23  blueyed
 * Merged rel_path_to_base() from post-phoenix
 *
 * Revision 1.35  2005/11/23 23:53:24  blueyed
 * Sorry, encoding messed up (latin1 again).
 *
 * Revision 1.34  2005/11/23 22:52:44  blueyed
 * minor (translation strings)
 *
 * Revision 1.33  2005/11/21 18:33:19  fplanque
 * Too many undiscussed changes all around: Massive rollback! :((
 * As said before, I am only taking CLEARLY labelled bugfixes.
 *
 * Revision 1.30  2005/11/18 07:53:05  blueyed
 * use $_FileRoot / $FileRootCache for absolute path, url and name of roots.
 *
 * Revision 1.29  2005/11/09 02:53:13  blueyed
 * made bytesreadable() more readable
 *
 * Revision 1.28  2005/11/02 20:11:19  fplanque
 * "containing entropy"
 *
 * Revision 1.27  2005/11/02 00:42:30  blueyed
 * Added get_admin_skins() and use it to perform additional checks (if there's a _adminUI.class.php file in there). Thinkl "CVS".. :)
 *
 * Revision 1.26  2005/11/02 00:03:46  blueyed
 * Fixed get_filenames() $basename behaviour.. sorry.
 *
 * Revision 1.25  2005/11/01 21:55:54  blueyed
 * Renamed retrieveFiles() to get_filenames(), added $basename parameter and fixed inner recursion (wrong params where given)
 *
 * Revision 1.24  2005/09/29 15:07:30  fplanque
 * spelling
 *
 * Revision 1.23  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.22  2005/07/26 18:50:47  fplanque
 * enhanced attached file handling
 *
 * Revision 1.21  2005/06/20 17:40:23  fplanque
 * minor
 *
 * Revision 1.20  2005/05/24 15:26:52  fplanque
 * cleanup
 *
 * Revision 1.19  2005/05/17 19:26:07  fplanque
 * FM: copy / move debugging
 *
 * Revision 1.18  2005/05/13 18:41:28  fplanque
 * made file links clickable... finally ! :P
 *
 * Revision 1.17  2005/05/13 16:49:17  fplanque
 * Finished handling of multiple roots in storing file data.
 * Also removed many full paths passed through URL requests.
 * No full path should ever be seen by the user (only the admins).
 *
 * Revision 1.16  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.15  2005/04/29 18:49:32  fplanque
 * Normalizing, doc, cleanup
 *
 * Revision 1.14  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.13  2005/04/27 19:05:46  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.12  2005/04/19 16:23:02  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.11  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.10  2005/01/15 17:30:08  blueyed
 * regexp_fileman moved to $Settings
 *
 * Revision 1.9  2005/01/13 20:27:07  blueyed
 * doc
 *
 * Revision 1.8  2005/01/05 03:04:00  blueyed
 * refactored
 *
 * Revision 1.7  2004/12/31 17:43:09  blueyed
 * enhanced bytesreadable(), fixed deldir_recursive()
 *
 * Revision 1.6  2004/12/30 16:45:40  fplanque
 * minor changes on file manager user interface
 *
 * Revision 1.5  2004/12/29 02:25:55  blueyed
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
 * }}}
 */
?>
