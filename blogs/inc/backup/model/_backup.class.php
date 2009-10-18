<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var strings base application paths
 */
global $basepath, $conf_subdir, $skins_subdir, $adminskins_subdir;
global $plugins_subdir, $media_subdir, $backup_subdir, $upgrade_subdir;

/**
 * @var array backup paths
 */
global $backup_paths;

/**
 * @var array backup tables
 */
global $backup_tables;


/**
 * Backup folder/files default settings
 * - 'label' checkbox label
 * - 'note' checkbox note
 * - 'path' path to folder or file
 * - 'included' true if folder or file must be in backup
 * @var array
 */
$backup_paths = array(
	'application_files'   => array (
		'label'    => T_( 'Application files' ), /* It is files root. Please, don't remove it. */
		'path'     => '*',
		'included' => true ),

	'configuration_files' => array (
		'label'    => T_( 'Configuration files' ),
		'path'     => $conf_subdir,
		'included' => true ),

	'skins_files'         => array (
		'label'    => T_( 'Skins' ),
		'path'     => array( 	$skins_subdir,
							$adminskins_subdir ),
		'included' => true ),

	'plugins_files'       => array (
		'label'    => T_( 'Plugins' ),
		'path'     => $plugins_subdir,
		'included' => true ),

	'media_files'         => array (
		'label'    => T_( 'Media folder' ),
		'path'     => $media_subdir,
		'included' => false ),

	'backup_files'        => array (
		'label'    => NULL,		// Don't display in form. Just exclude from backup.
		'path'     => $backup_subdir,
		'included' => false ),

	'upgrade_files'        => array (
		'label'    => NULL,		// Don't display in form. Just exclude from backup.
		'path'     => $upgrade_subdir,
		'included' => false ) );

/**
 * Backup database tables default settings
 * - 'label' checkbox label
 * - 'note' checkbox note
 * - 'tables' tables list
 * - 'included' true if database tables must be in backup
 * @var array
 */
$backup_tables = array(
	'content_tables'      => array (
		'label'    => T_( 'Content tables' ), /* It means collection of all of the tables. Please, don't remove it. */
		'table'   => '*',
		'included' => true ),

	'logs_stats_tables'   => array (
		'label'    => T_( 'Logs & stats tables' ),
		'table'   => array(
			'T_sessions',
			'T_hitlog',
			'T_basedomains',
			'T_track__goalhit',
			'T_track__keyphrase',
			'T_useragents',
		),
		'included' => false ) );


/**
 * Backup class
 * This class is responsible to backup application files and data.
 *
 */
class Backup
{
	/**
	 * All of the paths and their 'included' values defined in backup configuration file
	 * @var array
	 */
	var $backup_paths;

	/**
	 * All of the tables and their 'included' values defined in backup configuration file
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
	function Backup()
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
		global $backup_paths, $backup_tables, $Messages;

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

		// Check are there something to backup
		if( !$this->has_included( $this->backup_paths ) && !$this->has_included( $this->backup_tables ) )
		{
			$Messages->add( T_( 'There is nothing to backup. Please select at least one option' ), 'error' );
			return false;
		}

		return true;
	}


	/**
	 * Start backup
	 *
	 * @todo Tblue> Halt script if max_execution_time is about to be reached
	 *              (in case we cannot set a high time limit) and allow
	 *              the user to continue the backup process.
	 * fp> yes, this needs to be done but it's not critical.
	 * However we should check that the set_time_limit() has worked and warn the user if not.
	 * "Max PHP execution time is only: xx seconds. Backup may be interrupted. fail before it's compelte.". flush();
	 */
	function start_backup()
	{
		global $basepath, $backup_path, $servertimenow, $Messages;

		// Set time limit as backup can take much time
		set_time_limit( 1800 ); // 30 minutes

		// Enable maintenance mode
		$this->switch_maintenance_mode( true );

		// Create current backup path
		$cbackup_path = $backup_path.date( 'Y-m-d-H-i-s', $servertimenow ).'/';

 		printf( T_('Starting backup to: &laquo;%s&raquo; ...').'<br />', $cbackup_path );
 		flush();

		if( $Messages->count() == 0 && $this->prepare_backupdir( $backup_path, true ) )
		{	// We can backup files and database

			$backup_files_path = $this->pack_backup_files ? $cbackup_path : $cbackup_path.'files/';
			if( $this->has_included( $this->backup_paths ) && $this->prepare_backupdir( $backup_files_path ) )
			{	// We can backup files
				$this->backup_files( $backup_files_path );
			}

			$backup_tables_path = $this->pack_backup_files ? $cbackup_path : $cbackup_path.'db/';
			if( $Messages->count() == 0 && $this->has_included( $this->backup_tables ) && $this->prepare_backupdir( $backup_tables_path ) )
			{	// We can backup database
				$this->backup_database( $backup_tables_path );
			}
		}

		// Disable maintenance mode
		$this->switch_maintenance_mode( false );

		if( $Messages->count() > 0 )
		{
			@rmdir_r( $cbackup_path );
			return false;
		}

		$Messages->add( sprintf( T_('Backup complete. Directory: &laquo;%s&raquo;'), $cbackup_path ), 'success' );
		return true;
	}


	/**
	 * Enable/disable maintenance mode
	 *
	 * @param boolean true if maintenance mode need to be enabled
	 */
	function switch_maintenance_mode( $enable )
	{
		global $conf_path, $Messages;

		if( $this->maintenance_mode )
		{
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
					fwrite( $f, T_( 'System backup is in progress. Please reload this page in a few minutes.' ) );
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
	}


	/**
	 * Backup files
	 * @param string backup directory path
	 */
	function backup_files( $backup_dirpath )
	{
		global $basepath, $backup_paths, $Messages;

		echo '<h4 style="color:green">'.T_( 'Creating folders/files backup...' ).'</h4>';
		flush();

		$excluded_files = array();
		if( $this->pack_backup_files )
		{	// Create ZIPped backup

			// Find included and excluded files
			$included_files = array();

			if( $root_included = $this->backup_paths['application_files'] )
			{
				$included_files = get_filenames( $basepath, true, true, true, false, true, true );
			}

			// Prepare included/excluded paths
			foreach( $this->backup_paths as $name => $included )
			{
				foreach( $this->path_to_array( $backup_paths[$name]['path'] ) as $path )
				{
					if( $root_included && !$included )
					{
						$excluded_files[] = $path;
					}
					elseif( !$root_included && $included )
					{
						$included_files[] = $path;
					}
				}
			}

			$included_files = array_diff( $included_files, $excluded_files );

			// Load ZIP class
			load_class( '_ext/_zip_archives.php', 'zip_file' );


			// Create ZIPped backup
			$zip_filepath = $backup_dirpath.'files.zip';
			$zipfile = & new zip_file( $zip_filepath );
			echo sprintf( T_( 'Archiving files to &laquo;<strong>%s</strong>&raquo;...' ), $zip_filepath ).'<br/>';
			flush();
			$zipfile->set_options( array ( 'basedir'  => $basepath ) );
			$zipfile->add_files( $included_files );
			$zipfile->create_archive();

			// Check if backup is created
			if( !file_exists( $zip_filepath ) )
			{
				$Messages->add( sprintf( T_( 'Unable to create &laquo;%s&raquo;' ), $zip_filepath ), 'error' );
				return false;
			}

			// Display which folders/files backup created
			foreach( $included_files as $file )
			{
				// progressive display of what backup is doing
				echo sprintf( T_( '&laquo;<strong>%s</strong>&raquo; added to ZIP.' ), $file ).'<br/>';
			}
		}
		else
		{	// Copy directories and files to backup directory

			$src_dest_paths = array();
			if( $root_included = $this->backup_paths['application_files'] )
			{
				$src_dest_paths[] = array( $basepath, $backup_dirpath );
			}

			// Prepare included/excluded paths
			foreach( $this->backup_paths as $name => $included )
			{
				foreach( $this->path_to_array( $backup_paths[$name]['path'] ) as $path )
				{
					if( $root_included && !$included )
					{
						$excluded_files[] = no_trailing_slash( $basepath.$path );
					}
					elseif( !$root_included && $included )
					{
						$src_dest_paths[] = array( $basepath.$path, $backup_dirpath.$path );
					}
				}
			}

			// Copy prepared paths
			foreach( $src_dest_paths as $src_dest_path )
			{
				$this->recurse_copy( no_trailing_slash( $src_dest_path[0] ),
									no_trailing_slash( $src_dest_path[1] ), $excluded_files );
			}
		}
	}


	/**
	 * Backup database
	 *
	 * @todo Tblue> Respect time limits!
	 *
	 * @param string backup directory path
	 */
	function backup_database( $backup_dirpath )
	{
		global $DB, $db_config, $backup_tables, $Messages;

		echo '<h4 style="color:green">'.T_( 'Creating database backup...' ).'</h4>';
		flush();

		// Collect all included tables
		$ready_to_backup = array();
		foreach( $this->backup_tables as $name => $included )
		{
			if( $included )
			{
				$tables = $this->aliases_to_tables( $backup_tables[$name]['table'] );
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
				$tables = $this->aliases_to_tables( $backup_tables[$name]['table'] );
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

		// Create and save created SQL backup script
		$backup_sql_filename = 'db.sql';
		$backup_sql_filepath = $backup_dirpath.$backup_sql_filename;

		// Check if backup file exists
		if( file_exists( $backup_sql_filepath ) )
		{	// Stop tables backup, because backup file exists
			$Messages->add( sprintf( T_( 'Unable to write database dump. Database dump already exists: &laquo;%s&raquo;' ), $backup_sql_filepath ), 'error' );
			return false;
		}

		$f = @fopen( $backup_sql_filepath , 'w+' );
		if( $f == false )
		{	// Stop backup, because it can't open backup file for writting
			$Messages->add( sprintf( T_( 'Unable to write database dump. Could not open &laquo;%s&raquo; for writing.' ), $backup_sql_filepath ), 'error' );
			return false;
		}

		// Create and save created SQL backup script
		foreach( $ready_to_backup as $table )
		{
			// progressive display of what backup is doing
			echo sprintf( T_( 'Backing up table &laquo;<strong>%s</strong>&raquo; ...' ), $table ).'<br/>';
			flush();

			$row_table_data = $DB->get_row( 'SHOW CREATE TABLE '.$table, ARRAY_N );
			fwrite( $f, $row_table_data[1].";\n\n" );

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
				fwrite( $f, 'INSERT INTO '.$table.' VALUES '.implode( ',', $values_list ).";\n\n" );
			}

			unset( $values_list );

			// Flush the output to a file
			fflush( $f );
		}

		// Close backup file input stream
		fclose($f);

		if( $this->pack_backup_files )
		{	// Pack created backup SQL script

			// Load ZIP class
			load_class( '_ext/_zip_archives.php', 'zip_file' );

			$zipfile = & new zip_file( 'db.zip' );
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
	function recurse_copy( $src, $dest, &$excluded_files, $root = true )
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
					{	// We can copy the current directory as it is NOT in excluded directories list
						if( $root )
						{ // progressive display of what backup is doing
							echo sprintf( T_( 'Backing up &laquo;<strong>%s</strong>&raquo; ...' ), $srcfile ).'<br/>';
							flush();
						}
						$this->recurse_copy( $srcfile, $dest . '/' . $file, $excluded_files, false );
					}
				}
				else
				{ // Copy file
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


	/**
	 * Convert path to array
	 * @param mixed path
	 * @return array
	 */
	function path_to_array( $path )
	{
		if( is_array( $path ) )
		{
			return $path;
		}
		return array( $path );
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
}


/*
 * $Log$
 * Revision 1.3  2009/10/18 17:20:58  fplanque
 * doc/messages/minor refact
 *
 * Revision 1.2  2009/10/18 15:32:54  efy-maxim
 * 1. new maintenance mode switcher. 2. flush
 *
 * Revision 1.1  2009/10/18 10:24:28  efy-maxim
 * backup
 *
 */

?>
