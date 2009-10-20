<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


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
	global $conf_path, $Messages;

	$maintenance_mode_file = 'maintenance.txt';

	if( $enable )
	{	// Create maintenance file
		echo '<p>'.T_('Switching to maintenance mode...').'</p>';

		$f = @fopen( $conf_path.$maintenance_mode_file , 'w+' );
		if( $f == false )
		{	// Maintenance file has not been created
			$Messages->add( sprintf( T_( 'Unable to switch maintenance mode. Maintenance file can\'t be created: &laquo;%s&raquo;' ), $maintenance_mode_file ), 'error' );
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

		if( !unlink( $conf_path.$maintenance_mode_file ) )
		{
			$Messages->add( sprintf( T_( 'Unable to switch maintenance mode. Maintenance file can\'t be deleted: &laquo;%s&raquo;' ), $maintenance_mode_file ), 'error' );
			return false;
		}
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
	global $Messages;

	if( !file_exists( $dir_name ) )
	{	// We can create directory
		if ( ! mkdir_r( $dir_name ) )
		{
			$Messages->add( sprintf( T_( 'Unable to create &laquo;%s&raquo; directory.' ), $dir_name ), 'error' );
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
				$Messages->add( sprintf( T_( 'Unable to create &laquo;%s&raquo; file in directory.' ), $htaccess_name ), 'error' );
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
	global $Messages;

	if( !file_exists( $dest_dir ) )
	{	// We can create directory
		if ( !mkdir_r( $dest_dir ) )
		{
			$Messages->add( sprintf( T_( 'Unable to create &laquo;%s&raquo; directory.' ), $dest_dir ), 'error' );
			return false;
		}
	}

	if( extension_loaded( 'zip' ) )
	{
		$zip = new ZipArchive();

		$zip->open( $src_file );
		$success = $zip->extractTo( $dest_dir );
		$zip->close();

		if( !$success )
		{
			$Messages->add( sprintf( T_( 'Unable to unpack &laquo;%s&raquo; ZIP archive.' ), $src_file ), 'error' );
			return false;
		}
	}

	return true;
}


/**
 * Verify that destination files can be overwritten
 *
 * @param string source directory
 * @param string destination directory
 * @param array read only file list
 * @param string action name
 * @param boolean overwrite
 */
function verify_overwrite( $src, $dest, $action = '', $overwrite = true, &$read_only_list = NULL )
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

			if( $read_only_list != NULL && file_exists( $destfile ) && !is_writable( $destfile ) )
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


/*
 * $Log$
 * Revision 1.2  2009/10/20 14:38:54  efy-maxim
 * maintenance modulde: downloading - unpacking - verifying destination files - backing up - copying new files - upgrade database using regular script (Warning: it is very unstable version! Please, don't use maintenance modulde, because it can affect your data )
 *
 * Revision 1.1  2009/10/18 20:15:51  efy-maxim
 * 1. backup, upgrade have been moved to maintenance module
 * 2. maintenance module permissions
 *
 */
?>