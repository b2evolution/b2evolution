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
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
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

/**
 * Javascript to init hidden/shown state of something (like a DIV) based on a checkbox
 *
 * EXPERIMENTAL
 * Will be moved to another file, I'm leaving it here for a short period, in order to provide context
 *
 * @param string DOM id
 * @param string DOM id
 */
function JS_showhide_on_checkbox( $div_id, $checkbox_id )
{
	return '<script type="text/javascript">
		document.getElementById("'.$div_id.'").style.display = (document.getElementById("'.$checkbox_id.'").checked==true ? "" : "none")
	</script>';
}

/**
 * Javascript to init hidden/shown state of a fastform field based on a checkbox
 *
 * EXPERIMENTAL
 * Will be moved to another file, I'm leaving it here for a short period, in order to provide context
 *
 * @param string form field id as used when creating it with the Form class
 * @param string DOM id
 */
function JS_showhide_ffield_on_checkbox( $field_id, $checkbox_id )
{
	return '<script type="text/javascript">
		document.getElementById("ffield_'.$field_id.'").style.display = (document.getElementById("'.$checkbox_id.'").checked==true ? "" : "none")
	</script>';
}

/**
 * Javascript hide/show something (like a DIV) based on current checkbox
 *
 * EXPERIMENTAL
 * Will be moved to another file, I'm leaving it here for a short period, in order to provide context
 *
 * @param string DOM id
 */
function JS_showhide_on_this( $div_id )
{
	return 'document.getElementById("'.$div_id.'").style.display = (this.checked==true ? "" : "none")';
}

/**
 * Javascript hide/show a fastform field based on current checkbox
 *
 * EXPERIMENTAL
 * Will be moved to another file, I'm leaving it here for a short period, in order to provide context
 *
 * @param string DOM id
 */
function JS_showhide_ffield_on_this( $field_id )
{
	return 'document.getElementById("ffield_'.$field_id.'").style.display = (this.checked==true ? "" : "none")';
}


$Form = & new Form( NULL, 'files_checkchanges' );

$Form->begin_form( 'fform', T_('File Settings') );

$Form->hidden( 'ctrl', 'fileset' );
$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( T_('File Manager') );
	$Form->checkbox_input( 'fm_enabled', $Settings->get('fm_enabled'), T_('Enable Filemanager'), array(
		'note' => T_('Check to enable the Filemanager.' ), 'onclick' => JS_showhide_on_this('additional_file_settings') ) );
$Form->end_fieldset();

echo '<div id="additional_file_settings">';	// fp> TODO: not compatible with TABLE layout (many such abuses already in the code)

$Form->begin_fieldset( T_('Accessible file roots') );
	$Form->checkbox( 'fm_enable_roots_blog', $Settings->get('fm_enable_roots_blog'), T_('Enable blog directories'), T_('Check to enable root directories for blogs.' ) );
	// $Form->checkbox( 'fm_enable_roots_group', $Settings->get('fm_enable_roots_group'), T_('Enable group directories'), T_('Check to enable root directories for groups.' ) );
	$Form->checkbox( 'fm_enable_roots_user', $Settings->get('fm_enable_roots_user'), T_('Enable user directories'), T_('Check to enable root directories for users.' ) );
$Form->end_fieldset();

$Form->begin_fieldset( T_('File creation options') );
	$Form->checkbox( 'fm_enable_create_dir', $Settings->get('fm_enable_create_dir'), T_('Enable creation of folders'), T_('Check to enable creation of directories.' ) );
	$Form->checkbox( 'fm_enable_create_file', $Settings->get('fm_enable_create_file'), T_('Enable creation of files'), T_('Check to enable creation of files.' ) );
	$Form->checkbox_input( 'upload_enabled', $Settings->get('upload_enabled'), T_('Enable upload of files'), array(
		'note' => T_('Check to allow uploading files in general.' ), 'onclick' => JS_showhide_ffield_on_this('upload_maxkb') ) );
	$Form->text_input( 'upload_maxkb', $Settings->get('upload_maxkb'), 6, T_('Maximum upload filesize'), array(
		'note'=>T_('KB (This cannot be higher than your PHP/Webserver setting!)'), 'maxlength'=>7, 'required'=>true ) );
	// Javascript to init hidden/shown state:
	echo JS_showhide_ffield_on_checkbox( 'upload_maxkb', 'upload_enabled' );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Advanced options') );

	$Form->text_input( 'fm_default_chmod_dir', $Settings->get('fm_default_chmod_dir'), 4, T_('Default folder permissions'), array('note'=>T_('Default CHMOD (UNIX permissions) for new directories created by the file manager.' )) );

	// fp> Does the following also applu to *uploaded* files? (It should)
 	$Form->text_input( 'fm_default_chmod_file', $Settings->get('fm_default_chmod_file'), 4, T_('Default file permissions'), array('note'=>T_('Default CHMOD (UNIX permissions) for new files created by the file manager.' )) );

	if( empty( $force_regexp_filename ) || empty( $force_regexp_dirname ) )
	{ // At least one of these strings can be configured in the UI:

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
	}

$Form->end_fieldset();

echo '</div>';
// Javascript to init hidden/shown state:
echo JS_showhide_on_checkbox( 'additional_file_settings', 'fm_enabled' );

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Form->buttons( array(
			array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
			array( 'reset', '', T_('Reset'), 'ResetButton' ),
			array( 'submit', 'submit[restore_defaults]', T_('Restore defaults'), 'ResetButton' ),
		) );
}


$Form->end_form();


/*
 * $Log$
 * Revision 1.9  2006/12/06 18:06:18  fplanque
 * an experiment with JS hiding/showing form parts
 *
 * Revision 1.8  2006/11/28 01:40:13  fplanque
 * wording
 *
 * Revision 1.7  2006/11/26 01:42:09  fplanque
 * doc
 *
 */
?>