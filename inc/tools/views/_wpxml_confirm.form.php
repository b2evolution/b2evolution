<?php
/**
 * This file display the 2nd step of WordPress XML importer
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

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('WordPress XML Importer') );
$Form->add_crumb( 'wpxml' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'import' );
$Form->hiddens_by_key( get_memorized( 'blog' ) );

$Form->begin_fieldset( TD_('Confirm import') );

	// Display info for the wordpress importer:
	$wpxml_import_data = wpxml_info();

	if( $wpxml_import_data['errors'] === false )
	{	// Display found Item Types as selector:
		wpxml_item_types_selector( $wpxml_import_data['XML_file_path'], $wpxml_import_data['temp_zip_folder_path'] );
	}
	else
	{	// Display errors if import cannot be done:
		echo $wpxml_import_data['errors'];
		echo '<br /><p class="text-danger">'.T_('Import failed.').'</p>';
	}

$Form->end_fieldset();

$Form->buttons( array(
		array( 'submit', 'submit', T_('Confirm import'), 'SaveButton' ),
	) );

$Form->end_form();

?>