<?php
/**
 * This file implements the system diagnostics support functions.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */


/**
 * Check if a directory is ready for operation, i-e writable by PHP.
 *
 * @return integer result code, 0 means 'ok'
 */
function system_check_dir( $directory = 'media', $relative_path = NULL )
{
	global $media_path, $cache_path;

	switch( $directory )
	{
		case 'cache':
			$path = $cache_path;
			break;

		case 'media':
			$path = $media_path;
			break;

		default:
			return 1;
	}

	if( $relative_path != NULL )
	{
		$path .= $relative_path;
	}

	if( ! is_dir( $path ) )
	{
		return 2;
	}
	elseif( ! is_readable( $path ) )
	{
		return 3;
	}
	elseif( ! is_writable( $path ) )
	{
		return 4;
	}
	else
	{
		$tempfile_path = $path.'temp.tmp';
		if( !@touch( $tempfile_path ) || !@unlink( $tempfile_path ) )
		{
			return 5;
		}
	}

	return 0;
}


/**
 * Get corresponding status and message for the system_check_dir code.
 * 
 * @param integer system_check_dir result code
 * @param string before message
 */
function system_get_result( $check_dir_code, $before_msg = '' )
{
	$status = ( $check_dir_code == 0 ) ? 'ok' : 'error';
	$system_results = array(
		0 => T_( 'OK' ),
		1 => T_( 'Unknown directory' ), 
		2 => T_( 'The directory doesn\'t exist.' ),
		3 => T_( 'The directory is not readable.' ),
		4 => T_( 'The directory is not writable.' ),
		5 => T_( 'No permission to create/delete file in directory!' ) );
	return array( $status, $before_msg.$system_results[$check_dir_code] );
}


/**
 * Create cache/ and  /cache/plugins/ folders
 * 
 * @return boolean false if cache/ folder not exists or has limited editing permission, true otherwise
 */
function system_create_cache_folder()
{
	global $cache_path;
	// create /cache folder
	mkdir_r( $cache_path );
	// check /cache folder
	if( system_check_dir( 'cache' ) > 0 )
	{
		return false;
	}

	// create /cache/plugins/ folder
	mkdir_r( $cache_path.'plugins/' );
	return true;
}


/**
 * Get blog ids
 * 
 * @param boolean true to get only those blogs where cache is enabled
 * @return array blog ids
 */
function system_get_blogs( $only_cache_enabled )
{
	global $DB;
	$query = 'SELECT blog_ID FROM T_blogs';
	if( $only_cache_enabled )
	{
		$query .= ' INNER JOIN T_coll_settings ON ( blog_ID = cset_coll_ID
								AND cset_name = "cache_enabled"
								AND cset_value = "1" )';
	}
	return $DB->get_col( $query );
}


/**
 * Check if the given blog cache directory is ready for operation
 * 
 * @param mixed blog ID, or NULL to check the general cache
 * @param boolean true if function should try to repair the corresponding cache folder, false otherwise
 * @return mixed false if the corresponding setting is disabled, or array( status, message ).
 */
function system_check_blog_cache( $blog_ID = NULL, $repair = false )
{
	global $Settings;
	load_class( '_core/model/_pagecache.class.php', 'PageCache' );

	$Blog = NULL;
	$result = NULL;
	if( ( $blog_ID == NULL ) && ( $Settings->get( 'general_cache_enabled' ) ) )
	{
		$result = system_check_dir( 'cache', 'general/' );
		$before_msg = T_( 'General cache' ).': ';
	}
	else
	{
		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog_ID );
		if( $Blog->get_setting( 'cache_enabled' ) )
		{
			$result = system_check_dir( 'cache', 'c'.$blog_ID.'/' );
			$before_msg = sprintf( T_( '%s cache' ).': ', $Blog->get( 'shortname' ) );
		}
	}

	if( !isset( $result ) )
	{
		return false;
	}

	if( !$repair || ( $result == 0 ) )
	{
		return system_get_result( $result/*, $before_msg*/ );
	}

	// try to repair the corresponding cache folder
	$PageCache = new PageCache( $Blog );
	$PageCache->cache_delete();
	$PageCache->cache_create();
	return system_check_blog_cache( $blog_ID, false );
}


function system_check_caches( $repair = true )
{
	global $DB;

	// Check cache/ folder
	$result = system_check_dir( 'cache' );
	if( $result > 0 )
	{ // error with cache/ folder
		$failed = true;
		if( $repair && ( $result == 2 ) )
		{ // if cache folder not exists, and should repair, then try to create it
			$failed = ( $failed && !system_create_cache_folder() );
		}
		if( $failed )
		{ // could/should not repair
			list( $status, $message ) = system_get_result( $result, T_( 'Cache folder error' ).': ' );
			return array( $message );
		}
	}

	$error_messages = array();
	if( ( $result = system_check_blog_cache( NULL, $repair ) ) !== false )
	{ // general cache folder should exists
		list( $status, $message ) = $result;
		if( $status != 'ok' )
		{
			$error_messages[] = T_( 'General cache folder error' ).': '.$message;
		}
	}

	$cache_enabled_blogs = system_get_blogs( true ); 
	$BlogCache = & get_BlogCache();
	foreach( $cache_enabled_blogs as $blog_ID )
	{ // blog's cache folder should exists
		if( ( $result = system_check_blog_cache( $blog_ID, $repair ) ) !== false )
		{
			list( $status, $message ) = $result;
			if( $status != 'ok' )
			{
				$Blog = $BlogCache->get_by_ID( $blog_ID );
				$error_messages[] = sprintf( T_( '&laquo;%s&raquo; page cache folder' ),  $Blog->get( 'shortname' ) ).': '.$message;
			}
		}
	}

	return $error_messages;
}


/**
 * Initialize cache settings and folders (during install or upgrade)
 */
function system_init_caches()
{
	global $cache_path, $Settings, $DB;

	// create /cache and /cache/plugins/ folders
	if( !system_create_cache_folder() )
	{
		return false;
	}

	$Settings->set( 'newblog_cache_enabled', true );
	set_cache_enabled( 'general_cache_enabled', true );
	$existing_blogs = system_get_blogs( false );
	foreach( $existing_blogs as $blog_ID )
	{
		set_cache_enabled( 'cache_enabled', true, $blog_ID );
	}
	return true;
}


/**
 * @return boolean true if install directory has been removed
 */
function system_check_install_removed()
{
	global $basepath, $install_subdir;
	return ! is_dir( $basepath.$install_subdir );
}


/**
 * @return boolean true if DB supports UTF8
 */
function system_check_db_utf8()
{
	global $DB;

	$save_show_errors = $DB->show_errors;
	$save_halt_on_error = $DB->halt_on_error;
	$last_error = $DB->last_error;
	$error = $DB->error;
	// Blatantly ignore any error generated by SET NAMES...
	$DB->show_errors = false;
	$DB->halt_on_error = false;
	if( $DB->query( 'SET NAMES utf8' ) === false )
	{
		$ok = false;
	}
	else
	{
		$ok = true;
	}
	$DB->show_errors = $save_show_errors;
	$DB->halt_on_error = $save_halt_on_error;
	$DB->last_error = $last_error;
	$DB->error = $error;

	return $ok;
}


/**
 * @return array {id,name,name+id}
 */
function system_check_process_user()
{
	$process_uid = NULL;
	$process_user = NULL;
	if( function_exists('posix_geteuid') )
	{
		$process_uid = posix_geteuid();

		if( function_exists('posix_getpwuid')
			&& ($process_user = posix_getpwuid($process_uid)) )
		{
			$process_user = $process_user['name'];
		}

		$running_as = sprintf( '%s (uid %s)',
			($process_user ? $process_user : '?'), (!is_null($process_uid) ? $process_uid : '?') );
	}
	else
	{
		$running_as = '('.T_('Unknown').')';
	}

	return array( $process_uid, $process_user, $running_as );
}



/**
 * @return array {id,name,name+id}
 */
function system_check_process_group()
{
	$process_gid = null;
	$process_group = null;
	if( function_exists('posix_getegid') )
	{
		$process_gid = posix_getegid();

		if( function_exists('posix_getgrgid')
			&& ($process_group = posix_getgrgid($process_gid)) )
		{
			$process_group = $process_group['name'];
		}

		$running_as = sprintf( '%s (gid %s)',
			($process_group ? $process_group : '?'), (!is_null($process_gid) ? $process_gid : '?') );
	}
	else
	{
		$running_as = '('.T_('Unknown').')';
	}

	return array( $process_gid, $process_group, $running_as );
}


/**
 * @return integer
 */
function system_check_upload_max_filesize()
{
	$upload_max_filesize = ini_get('upload_max_filesize');
	if( strpos( $upload_max_filesize, 'M' ) )
	{
		$upload_max_filesize = intval($upload_max_filesize) * 1024;
	}

	return $upload_max_filesize;
}

/**
 * @return integer
 */
function system_check_post_max_size()
{
	$post_max_size = ini_get('post_max_size');
	if( strpos( $post_max_size, 'M' ) )
	{
		$post_max_size = intval($post_max_size) * 1024;
	}
	return $post_max_size;
}

/**
 * @return integer
 */
function system_check_memory_limit()
{
	$memory_limit = ini_get('memory_limit');

	if( strpos( $memory_limit, 'M' ) )
	{
		$memory_limit = intval($memory_limit) * 1024;
	}
	return $memory_limit;
}


/**
 * @return string
 */
function system_check_gd_version()
{
	if( ! function_exists( 'gd_info' ) )
	{
		return NULL;
	}

	$gd_info = gd_info();
	$gd_version = $gd_info['GD Version'];

	return $gd_version;
}

/*
 * $Log$
 * Revision 1.10  2011/03/15 09:34:05  efy-asimo
 * have checkboxes for enabling caching in new blogs
 * refactorize cache create/enable/disable
 *
 * Revision 1.9  2011/02/25 21:52:14  fplanque
 * partial rollback. install folder needs to be removed completely for max safety. There is no in between "ok" state.
 *
 * Revision 1.7  2010/02/08 17:54:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.6  2009/05/15 19:11:37  fplanque
 * attempt to not kill the '?'
 *
 * Revision 1.5  2009/04/13 14:50:22  tblue246
 * Typo, bugfix
 *
 * Revision 1.4  2009/04/12 20:15:38  tblue246
 * Make more strings available for translation
 *
 * Revision 1.3  2009/04/11 15:33:56  tblue246
 * Typo + Fixed possible bug when UID == 0 (also, posix_geteuid() does not return a special value on error).
 *
 * Revision 1.2  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.1  2008/04/24 22:05:59  fplanque
 * factorized system checks
 *
 */
?>
