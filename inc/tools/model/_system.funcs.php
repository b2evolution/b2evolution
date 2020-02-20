<?php
/**
 * This file implements the system diagnostics support functions.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
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
	$system_stats['general_pagecache_enabled'] = isset( $Settings ) ? $Settings->get( 'general_cache_enabled' ) : false;
	$system_stats['blog_pagecaches_enabled'] = count( system_get_blog_IDs( true ) );

	// Database:
	$system_stats['db_version'] = $DB->version;	// MySQL version
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
	$system_stats['php_user_cache'] = get_active_user_cache();

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
 * Create _cache/ and  /_cache/plugins/ folders
 *
 * @return boolean false if cache/ folder not exists or has limited editing permission, true otherwise
 */
function system_create_cache_folder( $verbose = false )
{
	global $cache_path, $basepath;

	if( $cache_path == $basepath.'_cache/' )
	{ // Cache path is the default one
		if( ! is_dir( $cache_path ) && is_dir( $basepath.'cache/' ) )
		{ // The default cache folder doesn't exists yet, but the old default does exist
			if( @rename( $basepath.'cache/', $cache_path ) )
			{ // Successful rename
				if( $verbose )
				{ // Display successefully renamed message
					echo '<strong>'.sprintf( T_('Your page cache folder was successfully renamed from "%s" to "%s".'), $basepath.'cache/', $cache_path ).'</strong>';
				}
				return true;
			}
			if( $verbose )
			{ // Display error message
				echo '<span class="error text-danger"><evo:error>'.sprintf( T_('You should rename your "%s" folder to "%s" for page caching to continue working without interruption.'), $basepath.'cache/', $cache_path ).'</evo:error></span>';
			}
			return false;
		}
	}
	// create /_cache folder
	mkdir_r( $cache_path );
	// check /cache folder
	if( system_check_dir( 'cache' ) > 0 )
	{
		if( $verbose )
		{ // The cache folder is not readable/writable or doesn't exist
			echo '<span class="error text-danger"><evo:error>'.T_('The /cache folder could not be created/written to. b2evolution will still work but without caching, which will make it operate slower than optimal.').'</evo:error></span>';
		}
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
	global $DB, $Settings;

	if( ! isset( $Settings ) )
	{	// Call from install script:
		return array();
	}

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
 * Get user IDs
 *
 * @return array user IDs
 */
function system_get_user_IDs()
{
	global $DB;

	return $DB->get_col( 'SELECT user_ID FROM T_users' );
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

	$Collection = $Blog = $Collection = $Blog = NULL;
	$result = NULL;
	if( $blog_ID == NULL )
	{
		if( isset( $Settings ) && $Settings->get( 'general_cache_enabled' ) )
		{
			$result = system_check_dir( 'cache', 'general/' );
			$before_msg = T_( 'General cache' ).': ';
		}
	}
	else
	{
		$BlogCache = & get_BlogCache();
		$Collection = $Blog = $BlogCache->get_by_ID( $blog_ID );
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


/**
 * Check and repair cache folders.
 */
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
				$Collection = $Blog = $BlogCache->get_by_ID( $blog_ID );
				$error_messages[] = sprintf( T_( '&laquo;%s&raquo; page cache folder' ),  $Blog->get( 'shortname' ) ).': '.$message;
			}
		}
	}

	return $error_messages;
}


/**
 * Initialize cache settings and folders (during install or upgrade)
 *
 * This is called on install and on upgrade.
 */
function system_init_caches( $verbose = false, $force_enable = true )
{
	global $cache_path, $Settings, $Plugins, $DB;

	// create /_cache and /_cache/plugins/ folders
	task_begin( 'Checking/creating <code>/_cache/</code> &amp; <code>/_cache/plugins/</code> folders...' );
	if( !system_create_cache_folder( $verbose ) )
	{ // The error message were displayed
		task_end('');
		return false;
	}
	task_end();

	if( $force_enable )
	{
		task_begin( 'Enabling page caching by default...' );

		if( ! is_object( $Settings ) )
		{ // create Settings object
			load_class( 'settings/model/_generalsettings.class.php', 'GeneralSettings' );
			$Settings = new GeneralSettings();
		}
		// New blog should have their cache enabled by default: (?)
		$Settings->set( 'newblog_cache_enabled', true );

		// Enable general cache
		set_cache_enabled( 'general_cache_enabled', true );

		// Enable caches for all collections:
		$existing_blogs = system_get_blog_IDs( false );
		foreach( $existing_blogs as $blog_ID )
		{
			set_cache_enabled( 'cache_enabled', true, $blog_ID );
		}
		task_end();
	}
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
	// Blatantly ignore any error generated by mysqli::set_charset..
	$DB->show_errors = false;
	$DB->halt_on_error = false;
	$ok = ( 'utf8' == ( $DB->connection_charset = 'utf8' ) );
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


/**
 * Check if some of the tables have different charset than what we expect
 *
 * @return boolean TRUE if the update is required
 */
function system_check_charset_update()
{
	global $DB, $evo_charset, $db_config, $tableprefix;

	$expected_connection_charset = DB::php_to_mysql_charmap( $evo_charset );

	$curr_db_charset = $DB->get_var( 'SELECT default_character_set_name
		FROM information_schema.SCHEMATA
		WHERE schema_name = '.$DB->quote( $db_config['name'] ) );

	$require_charset_update = ( $curr_db_charset != $expected_connection_charset ) ||
		$DB->get_var( 'SELECT COUNT( T.table_name )
			FROM information_schema.`TABLES` T,
				information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA
			WHERE CCSA.collation_name = T.table_collation
				AND T.table_schema = '.$DB->quote( $db_config['name'] ).'
				AND T.table_name LIKE "'.$tableprefix.'%"
				AND CCSA.character_set_name != '.$DB->quote( $expected_connection_charset ) );

	return $require_charset_update;
}


/**
 * Initialize system checking
 *
 * @param string System setting title
 * @param string System setting value
 * @param string Additional info
 */
function init_system_check( $name, $value, $info = '' )
{
	global $syscheck_name, $syscheck_value, $syscheck_info;
	$syscheck_name = $name;
	$syscheck_value = $value;
	$syscheck_info = $info;
	evo_flush();
}


/**
 * Display system checking
 *
 * @param string Condition
 * @param string Message
 */
function disp_system_check( $condition, $message = '' )
{
	global $syscheck_name, $syscheck_value, $syscheck_info;
	echo '<div class="system_check">';
	echo '<div class="system_check_name">';
	echo $syscheck_name;
	echo '</div>';
	echo '<div class="system_check_value_'.$condition.'">';
	echo $syscheck_value;
	echo '&nbsp;</div>';
	echo $syscheck_info;
	if( !empty( $message ) )
	{
		echo '<div class="system_check_message_'.$condition.'">';
		echo $message;
		echo '</div>';
	}
	echo '</div>';
}


/**
 * Check API url
 *
 * @param string Title
 * @param string Url
 */
function system_check_api_url( $title, $url )
{
	$ajax_response = fetch_remote_page( $url, $info );
	if( $ajax_response !== false )
	{	// Response is correct data:
		init_system_check( $title, 'OK', $url );
		disp_system_check( 'ok' );
	}
	else
	{	// Response is not correct data:
		init_system_check( $title, T_('Failed'), $url );
		disp_system_check( 'warning', T_('This API doesn\'t work properly on this server.' )
			.' <b>'.sprintf( T_('Error: %s'), $info['error'] ).'; '.sprintf( T_('Status code: %s'), $info['status'] ).'</b>' );
	}
}


/**
 * Display system checking
 *
 * @param array
 */
function display_system_check( $params )
{
	global $instance_name, $app_date, $app_version, $app_timestamp, $version_long,
		$localtimenow, $servertimenow, $allow_evodb_reset,
		$baseurl, $htsrv_url, $host,
		$media_path, $basepath, $install_subdir, $cache_path,
		$DB, $required_mysql_version, $required_php_version,
		$Messages, $global_Cache;

	$params = array_merge( array(
			'mode'                => 'backoffice', // 'backoffice' or 'install'
			'section_start'       => '',
			'section_end'         => '',
			'section_b2evo_title' => 'b2evolution'.get_manual_link( 'system-status-tab' ),
			'check_version'       => true,
		), $params );

	// Note: hopefully, the update swill have been downloaded in the shutdown function of a previous page (including the login screen)
	// However if we have outdated info, we will load updates here.
	load_funcs( 'dashboard/model/_dashboard.funcs.php' );

	// Let's clear any remaining messages that should already have been displayed before:
	$Messages->clear();

	if( $params['check_version'] && b2evonet_get_updates( true ) !== NULL )
	{	// Updates are allowed, display them:

		// Display info & error messages
		$Messages->display();

		$version_status_msg = $global_Cache->getx( 'version_status_msg' );
		if( ! empty( $version_status_msg ) )
		{	// We have managed to get updates (right now or in the past):
			$msg = '<p>'.$version_status_msg.'</p>';
			$extra_msg = $global_Cache->getx( 'extra_msg' );
			if( ! empty( $extra_msg ) )
			{
				$msg .= '<p>'.$extra_msg.'</p>';
			}
		}
	}
	else
	{
		$msg = '';
	}

	// Get system stats to display:
	$system_stats = get_system_stats();

	/**
	 * b2evolution
	 */
	echo str_replace( '#section_title#', $params['section_b2evo_title'], $params['section_start'] );

	// Instance name:
	init_system_check( T_('Instance name'), $instance_name );
	disp_system_check( 'note' );

	// Version:
	$app_timestamp = mysql2timestamp( $app_date );
	init_system_check( T_( 'b2evolution version' ), sprintf( /* TRANS: First %s: App version, second %s: release date */ T_( '%s released on %s' ), $app_version, $app_date ) );
	if( ! $params['check_version'] )
	{	// Version was not checked above,
		// e.g. this is impossible to do from install page where some global vars like $Settings, $global_Cache cannot be initialized:
		disp_system_check( 'note' );
	}
	elseif( ! empty( $msg ) )
	{	// Display status of checked version:
		switch( $global_Cache->getx( 'version_status_color' ) )
		{
			case 'green':
				disp_system_check( 'ok', $msg );
				break;

			case 'yellow':
				disp_system_check( 'warning', $msg );
				break;

			default:
				disp_system_check( 'error', $msg );
		}
	}
	else
	{	// Display error when no access to check version:
		$msg = '<p>Updates from b2evolution.net are disabled!</p>
				<p>You will <b>NOT</b> be alerted if you are running an insecure configuration.</p>';

		$app_age = ($localtimenow - $app_timestamp) / 3600 / 24 / 30;	// approx age in months
		if( $app_age > 12 )
		{
			$msg .= '<p>'.sprintf( T_('Furthermore, this version is old. You should check for newer releases on %s.'),
				'<a href="http://b2evolution.net/downloads/">b2evolution.net</a>' ).'</p>';
		}
		elseif( $app_age > 6 )
		{
			$msg .= '<p>'.sprintf( T_('Furthermore, this version is aging. You may want to check for newer releases on %s.'),
				'<a href="http://b2evolution.net/downloads/">b2evolution.net</a>' ).'</p>';
		}

		disp_system_check( 'error', $msg );
	}

	// Media folder writable?
	list( $mediadir_status, $mediadir_msg ) = system_get_result( $system_stats['mediadir_status'] );
	$mediadir_long = '';
	if( $mediadir_status == 'error' )
	{
		$mediadir_long = '<p>'.T_('You will not be able to upload files/images and b2evolution will not be able to generate thumbnails.')."</p>\n"
		.'<p>'.T_('Your host requires that you set special file permissions on your media directory.').get_manual_link('media-file-permission-errors')."</p>\n";
	}
	init_system_check( T_( 'Media directory' ), $mediadir_msg.' - '.$media_path );
	disp_system_check( $mediadir_status, $mediadir_long );

	// .htaccess
	$htaccess_path = $basepath.'.htaccess';
	$sample_htaccess_path = $basepath.'sample.htaccess';
	init_system_check( '.htaccess', $htaccess_path );
	if( ! file_exists( $htaccess_path ) )
	{	// No .htaccess
		disp_system_check( 'error', T_('Error').': '.sprintf( T_('%s has not been found.'), '<code>.htaccess</code>' ) );
	}
	elseif( ! file_exists( $sample_htaccess_path ) )
	{	// No sample.htaccess
		disp_system_check( 'warning', T_('Warning').': '.sprintf( T_('%s has not been found.'), '<code>sample.htaccess</code>' ) );
	}
	elseif( trim( file_get_contents( $htaccess_path ) ) != trim( file_get_contents( $sample_htaccess_path ) ) )
	{	// Different .htaccess
		disp_system_check( 'warning', T_('Warning').': '.sprintf( T_('%s differs from %s'), '<code>.htaccess</code>', '<code>sample.htaccess</code>' ) );
	}
	else
	{	// All OK
		disp_system_check( 'ok', sprintf( T_('%s is identical to %s'), '<code>.htaccess</code>', '<code>sample.htaccess</code>' ) );
	}

	// /install/ folder deleted?
	init_system_check( T_( 'Install folder' ), $system_stats['install_removed'] ?  T_('Deleted') : T_('Not deleted').' - '.$basepath.$install_subdir );
	if( ! $system_stats['install_removed'] )
	{
		disp_system_check( 'warning', T_('For maximum security, it is recommended that you delete your /blogs/install/ folder once you are done with install or upgrade.') );

		// Database reset allowed?
		init_system_check( T_( 'Database reset' ), $allow_evodb_reset ?  T_('Allowed').'!' : T_('Forbidden') );
		if( $allow_evodb_reset )
		{
			disp_system_check( 'error', '<p>'.T_('Currently, anyone who accesses your install folder could entirely reset your b2evolution database.')."</p>\n"
			 .'<p>'.T_('ALL YOUR DATA WOULD BE LOST!')."</p>\n"
			 .'<p>'.T_('As soon as possible, change the setting <code>$allow_evodb_reset = 0;</code> in your /conf/_basic.config.php.').'</p>' );
		}
		else
		{
			disp_system_check( 'ok' );
		}
	}
	else
	{
		disp_system_check( 'ok' );
	}

	init_system_check( 'Internal b2evo charset' , $system_stats['evo_charset'] );
	disp_system_check( 'note' );

	init_system_check( 'Blog count' , $system_stats['evo_blog_count'] );
	disp_system_check( 'note' );

	echo $params['section_end'];


	/*
	 * Caching
	 */
	echo str_replace( '#section_title#', T_('Caching'), $params['section_start'] );

	// Cache folder writable?
	list( $cachedir_status, $cachedir_msg ) = system_get_result( $system_stats['cachedir_status'] );
	$cachedir_long = '';
	if( $cachedir_status == 'error' )
	{
		$cachedir_long = '<p>'.T_('You will not be able to use page cache.')."</p>\n"
		.'<p>'.T_('Your host requires that you set special file permissions on your cache directory.').get_manual_link('cache-file-permission-errors')."</p>\n";
	}
	init_system_check( T_( 'Cache directory' ), $cachedir_msg.' - '.$cache_path );
	disp_system_check( $cachedir_status, $cachedir_long );

	// cache folder size
	init_system_check( 'Cache folder size', bytesreadable($system_stats['cachedir_size']) );
	disp_system_check( 'note' );

	if( $cachedir_status != 'error' )
	{ // 'cache/ directory exists and, it is writable

		// General cache is enabled
		init_system_check( T_( 'General caching' ), $system_stats['general_pagecache_enabled'] ? 'Enabled' : 'Disabled' );
		disp_system_check( 'note' );

		// how many blogs have enabled caches
		$error_messages = system_check_caches( false );
		$enabled_message = $system_stats['blog_pagecaches_enabled'].' enabled /'.$system_stats['evo_blog_count'].' blogs';
		init_system_check( T_( 'Blog\'s cache setting' ), $enabled_message );
		disp_system_check( 'note' );
		if( count( $error_messages ) > 0 )
		{ // show errors
			init_system_check( T_( 'Blog\'s cache errors' ), implode( '<br />', $error_messages ) );
			disp_system_check( 'error' );
		}
	}

	echo $params['section_end'];


	/*
	 * Time
	 */
	echo str_replace( '#section_title#', T_('Time'), $params['section_start'] );

	init_system_check( T_( 'Server time' ), date_i18n( locale_datetimefmt( ' - ' ), $servertimenow ) );
	disp_system_check( 'note' );

	init_system_check( T_( 'GMT / UTC time' ), gmdate( locale_datetimefmt( ' - ' ), $servertimenow ) );
	disp_system_check( 'note' );

	init_system_check( T_( 'b2evolution time' ), date_i18n( locale_datetimefmt( ' - ' ), $localtimenow ) );
	disp_system_check( 'note' );

	echo $params['section_end'];


	/*
	 * MySQL Version
	 */
	echo str_replace( '#section_title#', 'MySQL', $params['section_start'] );

	// Version:
	init_system_check( T_( 'MySQL version' ), $DB->version_long );
	if( version_compare( $system_stats['db_version'], $required_mysql_version['application'] ) < 0 )
	{
		disp_system_check( 'error', sprintf( T_('This version is too old. The minimum recommended MySQL version is %s.'), $required_mysql_version['application'] ) );
	}
	else
	{
		disp_system_check( 'ok' );
	}

	// UTF8 support?
	init_system_check( 'MySQL UTF-8 support', $system_stats['db_utf8'] ?  T_('Yes') : T_('No') );
	if( ! $system_stats['db_utf8'] )
	{
		disp_system_check( 'warning', T_('UTF-8 is not supported by your MySQL server.') ); // fp> TODO: explain why this is bad. Better yet: try to detect if we really need it, base don other conf variables.
	}
	else
	{
		disp_system_check( 'ok' );
	}

	echo $params['section_end'];


	/**
	 * PHP
	 */
	echo str_replace( '#section_title#', 'PHP', $params['section_start'] );


	// User ID:
	list( $uid, $uname, $running_as ) = system_check_process_user();
	init_system_check( 'PHP running as USER:', $running_as );
	disp_system_check( 'note' );


	// Group ID:
	list( $gid, $gname, $running_as ) = system_check_process_group();
	init_system_check( 'PHP running as GROUP:', $running_as );
	disp_system_check( 'note' );


	// PHP version
	$phpinfo_url = '?ctrl=tools&amp;action=view_phpinfo&amp;'.url_crumb('tools');
	$phpinfo_link = action_icon( T_('View PHP info'), 'info', $phpinfo_url, '', 5, '', array( 'target'=>'_blank', 'onclick'=>'return pop_up_window( \''.$phpinfo_url.'\', \'phpinfo\', 650 )' ) );
	init_system_check( 'PHP version', $system_stats['php_version'].' '.$phpinfo_link );

	if( version_compare( $system_stats['php_version'], $required_php_version['application'], '<' ) )
	{
		disp_system_check( 'error', T_('This version is too old. b2evolution will not run correctly. You must ask your host to upgrade PHP before you can run b2evolution.') );
	}
	elseif( version_compare( $system_stats['php_version'], '5.6', '<' ) )
	{
		disp_system_check( 'warning', T_('This version is old. b2evolution may run but some features may fail. You should ask your host to upgrade PHP before running b2evolution.')
			.'<br />'.T_( 'PHP 5.6 or greater is recommended for maximum security.' ) );
	}
	else
	{
		disp_system_check( 'ok' );
	}

	// register_globals?
	init_system_check( 'PHP register_globals', $system_stats['php_reg_globals'] ?  T_('On') : T_('Off') );
	if( $system_stats['php_reg_globals'] )
	{
		disp_system_check( 'warning', $facilitate_exploits.' '.sprintf( $change_ini, 'register_globals = Off' )  );
	}
	else
	{
		disp_system_check( 'ok' );
	}


	// allow_url_include? (since PHP 5.2, supercedes allow_url_fopen for require()/include())
	init_system_check( 'PHP allow_url_include', $system_stats['php_allow_url_include'] ?  T_('On') : T_('Off') );
	if( $system_stats['php_allow_url_include'] )
	{
		disp_system_check( 'warning', $facilitate_exploits.' '.sprintf( $change_ini, 'allow_url_include = Off' )  );
	}
	else
	{
		disp_system_check( 'ok' );
	}


	// Magic quotes:
	if( !strcasecmp( ini_get('magic_quotes_sybase'), 'on' ) )
	{
		$magic_quotes = T_('On').' (magic_quotes_sybase)';
		$message = 'magic_quotes_sybase = Off';
	}
	elseif( get_magic_quotes_gpc() )
	{
		$magic_quotes = T_('On').' (magic_quotes_gpc)';
		$message = 'magic_quotes_gpc = Off';
	}
	else
	{
		$magic_quotes = T_('Off');
		$message = '';
	}
	init_system_check( 'PHP Magic Quotes', $magic_quotes );
	if( !empty( $message ) )
	{
		disp_system_check( 'warning', T_('PHP is adding extra quotes to all inputs. This leads to unnecessary extra processing.')
			.' '.sprintf( $change_ini, $message ) );
	}
	else
	{
		disp_system_check( 'ok' );
	}


	// Max upload size:
	$upload_max_filesize = system_check_upload_max_filesize();
	init_system_check( 'PHP upload_max_filesize', ini_get('upload_max_filesize') );
	disp_system_check( 'ok' );

	// Max post size:
	$post_max_size = system_check_post_max_size();
	init_system_check( 'PHP post_max_size', ini_get('post_max_size') );
	if( $post_max_size > $upload_max_filesize )
	{
		disp_system_check( 'ok' );
	}
	elseif( $post_max_size == $upload_max_filesize )
	{
		disp_system_check( 'warning', T_('post_max_size should be larger than upload_max_filesize') );
	}
	else
	{
		disp_system_check( 'error', T_('post_max_size should be larger than upload_max_filesize') );
	}

	// Memory limit:
	$memory_limit = system_check_memory_limit();
	if( empty($memory_limit) )
	{
		init_system_check( 'PHP memory_limit', /* TRANS: "Not Available" */ T_('N/A') );
		disp_system_check( 'note' );
	}
	else
	{
		init_system_check( 'PHP memory_limit', $memory_limit == -1 ? T_('Unlimited') : ini_get('memory_limit') );
		if( $memory_limit == -1 )
		{
			disp_system_check( 'ok' );
		}
		elseif( $memory_limit < get_php_bytes_size( '256M' ) )
		{
			disp_system_check( 'error', T_('The memory_limit is too low. Some features like image manipulation will fail to work.') );
		}
		elseif( $memory_limit < get_php_bytes_size( '384M' ) )
		{
			disp_system_check( 'warning', T_('The memory_limit is low. Some features like image manipulation of large files may fail to work.') );
		}
		else
		{
			disp_system_check( 'ok' );
		}
	}

	// Maximum execution time of each script
	$max_execution_time = system_check_max_execution_time();
	if( empty( $max_execution_time ) )
	{
		init_system_check( 'PHP max_execution_time', T_('Unlimited') );
		disp_system_check( 'ok' );
	}
	else
	{	// Time is limited, can we request more?:
		$can_force_time = @ini_set( 'max_execution_time', 600 ); // Try to force max_execution_time to 10 minutes

		if( $can_force_time !== false )
		{
			$forced_max_execution_time = system_check_max_execution_time();
			init_system_check( 'PHP forced max_execution_time', sprintf( T_('%s seconds'), $forced_max_execution_time ) );
			disp_system_check( 'ok', sprintf( T_('b2evolution was able to request more time (than the default %s seconds) to execute complex tasks.'), $max_execution_time ) );
		}
		elseif( $max_execution_time <= 5 * 60 )
		{
			init_system_check( 'PHP max_execution_time', sprintf( T_('%s seconds'), $max_execution_time ) );
			disp_system_check( 'error', T_('b2evolution may frequently run out of time to execute properly.') );
		}
		elseif( $max_execution_time > 5 * 60 )
		{
			init_system_check( 'PHP max_execution_time', sprintf( T_('%s seconds'), $max_execution_time ) );
			disp_system_check( 'warning', T_('b2evolution may sometimes run out of time to execute properly.' ) );
		}
	}

	// mbstring extension
	init_system_check( 'PHP mbstring extension', extension_loaded('mbstring') ?  T_('Loaded') : T_('Not loaded') );
	if( ! extension_loaded('mbstring' ) )
	{
		disp_system_check( 'warning', T_('b2evolution will not be able to convert character sets and special characters/languages may not be displayed correctly. Enable the mbstring extension in your php.ini file or ask your hosting provider about it.') );
	}
	else
	{
		disp_system_check( 'ok' );
	}


	// XML extension
	init_system_check( 'PHP XML extension', extension_loaded('xml') ?  T_('Loaded') : T_('Not loaded') );
	if( ! extension_loaded('xml' ) )
	{
		disp_system_check( 'warning', T_('The XML extension is not loaded.') ); // fp> This message only repeats the exact same info that is already displayed. Not helpful.
		// fp>TODO: explain what we need it for. Is it a problem or not.
		// furthermore I think xmlrpc does dynamic loading (or has it been removed?), in which case this should be tested too.
		// dh> You mean the deprecated dl() loading? (fp>yes) We might just try this then here also before any warning.
	}
	else
	{
		disp_system_check( 'ok' );
	}

	// IMAP extension
	$imap_loaded = extension_loaded( 'imap' );
	init_system_check( 'PHP IMAP extension', $imap_loaded ? T_( 'Loaded' ) : T_( 'Not loaded' ) );
	if ( ! $imap_loaded )
	{
		disp_system_check( 'warning', T_( 'You will not be able to use the Post by Email feature and the Return Path email processing of b2evolution. Enable the IMAP extension in your php.ini file or ask your hosting provider about it.' ) );
	}
	else
	{
		disp_system_check( 'ok' );
	}

	// Opcode cache
	$opcode_cache = get_active_opcode_cache();
	init_system_check( 'PHP opcode cache', $opcode_cache );
	if( $opcode_cache == 'none' )
	{
		disp_system_check( 'warning', T_( 'Using an opcode cache allows all your PHP scripts to run faster by caching a "compiled" (opcode) version of the scripts instead of recompiling everything at every page load. Several opcode caches are available. We recommend APC (which is included with PHP starting from PHP 7).' ) );
	}
	else
	{
		disp_system_check( 'ok' );
	}

	// User cache
	$user_cache = get_active_user_cache();
	init_system_check( 'PHP user cache', $user_cache );
	if( $user_cache == 'none' )
	{
		disp_system_check( 'warning', T_( 'Using an user cache allows b2evolution to store some cached data (Block Cache) in memory instead of regenerated the blocks at each page load. Several user caches are available. We recommend APCu (which needs to be enabled separately from APC, starting from PHP 7).' ) );
	}
	else
	{
		disp_system_check( 'ok' );
	}

	// pre_dump( get_loaded_extensions() );

	echo $params['section_end'];



	/*
	 * GD Library
	 * windows: extension=php_gd2.dll
	 * unix: ?
	 * fp> Note: I'm going to use this for thumbnails for now, but I plan to use it for other things like small stats & status graphics.
	 */
	echo str_replace( '#section_title#', T_('GD Library (image handling)'), $params['section_start'] );

	$gd_version = system_check_gd_version();
	init_system_check( T_( 'GD Library version' ), isset($gd_version) ? $gd_version : T_('Not installed') );
	if( ! isset($gd_version) )
	{
		disp_system_check( 'warning', T_('You will not be able to automatically generate thumbnails for images. Enable the gd2 extension in your php.ini file or ask your hosting provider about it.') );
	}
	else
	{
		disp_system_check( 'ok' );

		$gd_info = gd_info();

		// JPG:
		// Tblue> Note: "JPG Support" was renamed to "JPEG Support" in PHP 5.3.
		init_system_check( 'GD JPG Support', ( ! empty($gd_info['JPG Support']) || ! empty($gd_info['JPEG Support']) ) ? T_('Read/Write') : T_('No') );
		if( empty($gd_info['JPG Support']) && empty($gd_info['JPEG Support']) )
		{
			disp_system_check( 'warning', T_('You will not be able to automatically generate thumbnails for JPG images.') );
		}
		else
		{
			disp_system_check( 'ok' );
		}

		// PNG:
		init_system_check( 'GD PNG Support', !empty($gd_info['PNG Support']) ? T_('Read/Write') : T_('No') );
		if( empty($gd_info['PNG Support']) )
		{
			disp_system_check( 'warning', T_('You will not be able to automatically generate thumbnails for PNG images.') );
		}
		else
		{
			disp_system_check( 'ok' );
		}

		// GIF:
		if( !empty($gd_info['GIF Create Support']) )
		{
			$gif_support = T_('Read/Write');
		}
		elseif( !empty($gd_info['GIF Read Support']) )
		{
			$gif_support = T_('Read');
		}
		else
		{
			$gif_support = T_('No');
		}
		init_system_check( 'GD GIF Support', $gif_support );
		if( $gif_support == T_('No') )
		{
			disp_system_check( 'warning', T_('You will not be able to automatically generate thumbnails for GIF images.') );
		}
		elseif( $gif_support == T_('Read') )
		{
			disp_system_check( 'warning', T_('Thumbnails for GIF images will be generated as PNG or JPG.') );
		}
		else
		{
			disp_system_check( 'ok' );
		}

		// FreeType:
		init_system_check( 'GD FreeType Support', !empty($gd_info['FreeType Support']) ?  T_('Yes') : T_('No') );
		if( empty($gd_info['FreeType Support']) )
		{
			disp_system_check( 'warning', T_('You will not be able to write text to images using TrueType fonts.') );
		}
		else
		{
			disp_system_check( 'ok' );
		}
		// pre_dump( $gd_info );
	}
	echo $params['section_end'];


	if( $params['mode'] == 'backoffice' )
	{
		/*
		 * API
		 */
		echo str_replace( '#section_title#', T_('APIs'), $params['section_start'] );

		// REST API:
		$api_title = 'REST API';
		$api_url = $baseurl.'api/v6/collections';
		$json_response = fetch_remote_page( $api_url, $api_info );
		$api_result = false;
		if( $json_response !== false )
		{	// Try to decode REST API json data:
			$decoded_response = @json_decode( $json_response );
			$api_result = ! empty( $decoded_response );
		}
		if( $api_result )
		{	// Response is correct json data:
			init_system_check( $api_title, 'OK', $api_url );
			disp_system_check( 'ok' );
		}
		else
		{	// Response is not json data:
			if( ! empty( $api_info['error'] ) )
			{	// Display error message from function fetch_remote_page():
				$api_error = ' <b>'.sprintf( T_('Error: %s'), $api_info['error'] ).'; '.sprintf( T_('Status code: %s'), $api_info['status'] ).'</b>';
			}
			else
			{	// Display error message from other places:
				$api_error = error_get_last();
				$api_error = ( isset( $api_error['message'] ) ? ' <b>'.sprintf( T_( 'Error: %s' ), $api_error['message'] ).'</b>' : '' );
			}
			init_system_check( $api_title, T_('Failed'), $api_url );
			disp_system_check( 'warning', T_('This API doesn\'t work properly on this server.' )
				.' '.T_('You should probably update your <code>.htaccess</code> file to the latest version and check the file permissions of this file.')
				.$api_error );
		}

		// XML-RPC:
		$api_title = 'XML-RPC';
		$api_file = 'xmlrpc.php';
		$api_url = $baseurl.$api_file;
		load_funcs( 'xmlrpc/model/_xmlrpc.funcs.php' );
		if( defined( 'CANUSEXMLRPC' ) && CANUSEXMLRPC === true )
		{	// Try XML-RPC API only if current server can use it:
			$client = new xmlrpc_client( $api_url );
			$message = new xmlrpcmsg( 'system.listMethods' );
			$result = $client->send( $message );
			$api_error_type = T_('This API doesn\'t work properly on this server.');
		}
		else
		{	// Get an error message if current server cannot use XML-RPC client:
			$result = false;
			$xmlrpc_error_message = CANUSEXMLRPC;
			$api_error_type = T_('This server cannot use XML-RPC client.');
		}
		if( $result && ! $result->faultCode() )
		{	// XML-RPC request is successful:
			init_system_check( $api_title, 'OK', $api_url );
			disp_system_check( 'ok' );
		}
		else
		{	// Some error on XML-RPC request:
			if( $result )
			{	// Get an error message of XML-RPC request:
				$xmlrpc_error_message = $result->faultString();
			}
			if( $xmlrpc_error_message == 'XML-RPC services are disabled on this system.' )
			{	// Exception for this error:
				$api_status_title = T_('Disabled');
				$api_status_type = 'ok';
			}
			else
			{	// Other errors:
				$api_status_title = T_('Failed');
				$api_status_type = 'warning';
			}
			init_system_check( $api_title, $api_status_title, $api_url );
			disp_system_check( $api_status_type, $api_error_type.' <b>'.sprintf( T_( 'Error: %s' ), $xmlrpc_error_message ).'</b>' );
		}

		// AJAX anon_async.php:
		system_check_api_url( 'AJAX anon_async.php', $htsrv_url.'anon_async.php?action=test_api' );

		// AJAX async.php:
		system_check_api_url( 'AJAX async.php', $htsrv_url.'async.php?action=test_api' );

		// AJAX action.php:
		system_check_api_url( 'AJAX action.php', $htsrv_url.'action.php?mname=test_api' );

		// AJAX call_plugin.php:
		system_check_api_url( 'AJAX call_plugin.php', $htsrv_url.'call_plugin.php?plugin_ID=-1&method=test_api' );

		echo $params['section_end'];
	}
}
?>