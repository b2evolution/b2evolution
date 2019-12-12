<?php
/**
 * This file display the 3rd step of WordPress XML importer
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

global $WordpressImport;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('WordPress XML Importer') );

$Form->begin_fieldset( T_('Report of the import') );

	// Start to log:
	$WordpressImport->start_log();

	// Display info for the wordpress importer:
	$WordpressImport->display_info( true );

	$form_buttons = array();

	if( $WordpressImport->info_data['errors'] === false )
	{	// Import the data and display a report on the screen:
		$WordpressImport->execute();
		$import_Blog = & $WordpressImport->get_Blog();
		$form_buttons[] = array( 'button', 'button', T_('Go to collection').' >>', 'SaveButton', 'onclick' => 'location.href=\''.$import_Blog->get( 'url' ).'\'' );
	}

	// End log:
	$WordpressImport->end_log();

$Form->end_fieldset();

$Form->buttons( $form_buttons );

$Form->end_form();

?>