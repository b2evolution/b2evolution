<?php
/**
 * This file implements the UI view for the file settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package admin
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
 * PROBLEM: jQuery is not necessarily loaded at the moment we use this :(
 *
 * @param string DOM class
 * @param string DOM id
 */
function JS_showhide_class_on_checkbox( $class, $checkbox_id )
{
	return '<script>
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
	$r = '<script>
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
	return '<script>
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

$Form->begin_form( 'fform', TB_('File manager settings') );

$Form->add_crumb( 'file' );
$Form->hidden( 'ctrl', 'fileset' );
$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( TB_('Accessible file roots').get_manual_link('accessible-file-roots'), array( 'id' => 'ffset_fileroots', 'class' => 'additional_file_settings' ) );
	$Form->checkbox( 'fm_enable_roots_blog', $Settings->get('fm_enable_roots_blog'), TB_('Enable blog directories'), TB_('Check to enable root directories for blogs.' ) );
	$Form->checkbox( 'fm_enable_roots_user', $Settings->get('fm_enable_roots_user'), TB_('Enable user directories'), TB_('Check to enable root directories for users.' ) );
	$Form->checkbox( 'fm_enable_roots_shared', $Settings->get('fm_enable_roots_shared'), TB_('Enable shared directory'), TB_('Check to enable shared root directory.' ) );
	$Form->checkbox( 'fm_enable_roots_skins', $Settings->get('fm_enable_roots_skins'), TB_('Enable skins directory'), TB_('Check to enable root directory for skins.' ) );	// fp> note: meaning may change to 1 dir per (installed) skin
	$Form->checkbox( 'fm_enable_roots_plugins', $Settings->get('fm_enable_roots_plugins'), TB_('Enable plugins directory'), TB_('Check to enable root directory for plugins.' ) );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('File creation options').get_manual_link('file-creation-options'), array( 'id' => 'ffset_filecreate', 'class' => 'additional_file_settings' ) );
	$Form->checkbox( 'fm_enable_create_dir', $Settings->get('fm_enable_create_dir'), TB_('Enable creation of folders'), TB_('Check to enable creation of directories.' ) );
	$Form->checkbox( 'fm_enable_create_file', $Settings->get('fm_enable_create_file'), TB_('Enable creation of files'), TB_('Check to enable creation of files.' ) );
	$Form->checkbox_input( 'upload_enabled', $Settings->get( 'upload_enabled', true ), TB_('Enable upload of files'), array(
		'note' => TB_('Check to allow uploading files in general.' ), 'onclick' => JS_showhide_ffield_on_this('upload_maxkb') ) );

	load_funcs( 'tools/model/_system.funcs.php' );
	$upload_max_filesize = get_php_bytes_size( ini_get( 'upload_max_filesize' ) );
	$post_max_size = get_php_bytes_size( ini_get( 'post_max_size' ) );
	$upload_maxkb = $Settings->get( 'upload_maxkb' ) * 1024;
	$upload_maxkb_before_note = $upload_maxkb_after_note = '';
	if( $upload_maxkb > $upload_max_filesize || $upload_maxkb > $post_max_size )
	{ // Mark field with red when it is higher than system max sizes:
		param_error( 'upload_maxkb', '' );
		$upload_maxkb_before_note = '<span class="red">';
		$upload_maxkb_after_note = '</span>';
	}
	$Form->text_input( 'upload_maxkb', $Settings->get('upload_maxkb'), 6, TB_('Maximum upload filesize'), $upload_maxkb_before_note.sprintf( /* TRANS: first %s is php.ini limit, second is setting/var name, third is file name, 4th is limit in b2evo conf */ TB_('KB. This cannot be higher than your PHP/Webserver setting (PHP: %s)!'), ini_get('upload_max_filesize').'/'.ini_get('post_max_size').' (upload_max_filesize/post_max_size)' ).$upload_maxkb_after_note, array( 'maxlength'=>7, 'required'=>true ) );
	// Javascript to init hidden/shown state:
	echo JS_showhide_ffield_on_checkbox( 'upload_maxkb', 'upload_enabled' );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('Advanced options').get_manual_link('advanced-file-options'), array( 'id' => 'ffset_fileadvanced', 'class' => 'additional_file_settings' ) );

	$Form->text_input( 'fm_default_chmod_dir', $Settings->get('fm_default_chmod_dir'), 4, TB_('Permissions for new folders'), TB_('Default CHMOD (UNIX permissions) for new directories created by b2evolution.') );

	// fp> Does the following also apply to *uploaded* files? (It should)
	// yb> Yes, I tested on December 8, 2018, ver. 6.10.4-stable, branch "develop".
	$Form->text_input( 'fm_default_chmod_file', $Settings->get('fm_default_chmod_file'), 4, TB_('Permissions for new files'), TB_('Default CHMOD (UNIX permissions) for new files created by b2evolution.') );

	if( empty( $force_regexp_filename ) || empty( $force_regexp_dirname ) )
	{ // At least one of these strings can be configured in the UI:

		// Do not display regexp for filename if the force_regexp_filename var is set
		if( empty($force_regexp_filename) )
		{
			$Form->text( 'regexp_filename',
											$Settings->get('regexp_filename'),
											40,
											TB_('Valid filename'),
											TB_('Regular expression'),
											255 );
		}
		// Do not display regexp for dirname if the force_regexp_dirname var is set
		if( empty( $force_regexp_dirname ) )
		{
			$Form->text( 'regexp_dirname',
											$Settings->get('regexp_dirname'),
											40,
											TB_('Valid dirname'),
											TB_('Regular expression'),
											255 );
		}
	}

	$Form->radio_input( 'evocache_foldername', $Settings->get( 'evocache_foldername' ), array(
						array( 'value' => '.evocache', 'label' => TB_('Use .evocache folders (system hidden folders)') ),
						array( 'value' => '_evocache', 'label' => TB_('Use _evocache folders (compatible with all webservers)') ) ), TB_('Cache folder names'), array( 'lines' => 2 ) );

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Image options').get_manual_link( 'image-options' ) );

	$Form->checkbox( 'exif_orientation', $Settings->get( 'exif_orientation' ), TB_('Use EXIF info in photos'), TB_('Use orientation tag to automatically rotate thumbnails to upright position.') );

	$Form->begin_line( TB_('Resize large images after upload'), 'fm_resize_enable' );
		$Form->checkbox( 'fm_resize_enable', $Settings->get( 'fm_resize_enable' ), '' );
		$Form->text( 'fm_resize_width', $Settings->get( 'fm_resize_width' ), 4, ' &nbsp; '.TB_('Fit to') );
		$Form->text( 'fm_resize_height', $Settings->get( 'fm_resize_height' ), 4, ' x ' );
		$Form->text( 'fm_resize_quality', $Settings->get( 'fm_resize_quality' ), 3, TB_('pixels').' &nbsp; ' );
	$Form->end_line( ' % '.TB_('quality') );

$Form->end_fieldset();

if( check_user_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Form->buttons( array(
			array( 'submit', 'submit[update]', TB_('Save Changes!'), 'SaveButton' ),
			array( 'submit', 'submit[restore_defaults]', TB_('Restore defaults'), 'ResetButton' ),
		) );
}

$Form->end_form();

if( check_user_perm( 'options', 'edit', false ) )
{	// TODO: better perm check
	echo '<p class="note">'.TB_('See also:').' ';
	echo TB_('Blog Settings').' &gt; '.TB_('Advanced').' &gt; '.TB_('Media directory location');
}

?>