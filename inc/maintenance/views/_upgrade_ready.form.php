<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package maintenance
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $block_item_Widget, $action, $new_version_status, $Settings, $upgrade_name;
global $backup_paths, $backup_tables, $backup_path, $backup_exclude_folders;

$new_version_status = check_version( $upgrade_name );
if( empty( $new_version_status ) )
{ // New version
	echo '<p><b>'.T_( 'The new files are ready to be installed.' ).'</b></p>';
}
else
{ // Old/Same version
	echo '<div class="alert '.( $new_version_status['error'] == 'old' ? 'alert-danger' : 'alert-warning' ).'">'.$new_version_status['message'].'</div>';
}

echo '<p>'
	.sprintf( T_( 'If you continue, the following sequence will be carried out automatically (trying to minimize "<a %s>maintenance time</a>" for the site):' ),
		'href="http://b2evolution.net/man/installation-upgrade/configuration-files/maintenance-html" target="_blank"' )
	.'<ul><li>'.sprintf( T_( 'The site will switch to <a %s>maintenance mode</a>' ),
				'href="http://b2evolution.net/man/installation-upgrade/configuration-files/maintenance-html" target="_blank"' ).'</li>'
		.'<li>'.T_( 'A backup will be performed' ).'</li>'
		.'<li>'.T_( 'The upgrade will be applied' ).'</li>'
		.'<li>'.T_( 'The install script of the new version will be called' ).'</li>'
		.'<li>'.sprintf( T_( 'The cleanup rules from %s will be applied' ), '<code>'.get_upgrade_config_file_name().'</code>' ).'</li>'
		.'<li>'.T_( 'The site will switch to normal mode again at the end of the install script.' ).'</li>'
	.'</ul></p>';

if( isset( $block_item_Widget ) )
{
	$block_item_Widget->disp_template_replaced( 'block_end' );
}

$Form = new Form( NULL, 'upgrade_form', 'post' );

$Form->add_crumb( 'upgrade_is_launched' ); // In case we want to continue
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_form( 'fform' );

// Display the backup options to select what should be backuped:
$Form->begin_fieldset( T_( 'Advanced backup options' ).get_manual_link( 'upgrade-advanced-backup-options' ), array( 'id' => 'upgrade_backup_options', 'fold' => true ) );

// Create Backup object:
load_class( 'maintenance/model/_backup.class.php', 'Backup' );
$current_Backup = new Backup();

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

$db_structure_checkboxes = array(
	array( 'db_structure', 1, sprintf( T_('Add %s statements for ALL tables, in order to allow quick restore.'), '<code>CREATE TABLE</code>' ), $current_Backup->backup_db_structure ),
	array( 'drop_table_first', 1, sprintf( T_('Add %s before every %s.'), '<code>DROP TABLE IF EXISTS</code>', '<code>CREATE TABLE</code>' ), $current_Backup->drop_table_first )
);
$Form->checklist( $db_structure_checkboxes, 'db_structure_options', T_('DB Structure') );

if( function_exists( 'gzopen' ) )
{
	$Form->checkbox( 'bk_pack_backup_files', $current_Backup->pack_backup_files, 'ZIP', T_('Compress backup files into ZIP archive.') );
}

$Form->end_fieldset();

// Display file options:
$Form->begin_fieldset( T_('File options').get_manual_link( 'upgrade-file-options' ), array( 'id' => 'upgrade_file_options', 'fold' => true ) );
	$Form->text_input( 'fm_default_chmod_dir', $Settings->get('fm_default_chmod_dir'), 4, T_('Permissions for new folders'), T_('Default CHMOD (UNIX permissions) for new directories created by b2evolution.') );
	$Form->text_input( 'fm_default_chmod_file', $Settings->get('fm_default_chmod_file'), 4, T_('Permissions for new files'), T_('Default CHMOD (UNIX permissions) for new files created by b2evolution.') );
$Form->end_fieldset();

// Display the form buttons
$Form->begin_fieldset( T_( 'Actions' ) );

$action_backup_title = ( empty( $new_version_status ) ) ? T_( 'Backup & Upgrade' ) : T_( 'Force Backup & Upgrade' );

$Form->end_form( array( array( 'submit', 'actionArray[backup_and_overwrite]', $action_backup_title, 'SaveButton'.( empty( $new_version_status ) ? '' : ' btn-warning' ) ) ) );

// Fieldset folding
echo_fieldset_folding_js();
?>