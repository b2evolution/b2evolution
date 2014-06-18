<?php
/**
 * This file implements the system diagnostics support functions.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _system.funcs.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Collect system stats for display on the "About this system" page
 *
 * @return array
 */
function get_system_stats()
{
	global $evo_charset, $DB, $Settings, $cache_path;

	static $system_stats = array();

	if( !empty($system_stats) )
	{
		return $system_stats;
	}

	// b2evo config choices:
	$system_stats['mediadir_status'] = system_check_dir('media'); // If error, then the host is potentially borked
	$system_stats['install_removed'] = system_check_install_removed();
	$system_stats['evo_charset'] = $evo_charset;
	$system_stats['evo_blog_count'] = count( system_get_blog_IDs( false ) );

	// Caching:
	$system_stats['cachedir_status'] = system_check_dir('cache'); // If error, then the host is potentially borked
	$system_stats['cachedir_size'] = get_dirsize_recursive( $cache_path );
	$system_stats['general_pagecache_enabled'] = $Settings->get( 'general_cache_enabled' );
	$system_stats['blog_pagecaches_enabled'] = count( system_get_blog_IDs( true ) );

	// Database:
	$system_stats['db_version'] = $DB->get_version();	// MySQL version
	$system_stats['db_utf8'] = system_check_db_utf8();

	// PHP:
	list( $uid, $uname ) = system_check_process_user();
	$system_stats['php_uid'] = $uid;
	$system_stats['php_uname'] = $uname;	// Potential unsecure hosts will use names like 'nobody', 'www-data'
	list( $gid, $gname ) = system_check_process_group();
	$system_stats['php_gid'] = $gid;
	$system_stats['php_gname'] = $gname;	// Potential unsecure hosts will use names like 'nobody', 'www-data'
	$system_stats['php_version'] = PHP_VERSION;
	$system_stats['php_reg_globals'] = ini_get('register_globals');
	$system_stats['php_allow_url_include'] = ini_get('allow_url_include');
	$system_stats['php_allow_url_fopen'] = ini_get('allow_url_fopen');
	// TODO php_magic quotes
	$system_stats['php_upload_max'] = system_check_upload_max_filesize();
	$system_stats['php_post_max'] = system_check_post_max_size();
	$system_stats['php_memory'] = system_check_memory_limit(); // how much room does b2evo have to move?
	$system_stats['php_mbstring'] = extension_loaded('mbstring');
	$system_stats['php_xml'] = extension_loaded('xml');
	$system_stats['php_imap'] = extension_loaded('imap');
	$system_stats['php_opcode_cache'] = get_active_opcode_cache();

	// GD:
	$system_stats['gd_version'] = system_check_gd_version();

	return $system_stats;
}


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

	if( $directory == 'cache' && $relative_path != NULL )
	{ // Create .htaccess file with deny rules
		if( ! create_htaccess_deny( $cache_path ) )
		{
			return 6;
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
	// fp> note: you can add statuses but not change existing ones.
		0 => T_( 'OK' ),
		1 => T_( 'Unknown directory' ),
		2 => T_( 'The directory doesn\'t exist.' ),
		3 => T_( 'The directory is not readable.' ),
		4 => T_( 'The directory is not writable.' ),
		5 => T_( 'No permission to create/delete file in directory!' ),
		6 => T_( 'No permission to create .htaccess file in directory!' ) );
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
function system_get_blog_IDs( $only_cache_enabled )
{
	global $DB;
	$query = 'SELECT blog_ID FROM T_blogs';
	if( $only_cache_enabled )
	{
		$query .= ' INNER JOIN T_coll_settings ON
										( blog_ID = cset_coll_ID
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
	if( $blog_ID == NULL )
	{
		if( $Settings->get( 'general_cache_enabled' ) )
		{
			$result = system_check_dir( 'cache', 'general/' );
			$before_msg = T_( 'General cache' ).': ';
		}
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

	$cache_enabled_blogs = system_get_blog_IDs( true );
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
	$existing_blogs = system_get_blog_IDs( false );
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
	return get_php_bytes_size( ini_get('upload_max_filesize') );
}

/**
 * @return integer
 */
function system_check_post_max_size()
{
	return get_php_bytes_size( ini_get('post_max_size') );
}

/**
 * @return integer
 */
function system_check_memory_limit()
{
	return get_php_bytes_size( ini_get('memory_limit') );
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

/**
 * @return integer
 */
function system_check_max_execution_time()
{
	$max_execution_time = ini_get('max_execution_time');

	return $max_execution_time;
}


/**
 * Get how much bytes php ini value takes
 *
 * @param string PHP ini value,
 *    Examples:
 *         912 - 912 bytes
 *          4K - 4 Kilobytes
 *         13M - 13 Megabytes
 *          8G - 8 Gigabytes
 * @return integer Bytes
 */
function get_php_bytes_size( $php_ini_value )
{
	if( (string) intval( $php_ini_value ) === (string) $php_ini_value )
	{ // Bytes
		return $php_ini_value;
	}
	elseif( strpos( $php_ini_value, 'K' ) !== false )
	{ // Kilobytes
		return intval( $php_ini_value ) * 1024;
	}
	elseif( strpos( $php_ini_value, 'M' ) !== false  )
	{ // Megabytes
		return intval( $php_ini_value ) * 1024 * 1024;
	}
	elseif( strpos( $php_ini_value, 'G' ) !== false  )
	{ // Gigabytes
		return intval( $php_ini_value ) * 1024 * 1024 * 1024;
	}

	// Unknown format
	return $php_ini_value;
}
?>