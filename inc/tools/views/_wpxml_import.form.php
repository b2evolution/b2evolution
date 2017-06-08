<?php
/**
 * This file display the 2nd step of WordPress XML importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $wp_blog_ID;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('WordPress XML Importer') );

$Form->begin_fieldset( T_('Report of the import') );

	// Get data to import from wordpress XML file:
	$wp_file = get_param( 'wp_file' );
	$wpxml_import_data = wpxml_get_import_data( $wp_file );

	echo '<p>';

	if( preg_match( '/\.zip$/i', $wp_file ) )
	{	// ZIP archive:
		echo '<b>'.T_('Source ZIP').':</b> <code>'.$wp_file.'</code><br />';
		// XML file from ZIP archive:
		echo '<b>'.T_('Source XML').':</b> '
			.( empty( $wpxml_import_data['XML_file_path'] ) ? T_('Not found') : '<code>'.$wpxml_import_data['XML_file_path'].'</code>' ).'<br />';
	}
	else
	{	// XML file:
		echo '<b>'.T_('Source XML').':</b> <code>'.$wp_file.'</code><br />';
	}

	echo '<b>'.T_('Source attachments folder').':</b> '
		.( empty( $wpxml_import_data['attached_files_path'] ) ? T_('Not found') : '<code>'.$wpxml_import_data['attached_files_path'].'</code>' ).'<br />';

	$BlogCache = & get_BlogCache();
	$Collection = $Blog = & $BlogCache->get_by_ID( $wp_blog_ID );
	echo '<b>'.T_('Destination collection').':</b> '.$Blog->dget( 'shortname' ).' &ndash; '.$Blog->dget( 'name' );

	echo '</p>';

	if( $wpxml_import_data['errors'] === false )
	{	// Import the data and display a report on the screen:
		wpxml_import( $wpxml_import_data['XML_file_path'], $wpxml_import_data['attached_files_path'], $wpxml_import_data['temp_zip_folder_path'] );
	}
	else
	{	// Display errors if import cannot be done:
		echo $wpxml_import_data['errors'];
		echo '<br /><p class="text-danger">'.T_('Import failed.').'</p>';
	}

$Form->end_fieldset();

$Form->buttons( array(
		array( 'button', 'button', T_('Go to Blog'), 'SaveButton', 'onclick' => 'location.href=\''.$Blog->get( 'url' ).'\'' ),
	) );

$Form->end_form();

?>