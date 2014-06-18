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
 * @version $Id: _backup.form.php 6144 2014-03-10 14:37:52Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var back up configuration
 */
global $backup_paths, $backup_tables, $backup_path;

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
$Form->begin_fieldset( T_( 'Folders & files' ), array( 'class'=>'fieldset clear' ) );

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
$Form->begin_fieldset( T_( 'General Options' ), array( 'class'=>'fieldset clear' ) );

$Form->checkbox( 'bk_maintenance_mode', true, T_( 'Maintenance mode' ), T_( 'Put b2evolution into Maintenance Mode while backing up - Recommended' ) );

if( function_exists('gzopen') )
{
	$Form->checkbox( 'bk_pack_backup_files', $current_Backup->pack_backup_files, 'ZIP', T_('Compress backup files into ZIP archive.') );
}
$Form->add_crumb( 'backup' );
$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'actionArray[backup]', T_('Backup'), 'SaveButton' ) ) );

?>