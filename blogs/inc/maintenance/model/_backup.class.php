<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package maintenance
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _backup.class.php 8181 2015-02-06 07:52:35Z yura $
 */
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
	'application_files'   => array(
		'label'    => T_('Application files'), /* It is files root. Please, don't remove it. */
		'path'     => '*',
		'included' => true ),

	'configuration_files' => array(
		'label'    => T_('Configuration files'),
		'path'     => $conf_subdir,
		'included' => true ),

	'skins_files'         => array(
		'label'    => T_('Skins'),
		'path'     => array( $skins_subdir,
							$adminskins_subdir ),
		'included' => true ),

	'plugins_files'       => array(
		'label'    => T_('Plugins'),
		'path'     => $plugins_subdir,
		'included' => true ),

	'media_files'         => array(
		'label'    => T_('Media folder'),
		'path'     => $media_subdir,
		'included' => false ),

	'backup_files'        => array(
		'label'    => NULL,		// Don't display in form. Just exclude from backup.
		'path'     => $backup_subdir,
		'included' => false ),

	'upgrade_files'        => array(
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
	'content_tables'      => array(
		'label'    => T_('Content tables'), /* It means collection of all of the tables. Please, don't remove it. */
		'table'   => '*',
		'included' => true ),

	'logs_stats_tables'   => array(
		'label'    => T_('Logs & stats tables'),
		'table'   => array(
			'T_sessions',
			'T_hitlog',
			'T_basedomains',
			'T_track__goalhit',
			'T_track__keyphrase',
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

		$this->pack_backup_files = true;
	}


	/**
	 * Load settings from request
	 *
	 * @param boolean TRUE to memorize all params
	 */
	function load_from_Request( $memorize_params = false )
	{
		global $backup_paths, $backup_tables, $Messages;

		// Load folders/files settings from request
		foreach( $backup_paths as $name => $settings )
		{
			if( array_key_exists( 'label', $settings ) && !is_null( $settings['label'] ) )
			{	// We can set param
				$this->backup_paths[$name] = param( 'bk_'.$name, 'boolean', 0, $memorize_params );
			}
		}

		// Load tables settings from request
		foreach( $backup_tables as $name => $settings )
		{
			$this->backup_tables[$name] = param( 'bk_'.$name, 'boolean', 0, $memorize_params );
		}

		$this->pack_backup_files = param( 'bk_pack_backup_files', 'boolean', 0, $memorize_params );

		// Check are there something to backup
		if( !$this->has_included( $this->backup_paths ) && !$this->has_included( $this->backup_tables ) )
		{
			$Messages->add( T_('You have not selected anything to backup.'), 'error' );
			return false;
		}

		return true;
	}


	/**
	 * Start backup
	 */
	function start_backup()
	{
		global $basepath, $backup_path, $servertimenow;

		// Create current backup path
		$cbackup_path = $backup_path.date( 'Y-m-d-H-i-s', $servertimenow ).'/';

		echo '<p>'.sprintf( T_('Starting backup to: &laquo;%s&raquo; ...'), $cbackup_path ).'</p>';
		evo_flush();

		// Prepare backup directory
		$success = prepare_maintenance_dir( $backup_path, true );

		// Backup directories and files
		if( $success && $this->has_included( $this->backup_paths ) )
		{
			$backup_files_path = $this->pack_backup_files ? $cbackup_path : $cbackup_path.'files/';

			// Prepare files backup directory
			if( $success = prepare_maintenance_dir( $backup_files_path, false ) )
			{	// We can backup files
				$success = $this->backup_files( $backup_files_path );
			}
		}

		// Backup database
		if( $success && $this->has_included( $this->backup_tables ) )
		{
			$backup_tables_path = $this->pack_backup_files ? $cbackup_path : $cbackup_path.'db/';

			// Prepare database backup directory
			if( $success = prepare_maintenance_dir( $backup_tables_path, false ) )
			{	// We can backup database
				$success = $this->backup_database( $backup_tables_path );
			}
		}

		if( $success )
		{
			echo '<p>'.sprintf( T_('Backup complete. Directory: &laquo;%s&raquo;'), $cbackup_path ).'</p>';
			evo_flush();

			return true;
		}

		@rmdir_r( $cbackup_path );
		return false;
	}


	/**
	 * Backup files
	 * @param string backup directory path
	 */
	function backup_files( $backup_dirpath )
	{
		global $basepath, $backup_paths, $inc_path;

		echo '<h4>'.T_('Creating folders/files backup...').'</h4>';
		evo_flush();

		// Find included and excluded files

		$included_files = array();

		if( $root_included = $this->backup_paths['application_files'] )
		{
			$filename_params = array(
					'recurse'        => false,
					'basename'       => true,
					'trailing_slash' => true,
					//'inc_evocache' => true, // Uncomment to backup ?evocache directories
					'inc_temp'       => false,
				);
			$included_files = get_filenames( $basepath, $filename_params );
		}

		// Prepare included/excluded paths
		$excluded_files = array();

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

		// Remove excluded list from included list
		$included_files = array_diff( $included_files, $excluded_files );

		if( $this->pack_backup_files )
		{ // Create ZIPped backup
			$zip_filepath = $backup_dirpath.'files.zip';

			// Pack using 'zlib' extension and PclZip wrapper

			if( ! defined( 'PCLZIP_TEMPORARY_DIR' ) )
			{ // Set path for temp files of PclZip
				define( 'PCLZIP_TEMPORARY_DIR', $backup_dirpath );
			}
			// Load PclZip class (PHP4):
			load_class( '_ext/pclzip/pclzip.lib.php', 'PclZip' );

			$PclZip = new PclZip( $zip_filepath );

			echo sprintf( T_('Archiving files to &laquo;<strong>%s</strong>&raquo;...'), $zip_filepath ).'<br/>';
			evo_flush();

			foreach( $included_files as $included_file )
			{
				echo sprintf( T_('Backing up &laquo;<strong>%s</strong>&raquo; ...'), $basepath.$included_file );
				evo_flush();

				$file_list = $PclZip->add( no_trailing_slash( $basepath.$included_file ), PCLZIP_OPT_REMOVE_PATH, no_trailing_slash( $basepath ) );
				if( $file_list == 0 )
				{
					echo '<p style="color:red">'.sprintf( T_('Unable to create &laquo;%s&raquo;'), $zip_filepath ).'</p>';
					evo_flush();

					return false;
				}
				else
				{
					echo ' OK.<br />';
					evo_flush();
				}
			}
		}
		else
		{	// Copy directories and files to backup directory
			foreach( $included_files as $included_file )
			{
				$this->recurse_copy( no_trailing_slash( $basepath.$included_file ),
										no_trailing_slash( $backup_dirpath.$included_file ) );
			}
		}

		return true;
	}


	/**
	 * Backup database
	 *
	 * @param string backup directory path
	 */
	function backup_database( $backup_dirpath )
	{
		global $DB, $db_config, $backup_tables, $inc_path;

		echo '<h4>'.T_('Creating database backup...').'</h4>';
		evo_flush();

		// Collect all included tables
		$ready_to_backup = array();
		foreach( $this->backup_tables as $name => $included )
		{
			if( $included )
			{
				$tables = aliases_to_tables( $backup_tables[$name]['table'] );
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
				$tables = aliases_to_tables( $backup_tables[$name]['table'] );
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
			echo '<p style="color:red">'.sprintf( T_('Unable to write database dump. Database dump already exists: &laquo;%s&raquo;'), $backup_sql_filepath ).'</p>';
			evo_flush();

			return false;
		}

		$f = @fopen( $backup_sql_filepath , 'w+' );
		if( $f == false )
		{	// Stop backup, because it can't open backup file for writing
			echo '<p style="color:red">'.sprintf( T_('Unable to write database dump. Could not open &laquo;%s&raquo; for writing.'), $backup_sql_filepath ).'</p>';
			evo_flush();

			return false;
		}

		echo sprintf( T_('Dumping tables to &laquo;<strong>%s</strong>&raquo;...'), $backup_sql_filepath ).'<br/>';
		evo_flush();

		// Create and save created SQL backup script
		foreach( $ready_to_backup as $table )
		{
			// progressive display of what backup is doing
			echo sprintf( T_('Backing up table &laquo;<strong>%s</strong>&raquo; ...'), $table );
			evo_flush();

			$row_table_data = $DB->get_row( 'SHOW CREATE TABLE '.$table, ARRAY_N );
			fwrite( $f, $row_table_data[1].";\n\n" );

			$page = 0;
			$page_size = 500;
			$is_insert_sql_started = false;
			$is_first_insert_sql_value = true;
			while( ! empty( $rows ) || $page == 0 )
			{ // Get the records by page(500) in order to save memory and avoid fatal error
				$rows = $DB->get_results( 'SELECT * FROM '.$table.' LIMIT '.( $page * $page_size ).', '.$page_size, ARRAY_N );

				if( $page == 0 && ! $is_insert_sql_started && ! empty( $rows ) )
				{ // Start SQL INSERT clause
					fwrite( $f, 'INSERT INTO '.$table.' VALUES ' );
					$is_insert_sql_started = true;
				}

				foreach( $rows as $row )
				{
					$values = '(';
					$num_fields = count( $row );
					for( $index = 0; $index < $num_fields; $index++ )
					{
						if( isset( $row[$index] ) )
						{
							$row[$index] = str_replace("\n","\\n", addslashes( $row[$index] ) );
							$values .= '\''.$row[$index].'\'' ;
						}
						else
						{ // The $row[$index] value is not set or is NULL
							$values .= 'NULL';
						}

						if( $index<( $num_fields-1 ) )
						{
							$values .= ',';
						}
					}
					$values .= ')';
					if( $is_first_insert_sql_value )
					{ // Don't write a comma before first row values
						$is_first_insert_sql_value = false;
					}
					else
					{ // Write a comma between row values
						$values = ','.$values;
					}

					fwrite( $f, $values );
				}
				unset( $rows );
				$page++;
			}

			if( $is_insert_sql_started )
			{ // End SQL INSERT clause
				fwrite( $f, ";\n\n" );
			}

			// Flush the output to a file
			if( fflush( $f ) )
			{
				echo ' OK.';
			}
			echo '<br />';
			evo_flush();
		}

		// Close backup file input stream
		fclose( $f );

		if( $this->pack_backup_files )
		{ // Pack created backup SQL script

			// Pack using 'zlib' extension and PclZip wrapper

			if( ! defined( 'PCLZIP_TEMPORARY_DIR' ) )
			{ // Set path for temp files of PclZip
				define( 'PCLZIP_TEMPORARY_DIR', $backup_dirpath );
			}
			// Load PclZip class (PHP4):
			load_class( '_ext/pclzip/pclzip.lib.php', 'PclZip' );

			$zip_filepath = $backup_dirpath.'db.zip';
			$PclZip = new PclZip( $zip_filepath );

			$file_list = $PclZip->add( $backup_dirpath.$backup_sql_filename, PCLZIP_OPT_REMOVE_PATH, no_trailing_slash( $backup_dirpath ) );
			if( $file_list == 0 )
			{
				echo '<p style="color:red">'.sprintf( T_('Unable to create &laquo;%s&raquo;'), $zip_filepath ).'</p>';
				evo_flush();

				return false;
			}

			unlink( $backup_sql_filepath );
		}

		return true;
	}


	/**
	 * Copy directory recursively
	 *
	 * @param string source directory
	 * @param string destination directory
	 * @param array excluded directories
	 */
	function recurse_copy( $src, $dest, $root = true )
	{
		if( is_dir( $src ) )
		{
			if( ! ( $dir = opendir( $src ) ) )
			{
				return false;
			}
			if( ! evo_mkdir( $dest ) )
			{
				return false;
			}
			while( false !== ( $file = readdir( $dir ) ) )
			{
				if( $file == '.' || $file == '..' )
				{ // Skip these reserved names
					continue;
				}

				if( $file == 'upload-tmp' )
				{ // Skip temp folder
					continue;
				}

				$srcfile = $src.'/'.$file;
				if( is_dir( $srcfile ) )
				{
					if( $root )
					{ // progressive display of what backup is doing
						echo sprintf( T_('Backing up &laquo;<strong>%s</strong>&raquo; ...'), $srcfile ).'<br/>';
						evo_flush();
					}
					$this->recurse_copy( $srcfile, $dest . '/' . $file, false );
				}
				else
				{ // Copy file
					copy( $srcfile, $dest.'/'. $file );
				}
			}
			closedir( $dir );
		}
		else
		{
			copy( $src, $dest );
		}
	}


	/**
	 * Include all of the folders and tables to backup.
	 */
	function include_all()
	{
		global $backup_paths, $backup_tables;

		foreach( $backup_paths as $name => $settings )
		{
			if( array_key_exists( 'label', $settings ) && !is_null( $settings['label'] ) )
			{
				$this->backup_paths[$name] = true;
			}
		}

		foreach( $backup_tables as $name => $settings )
		{
			$this->backup_tables[$name] = true;
		}
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
}

?>