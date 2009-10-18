<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var back up configuration
 */
global $backup_paths, $backup_tables;

/**
 * @var action
 */
global $action;

/**
 * @var instance of Backup class
 */
global $current_Backup;


$Form = & new Form( NULL, 'backup_settings', 'post' );


$Form->begin_form( 'fform', T_('Backup application files and data') );

$Form->hiddens_by_key( get_memorized( 'action' ) );

// Backup settings for folders and files
$Form->begin_fieldset( T_( 'Folders & files' ), array( 'class'=>'fieldset clear' ) );

foreach( $backup_paths as $name => $settings )
{
	if( !is_null( $settings['label'] ) )
	{
		$note = '';
		if( array_key_exists( 'note', $settings ) )
		{
			$note = $settings['note'];
		}

		$Form->checkbox( 'bk_'.$name, $current_Backup->backup_paths[$name], $settings['label'], $note );
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

	$Form->checkbox( 'bk_'.$name, $current_Backup->backup_tables[$name], $settings['label'], $note );
}

$Form->end_fieldset();

// Enable/Disable maintenance mode
$Form->begin_fieldset( T_( 'Maintenance' ), array( 'class'=>'fieldset clear' ) );

$Form->checkbox( 'bk_maintenance_mode', $current_Backup->maintenance_mode, T_( 'Maintenance mode' ), T_( 'Put b2evolution into Maintenance Mode while backing up - Recommended' ) );
$Form->checkbox( 'bk_pack_backup_files', $current_Backup->pack_backup_files, T_( 'ZIP' ), T_('Compress backup into ZIP files') );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'actionArray[backup]', T_('Backup'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.7  2009/10/18 17:26:26  fplanque
 * doc
 *
 * Revision 1.6  2009/10/18 17:20:58  fplanque
 * doc/messages/minor refact
 *
 * Revision 1.5  2009/10/18 15:32:54  efy-maxim
 * 1. new maintenance mode switcher. 2. flush
 *
 * Revision 1.4  2009/10/18 10:24:28  efy-maxim
 * backup
 *
 * Revision 1.3  2009/10/18 08:16:55  efy-maxim
 * log
 *
 */

?>
