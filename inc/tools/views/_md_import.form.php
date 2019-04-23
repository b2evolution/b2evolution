<?php
/**
 * This file display the 2nd step of Markdown Importer
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

global $md_blog_ID;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('Markdown Importer') );

$Form->begin_fieldset( T_('Report of the import').get_manual_link( 'markdown-importer' ) );

	// Get data to import from the file/folder:
	$md_file = get_param( 'import_file' );
	$md_import_data = md_get_import_data( $md_file );

	echo '<p>';

	if( $md_import_data['source_type'] == 'zip' )
	{	// ZIP archive:
		echo '<b>'.T_('Source ZIP').':</b> <code>'.$md_file.'</code><br />';
	}
	else
	{	// Folder:
		echo '<b>'.T_('Source folder').':</b> <code>'.$md_file.'</code><br />';
	}

	$BlogCache = & get_BlogCache();
	$Collection = $Blog = & $BlogCache->get_by_ID( $md_blog_ID );
	echo '<b>'.T_('Destination collection').':</b> '.$Blog->dget( 'shortname' ).' &ndash; '.$Blog->dget( 'name' );

	echo '</p>';

	if( $md_import_data['errors'] === false )
	{	// Import the data and display a report on the screen:
		md_import( $md_import_data['folder_path'], $md_import_data['source_type'] );
	}
	else
	{	// Display errors if import cannot be done:
		echo $md_import_data['errors'];
		echo '<br /><p class="text-danger">'.T_('Import failed.').'</p>';
	}

$Form->end_fieldset();

$Form->buttons( array(
		array( 'button', 'button', T_('Go to Blog'), 'SaveButton', 'onclick' => 'location.href=\''.$Blog->get( 'url' ).'\'' ),
	) );

$Form->end_form();

?>