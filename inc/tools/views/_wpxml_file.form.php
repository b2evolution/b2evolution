<?php
/**
 * This file display the 1st step of WordPress XML importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $media_subdir, $media_path, $Session;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('WordPress XML Importer') );

$Form->add_crumb( 'wpxml' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'confirm' );

// Display a panel to upload files before import:
$import_files = display_importer_upload_panel( array(
		'allowed_extensions'  => 'xml|txt|zip',
		'infolder_extensions' => 'xml|txt',
		'find_attachments'    => true,
		'display_type'        => true,
		'help_slug'           => 'xml-importer',
		'refresh_url'         => $admin_url.'?ctrl=wpimportxml',
	) );

if( ! empty( $import_files ) )
{
	$import_type = param( 'import_type', 'string', 'append' );

	$Form->begin_fieldset( T_('Destination collection') );

	$BlogCache = & get_BlogCache();
	$BlogCache->load_all( 'shortname,name', 'ASC' );
	$BlogCache->none_option_text = T_('Please select...');

	$Form->select_input_object( 'wp_blog_ID', $Session->get( 'last_import_coll_ID' ), $BlogCache, T_('Destination collection'), array(
			'note' => T_('This blog will be used for import.').' <a href="'.$admin_url.'?ctrl=collections&action=new">'.T_('Create new blog').' &raquo;</a>',
			'allow_none' => true,
			'required' => true,
			'loop_object_method' => 'get_extended_name' ) );

	$Form->radio_input( 'import_type', $import_type, array(
				array(
					'value' => 'append',
					'label' => T_('Append to existing contents'),
					'id'    => 'import_type_append' ),
			), T_('Import mode'), array( 'lines' => true ) );

	$Form->radio_input( 'import_type', $import_type, array(
				array(
					'value' => 'replace',
					'label' => T_('Replace existing contents'),
					'note'  => T_('WARNING: this option will permanently remove existing posts, comments, categories and tags from the selected collection.'),
					'id'    => 'import_type_replace' ),
			), '', array( 'lines' => true ) );

	echo '<div id="checkbox_delete_files"'.( $import_type == 'replace' ? '' : ' style="display:none"' ).'>';
	$Form->checkbox_input( 'delete_files', param( 'delete_files', 'integer', 0 ), '', array(
		'input_suffix' => '<label for="delete_files" style="padding-left:0">'.T_(' Also delete media files that will no longer be referenced in the destination collection after replacing its contents').'</label>',
		'input_prefix' => '<span style="margin-left:25px"></span>') );
	echo '</div>';

	$Form->checklist( array(
			array( 'import_img', 1, sprintf( TB_('Try to replace %s tags with imported attachments based on filename'), '<code>&lt;img src="...&gt;</code>' ), param( 'import_img', 'integer', 1 ) ),
			array( 'stop_error_enabled', 1, sprintf( TB_('Stop import after %s errors'), '<input type="text" name="stop_error_num" class="form-control" size="6" value="'.param( 'stop_error_num', 'integer', 100 ).'" />' ), param( 'stop_error_enabled', 'integer', 1 ), '', '', 'checkbox_with_input' ),
			array( 'convert_links', 1, sprintf( TB_('Convert wp links like %s or %s to b2evo shortlinks'), '<code>?p=</code>', '<code>?page_id=</code>' ), param( 'convert_links', 'integer', 1 ) ),
			array( 'use_yoast_cover', 1, TB_('Use Yoast opengraph or twitter image as Cover image if available'), param( 'use_yoast_cover', 'integer', 1 ) ),
		), 'perm_management', T_('Options') );

	$Form->end_fieldset();

	$Form->buttons( array( array( 'submit', 'submit', T_('Continue').'!', 'SaveButton' ) ) );
}

$Form->end_form();
?>
<script>
jQuery( 'input[name=import_type]' ).click( function()
{ // Show/Hide checkbox to delete files
	if( jQuery( this ).val() == 'replace' )
	{
		jQuery( '#checkbox_delete_files' ).show();
	}
	else
	{
		jQuery( '#checkbox_delete_files' ).hide();
	}
} );
</script>