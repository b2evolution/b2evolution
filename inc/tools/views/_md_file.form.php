<?php
/**
 * This file display the 1st step of Markdown Importer
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

global $admin_url, $media_subdir, $media_path, $Session, $MarkdownImport;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', TB_('Markdown Importer') );

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
	$Form->begin_fieldset( TB_('Destination collection') );

	$BlogCache = & get_BlogCache();
	$BlogCache->load_all( 'shortname,name', 'ASC' );
	$BlogCache->none_option_text = TB_('Please select...');

	$Form->select_input_object( 'md_blog_ID', $Session->get( 'last_import_coll_ID' ), $BlogCache, TB_('Destination collection'), array(
			'note' => TB_('This blog will be used for import.').' <a href="'.$admin_url.'?ctrl=collections&action=new">'.TB_('Create new blog').' &raquo;</a>',
			'allow_none' => true,
			'required' => true,
			'loop_object_method' => 'get_extended_name' ) );

	// Import mode:
	$import_type_value = param( 'import_type', $MarkdownImport->options_defs['import_type']['type'], NULL );
	$i = 0;
	foreach( $MarkdownImport->options_defs['import_type']['options'] as $option_value => $option )
	{
		$Form->radio_input( 'import_type', $MarkdownImport->get_option( 'import_type' ), array(
				array(
					'value' => $option_value,
					'label' => $option['title'],
					'note'  => isset( $option['note'] ) ? $option['note'] : '',
					'suffix'=> isset( $option['suffix'] ) ? $option['suffix'] : '',
					'id'    => 'import_type_'.$option_value ),
			), ( $i == 0 ? $MarkdownImport->options_defs['import_type']['title'] : '' ), array( 'lines' => true ) );
		foreach( $MarkdownImport->options_defs as $sub_option_key => $sub_option )
		{
			if( $sub_option['group'] == 'import_type' && $sub_option['subgroup'] == $option_value )
			{
				echo '<div id="checkbox_'.$sub_option_key.'"'.( $sub_option['subgroup'] == $MarkdownImport->get_option( 'import_type' ) ? '' : ' style="display:none"' ).'>';
				$Form->checkbox_input( $sub_option_key, $MarkdownImport->get_option( $sub_option_key ), '', array(
					'input_suffix' => $sub_option['title'],
					'note'         => isset( $sub_option['note'] ) ? $sub_option['note'] : '',
					'input_prefix' => '<span style="margin-left:25px"></span>') );
				echo '</div>';
			}
		}
		$i++;
	}

	// Options:
	$checklist_options = array();
	foreach( $MarkdownImport->options_defs as $option_key => $option )
	{
		if( $option['group'] != 'options' )
		{	// Skip option from different group:
			continue;
		}
		$option_attrs = array();
		if( ! empty( $option['indent'] ) )
		{
			$option_attrs['style'] = 'margin-left:'.( $option['indent'] * 20 ).'px';
		}
		$checklist_options[] = array( $option_key, '1', $option['title'], $MarkdownImport->get_option( $option_key ), ( isset( $option['disabled'] ) ? $option['disabled'] : NULL ), ( isset( $option['note'] ) ? $option['note'] : NULL ), NULL, NULL, $option_attrs );
	}
	if( ! empty( $checklist_options ) )
	{
		$Form->checklist( $checklist_options, 'md_options', TB_('Options') );
	}

	// Radio options:
	foreach( $MarkdownImport->options_defs as $option_key => $option )
	{
		if( $option['group'] == 'radio' )
		{	// Display here only radio options:
			$radio_options = $option['options'];
			foreach( $radio_options as $radio_option_value => $radio_option_data )
			{
				$radio_options[ $radio_option_value ] = array( 'value' => $radio_option_value ) + $radio_option_data;
			}
			$Form->radio_input( $option_key, $MarkdownImport->get_option( $option_key ), $radio_options, $option['title'], array( 'lines' => true ) );
		}
	}

	$Form->end_fieldset();

	$Form->buttons( array( array( 'submit', 'submit', TB_('Continue').'!', 'SaveButton' ) ) );
}

$Form->end_form();
?>
<script>
function evo_md_import_update_mode_visibility()
{	// Show/Hide additional options for import mode:
	var mode = jQuery( 'input[name=import_type]:checked' ).val();
	jQuery( '#checkbox_delete_files' ).toggle( mode == 'delete' );
	jQuery( '#checkbox_reuse_cats' ).toggle( mode == 'append' );
	if( mode == 'delete' )
	{
		jQuery( '#import_type_delete_confirm_block' ).css( 'display', 'inline-block' );
	}
	else
	{
		jQuery( '#import_type_delete_confirm_block' ).hide();
	}
}
jQuery( 'input[name=import_type]' ).click( evo_md_import_update_mode_visibility );
jQuery( document ).ready( evo_md_import_update_mode_visibility );
</script>