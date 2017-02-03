<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package maintenance
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load Backup class (PHP4):
load_class( 'maintenance/model/_backup.class.php', 'Backup' );

/**
 * @var back up configuration
 */
global $backup_paths, $backup_tables, $backup_path, $backup_exclude_folders;

/**
 * @var instance of Backup class
 */
$current_Backup = new Backup();

$Form->begin_fieldset( T_( 'Advanced backup options' ), array( 'id' => 'upgrade_backup_options', 'fold' => true ) );

echo '<div id="clickdiv_backup_options">';

// Display checkboxes
foreach( $backup_paths as $name => $settings )
{
	if( !is_null( $settings['label'] ) )
	{
		$note = '';
		if( array_key_exists( 'note', $settings ) )
		{
			$note = $settings['note'];
		}
		else
		{
			$note = get_affected_paths( $settings['path'] );
		}

		$Form->checkbox( 'bk_'.$name, $current_Backup->backup_paths[$name], $settings['label'], $note );
	}
}

// Display checkboxes to exclude the paths:
$backup_exclude_checkboxes = array();
foreach( $backup_exclude_folders as $name => $settings )
{
	if( count( $settings['path'] ) > 2 )
	{
		$exclude_folder_name_last = $settings['path'][ count( $settings['path'] ) - 1 ];
		array_pop( $settings['path'] );
		$exclude_folder_names = '<code>'.implode( '</code>, <code>', $settings['path'] ).'</code>';
		$exclude_folder_names .= ' '.T_('or').' <code>'.$exclude_folder_name_last.'</code>';
	}
	else
	{
		$exclude_folder_names = '<code>'.implode( '</code> '.T_('or').' <code>', $settings['path'] ).'</code>';
	}
	$backup_exclude_checkboxes[] = array( 'exclude_bk_'.$name, $current_Backup->exclude_folders[ $name ], sprintf( T_('Exclude all %s folders'), $exclude_folder_names ), $settings['excluded'] );
}
if( count( $backup_exclude_checkboxes ) )
{
	$Form->checklist( $backup_exclude_checkboxes, 'exclude_bk', T_('Exclude folders') );
}

$Form->checkbox( 'ignore_bk_config', $current_Backup->ignore_config, 'backup_ignore.conf', sprintf( T_('Ignore files and folders listed in %s'), '<code>conf/backup_ignore.conf</code>' ) );

// Display checkboxes
foreach( $backup_tables as $name => $settings )
{
	$note = '';
	if( array_key_exists( 'note', $settings ) )
	{
		$note = $settings['note'];
	}
	else
	{
		$note = get_affected_tables( $settings['table'] );
	}

	$Form->checkbox( 'bk_'.$name, $current_Backup->backup_tables[$name], $settings['label'], $note );
}

if( function_exists( 'gzopen' ) )
{
	$Form->checkbox( 'bk_pack_backup_files', $current_Backup->pack_backup_files, 'ZIP', T_('Compress backup files into ZIP archive.') );
}

echo '</div>';

$Form->end_fieldset();

// Fieldset folding
echo_fieldset_folding_js();
?>