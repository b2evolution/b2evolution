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

	if( preg_match( '/\.zip$/i', $MarkdownImport->source ) )
	{	// ZIP archive:
		echo '<b>'.T_('Source ZIP').':</b> <code>'.$MarkdownImport->source.'</code><br />';
		echo '<b>'.T_('Unzipping').'...</b> '.( $MarkdownImport->unzip() ? T_('OK').'<br />' : '' );
	}
	else
	{	// Folder:
		echo '<b>'.T_('Source folder').':</b> <code>'.$MarkdownImport->source.'</code><br />';
	}
	if( $MarkdownImport->get_data( 'errors' ) !== false )
	{	// Display errors:
		echo $MarkdownImport->get_data( 'errors' );
	}

	$BlogCache = & get_BlogCache();
	$Collection = $Blog = & $BlogCache->get_by_ID( $MarkdownImport->coll_ID );
	echo '<b>'.T_('Destination collection').':</b> '.$Blog->dget( 'shortname' ).' &ndash; '.$Blog->dget( 'name' ).'<br />';

	echo '<b>'.T_('Mode').':</b> ';
	$import_type = param( 'import_type', 'string', NULL );
	echo isset( $MarkdownImport->options_defs['import_type']['options'][ $import_type ] )
		? $MarkdownImport->options_defs['import_type']['options'][ $import_type ]['title']
		: '<b class="red">Unknown mode!</b>';
	echo '</p>';
	$selected_options = array();
	foreach( $MarkdownImport->options_defs as $option_key => $option )
	{
		if( $option['group'] != 'options' )
		{	// Skip option from different group:
			continue;
		}
		if( param( $option_key, $option['type'], 0 ) )
		{
			$selected_options[ $option_key ] = array( $option['title'], isset( $option['indent'] ) ? $option['indent'] : 0 );
		}
	}
	if( $selected_options_count = count( $selected_options ) )
	{
		echo '<b>'.T_('Options').':</b> ';
		if( $selected_options_count == 1 )
		{
			echo $selected_options[0];
		}
		else
		{
			echo '<ul class="list-default">';
			foreach( $selected_options as $option_key => $option )
			{
				echo '<li'.( $option[1] ? ' style="margin-left:'.( $option[1] * 10 ).'px"' : '' ).'>'.$option[0].'</li>';
			}
			echo '</ul>';
		}
	}

	if( $MarkdownImport->get_data( 'errors' ) === false )
	{	// Import the data and display a report on the screen:
		$MarkdownImport->execute();
	}
	else
	{	// Display errors if import cannot be done:
		echo '<p class="text-danger">'.T_('Import failed.').'</p>';
	}

$Form->end_fieldset();

$Form->buttons( array(
		array( 'button', 'button', T_('Go to collection').' >>', 'SaveButton', 'onclick' => 'location.href=\''.$Blog->get( 'url' ).'\'' ),
	) );

$Form->end_form();

?>