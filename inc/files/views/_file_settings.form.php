<?php
/**
 * This file implements the UI view for the file settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var GeneralSettings
 */
global $Settings;

global $upload_maxmaxkb;

/**
 * Javascript to init hidden/shown state of something (like a DIV) based on a checkbox
 *
 * EXPERIMENTAL
 * Will be moved to another file, I'm leaving it here for a short period, in order to provide context
 *
 * PROBLEM: jQuery is not necessarily loaded at the moment we use this :(
 *
 * @param string DOM class
 * @param string DOM id
 */
function JS_showhide_class_on_checkbox( $class, $checkbox_id )
{
	return '<script type="text/javascript">
    if( document.getElementById("'.$checkbox_id.'").checked )
		{
 			jQuery(".'.$class.'").show();
		}
		else
		{
 			jQuery(".'.$class.'").hide();
		}
	</script>';
}

/**
 * Javascript to init hidden/shown state of something (like a DIV) based on a checkbox
 *
 * EXPERIMENTAL
 * Will be moved to another file, I'm leaving it here for a short period, in order to provide context
 *
 * @param array|string DOM id
 * @param string DOM id
 */
function JS_showhide_ids_on_checkbox( $div_ids, $checkbox_id )
{
	if( !is_array($div_ids) )
	{
		$div_ids = array( $div_ids );
	}
	$r = '<script type="text/javascript">
		var display = document.getElementById("'.$checkbox_id.'").checked ? "" : "none";'."\n";
	foreach( $div_ids as $div_id )
	{
		$r .= 'document.getElementById("'.$div_id.'").style.display = display;'."\n";
	}
	$r .= '</script>';
	return $r;
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
		document.getElementById("ffield_'.$field_id.'").style.display = (document.getElementById("'.$checkbox_id.'").checked ? "" : "none")
	</script>';
}

/**
 * Javascript hide/show all DOM elements with a particular class based on current checkbox
 *
 * EXPERIMENTAL
 * Will be moved to another file, I'm leaving it here for a short period, in order to provide context
 *
 * @param string DOM class name
 */
function JS_showhide_class_on_this( $class )
{
	return 'if( this.checked )
		{
 			jQuery(".'.$class.'").show();
		}
		else
		{
 			jQuery(".'.$class.'").hide();
		}';
}

/**
 * Javascript hide/show something (like a DIV) based on current checkbox
 *
 * EXPERIMENTAL
 * Will be moved to another file, I'm leaving it here for a short period, in order to provide context
 *
 * @param array|string DOM ids
 */
function JS_showhide_ids_on_this( $div_ids )
{
	if( !is_array($div_ids) )
	{
		$div_ids = array( $div_ids );
	}
	$r = 'var display = this.checked ? "" : "none";'."\n";
	foreach( $div_ids as $div_id )
	{
		$r .= 'document.getElementById("'.$div_id.'").style.display = display;'."\n";
	}
	return $r;
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
	return 'document.getElementById("ffield_'.$field_id.'").style.display = (this.checked ? "" : "none")';
}


$Form = new Form( NULL, 'files_checkchanges' );

$Form->begin_form( 'fform', T_('File manager settings') );

$Form->add_crumb( 'file' );
$Form->hidden( 'ctrl', 'fileset' );
$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( T_('Accessible file roots').get_manual_link('accessible_file_roots'), array( 'id' => 'ffset_fileroots', 'class' => 'additional_file_settings' ) );
	$Form->checkbox( 'fm_enable_roots_blog', $Settings->get('fm_enable_roots_blog'), T_('Enable blog directories'), T_('Check to enable root directories for blogs.' ) );
	$Form->checkbox( 'fm_enable_roots_user', $Settings->get('fm_enable_roots_user'), T_('Enable user directories'), T_('Check to enable root directories for users.' ) );
	$Form->checkbox( 'fm_enable_roots_shared', $Settings->get('fm_enable_roots_shared'), T_('Enable shared directory'), T_('Check to enable shared root directory.' ) );
	$Form->checkbox( 'fm_enable_roots_skins', $Settings->get('fm_enable_roots_skins'), T_('Enable skins directory'), T_('Check to enable root directory for skins.' ) );	// fp> note: meaning may change to 1 dir per (installed) skin
$Form->end_fieldset();

$Form->begin_fieldset( T_('File creation options'), array( 'id' => 'ffset_filecreate', 'class' => 'additional_file_settings' ) );
	$Form->checkbox( 'fm_enable_create_dir', $Settings->get('fm_enable_create_dir'), T_('Enable creation of folders'), T_('Check to enable creation of directories.' ) );
	$Form->checkbox( 'fm_enable_create_file', $Settings->get('fm_enable_create_file'), T_('Enable creation of files'), T_('Check to enable creation of files.' ) );
	$Form->checkbox_input( 'upload_enabled', $Settings->get( 'upload_enabled', true ), T_('Enable upload of files'), array(
		'note' => T_('Check to allow uploading files in general.' ), 'onclick' => JS_showhide_ffield_on_this('upload_maxkb') ) );
	$Form->text_input( 'upload_maxkb', $Settings->get('upload_maxkb'), 6, T_('Maximum upload filesize'), sprintf( /* TRANS: first %s is php.ini limit, second is setting/var name, third is file name, 4th is limit in b2evo conf */ T_('KB. This cannot be higher than your PHP/Webserver setting (PHP: %s) and the limit of %s (in %s), which is currently %s!'), ini_get('upload_max_filesize').'/'.ini_get('post_max_size').' (upload_max_filesize/post_max_size)', '$upload_maxmaxkb', '/conf/_advanced.php', $upload_maxmaxkb.' '.T_('KB') ), array( 'maxlength'=>7, 'required'=>true ) );
	// Javascript to init hidden/shown state:
	echo JS_showhide_ffield_on_checkbox( 'upload_maxkb', 'upload_enabled' );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Advanced options'), array( 'id' => 'ffset_fileadvanced', 'class' => 'additional_file_settings' ) );

	$Form->text_input( 'fm_default_chmod_dir', $Settings->get('fm_default_chmod_dir'), 4, T_('Default folder permissions'), T_('Default CHMOD (UNIX permissions) for new directories created by the file manager.' ) );

	// fp> Does the following also applu to *uploaded* files? (It should)
 	$Form->text_input( 'fm_default_chmod_file', $Settings->get('fm_default_chmod_file'), 4, T_('Default file permissions'), T_('Default CHMOD (UNIX permissions) for new files created by the file manager.' ) );

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

	$Form->radio_input( 'evocache_foldername', $Settings->get( 'evocache_foldername' ), array(
						array( 'value' => '.evocache', 'label' => T_('Use .evocache folders (system hidden folders)') ),
						array( 'value' => '_evocache', 'label' => T_('Use _evocache folders (compatible with all webservers)') ) ), T_('Cache folder names'), array( 'lines' => 2 ) );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Image options').get_manual_link( 'image-options' ) );

	$Form->checkbox( 'exif_orientation', $Settings->get( 'exif_orientation' ), T_('Use EXIF info in photos'), T_('Use orientation tag to automatically rotate thumbnails to upright position.') );

	$resize_input_suffix = ' '.T_('Fit to').' ';
	$resize_input_suffix .= '<input type="text" id="fm_resize_width" name="fm_resize_width" class="form_text_input" size="4" maxlength="4" value="'.$Settings->get( 'fm_resize_width' ).'" />';
	$resize_input_suffix .= ' x ';
	$resize_input_suffix .= '<input type="text" id="fm_resize_height" name="fm_resize_height" class="form_text_input" size="4" maxlength="4" value="'.$Settings->get( 'fm_resize_height' ).'" />';
	$resize_input_suffix .= ' '.T_('pixels').' ';
	$resize_input_suffix .= '<input type="text" id="fm_resize_quality" name="fm_resize_quality" class="form_text_input" size="3" maxlength="3" style="margin-left:10px" value="'.$Settings->get( 'fm_resize_quality' ).'" />';
	$resize_input_suffix .= ' % '.T_('quality').' ';
	$Form->checkbox_input( 'fm_resize_enable', $Settings->get( 'fm_resize_enable' ), T_('Resize large images after upload'), array( 'input_suffix' => $resize_input_suffix ) );

$Form->end_fieldset();

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Form->buttons( array(
			array( 'submit', 'submit[update]', T_('Save Changes!'), 'SaveButton' ),
			array( 'submit', 'submit[restore_defaults]', T_('Restore defaults'), 'ResetButton' ),
		) );
}

$Form->end_form();

if( $current_User->check_perm( 'options', 'edit', false ) )
{	// TODO: better perm check
	echo '<p class="note">'.T_('See also:').' ';
	echo T_('Blog Settings').' &gt; '.T_('Advanced').' &gt; '.T_('Media directory location');
}

?>