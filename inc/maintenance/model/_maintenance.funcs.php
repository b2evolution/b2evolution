<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get the upgrade folder path
 *
 * @param string Name of folder with current downloaded version
 * @return string The upgrade folder path (No slash at the end)
 */
function get_upgrade_folder_path( $version_folder_name )
{
	global $upgrade_path;

	if( empty( $version_folder_name ) || ! file_exists( $upgrade_path.$version_folder_name ) )
	{ // Don't allow an invalid upgrade folder
		debug_die( 'Invalid name of upgrade folder' );
	}

	// Use a root path by default
	$upgrade_folder_path = $upgrade_path.$version_folder_name;

	if( file_exists( $upgrade_folder_path.'/b2evolution/blogs' ) )
	{ // Use 'b2evolution/blogs' folder
		$upgrade_folder_path .= '/b2evolution/blogs';
	}
	else if( file_exists( $upgrade_folder_path.'/b2evolution/site' ) )
	{ // Use 'b2evolution/site' folder
		$upgrade_folder_path .= '/b2evolution/site';
	}
	else if( file_exists( $upgrade_folder_path.'/b2evolution' ) )
	{ // Use 'b2evolution' folder
		$upgrade_folder_path .= '/b2evolution';
	}

	return $upgrade_folder_path;
}


/**
 * Check version of downloaded upgrade vs. current version
 *
 * @param new version dir name
 * @return array|NULL NULL - version is new, Array - version is old or same,
 *                    keys 'error' => 'old' or 'same', 'message' - Message text
 */
function check_version( $new_version_dir )
{
	global $rsc_url, $upgrade_path, $conf_path;

	$new_version_file = get_upgrade_folder_path( $new_version_dir ).'/conf/_application.php';

	if( ! file_exists( $new_version_file ) )
	{ // Invalid structure of the downloaded upgrade package
		debug_die( '/conf/_application.php not found in /b2evolution/blogs/ nor /b2evolution/site/ nor /b2evolution/! You may have downloaded an invalid ZIP package.' );
	}

	require( $new_version_file );

	$vc = evo_version_compare( $app_version, $GLOBALS['app_version'] );

	if( $vc < 0 )
	{
		$result = 'old';
	}
	elseif( $vc == 0 )
	{
		if( $app_date == $GLOBALS['app_date'] )
		{
			$result = 'same';
		}
		elseif( $app_date < $GLOBALS['app_date'] )
		{
			$result = 'old';
		}
	}

	if( empty( $result ) )
	{	// New version:
		return NULL;
	}
	elseif( $result == 'old' )
	{	// Old version:
		return array(
				'error'   => 'old',
				'message' => T_( 'This is an old version!' ).'<br />'
					.T_('You should NOT install this older version.')
			);
	}
	elseif( $result == 'same' )
	{	// Same version:
		return array(
				'error'   => 'same',
				'message' => T_( 'This package is already installed!' ).'<br />'
					.T_( 'No upgrade is needed at this time. You might force a re-install if you want to force a cleanup.' )
			);
	}
}


/**
 * Enable/disable maintenance mode
 *
 * @param boolean true if maintenance mode need to be enabled
 * @param string Mode: 'all', 'install', 'upgrade'
 * @param string maintenance mode message
 * @param boolean TRUE to don't print out a message status
 */
function switch_maintenance_mode( $enable, $mode = 'all', $msg = '', $silent = false )
{
	global $conf_path;

	switch( $mode )
	{
		case 'install':
			// Use maintenance mode except of install actions
			$maintenance_mode_file = 'imaintenance.html';
			break;

		case 'upgrade':
			// Use maintenance mode except of upgrade actions
			$maintenance_mode_file = 'umaintenance.html';
			break;

		default:
			// Use full maintenance mode
			$maintenance_mode_file = 'maintenance.html';
			break;
	}

	if( $enable )
	{	// Create maintenance file
		echo '<p>'.T_('Switching to maintenance mode...');
		evo_flush();

		$content = '<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Site temporarily down for maintenance.</title>
</head>
<body>
<h1>503 Service Unavailable</h1>
<p>'.$msg.'</p>
<hr />
<p>Site administrators: please view the source of this page for details.</p>
<!--
If you need to manually put b2evolution OUT of maintenance mode, delete or rename the file
/conf/maintenance.html or /conf/imaintenance.html or /conf/umaintenance.html .
The presence of any of these files will make b2evolution show it is in maintenance mode.

WARNING: If you just had an upgrade fail in the middle of it, it is a very bad idea to just
get out of maintenance mode without immdiately restoring a DB backup first. Continuing without
a clean DB may make it impossible to ever ugrade your b2evolution in the future.
-->
</body>
</html>';

		if( save_to_file( $content, $conf_path.$maintenance_mode_file, 'w+' ) )
		{ // Maintenance file has been created
			echo ' OK.</p>';
		}
		else
		{ // Maintenance file has not been created
			echo '</p><p style="color:red"><evo:error>'.sprintf( T_( 'Unable to switch to maintenance mode. Maintenance file can\'t be created: &laquo;%s&raquo;' ), $maintenance_mode_file ).'</evo:error></p>';
			evo_flush();

			return false;
		}
	}
	else
	{	// Delete maintenance file
		if( ! $silent )
		{
			echo '<p>'.T_('Switching out of maintenance mode...');
		}
		// Delete a maintenance file if it exists and writable:
		if( is_writable( $conf_path.$maintenance_mode_file ) && @unlink( $conf_path.$maintenance_mode_file ) )
		{	// Unlink was successful:
			if( ! $silent )
			{	// Dispaly OK message:
				echo ' OK.</p>';
				evo_flush();
			}
		}
		else
		{	// Unlink failed:
			echo '</p><p style="color:red"><evo:error>'.sprintf( T_( 'Unable to delete a maintenance file: &laquo;%s&raquo;' ), $maintenance_mode_file ).'</evo:error></p>';
			evo_flush();

			return false;
		}
	}

	return true;
}


/**
 * Enable/disable maintenance lock
 *
 * @param boolean true if maintenance lock need to be enabled
 * @return bollean true on success, false otherwise
 */
function switch_maintenance_lock( $enable )
{
	global $Settings;

	if( $Settings->get( 'system_lock' ) != $enable )
	{ // Enable system lock
		$Settings->set( 'system_lock', $enable );
		return $Settings->dbupdate();
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
			evo_flush();

			return false;
		}
	}

	if( $deny_access )
	{	// Create .htaccess file
		echo '<p>'.T_('Checking .htaccess denial for directory: ').$dir_name;
		evo_flush();

		$htaccess_name = $dir_name.'.htaccess';

		if( !file_exists( $htaccess_name ) )
		{	// We can create .htaccess file
			if( ! save_to_file( 'deny from all', $htaccess_name, 'w' ) )
			{
				echo '</p><p style="color:red">'.sprintf( T_( 'Unable to create &laquo;%s&raquo; file in directory.' ), $htaccess_name ).'</p>';
				evo_flush();

				return false;
			}

			if( ! file_exists($dir_name.'index.html') )
			{	// Create index.html to disable directory browsing
				save_to_file( '', $dir_name.'index.html', 'w' );
			}
		}

		echo ' : OK.</p>';
		evo_flush();

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
 * @param string Zip file name
 * @return boolean results
 */
function unpack_archive( $src_file, $dest_dir, $mk_dest_dir = false, $src_file_name = '' )
{
	if( !file_exists( $dest_dir ) )
	{ // We can create directory
		if ( ! mkdir_r( $dest_dir ) )
		{
			echo '<p style="color:red">'.sprintf( T_( 'Unable to create &laquo;%s&raquo; directory to extract files from ZIP archive.' ), $dest_dir ).'</p>';
			evo_flush();

			return false;
		}
	}

	if( function_exists( 'gzopen' ) )
	{ // Unpack using 'zlib' extension and PclZip wrapper

		// Load PclZip class (PHP4):
		load_class( '_ext/pclzip/pclzip.lib.php', 'PclZip' );

		$PclZip = new PclZip( $src_file );
		if( $PclZip->extract( PCLZIP_OPT_PATH, $dest_dir ) == 0 )
		{
			if( empty( $src_file_name ) )
			{ // Set zip file name
				$src_file_name = $src_file;
			}
			echo '<p style="color:red">'
					.sprintf( T_( 'Error: %s' ), $PclZip->errorInfo( true ) ).'<br />'
					.sprintf( T_( 'Unable to decompress &laquo;%s&raquo; ZIP archive.' ), $src_file_name )
				.'</p>';
			evo_flush();

			return false;
		}
	}
	else
	{
		debug_die( 'Unable to decompress the file because there is no \'zip\' or \'zlib\' extension installed in your PHP!' );
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
	global $basepath;

	/**
	 * Result of this function is FALSE when some error was detected
	 * @var boolean
	 */
	$result = true;

	$dir = opendir( $src );

	if( $dir === false )
	{ // $dir is not a valid directory or it can not be opened due to permission restrictions
		echo '<div class="red">The &laquo;'.htmlspecialchars( $src ).'&raquo; is not a valid direcotry or the directory can not be opened due to permission restrictions or filesystem errors.</div>';
		return false;
	}

	$dir_list = array();
	$file_list = array();
	while( false !== ( $file = readdir( $dir ) ) )
	{
		if ( ( $file != '.' ) && ( $file != '..' ) )
		{
			$srcfile = $src.'/'.$file;
			$destfile = $dest.'/'.$file;

			if( isset( $read_only_list ) && file_exists( $destfile ) && !is_writable( $destfile ) )
			{ // Folder or file is not writable
				$read_only_list[] = $destfile;
			}

			if ( is_dir( $srcfile ) )
			{
				$dir_list[$srcfile] = $destfile;
			}
			elseif( $overwrite )
			{ // Add to overwrite
				$file_list[$srcfile] = $destfile;
			}
		}
	}

	$config_ignore_files = get_upgrade_config( 'ignore' );
	$config_softmove_files = get_upgrade_config( 'softmove');
	$config_forcemove_files = get_upgrade_config( 'forcemove' );

	if( ! empty( $action ) && $action == 'Copying' )
	{ // Display errors about config file or the unknown and incorrect commands from config file
		$config_has_errors = false;
		if( is_string( $config_ignore_files ) )
		{ // Config file has some errors, but the upgrade should not fail because of that
			echo '<div class="red">'.$config_ignore_files.'</div>';
			$config_has_errors = true;
		}
		else
		{
			$config_unknown_commands = get_upgrade_config( 'unknown' );
			$config_incorrect_commands = get_upgrade_config( 'incorrect' );

			if( ! empty( $config_unknown_commands ) && is_array( $config_unknown_commands ) )
			{ // Unknown commands
				foreach( $config_unknown_commands as $config_unknown_command )
				{
					echo '<div class="red">'.sprintf( T_('Unknown policy command: %s'), $config_unknown_command ).'</div>';
				}
				$config_has_errors = true;
			}

			if( ! empty( $config_incorrect_commands ) && is_array( $config_incorrect_commands ) )
			{ // Incorrect commands
				foreach( $config_incorrect_commands as $config_incorrect_command )
				{
					echo '<div class="red">'.sprintf( T_('Incorrect policy command: %s'), $config_incorrect_command ).'</div>';
				}
				$config_has_errors = true;
			}
		}

		if( $config_has_errors )
		{ // The upgrade config file contains the errors, Stop the upgrading process
			echo '<div class="red">'.sprintf( T_('To continue the upgrade process please fix the issues of the file %s or delete it.'), '<code>'.get_upgrade_config_file_name().'</code>' ).'</div>';
			return false;
		}
	}

	foreach( $dir_list as $src_dir => $dest_dir )
	{
		$dest_dir_name = str_replace( $basepath, '', $dest_dir );
		// Detect if we should ignore this folder
		$ignore_dir = $overwrite && is_array( $config_ignore_files ) && in_array( $dest_dir_name, $config_ignore_files );

		$dir_success = false;
		if( !empty( $action ) )
		{
			if( $ignore_dir )
			{ // Ignore folder
				echo '<div class="orange">'.sprintf( T_('Ignoring %s because of %s'), '&laquo;<b>'.$dest_dir.'</b>&raquo;', '<code>'.get_upgrade_config_file_name().'</code>' ).'</div>';
			}
			else
			{ // progressive display of what backup is doing
				echo $action.' &laquo;<strong>'.$dest_dir.'</strong>&raquo;...';
				$dir_success = true;
			}
			evo_flush();
		}
		elseif( $ignore_dir )
		{ // This subfolder must be ingored, Display message about this
			echo '<div class="orange">'.sprintf( T_('Ignoring %s because of %s'), '&laquo;<b>'.$dest_dir_name.'</b>&raquo;', '<code>'.get_upgrade_config_file_name().'</code>' ).'</div>';
			$dir_success = false;
			evo_flush();
		}

		if( $ignore_dir )
		{ // Skip the ignored folder
			continue;
		}

		if( $overwrite && !file_exists( $dest_dir ) )
		{
			// Create destination directory
			if( ! evo_mkdir( $dest_dir ) )
			{ // No permission to create a folder
				echo '<div class="red">'.sprintf( T_('Unavailable creating of folder %s, probably no permissions.'), '&laquo;<b>'.$dest_dir_name.'</b>&raquo;' ).'</div>';
				$result = false;
				$dir_success = false;
				evo_flush();
				continue;
			}
		}

		if( $dir_success )
		{
			echo ' OK.<br />';
			evo_flush();
		}

		$result = $result && verify_overwrite( $src_dir, $dest_dir, '', $overwrite, $read_only_list );
	}

	foreach( $file_list as $src_file => $dest_file )
	{ // Overwrite destination file
		$dest_file_name = str_replace( $basepath, '', $dest_file );
		if( is_array( $config_ignore_files ) && in_array( $dest_file_name, $config_ignore_files ) )
		{ // Ignore this file
			echo '<div class="orange">'.sprintf( T_('Ignoring %s because of %s'), '&laquo;<b>'.$dest_file_name.'</b>&raquo;', '<code>'.get_upgrade_config_file_name().'</code>' ).'</div>';
			evo_flush();
			continue;
		}

		if( is_array( $config_softmove_files ) && !empty( $config_softmove_files[ $dest_file_name ] ) )
		{ // Action 'softmove': This file should be copied to other location with saving old file
			$copy_file_name = $config_softmove_files[ $dest_file_name ];
			// Don't rewrite old file
			$rewrite_old_file = false;
		}
		if( is_array( $config_forcemove_files ) && !empty( $config_forcemove_files[ $dest_file_name ] ) )
		{ // Action 'forcemove': This file should be copied to other location with rewriting old file
			$copy_file_name = $config_forcemove_files[ $dest_file_name ];
			// Rewrite old file
			$rewrite_old_file = true;
		}

		if( ! empty( $copy_file_name ) )
		{ // This file is marked in config to copy to other location
			$copy_file = $basepath.$copy_file_name;
			if( ! $rewrite_old_file && file_exists( $copy_file ) )
			{ // Display warning if we cannot rewrite an existing file
				echo '<div class="orange">'.sprintf( T_('Ignoring softmove of %s because %s is already in place (see %s)'),
						'&laquo;<b>'.$dest_file_name.'</b>&raquo;',
						'&laquo;<b>'.$copy_file_name.'</b>&raquo;',
						'<code>'.get_upgrade_config_file_name().'</code>' ).'</div>';
				evo_flush();
				unset( $copy_file_name );
				continue; // Skip this file
			}
			else
			{ // We can copy this file to other location
				echo '<div class="orange">'.sprintf( T_('Moving %s to %s as stated in %s'),
						'&laquo;<b>'.$dest_file_name.'</b>&raquo;',
						'&laquo;<b>'.$copy_file_name.'</b>&raquo;',
						'<code>'.get_upgrade_config_file_name().'</code>' ).'</div>';
				evo_flush();
				// Set new location for a moving file
				$dest_file = $copy_file;
				$dest_file_name = $copy_file_name;
				unset( $copy_file_name );
			}
		}

		// Copying
		if( ! @copy( $src_file, $dest_file ) )
		{ // Display error if a copy command is unavailable
			echo '<div class="red">'.sprintf( T_('Unavailable copying to %s, probably no permissions.'), '&laquo;<b>'.$dest_file_name.'</b>&raquo;' ).'</div>';
			$result = false;
			evo_flush();
		}
	}

	closedir( $dir );

	return $result;
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


/**
 * Check if the upgrade config file exists and display error message if config doesn't exist
 *
 * @return boolean TRUE if config exists
 */
function check_upgrade_config( $display_message = false )
{
	global $conf_path;

	if( ! file_exists( $conf_path.'upgrade_policy.conf' ) )
	{	// No upgrade config file
		if( $display_message )
		{	// Display error message:
			global $Messages;
			$Messages->add( T_('WARNING: <code>upgrade_policy.conf</code> not found. We will use <code>/conf/upgrade_policy_sample.conf</code> by default but it is highly recommended you duplicate this file to <code>upgrade_policy.conf</code> and check its contents to make sure the upgrade policy is appropriate for your particluar site.'), 'warning' );
		}
		return false;
	}

	return true;
}


/**
 * Get file name of the upgrade config depending on what exists
 *
 * @return string
 */
function get_upgrade_config_file_name()
{
	global $conf_path;

	if( file_exists( $conf_path.'upgrade_policy.conf' ) )
	{	// Use custom file firstly:
		return 'upgrade_policy.conf';
	}
	else
	{	// Use sample file:
		return 'upgrade_policy_sample.conf';
	}
}


/**
 * Get a list of files and folders that must be ignored/removed on upgrade
 *
 * @param string Type of action: 'ignore', 'remove', 'softmove', 'forcemove'
 *                               'unknown' - Stores all unknown actions
 *                               'incorrect' - Stores all incorrect actions
 * @return array|string List of files and folders | Error message
 */
function get_upgrade_config( $action )
{
	global $conf_path, $upgrade_policy_config;

	if( ! isset( $upgrade_policy_config ) )
	{ // Init global array first time
		$upgrade_policy_config = array();
	}
	elseif( is_string( $upgrade_policy_config ) )
	{ // Return error about config file
		return $upgrade_policy_config;
	}

	if( isset( $upgrade_policy_config[ $action ] ) )
	{ // The config files were already initialized before, Don't make it twice
		return $upgrade_policy_config[ $action ];
	}

	$config_handle = @fopen( $conf_path.get_upgrade_config_file_name(), 'r' );
	if( ! $config_handle )
	{ // No permissions to open file
		$upgrade_policy_config = sprintf( T_('No permission to open the %s file.'), '<code>'.get_upgrade_config_file_name().'</code>' );
		return $upgrade_policy_config;
	}

	// Get content from config file
	$config_content = '';
	while( !feof( $config_handle ) )
	{
		$config_content .= fgets( $config_handle, 4096 );
	}
	fclose( $config_handle );

	if( empty( $config_content ) )
	{ // Config file is empty for required action
		$upgrade_policy_config = sprintf( T_('The %s file is empty.'), '<code>'.get_upgrade_config_file_name().'</code>' );
		return $upgrade_policy_config;
	}

	// Only these actions are available in the upgrade_policy.conf
	$available_actions = array( 'ignore', 'remove', 'softmove', 'forcemove' );

	$all_actions = array_merge( $available_actions, array( 'unknown', 'incorrect' ) );
	foreach( $all_actions as $available_action )
	{ // Init array for all actions only first time
		if( !isset( $upgrade_policy_config[ $available_action ] ) )
		{
			$upgrade_policy_config[ $available_action ] = array();
		}
	}

	$config_content = str_replace( "\r", '', $config_content );
	$config_content = explode( "\n", $config_content );

	foreach( $config_content as $config_line )
	{
		if( substr( $config_line, 0, 1 ) == ';' )
		{ // This line is comment text, Skip it
			continue;
		}

		$config_line = trim( $config_line );

		$config_line_params = explode( ' ', $config_line );
		$line_action =  $config_line_params[0];
		if( in_array( $line_action, $available_actions ) )
		{ // This line has an available action
			if( empty( $config_line_params[1] ) )
			{ // Incorrect command
				$upgrade_policy_config[ 'incorrect' ][] = $config_line;
				continue;
			}
			if( $line_action == 'softmove' || $line_action == 'forcemove' )
			{ // These actions have two params
				if( empty( $config_line_params[1] ) || empty( $config_line_params[2] ) )
				{ // Incorrect command
					$upgrade_policy_config[ 'incorrect' ][] = $config_line;
					continue;
				}
				$upgrade_policy_config[ $line_action ][ $config_line_params[1] ] = $config_line_params[2];
			}
			else
			{ // Actions 'ignore' & 'remove' have only one param
				$upgrade_policy_config[ $line_action ][] = $config_line_params[1];
			}
		}
		elseif( !empty( $line_action ) )
		{ // Also save all unknown actions to display error
			$upgrade_policy_config[ 'unknown' ][] = $config_line;
		}
	}

	return $upgrade_policy_config[ $action ];
}


/**
 * Remove files/folders after upgrade, See file upgrade_policy.conf
 */
function remove_after_upgrade()
{
	global $basepath, $conf_path;

	$upgrade_removed_files = get_upgrade_config( 'remove' );

	echo '<h4>'.T_('Cleaning up...').'</h4>';
	evo_flush();

	if( is_string( $upgrade_removed_files ) )
	{ // Errors on opening of upgrade_policy.conf
		$config_error = $upgrade_removed_files;
	}
	elseif( empty( $upgrade_removed_files ) )
	{ // No files/folders to remove, Exit here
		$config_error = sprintf( T_('No "remove" sections have been defined in the file %s.'), '<code>'.get_upgrade_config_file_name().'</code>' );
	}

	if( !empty( $config_error ) )
	{ // Display config error
		echo '<div class="red">';
		echo $config_error;
		echo ' '.T_('No cleanup is being done. You should manually remove the <code>/install</code> folder and check for other unwanted files...');
		echo '</div>';
		return;
	}

	foreach( $upgrade_removed_files as $file_path )
	{
		$file_path = $basepath.$file_path;
		$log_message = sprintf( T_('Removing %s as stated in %s...'), '<code>'.$file_path.'</code>', '<code>'.get_upgrade_config_file_name().'</code>' ).' ';
		$success = true;
		if( file_exists( $file_path ) )
		{ // File exists
			if( is_dir( $file_path ) )
			{ // Remove folder recursively
				if( rmdir_r( $file_path ) )
				{ // Success
					$log_message .= T_('OK');
				}
				else
				{ // Failed
					$log_message .= T_('Failed').': '.T_('No permissions to delete the folder');
					$success = false;
				}
			}
			elseif( is_writable( $file_path ) )
			{ // Remove file
				if( @unlink( $file_path ) )
				{ // Success
					$log_message .= T_('OK');
				}
				else
				{ // Failed
					$log_message .= T_('Failed').': '.T_('No permissions to delete the file');
					$success = false;
				}
			}
			else
			{ // File is not writable
				$log_message .= T_('Failed').': '.T_('No permissions to delete the file');
				$success = false;
			}
		}
		else
		{ // No file/folder
			$log_message .= T_('Failed').': '.T_('No file found');
			$success = false;
		}

		echo $success ? $log_message.'<br />' : '<div class="orange">'.$log_message.'</div>';
		evo_flush();
	}
}


/**
 * Get affected paths
 *
 * @param string Path
 * @return string
 */
function get_affected_paths( $path )
{
	global $basepath;

	$affected_paths = T_( 'Affected paths:' ).' ';
	if( is_array( $path ) )
	{
		$paths = array();
		foreach( $path as $p )
			$paths[] = no_trailing_slash( $p );

		$affected_paths .= implode( ', ', $paths );
	}
	elseif( $path == '*' )
	{
		$filename_params = array(
				'inc_files'	=> false,
				'recurse'	=> false,
				'basename'	=> true,
			);
		$affected_paths .= implode( ', ', get_filenames( $basepath, $filename_params ) );
	}
	else
	{
		$affected_paths .= no_trailing_slash( $path );
	}
	return $affected_paths;
}


/**
 * Get affected tables
 *
 * @param string Table
 * @return string
 */
function get_affected_tables( $table )
{
	global $DB;

	$affected_tables = T_( 'Affected tables:' ).' ';
	if( is_array( $table ) )
	{
		$affected_tables .= implode( ', ', aliases_to_tables( $table ) );
	}
	elseif( $table == '*' )
	{
		$tables = array();
		foreach( $DB->get_results( 'SHOW TABLES', ARRAY_N ) as $row )
				$tables[] = $row[0];

		$affected_tables .= implode( ', ', $tables );
	}
	else
	{
		$affected_tables .= aliases_to_tables( $table );
	}
	return $affected_tables;
}

/**
 * Get html template of steps panel
 *
 * @param array Steps
 * @param integer Current step
 * @return string
 */
function get_tool_steps( $steps, $current_step )
{
	if( empty( $steps ) || empty( $current_step ) )
	{ // Bad input data
		return '';
	}

	$r = '<div class="tool_steps">';
	foreach( $steps as $step_num => $step_title )
	{
		$r .= '<div class="step'
						.( $step_num == $current_step ? ' current' : '' )
						.( $step_num < $current_step ? ' completed' : '' ).'">'
					.'<div>'.$step_num
						.( $step_num < $current_step ? '<span>&#10003;</span>' : '' )
					.'</div>'
					.$step_title
				.'</div>';
	}
	$r .= '</div>';

	return $r;
}

/**
 * Display steps panel
 *
 * @param integer Current step
 */
function autoupgrade_display_steps( $current_step )
{
	$steps = array(
			1 => T_('Check for updates'),
			2 => T_('Download'),
			3 => T_('Unzip'),
			4 => T_('Ready to upgrade'),
			5 => T_('Backup &amp; Upgrade'),
			6 => T_('Installer script'),
		);

	echo get_tool_steps( $steps, $current_step );
}


/**
 * Display steps panel
 *
 * @param integer Current step
 */
function svnupgrade_display_steps( $current_step )
{
	$steps = array(
			1 => T_('Connect to SVN'),
			2 => T_('Export'),
			3 => T_('Ready to upgrade'),
			4 => T_('Backup &amp; Upgrade'),
			5 => T_('Installer script'),
		);

	echo get_tool_steps( $steps, $current_step );
}


/**
 * Callback function to decide what folders backup on zip
 *
 * @param integer Event number, e.g. PCLZIP_CB_PRE_ADD, see class PclZip
 * @param array Params of current file/folder
 * @return integer 1 - to include, 0 - to exclude
 */
function callback_backup_files( $p_event, & $p_header )
{
	global $backup_current_exclude_folders;

	if( empty( $backup_current_exclude_folders ) )
	{	// Nothing to exclude:
		return 1;
	}

	foreach( $backup_current_exclude_folders as $exclude_folder_name )
	{
		if( $p_header['stored_filename'] == $exclude_folder_name ||
		    strpos( $p_header['stored_filename'].'/', '/'.$exclude_folder_name.'/' ) !== false )
		{	// Skip this file/folder:
			return 0;
		}
	}

	// Include this file/folder to backup zip archive:
	return 1;
}
?>