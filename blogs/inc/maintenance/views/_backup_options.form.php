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
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _backup_options.form.php 6155 2014-03-12 05:49:36Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load Backup class (PHP4):
load_class( 'maintenance/model/_backup.class.php', 'Backup' );

/**
 * @var back up configuration
 */
global $backup_paths, $backup_tables, $backup_path;

/**
 * @var instance of Backup class
 */
$current_Backup = new Backup();

$Form->begin_fieldset( T_( 'Advanced backup options' ).' '.get_icon( 'collapse', 'imgtag', array( 'id' => 'clickimg_backup_options' ) ),
	array( 'legend_params' => array( 'onclick' => 'toggle_clickopen(\'backup_options\')' ) ) );

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

		$Form->checkbox( 'bk_'.$name, 1/*$current_Backup->backup_paths[$name]*/, $settings['label'], $note );
	}
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

	$Form->checkbox( 'bk_'.$name, 1/*$current_Backup->backup_tables[$name]*/, $settings['label'], $note );
}

if( function_exists( 'gzopen' ) )
{
	$Form->checkbox( 'bk_pack_backup_files', $current_Backup->pack_backup_files, 'ZIP', T_('Compress backup files into ZIP archive.') );
}

echo '</div>';

$Form->end_fieldset();
?>
<script type="text/javascript">toggle_clickopen( "backup_options" );</script>