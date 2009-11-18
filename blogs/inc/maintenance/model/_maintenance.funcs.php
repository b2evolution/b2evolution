<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Check version
 * @param new version dir name
 * @return string message or NULL
 */
function check_version( $new_version_dir )
{
	global $install_subdir, $install_path, $upgrade_path;
	// Upgrade database using regular upgrader script
	require_once( $install_path.'/_version.php' );
	require_once( $install_path.'/_version.php' );

	$new_version_file = $upgrade_path.$new_version_dir.'/'.$install_subdir.'_version.php';
	$current_version_file = $install_path.'/_version.php';

	if( !file_exists( $current_version_file ) )
	{
		return T_( 'Installed version doesn\'t support upgrade!' );
	}

	require( $new_version_file );
	$new_version = $current_version;

	unset( $current_version );

	require( $current_version_file );
	$current_version = $current_version;

	if( $new_version == $current_version )
	{
		return T_( 'This package already installed!' );
	}
	elseif( $new_version < $current_version )
	{
		return T_( 'This is old version!' );
	}

	return NULL;
}


/**
 * Set max execution time
 * @param integer seconds
 */
function set_max_execution_time( $seconds )
{
	if( function_exists( 'set_time_limit' ) )
	{
		set_time_limit( $seconds );
	}
	@ini_set( 'max_execution_time', $seconds );
}


/**
 * Enable/disable maintenance mode
 *
 * @param boolean true if maintenance mode need to be enabled
 * @param string maintenance mode message
 */
function switch_maintenance_mode( $enable, $msg = '' )
{
	global $conf_path;

	$maintenance_mode_file = 'maintenance.txt';

	if( $enable )
	{	// Create maintenance file
		echo '<p>'.T_('Switching to maintenance mode...').'</p>';
		flush();

		$f = @fopen( $conf_path.$maintenance_mode_file , 'w+' );
		if( $f == false )
		{	// Maintenance file has not been created
			echo '<p style="color:red">'.sprintf( T_( 'Unable to switch maintenance mode. Maintenance file can\'t be created: &laquo;%s&raquo;' ), $maintenance_mode_file ).'</p>';
    		flush();

			return false;
		}
		else
		{	// Write content
			fwrite( $f, $msg );
			fclose($f);
		}
	}
	else
	{	// Delete maintenance file
		echo '<p>'.T_('Switching out of maintenance mode...').'</p>';
		unlink( $conf_path.$maintenance_mode_file );
	}

	return true;
}


/**
 * Prepare maintenance directory
 *
 * @param string directory path
 * @param boolean create .htaccess file with 'deny from all' text
 * @return boolean
 */
function prepare_maintenance_dir( $dir_name, $deny_access = false )
{
	if( !file_exists( $dir_name ) )
	{	// We can create directory
		if ( ! mkdir_r( $dir_name ) )
		{
			echo '<p style="color:red">'.sprintf( T_( 'Unable to create &laquo;%s&raquo; directory.' ), $dir_name ).'</p>';
			flush();

			return false;
		}
	}

	if( $deny_access )
	{	// Create .htaccess file
		$htaccess_name = $dir_name.'.htaccess';

		if( !file_exists( $htaccess_name ) )
		{	// We can create .htaccess file
			$f = @fopen( $htaccess_name , 'w+' );
			if( $f == false )
			{
				echo '<p style="color:red">'.sprintf( T_( 'Unable to create &laquo;%s&raquo; file in directory.' ), $htaccess_name ).'</p>';
				flush();

				return false;
			}
			else
			{	// Write content
				fwrite( $f, 'deny from all' );
				fclose($f);
			}
		}
	}

	return true;
}


/**
 * Unpack ZIP archive to destination directory
 *
 * @param string source file path
 * @param string destination directory path
 * @param boolean true if create destination directory
 * @return boolean results
 */
function unpack_archive( $src_file, $dest_dir, $mk_dest_dir = false )
{
	global $inc_path;

	if( !file_exists( $dest_dir ) )
	{	// We can create directory
		if ( !mkdir_r( $dest_dir ) )
		{
			echo '<p style="color:red">'.sprintf( T_( 'Unable to create &laquo;%s&raquo; directory.' ), $dest_dir ).'</p>';
			flush();

			return false;
		}
	}

	if( function_exists('gzopen') )
	{	// Unpack using 'zlib' extension and PclZip wrapper
		require_once( $inc_path.'_ext/pclzip/pclzip.lib.php' );

		$PclZip = new PclZip( $src_file );
		if( $PclZip->extract( PCLZIP_OPT_PATH, $dest_dir ) == 0 )
		{
			echo '<p style="color:red">'.sprintf( T_( 'Unable to unpack &laquo;%s&raquo; ZIP archive.' ), $src_file ).'</p>';
			flush();

			return false;
		}
	}
	else
	{
		debug_die( 'There is no \'zip\' or \'zlib\' extension installed!' );
	}

	return true;
}


/**
 * Verify that destination files can be overwritten
 *
 * @param string source directory
 * @param string destination directory
 * @param string action name
 * @param boolean overwrite
 * @param array read only file list
 */
function verify_overwrite( $src, $dest, $action = '', $overwrite = true, &$read_only_list )
{
	$dir = opendir( $src );

	$dir_list = array();
	$file_list = array();
	while( false !== ( $file = readdir( $dir ) ) )
	{
		if ( ( $file != '.' ) && ( $file != '..' ) )
		{
			$srcfile = $src.'/'.$file;
			$destfile = $dest.'/'.$file;

			if( isset( $read_only_list ) && file_exists( $destfile ) && !is_writable( $destfile ) )
			{	// Folder or file is not writable
				$read_only_list[] = $destfile;
			}

			if ( is_dir( $srcfile ) )
			{
				$dir_list[$srcfile] = $destfile;
			}
			elseif( $overwrite )
			{	// Add to overwrite
				$file_list[$srcfile] = $destfile;
			}
		}
	}

	foreach( $dir_list as $src_dir => $dest_dir )
	{
		if( !empty( $action ) )
		{
			// progressive display of what backup is doing
			echo $action.sprintf( T_( ' &laquo;<strong>%s</strong>&raquo; ...' ), $dest_dir ).'<br/>';
			flush();
		}

		if( $overwrite && !file_exists( $dest_dir ) )
		{
			// Create destination directory
			@mkdir( $dest_dir );
		}

		verify_overwrite( $src_dir, $dest_dir, '', $overwrite, $read_only_list );
	}

	foreach( $file_list as $src_file => $dest_file )
	{	// Overwrite destination file
		copy( $src_file, $dest_file );
	}

	closedir( $dir );
}


/**
 * Get upgrade action
 * @param string download url
 * @return upgrade action
 */
function get_upgrade_action( $download_url )
{
	global $upgrade_path, $servertimenow;

	// Construct version name from download URL
	$slash_pos = strrpos( $download_url, '/' );
	$point_pos = strrpos( $download_url, '.' );

	if( $slash_pos < $point_pos )
	{
		$version_name = substr( $download_url, $slash_pos + 1, $point_pos - $slash_pos - 1 );
	}

	if( empty( $version_name ) )
	{
		return false;
	}

	if( file_exists( $upgrade_path ) )
	{
		// Search if there is unpacked version in '_upgrade' directory
		foreach( get_filenames( $upgrade_path, false, true, true, false, true ) as $dir_name )
		{
			if( strpos( $dir_name, $version_name ) === 0 )
			{
				$new_version_status = check_version( $dir_name );
				if( !empty( $new_version_status ) )
				{
					return array( 'action' => 'none', 'status' => $new_version_status );
				}
				else
				{
					return array( 'action' => 'install', 'name' => $dir_name );
				}
			}
		}

		// Search if there is packed version in '_upgrade' directory
		foreach( get_filenames( $upgrade_path, true, false, true, false, true ) as $file_name )
		{
			if( strpos( $file_name, $version_name ) === 0 )
			{
				return array( 'action' => 'unzip', 'name' => substr( $file_name, 0, strrpos( $file_name, '.' ) ) );
			}
		}
	}

	// There is no any version in '_upgrade' directory. So, we need download package before.
	return array( 'action' => 'download', 'name' => $version_name.'-'.date( 'Y-m-d', $servertimenow ) );
}


/**
 * Convert aliases to real table names as table backup works with real table names
 * @param mixed aliases
 * @return mixed
 */
function aliases_to_tables( $aliases )
{
	global $DB;

	if( is_array( $aliases ) )
	{
		$tables = array();
		foreach( $aliases as $alias )
		{
			$tables[] = preg_replace( $DB->dbaliases, $DB->dbreplaces, $alias );
		}
		return $tables;
	}
	elseif( $aliases == '*' )
	{
		return $aliases;
	}
	else
	{
		return preg_replace( $DB->dbaliases, $DB->dbreplaces, $aliases );
	}
}


/*
 * $Log$
 * Revision 1.4  2009/11/18 21:54:25  efy-maxim
 * compatibility fix for PHP4
 *
 * Revision 1.3  2009/10/21 14:27:39  efy-maxim
 * upgrade
 *
 * Revision 1.2  2009/10/20 14:38:54  efy-maxim
 * maintenance modulde: downloading - unpacking - verifying destination files - backing up - copying new files - upgrade database using regular script (Warning: it is very unstable version! Please, don't use maintenance modulde, because it can affect your data )
 *
 * Revision 1.1  2009/10/18 20:15:51  efy-maxim
 * 1. backup, upgrade have been moved to maintenance module
 * 2. maintenance module permissions
 *
 */
?>