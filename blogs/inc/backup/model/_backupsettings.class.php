<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * BackupSettings class
 * This class is responsible to backup application files and data.
 *
 */
class BackupSettings
{
	/**
	 * All of the paths and theirs 'included' values defined in backup configuration file
	 * @var array
	 */
	var $backup_paths;

	/**
	 * All of the tables and theirs 'included' values defined in backup configuration file
	 * @var array
	 */
	var $backup_tables;

	/**
	 * True if enable maintenance mode before backup
	 * @var boolean
	 */
	var $maintenance_mode;

	/**
	 * True if pack backup files
	 * @var boolean
	 */
	var $pack_backup_files;


	/**
	 * Constructor
	 */
	function BackupSettings()
	{
		global $backup_paths, $backup_tables;

		// Set default settings defined in backup configuration file

		// Set backup folders/files default settings
		$this->backup_paths = array();
		foreach( $backup_paths as $name => $settings )
		{
			$this->backup_paths[$name] = $settings['included'];
		}

		// Set backup tables default settings
		$this->backup_tables = array();
		foreach( $backup_tables as $name => $settings )
		{
			$this->backup_tables[$name] = $settings['included'];
		}

		$this->maintenance_mode = true;
		$this->pack_backup_files = true;
	}


	/**
	 * Load settings from request
	 */
	function load_from_Request()
	{
		global $backup_paths, $backup_tables;

		// Load folders/files settings from request
		foreach( $backup_paths as $name => $settings )
		{
			if( array_key_exists( 'label', $settings ) )
			{	// We can set param
				$this->backup_paths[$name] = param( 'bk_'.$name, 'boolean' );
			}
		}

		// Load tables settings from request
		foreach( $backup_tables as $name => $settings )
		{
			$this->backup_tables[$name] = param( 'bk_'.$name, 'boolean' );
		}

		$this->maintenance_mode = param( 'bk_maintenance_mode', 'boolean' );
		$this->pack_backup_files = param( 'bk_pack_backup_files', 'boolean' );
	}


	/**
	 * Start backup
	 */
	function backup()
	{
		global $basepath, $backup_subdir, $servertimenow, $Messages;

		// Check are there something to backup
		$do_backup_files = $this->has_included( $this->backup_paths );
		$do_backup_tables = $this->has_included( $this->backup_tables );

		if( !$do_backup_files && !$do_backup_tables )
		{
			$Messages->add( T_( 'There is nothing to backup. Please select at least one option' ), 'error' );
			return false;
		}

		// Set time limit as backup can take much time
		set_time_limit( 1800 ); // 30 minutes

		// Enable maintenance mode
		$this->switch_maintenance_mode( 1 );

		// Create backup paths
		$backups_root_path = $basepath.$backup_subdir;
		$backups_path = $backups_root_path.date( 'Y-m-d-H-i-s', $servertimenow );

		if( $this->prepare_backupdir( $backups_root_path, true ) )
		{	// We can backup files and database
			$backup_files_path = $backups_path.'/files/';
			if( $do_backup_files && $this->prepare_backupdir( $backup_files_path ) )
			{	// We can backup files
				$this->backup_files( $backup_files_path );
			}

			$backup_tables_path = $backups_path.'/db/';
			if( $Messages->count() == 0 && $do_backup_tables && $this->prepare_backupdir( $backup_tables_path ) )
			{	// We can backup database
				$this->backup_database( $backup_tables_path );
			}
		}

		// Disable maintenance mode
		$this->switch_maintenance_mode( 0 );

		if( $Messages->count() > 0 )
		{
			rmdir_r( $backups_path );
			return false;
		}

		$Messages->add( sprintf( T_('Backup has been created in the following directory: &laquo;%s&raquo;'), $backups_path ), 'success' );
		return true;
	}


	/**
	 * Enable/disable maintenance mode
	 * @param integer enabled
	 */
	function switch_maintenance_mode( $enabled )
	{
		global $conf_path, $Messages;

		if( $this->maintenance_mode )
		{
			$conf_filepath = $conf_path.'_basic_config.php';

			// Read current config file
			$file_loaded = @file( $conf_filepath );

			if( empty( $file_loaded ) )
			{
				$Messages->add( sprintf( T_( 'Unable to switch maintenance mode. Configuration file not found: &laquo;%s&raquo;' ), $conf_filepath ), 'error' );
				return false;
			}

			// File loaded...
			$conf = implode( '', $file_loaded );

			// Update conf
			$conf = preg_replace( 	array( 	'/\$maintenance_mode = 0;/',
											'/\$maintenance_mode = 1;/'),
									'$maintenance_mode = '.$enabled.';', $conf );
			if( $conf )
			{
				$f = @fopen( $conf_filepath , 'w' );
				if( $f == false )
				{
					$Messages->add( sprintf( T_( 'Unable to switch maintenance mode. Could not open &laquo;%s&raquo; for writing.' ), $conf_filepath ), 'error' );
					return false;
				}
				else
				{	// Write new content
					fwrite( $f, $conf );
					fclose($f);
				}
			}
		}
	}


	/**
	 * Backup files
	 * @param string backup directory path
	 */
	function backup_files( $backup_dirpath )
	{
		global $basepath, $backup_paths, $Messages;

		$excluded_files = array();
		if( $this->pack_backup_files )
		{	// Create ZIPped backup

			// Load ZIP class
			load_class( '_ext/_zip_archives.php', 'zip_file' );

			$zip_filepath = $backup_dirpath.'files.zip';
			$zipfile = & new zip_file( $zip_filepath );
			$zipfile->set_options( array ( 'basedir'  => $basepath ) );

			// Find included and excluded files
			$included_files = array();

			if( $this->backup_paths['application_files'] )
			{
				$included_files = get_filenames( $basepath, true, true, true, false, true, true );

				foreach( $this->backup_paths as $name => $included )
				{
					if( !$included )
					{
						$excluded_files[] = $backup_paths[$name]['path'];
					}
				}
			}
			else
			{
				foreach( $this->backup_paths as $name => $included )
				{
					if( $included )
					{
						$included_files[] = $backup_paths[$name]['path'];
					}
				}
			}

			$zipfile->add_files( $included_files );
			$zipfile->exclude_files( $excluded_files );

			// Create archive
			$zipfile->create_archive();

			if( !file_exists( $zip_filepath ) )
			{
				$Messages->add( sprintf( T_( 'Unable to create &laquo;%s&raquo;' ), $zip_filepath ), 'error' );
				return false;
			}
		}
		else
		{	// Copy directories and files to backup directory
			if( $this->backup_paths['application_files'] )
			{	// Copy all of the directories and files except excluded ones
				foreach( $this->backup_paths as $name => $included )
				{
					if( !$included )
					{
						$excluded_files[] = no_trailing_slash( $basepath.$backup_paths[$name]['path'] );
					}
				}
				$this->recurse_copy( no_trailing_slash( $basepath ),
							no_trailing_slash( $backup_dirpath ), $excluded_files );
			}
			else
			{	// Copy only included directories and files
				foreach( $this->backup_paths as $name => $included )
				{
					if( $included )
					{
						$path = $backup_paths[$name]['path'];
						$this->recurse_copy( no_trailing_slash( $basepath.$path ),
									no_trailing_slash( $backup_dirpath.$path ), $excluded_files );
					}
				}
			}
		}
	}


	/**
	 * Backup database
	 * @param string backup directory path
	 */
	function backup_database( $backup_dirpath )
	{
		global $DB, $db_config, $backup_tables, $Messages;

		// Collect all included tables
		$ready_to_backup = array();
		foreach( $this->backup_tables as $name => $included )
		{
			if( $included )
			{
				$tables = $backup_tables[$name]['tables'];
				if( is_array( $tables ) )
				{
					$ready_to_backup = array_merge( $ready_to_backup, $tables );
				}
				elseif( $tables == '*' )
				{
					foreach( $DB->get_results( 'SHOW TABLES', ARRAY_N ) as $row )
					{
						$ready_to_backup[] = $row[0];
					}
				}
				else
				{
					$ready_to_backup[] = $tables;
				}
			}
		}

		// Ensure there are no duplicated tables
		$ready_to_backup = array_unique( $ready_to_backup );

		// Exclude tables
		foreach( $this->backup_tables as $name => $included )
		{
			if( !$included )
			{
				$tables = $backup_tables[$name]['tables'];
				if( is_array( $tables ) )
				{
					$ready_to_backup = array_diff( $ready_to_backup, $tables );
				}
				elseif( $tables != '*' )
				{
					$index = array_search( $tables, $ready_to_backup );
					if( $index )
					{
						unset( $ready_to_backup[$index] );
					}
				}
			}
		}

		// Create backup SQL script
		$backup = '';
		foreach( $ready_to_backup as $table )
		{
			$row_table_data = $DB->get_row( 'SHOW CREATE TABLE '.$table, ARRAY_N );
			$backup .= $row_table_data[1].";\n\n";

			$values_list = array();
			foreach( $DB->get_results( 'SELECT * FROM '.$table, ARRAY_N ) as $row )
			{
				$values = '(';
				$num_fields = count( $row );
				for( $index = 0; $index < $num_fields; $index++ )
				{
					$row[$index] = ereg_replace("\n","\\n", addslashes( $row[$index] ) );

	            	if ( isset($row[$index]) )
	            	{
						$values .= '\''.$row[$index].'\'' ;
					}
					else
					{
						$values .= '\'\'';
					}

					if ( $index<( $num_fields-1 ) )
					{
						$values .= ',';
					}
	            }
	            $values_list[] = $values.')';
			}

			if( !empty( $values_list ) )
			{
				$backup .= 'INSERT INTO '.$table.' VALUES '.implode( ',', $values_list ).";\n\n";
			}

			unset( $values_list );
		}

		// Save created SQL backup script
		$backup_sql_filename = 'backup.sql';
		$backup_sql_filepath = $backup_dirpath.$backup_sql_filename;
		if( !file_exists( $backup_sql_filepath ) )
		{
			$f = @fopen( $backup_sql_filepath , 'w+' );
			if( $f == false )
			{
				$Messages->add( sprintf( T_( 'Unable to write database dump. Could not open &laquo;%s&raquo; for writing.' ), $backup_sql_filepath ), 'error' );
				return false;
			}
			else
			{
				fwrite( $f, $backup );
				fclose($f);
			}
		}
		else
		{
			$Messages->add( sprintf( T_( 'Unable to write database dump. Database dump already exists: &laquo;%s&raquo;' ), $backup_sql_filepath ), 'error' );
			return false;
		}

		if( $this->pack_backup_files )
		{	// Pack created backup SQL script

			// Load ZIP class
			load_class( '_ext/_zip_archives.php', 'zip_file' );

			$zipfile = & new zip_file( 'backup.zip' );
			$zipfile->set_options( array ( 'basedir'  => $backup_dirpath ) );
			$zipfile->add_files( $backup_sql_filename );
			$zipfile->create_archive();

			if( $zipfile->error )
			{
				foreach( $zipfile->error as $error_msg )
				{
					$Messages->add( $error_msg, 'error' );
				}
				return false;
			}
			unlink( $backup_sql_filepath );
		}

		return true;
	}


	/**
	 * Prepare backup directory
	 * @param string directory path
	 * @param boolean create .htaccess file with 'deny from all' text
	 * @return boolean
	 */
	function prepare_backupdir( $dir_name, $deny_access = false )
	{
		global $Messages;

		if( !file_exists( $dir_name ) )
		{	// We can create directory
			if ( ! mkdir_r( $dir_name ) )
			{
				$Messages->add( sprintf( T_( 'Unable to create &laquo;%s&raquo; backup directory.' ), $dir_name ), 'error' );
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
					$Messages->add( sprintf( T_( 'Unable to create &laquo;%s&raquo; file in backup directory.' ), $htaccess_name ), 'error' );
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
	 * Copy directory recursively
	 * @param string source directory
	 * @param string destination directory
	 * @param array excluded directories
	 */
	function recurse_copy( $src, $dest, &$excluded_files )
	{
    	$dir = opendir( $src );
    	@mkdir( $dest );
    	while( false !== ( $file = readdir( $dir ) ) )
    	{
        	if ( ( $file != '.' ) && ( $file != '..' ) )
        	{
            	$srcfile = $src.'/'.$file;
        		if ( is_dir( $srcfile ) )
            	{
            		if( !in_array( $srcfile, $excluded_files ) )
            		{	// We can copy the current directory as it isn't in excluded directories list
                		$this->recurse_copy( $srcfile, $dest . '/' . $file, $excluded_files );
            		}
            	}
            	else
            	{	// Copy file
                	copy( $srcfile, $dest.'/'. $file );
            	}
        	}
		}
    	closedir( $dir );
	}


	/**
	 * Check has data list included directories/files or tables
	 * @param array list
	 * @return boolean
	 */
	function has_included( & $data_list )
	{
		foreach( $data_list as $included )
		{
			if( $included )
			{
				return true;
			}
		}
		return false;
	}
}

?>