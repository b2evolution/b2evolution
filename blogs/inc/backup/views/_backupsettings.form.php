<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of BackupSettings class
 */
global $backup_Settings;

/**
 * @var back up configuration
 */
global $backup_paths, $backup_tables;

/**
 * @var action
 */
global $action;

$creating = is_create_action( $action );

$Form = & new Form( NULL, 'backup_settings', 'post' );

$Form->begin_form( 'fform', T_('Backup application files and data') );


$Form->hiddens_by_key( get_memorized( 'action' ) );

// Backup settings for folders and files
$Form->begin_fieldset( T_( 'Folders & files' ), array( 'class'=>'fieldset clear' ) );

foreach( $backup_paths as $name => $settings )
{

	if( array_key_exists( 'label', $settings ) )
	{
		$note = '';
		if( array_key_exists( 'note', $settings ) )
		{
			$note = $settings['note'];
		}

		$Form->checkbox( 'bk_'.$name, $backup_Settings->backup_paths[$name], $settings['label'], $note );
	}
}

$Form->end_fieldset();

// Backup settings for database tables
$Form->begin_fieldset( T_( 'Database tables' ), array( 'class'=>'fieldset clear' ) );

foreach( $backup_tables as $name => $settings )
{
	$note = '';
	if( array_key_exists( 'note', $settings ) )
	{
		$note = $settings['note'];
	}

	$Form->checkbox( 'bk_'.$name, $backup_Settings->backup_tables[$name], $settings['label'], $note );
}

$Form->end_fieldset();

// Enable/Disable maintenance mode
$Form->begin_fieldset( T_( 'Maintenance' ), array( 'class'=>'fieldset clear' ) );

$Form->checkbox( 'bk_maintenance_mode', $backup_Settings->maintenance_mode, T_( 'Maintenance mode' ), T_( 'Put b2evolution into Maintenance Mode while backing up - Recommended' ) );
$Form->checkbox( 'bk_pack_backup_files', $backup_Settings->pack_backup_files, T_( 'ZIP' ), T_('Compress backup into ZIP files') );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'actionArray[backup]', T_('Backup'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.3  2009/10/18 08:16:55  efy-maxim
 * log
 *
 */

?>