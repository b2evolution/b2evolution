<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2013 by Francois PLANQUE - {@link http://fplanque.net/}
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

/**
 * Get affected paths
 * @param mixed path
 * @return string
 */
function get_affected_paths( $path )
{
	global $basepath;

	$affected_paths = T_( 'Affected paths:' ).' ';
	if( is_array( $path ) )
	{
		$paths = array();
		foreach( $path as $p )
			$paths[] = no_trailing_slash( $p );

		$affected_paths .= implode( ', ', $paths );
	}
	elseif( $path == '*' )
	{
		$filename_params = array(
				'inc_files'	=> false,
				'recurse'	=> false,
				'basename'	=> true,
			);
		$affected_paths .= implode( ', ', get_filenames( $basepath, $filename_params ) );
	}
	else
	{
		$affected_paths .= no_trailing_slash( $path );
	}
	return $affected_paths;
}

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

/**
 * Get affected tables
 * @param mixed table
 * @return string
 */
function get_affected_tables( $table )
{
	global $DB;

	$affected_tables = T_( 'Affected tables:' ).' ';
	if( is_array( $table ) )
	{
		$affected_tables .= implode( ', ', aliases_to_tables( $table ) );
	}
	elseif( $table == '*' )
	{
		$tables = array();
		foreach( $DB->get_results( 'SHOW TABLES', ARRAY_N ) as $row )
				$tables[] = $row[0];

		$affected_tables .= implode( ', ', $tables );
	}
	else
	{
		$affected_tables .= aliases_to_tables( $table );
	}
	return $affected_tables;
}

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
$Form->begin_fieldset( T_( 'Maintenance' ), array( 'class'=>'fieldset clear' ) );

$Form->checkbox( 'bk_maintenance_mode', true, T_( 'Maintenance mode' ), T_( 'Put b2evolution into Maintenance Mode while backing up - Recommended' ) );

if( function_exists('gzopen') )
{
	$Form->checkbox( 'bk_pack_backup_files', $current_Backup->pack_backup_files, 'ZIP', T_('Compress backup files into ZIP archive.') );
}
$Form->add_crumb( 'backup' );
$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'actionArray[backup]', T_('Backup'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

?>