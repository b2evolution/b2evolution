<?php
/**
 * This file implements the UI view for the file settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 * @author fsaya: Fabrice SAYA-GASNIER / PROGIDISTRI
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var GeneralSettings
 */
global $Settings;


$Form = & new Form( NULL, 'files_checkchanges' );

$Form->begin_form( 'fform', T_('File Settings') );

$Form->hidden( 'ctrl', 'fileset' );
$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( T_('Filemanager options') );
	$Form->checkbox( 'fm_enabled', $Settings->get('fm_enabled'), T_('Enable Filemanager'), T_('Check to enable the Filemanager.' ) );
	$Form->checkbox( 'fm_enable_roots_blog', $Settings->get('fm_enable_roots_blog'), T_('Enable blog directories'), T_('Check to enable root directories for blogs.' ) );
	// $Form->checkbox( 'fm_enable_roots_group', $Settings->get('fm_enable_roots_group'), T_('Enable group directories'), T_('Check to enable root directories for groups.' ) );
	$Form->checkbox( 'fm_enable_roots_user', $Settings->get('fm_enable_roots_user'), T_('Enable user directories'), T_('Check to enable root directories for users.' ) );
	$Form->checkbox( 'fm_enable_create_dir', $Settings->get('fm_enable_create_dir'), T_('Enable creation of dirs'), T_('Check to enable creation of directories.' ) );
	$Form->checkbox( 'fm_enable_create_file', $Settings->get('fm_enable_create_file'), T_('Enable creation of files'), T_('Check to enable creation of files.' ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Upload options') );
	$Form->checkbox( 'upload_enabled', $Settings->get('upload_enabled'), T_('Enable upload'), T_('Check to allow uploading files in general.' ) );
	$Form->text_input( 'upload_maxkb', $Settings->get('upload_maxkb'), 6, T_('Maximum allowed filesize'), array( 'note'=>T_('KB (This cannot be higher than your PHP/Webserver setting!)'), 'maxlength'=>7, 'required'=>true ) );
$Form->end_fieldset();

if( empty( $force_regexp_filename ) || empty( $force_regexp_dirname ) )
{ // At least one of these strings can be configured in the UI:
	$Form->begin_fieldset( T_('Advanced options') );
		// Do not display regexp for filename if the force_regexp_filename var is set
		if( empty($force_regexp_filename) )
		{
			$Form->text( 'regexp_filename',
											$Settings->get('regexp_filename'),
											40,
											T_('Valid filename'),
											T_('Regular expression'),
											255 );
		}
		// Do not display regexp for dirname if the force_regexp_dirname var is set
		if( empty( $force_regexp_dirname ) )
		{
			$Form->text( 'regexp_dirname',
											$Settings->get('regexp_dirname'),
											40,
											T_('Valid dirname'),
											T_('Regular expression'),
											255 );
		}
	$Form->end_fieldset();
}


if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Form->buttons( array(
			array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
			array( 'reset', '', T_('Reset'), 'ResetButton' ),
			array( 'submit', 'submit[restore_defaults]', T_('Restore defaults'), 'ResetButton' ),
		) );
}


$Form->end_form();

?>