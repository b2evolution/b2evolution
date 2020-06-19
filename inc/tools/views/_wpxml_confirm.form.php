<?php
/**
 * This file display the 2nd step of WordPress XML importer
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


global $WordpressImport, $action;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', TB_('WordPress XML Importer') );
$Form->add_crumb( 'wpxml' );
$Form->hidden_ctrl();
$Form->hiddens_by_key( get_memorized( 'blog' ) );

$Form->begin_fieldset( TB_('Confirm import') );

	// Display info for the wordpress importer:
	$WordpressImport->display_info( $action == 'use_existing_folder', $action == 'delete_extract' );

	$form_buttons = array();

	if( $WordpressImport->info_data['errors'] === false )
	{	// Display found Item Types as selector:
		wpxml_item_types_selector( $WordpressImport->info_data['XML_file_path'], $WordpressImport->info_data['ZIP_folder_path'] );
		$form_buttons[] = array( 'submit', 'actionArray[import]', TB_('Confirm import'), 'SaveButton' );
	}
	elseif( $WordpressImport->info_data['error_type'] == 'folder_exists' )
	{	// Display buttons to delete or use already existing folder:
		$form_buttons[] = array( 'submit', 'actionArray[delete_extract]', TB_('Delete and extract again'), 'DefaultButton' );
		$form_buttons[] = array( 'submit', 'actionArray[use_existing_folder]', TB_('Continue with existing folder'), 'SaveButton' );
	}

$Form->end_fieldset();

$Form->buttons( $form_buttons );

$Form->end_form();

?>