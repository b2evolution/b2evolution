<?php
/**
 * This file display the 1st step of Markdown Importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $media_subdir, $media_path;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('Markdown Importer') );

$Form->add_crumb( 'mdimport' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'import' );

// Display a panel to upload files before import:
$import_files = display_importer_upload_panel( array(
		'allowed_extensions'     => 'zip',
		'folder_with_extensions' => 'md',
		'display_type'           => true,
		'help_slug'              => 'markdown-importer',
		'refresh_url'            => $admin_url.'?ctrl=mdimport',
	) );

if( ! empty( $import_files ) )
{
	$Form->begin_fieldset( T_('Destination collection') );

	$BlogCache = & get_BlogCache();
	$BlogCache->load_all( 'shortname,name', 'ASC' );
	$BlogCache->none_option_text = T_('Please select...');

	$Form->select_input_object( 'md_blog_ID', param( 'md_blog_ID', 'integer', 0 ), $BlogCache, T_('Destination collection'), array(
			'note' => T_('This blog will be used for import.').' <a href="'.$admin_url.'?ctrl=collections&action=new">'.T_('Create new blog').' &raquo;</a>',
			'allow_none' => true,
			'required' => true,
			'loop_object_method' => 'get_extended_name' ) );

	$import_type = param( 'import_type', 'string', 'replace' );
	$Form->radio_input( 'import_type', $import_type, array(
				array(
					'value' => 'replace',
					'label' => T_('Replace existing contents'),
					'note'  => T_('WARNING: this option will permanently remove existing posts, comments, categories and tags from the selected collection.'),
					'id'    => 'import_type_replace' ),
			), '', array( 'lines' => true ) );

	echo '<div id="checkbox_delete_files"'.( $import_type == 'replace' ? '' : ' style="display:none"' ).'>';
	$Form->checkbox_input( 'delete_files', param( 'delete_files', 'integer', 0 ), '', array(
		'input_suffix' => '<label for="delete_files">'.T_(' Also delete files that will no longer be referenced in the destination collection after replacing its contents').'</label>',
		'input_prefix' => '<span style="margin-left:25px"></span>') );
	echo '</div>';

	$Form->radio_input( 'import_type', $import_type, array(
				array(
					'value' => 'append',
					'label' => T_('Append to existing contents'),
					'id'    => 'import_type_append' ),
			), '', array( 'lines' => true ) );

	$Form->end_fieldset();

	$Form->buttons( array( array( 'submit', 'submit', T_('Continue').'!', 'SaveButton' ) ) );
}

$Form->end_form();
?>
<script>
jQuery( 'input[name=import_type]' ).click( function()
{	// Show/Hide checkbox to delete files:
	jQuery( '#checkbox_delete_files' ).toggle( jQuery( this ).val() == 'replace' );
} );
</script>