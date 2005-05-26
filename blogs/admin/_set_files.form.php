<?php
/**
 * This file implements the UI view for the file settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author blueyed: Daniel HAHLER
 * @author fplanque: François PLANQUE / PROGIDISTRI
 * @author fsaya: Fabrice SAYA-GASNIER / PROGIDISTRI
 *
 * @package admin
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

if( false )
{	/**
	 * This is ugly, sorry, but I temporarily need this until NuSphere fixes their CodeInsight :'(
	 */
	include('_header.php');
	include('fileset.php');
}

$Form = & new Form( 'fileset.php', 'filesform' );

$Form->begin_form( 'fform', T_('File Settings') );
$Form->hidden( 'action', 'update' );

$Form->fieldset( T_('Filemanager options') );
	$Form->checkbox( 'fm_enabled', $Settings->get('fm_enabled'),
											T_('Enable Filemanager'), T_('Check to enable the Filemanager.' ) );
	$Form->checkbox( 'fm_enable_roots_blog', $Settings->get('fm_enable_roots_blog'),
											T_('Enable blog directories'), T_('Check to enable root directories for blogs.' ) );
	// $Form->checkbox( 'fm_enable_roots_group', $Settings->get('fm_enable_roots_group'),
	//										T_('Enable group directories'), T_('Check to enable root directories for groups.' ) );
	$Form->checkbox( 'fm_enable_roots_user', $Settings->get('fm_enable_roots_user'),
											T_('Enable user directories'), T_('Check to enable root directories for users.' ) );
	$Form->checkbox( 'fm_enable_create_dir', $Settings->get('fm_enable_create_dir'),
											T_('Enable creation of dirs'), T_('Check to enable creation of directories.' ) );
	$Form->checkbox( 'fm_enable_create_file', $Settings->get('fm_enable_create_file'),
											T_('Enable creation of files'), T_('Check to enable creation of files.' ) );
$Form->fieldset_end();


$Form->fieldset( T_('Upload options') );
	$Form->checkbox( 'upload_enabled', $Settings->get('upload_enabled'),
												T_('Enable upload'), T_('Check to allow uploading files in general.' ) );
	$Form->text( 'upload_allowedext',
										$Settings->get('upload_allowedext'),
										40,
										T_('Allowed file extensions'),
										T_('Seperated by space.' )
										.' '.T_('Leave it empty to disable this check.')
										.' '.sprintf( /* TRANS: %s gets replaced with an example setting */ T_('E.g. &laquo;%s&raquo;'), $Settings->getDefault( 'upload_allowedext' ) ),
										255 );
	$Form->text( 'upload_maxkb',
										$Settings->get('upload_maxkb'),
										6,
										T_('Maximal allowed filesize'),
										T_('KB (This cannot be higher than your PHP/Webserver setting!)'),
										7 );
$Form->fieldset_end();


$Form->fieldset( T_('Advanced options') );
	$Form->text( 'regexp_filename',
										$Settings->get('regexp_filename'),
										40,
										T_('Valid filename'),
										T_('Regular expression'),
										255 );
$Form->fieldset_end();


// TODO: check/transform $upload_url
// TODO: check/transform $upload_realpath
// fplanque->blueyed: are these TODOs real? I don't think they are relevant any more?


$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ),
												array( 'submit', 'submit', T_('Restore defaults'), 'ResetButton' ),
											) );

?>
