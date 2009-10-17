<?php

/**
 * @var strings base application paths
 */
global $basepath, $conf_subdir, $skins_subdir, $adminskins_subdir, $plugins_subdir, $media_subdir;

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
$blogs_subdir = 'blogs/';
$backup_subdir = $blogs_subdir.'_backup/';

/**
 * Backup folder/files default settings
 * - 'label' checkbox label
 * - 'note' checkbox note
 * - 'path' path to folder or file
 * - 'included' true if folder or file must be in backup
 * @var array
 */
$backup_paths = array( 	'application_files'   => array ( 'label'    => T_( 'Application files' ), /* It is files root. Please, don't remove it. */
														 'path'     => '*',
														 'included' => true ),

						'configuration_files' => array ( 'label'    => T_( 'Configuration files' ),
														 'path'     => $conf_subdir,
														 'included' => true ),

						'skins_files'         => array ( 'label'    => T_( 'Skins' ),
														 'path'     => array( 	$skins_subdir,
																				$adminskins_subdir ),
														 'included' => true ),

						'plugins_files'       => array ( 'label'    => T_( 'Plugins' ),
														 'path'     => $plugins_subdir,
														 'included' => true ),

						'media_files'         => array ( 'label'    => T_( 'Media folder' ),
														 'path'     => $media_subdir,
														 'included' => false ),

	// fp> why is the following listed here?
						'backup_files'        => array ( 'path'     => $blogs_subdir,
														 'included' => false ) );

/**
 * Backup database tables default settings
 * - 'label' checkbox label
 * - 'note' checkbox note
 * - 'tables' tables list
 * - 'included' true if database tables must be in backup
 * @var array
 */
$backup_tables = array(	'content_tables'      => array ( 'label'    => T_( 'Content tables' ), /* It means collection of all of the tables. Please, don't remove it. */
														 'table'   => '*',
														 'included' => true ),

						'logs_stats_tables'   => array ( 'label'    => T_( 'Logs & stats tables' ),
														 'table'   => array(
// TODO: please use T_hitlog syntax instead of $tableprefix.'hitlog'
																$tableprefix.'sessions',
																$tableprefix.'hitlog',
																$tableprefix.'basedomains',
																$tableprefix.'track__goalhit',
																$tableprefix.'track__keyphrase',
																$tableprefix.'useragents',
															),
														 'included' => false ) )

/*
 * $Log$
 * Revision 1.5  2009/10/17 23:58:48  fplanque
 * doc
 *
 */
?>
