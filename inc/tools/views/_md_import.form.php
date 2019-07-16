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

global $MarkdownImport;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('Markdown Importer') );

$Form->begin_fieldset( T_('Import log').get_manual_link( 'markdown-importer' ) );

	echo '<p style="margin-bottom:0">';

	if( $MarkdownImport->get_data( 'type' ) == 'zip' )
	{	// ZIP archive:
		echo '<b>'.T_('Source ZIP').':</b> <code>'.$MarkdownImport->source.'</code><br />';
	}
	else
	{	// Folder:
		echo '<b>'.T_('Source folder').':</b> <code>'.$MarkdownImport->source.'</code><br />';
	}

	$BlogCache = & get_BlogCache();
	$Collection = $Blog = & $BlogCache->get_by_ID( $MarkdownImport->coll_ID );
	echo '<b>'.T_('Destination collection').':</b> '.$Blog->dget( 'shortname' ).' &ndash; '.$Blog->dget( 'name' ).'<br />';

	echo '<b>'.T_('Mode').':</b> ';
	switch( param( 'import_type', 'string', NULL ) )
	{
		case 'replace':
			echo T_('Replace existing contents');
			break;
		case 'update':
			echo T_('Update existing contents');
			break;
		case 'append':
			echo T_('Append to existing contents');
			break;
	}
	echo '</p>';
	$selected_options = array();
	if( param( 'convert_md_links', 'integer', 0 ) )
	{
		$selected_options[] = T_('Convert Markdown links to b2evolution ShortLinks');
	}
	if( param( 'force_item_update', 'integer', 0 ) )
	{
		$selected_options[] = T_('Force Item update, even if file hash has not changed');
	}
	if( $selected_options_count = count( $selected_options ) )
	{
		echo '<b>'.T_('Options').':</b> '.( $selected_options_count == 1 ? $selected_options[0] : '<ul class="list-default"><li>'.implode( '</li><li>', $selected_options ).'</li></ul>' );
	}

	if( $MarkdownImport->get_data( 'errors' ) === false )
	{	// Import the data and display a report on the screen:
		$MarkdownImport->execute();
	}
	else
	{	// Display errors if import cannot be done:
		echo $MarkdownImport->get_data( 'errors' );
		echo '<br /><p class="text-danger">'.T_('Import failed.').'</p>';
	}

$Form->end_fieldset();

$Form->buttons( array(
		array( 'button', 'button', T_('Go to collection').' >>', 'SaveButton', 'onclick' => 'location.href=\''.$Blog->get( 'url' ).'\'' ),
	) );

$Form->end_form();

?>