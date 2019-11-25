<?php
/**
 * This file display the 1st step of Item Type importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $media_subdir, $media_path, $Session;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', TB_('Item Type Importer') );

$Form->add_crumb( 'itimport' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'import' );

// Display a panel to upload files before import:
$import_files = display_importer_upload_panel( array(
		'allowed_extensions'  => 'xml',
		'display_type'        => true,
		'help_slug'           => 'item-type-importer',
		'refresh_url'         => $admin_url.'?ctrl=itimport',
	) );

if( ! empty( $import_files ) )
{
	$import_type = param( 'import_type', 'string', 'skip' );

	$Form->begin_fieldset( TB_('Options') );

	$BlogCache = & get_BlogCache();
	$BlogCache->load_all( 'shortname,name', 'ASC' );
	//$BlogCache->none_option_text = TB_('Please select...');

	$Form->select_input_object( 'it_blog_ID', $Session->get( 'last_import_coll_ID' ), $BlogCache, TB_('Enable for collection'), array(
			'note' => TB_('All item types from the selected XML file will be enabled for this collection.').' <a href="'.$admin_url.'?ctrl=collections&action=new">'.TB_('Create new blog').' &raquo;</a>',
			'allow_none' => true,
			'loop_object_method' => 'get_extended_name' ) );

	$Form->radio_input( 'import_type', $import_type, array(
				array(
					'value' => 'skip',
					'label' => TB_('Import only not existing item types'),
				),
				array(
					'value' => 'update',
					'label' => TB_('Update existing item types'),
				),
			), TB_('Import mode'), array( 'lines' => true ) );

	$Form->end_fieldset();

	$Form->buttons( array( array( 'submit', 'submit', T_('Continue').'!', 'SaveButton' ) ) );
}

$Form->end_form();
?>