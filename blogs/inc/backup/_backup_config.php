<?php

/**
 * @var strings base application paths
 */
global $basepath, $conf_subdir, $skins_subdir, $plugins_subdir, $media_subdir;

/**
 * @var table prefix
 */
global $tableprefix;

/**
 * @var backup sub directory
 */
global $backup_subdir;

/**
 * @var array backup paths
 */
global $backup_paths;

/**
 * @var array backup tables
 */
global $backup_tables;

/**
 * @var string backup sub directory
 *
 */
$backup_subdir = 'blogs/_backup/';

/**
 * Backup folder/files default settings
 * - 'label' checkbox label
 * - 'path' path to folder or file
 * - 'included' true if folder or file must be in backup
 * @var array
 */
$backup_paths = array( 	'application_files'   => array ( 'label'    => T_( 'Application files' ),
														 'path'     => '.',
														 'included' => true ),

						'configuration_files' => array ( 'label'    => T_( 'Configuration files' ),
														 'path'     => $conf_subdir,
														 'included' => true ),

						'skins_files'         => array ( 'label'    => T_( 'Skins' ),
														 'path'     => $skins_subdir,
														 'included' => true ),

						'plugins_files'       => array ( 'label'    => T_( 'Plugins' ),
														 'path'     => $plugins_subdir,
														 'included' => true ),

						'media_files'         => array ( 'label'    => T_( 'Media folder' ),
														 'path'     => $media_subdir,
														 'included' => true ),

						'backup_files'        => array ( 'path'     => $backup_subdir,
														 'included' => false ) );

/**
 * Backup database tables default settings
 * - 'label' checkbox label
 * - 'tables' tables list
 * - 'included' true if database tables must be in backup
 * @var array
 */
$backup_tables = array(	'content_tables'      => array ( 'label'    => T_( 'Content tables' ),
														 'tables'   => '*',
														 'included' => true ),

						'logs_stats_tables'   => array ( 'label'    => T_( 'Logs & stats tables' ),
														 'tables'   => array( 	$tableprefix.'cron__log',
																				$tableprefix.'hitlog' ),
														 'included' => true ) )

?>