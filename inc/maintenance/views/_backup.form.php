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

/**
 * @var back up configuration
 */
global $backup_paths, $backup_tables, $backup_path, $backup_exclude_folders;

/**
 * @var action
 */
global $action;

/**
 * @var instance of Backup class
 */
global $current_Backup;


$Form = new Form( NULL, 'backup_settings', 'post' );


$Form->begin_form( 'fform', T_('Backup application files and data') );

echo '<p>Your backups will be saved into the directory: <b>'.$backup_path.'</b> (on your web server).</p>';

$Form->hiddens_by_key( get_memorized( 'action' ) );

// Backup settings for folders and files
$Form->begin_fieldset( T_( 'Folders & files' ).get_manual_link( 'backup-tab' ), array( 'class'=>'fieldset clear' ) );

// Display checkboxes to include the paths:
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
		$exclude_folder_note = T_('Exclude all %s folders');
	}
	elseif( count( $settings['path'] ) == 2 )
	{
		$exclude_folder_names = '<code>'.implode( '</code> '.T_('or').' <code>', $settings['path'] ).'</code>';
		$exclude_folder_note = T_('Exclude all %s folders');
	}
	else
	{
		$exclude_folder_names = '<code>'.$settings['path'][0].'</code>';
		$exclude_folder_note = T_('Exclude the %s folder');
	}
	$backup_exclude_checkboxes[] = array( 'exclude_bk_'.$name, $current_Backup->exclude_folders[ $name ], sprintf( $exclude_folder_note, $exclude_folder_names ), $settings['excluded'] );
}
if( count( $backup_exclude_checkboxes ) )
{
	$Form->checklist( $backup_exclude_checkboxes, 'exclude_bk', T_('Exclude folders') );
}

$Form->checkbox( 'ignore_bk_config', $current_Backup->ignore_config, 'backup_ignore.conf', sprintf( T_('Ignore files and folders listed in %s'), '<code>conf/backup_ignore.conf</code>' ) );

$Form->end_fieldset();

// Backup settings for database tables
$Form->begin_fieldset( T_( 'Database tables' ), array( 'class'=>'fieldset clear' ) );

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

$Form->end_fieldset();

// Enable/Disable maintenance mode
$Form->begin_fieldset( T_('General Options').get_manual_link( 'set-system-lock-during-backup' ), array( 'class'=>'fieldset clear' ) );

$Form->radio( 'bk_lock_type', 'maintenance_mode',
		array(
			array( 'maintenance_mode', T_('Maintenance mode').' ('.T_('Recommended').')', T_('check this to completely lock b2evolution' ) ),
			array( 'maintenance_lock', T_('Maintenance lock'), T_('check this to prevent login (except for admins), sending comments/messages and receiving DB updates (other than logging)') ),
			array( 'open', T_( 'Leave the site open for modifications during backup. (Not recommended).') )
		), T_( 'Lock b2evolution while backing up' ), true );

if( function_exists('gzopen') )
{
	$Form->checkbox( 'bk_pack_backup_files', $current_Backup->pack_backup_files, 'ZIP', T_('Compress backup files into ZIP archive.') );
}
$Form->add_crumb( 'backup' );
$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'actionArray[backup]', T_('Backup'), 'SaveButton' ) ) );

?>