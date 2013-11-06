<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Check version of downloaded upgrade vs. current version
 *
 * @param new version dir name
 * @return string message or NULL
 */
function check_version( $new_version_dir )
{
	global $rsc_url, $upgrade_path, $conf_path;

	$new_version_file = $upgrade_path.$new_version_dir.'/b2evolution/blogs/conf/_application.php';

	require( $new_version_file );

	$vc = version_compare( $app_version, $GLOBALS['app_version'] );

	if( $vc < 0 )
	{
		return T_( 'This is an old version!' );
	}
	elseif( $vc == 0 )
	{
		if( $app_date == $GLOBALS['app_date'] )
		{
			return T_( 'This package is already installed!' );
		}
		elseif( $app_date < $GLOBALS['app_date'] )
		{
			return T_( 'This is an old version!' );
		}
	}

	return NULL;
}


/**
 * Enable/disable maintenance mode
 *
 * @param boolean true if maintenance mode need to be enabled
 * @param string maintenance mode message
 */
function switch_maintenance_mode( $enable, $msg = '', $silent = false )
{
	global $conf_path;

	$maintenance_mode_file = 'maintenance.html';

	if( $enable )
	{	// Create maintenance file
		echo '<p>'.T_('Switching to maintenance mode...').'</p>';
		flush();

		$content = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<title>Site temporarily down for maintenance.</title>
</head>
<body>
<h1>503 Service Unavailable</h1>
<p>'.$msg.'</p>
<hr />
<p>Site administrators: please view the source of this page for details.</p>
<!--
If you need to manually put b2evolution out of maintenance mode, delete or rename the file /conf/maintenance.html
-->
</body>
</html>';

		if( ! save_to_file( $content, $conf_path.$maintenance_mode_file, 'w+' ) )
		{	// Maintenance file has not been created
			echo '<p style="color:red">'.sprintf( T_( 'Unable to switch maintenance mode. Maintenance file can\'t be created: &laquo;%s&raquo;' ), $maintenance_mode_file ).'</p>';
    		flush();

			return false;
		}
	}
	else
	{	// Delete maintenance file
		if( ! $silent )
		{
			echo '<p>'.T_('Switching out of maintenance mode...').'</p>';
		}
		@unlink( $conf_path.$maintenance_mode_file );
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
function prepare_maintenance_dir( $dir_name, $deny_access = true )
{

	// echo '<p>'.T_('Checking destination directory: ').$dir_name.'</p>';
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
		echo '<p>'.T_('Checking .htaccess denial for directory: ').$dir_name.'</p>';

		$htaccess_name = $dir_name.'.htaccess';

		if( !file_exists( $htaccess_name ) )
		{	// We can create .htaccess file
			if( ! save_to_file( 'deny from all', $htaccess_name, 'w' ) )
			{
				echo '<p style="color:red">'.sprintf( T_( 'Unable to create &laquo;%s&raquo; file in directory.' ), $htaccess_name ).'</p>';
				flush();

				return false;
			}

			if( ! file_exists($dir_name.'index.html') )
			{	// Create index.html to disable directory browsing
				save_to_file( '', $dir_name.'index.html', 'w' );
			}
		}

		// fp> TODO: make sure "deny all" actually works by trying to request the directory through HTTP
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

		// Load PclZip class (PHP4):
		load_class( '_ext/pclzip/pclzip.lib.php', 'PclZip' );

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
function verify_overwrite( $src, $dest, $action = '', $overwrite = true, & $read_only_list )
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
			// pre_dump($srcfile,$destfile);

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
			echo $action.' &laquo;<strong>'.$dest_dir.'</strong>&raquo;...<br />';
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
	global $upgrade_path, $servertimenow, $debug;

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
		$filename_params = array(
				'inc_files'	=> false,
				'recurse'	=> false,
				'basename'	=> true,
			);
		// Search if there is unpacked version in '_upgrade' directory
		foreach( get_filenames( $upgrade_path, $filename_params ) as $dir_name )
		{
			if( strpos( $dir_name, $version_name ) === 0 )
			{
				$action_props = array();
				$new_version_status = check_version( $dir_name );
				if( !empty( $new_version_status ) )
				{
					$action_props['action'] = 'none';
					$action_props['status'] = $new_version_status;
				}

				if( $debug > 0 || empty( $new_version_status ) )
				{
					$action_props['action'] = 'install';
					$action_props['name'] = $dir_name;
				}

				return $action_props;
			}
		}

		$filename_params = array(
				'inc_dirs'	=> false,
				'recurse'	=> false,
				'basename'	=> true,
			);
		// Search if there is packed version in '_upgrade' directory
		foreach( get_filenames( $upgrade_path, $filename_params ) as $file_name )
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
 * Revision 1.14  2013/11/06 08:04:25  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>