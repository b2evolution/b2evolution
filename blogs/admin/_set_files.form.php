<?php
/**
 * This file implements the UI view for the file settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


$FilesForm = new Form( 'fileset.php', 'filesform' );

$FilesForm->begin_form( 'fform' );
form_hidden( 'action', 'update' );
form_hidden( 'tab', $tab );

$FilesForm->begin_fieldset( T_('Filemanager options'), 'fm_enabled' );
$FilesForm->checkbox( 'fm_enabled',
								$Settings->get('fm_enabled'),
								T_('Enable Filemanager'),
								T_('Check to enable the Filemanager.' ) );
$FilesForm->checkbox( 'fm_enable_roots_blog',
								$Settings->get('fm_enable_roots_blog'),
								T_('Enable blog directories'),
								T_('Check to enable root directories for blogs.' ),
								'',
								Form::disabled( !$Settings->get('fm_enabled') ) );
$FilesForm->checkbox( 'fm_enable_roots_group',
								$Settings->get('fm_enable_roots_group'),
								T_('Enable group directories'),
								T_('Check to enable root directories for groups.' ),
								'',
								Form::disabled( !$Settings->get('fm_enabled') ) );
$FilesForm->checkbox( 'fm_enable_roots_user',
								$Settings->get('fm_enable_roots_user'),
								T_('Enable user directories'),
								T_('Check to enable root directories for users.' ),
								'',
								Form::disabled( !$Settings->get('fm_enabled') ) );
$FilesForm->checkbox( 'fm_enable_create_dir',
								$Settings->get('fm_enable_create_dir'),
								T_('Enable creation of dirs'),
								T_('Check to enable creation of directories.' ),
								'',
								Form::disabled( !$Settings->get('fm_enabled') ) );
$FilesForm->checkbox( 'fm_enable_create_file',
								$Settings->get('fm_enable_create_file'),
								T_('Enable creation of files'),
								T_('Check to enable creation of files.' ),
								'',
								Form::disabled( !$Settings->get('fm_enabled') ) );
$FilesForm->end_fieldset();


$FilesForm->begin_fieldset( T_('Upload options'), 'upload_enabled' );

$FilesForm->checkbox( 'upload_enabled',
											$Settings->get('upload_enabled'),
											T_('Enable upload'),
											T_('Check to allow uploading files in general.' ) );
$FilesForm->text( 'upload_allowedext',
									$Settings->get('upload_allowedext'),
									40,
									T_('Allowed file extensions'),
									T_('Seperated by space.' )
									.' '.T_('Leave it empty to disable this check.')
									.' '.sprintf( /* TRANS: %s gets replaced with an example setting */ T_('E.g. &laquo;%s&raquo;'), $Settings->getDefault( 'upload_allowedext' ) ),
									255 );
$FilesForm->text( 'upload_allowedmime',
									$Settings->get('upload_allowedmime'),
									40,
									T_('Allowed MIME type'),
									T_('Seperated by space.' )
									.' '.T_('Leave it empty to disable this check.')
									.' '.sprintf( /* TRANS: %s gets replaced with an example setting */ T_('E.g. &laquo;%s&raquo;'), $Settings->getDefault( 'upload_allowedmime' ) ),
									255 );
$FilesForm->text( 'upload_maxkb',
									$Settings->get('upload_maxkb'),
									6,
									T_('Maximal allowed filesize'),
									T_('KB'),
									7 );

// TODO: check/transform $upload_url
// TODO: check/transform $upload_realpath

$FilesForm->end_fieldset();


if( $current_User->check_perm( 'options', 'edit' ) )
{ ?>
<fieldset class="submit">
	<fieldset>
		<div class="input">
			<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search" />
			<input type="submit" name="submit" value="<?php echo T_('Set defaults') ?>" class="search" />
			<input type="reset" value="<?php echo T_('Reset form') ?>" class="search" />
		</div>
	</fieldset>
</fieldset>
<?php
}

$FilesForm->end_form();

?>
