<?php
/**
 * This file display the 2nd step of Markdown Importer
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

global $MarkdownImport;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('Markdown Importer') );

$Form->begin_fieldset( T_('Import log').get_manual_link( 'markdown-importer' ) );

	// Start to log:
	$MarkdownImport->start_log();

	$MarkdownImport->log( '<p style="margin-bottom:0">' );

	if( preg_match( '/\.zip$/i', $MarkdownImport->source ) )
	{	// ZIP archive:
		$MarkdownImport->log( '<b>'.T_('Source ZIP').':</b> <code>'.$MarkdownImport->source.'</code><br />' );
		$MarkdownImport->log( '<b>'.T_('Unzipping').'...</b> '.( $MarkdownImport->unzip() ? T_('OK').'<br />' : '' ) );
	}
	else
	{	// Folder:
		$MarkdownImport->log( '<b>'.T_('Source folder').':</b> <code>'.$MarkdownImport->source.'</code><br />' );
	}
	if( $MarkdownImport->get_data( 'errors' ) !== false )
	{	// Display errors:
		$MarkdownImport->log( $MarkdownImport->get_data( 'errors' ) );
	}

	$BlogCache = & get_BlogCache();
	$Collection = $Blog = & $BlogCache->get_by_ID( $MarkdownImport->coll_ID );
	$MarkdownImport->log( '<b>'.T_('Destination collection').':</b> '.$Blog->dget( 'shortname' ).' &ndash; '.$Blog->dget( 'name' ).'<br />' );
	$import_type = param( 'import_type', 'string', NULL );
	$MarkdownImport->log( '<b>'.T_('Mode').':</b> '
		.( isset( $MarkdownImport->options_defs['import_type']['options'][ $import_type ] )
			? $MarkdownImport->options_defs['import_type']['options'][ $import_type ]['title']
			: '<b class="red">Unknown mode!</b>' ) );
	$MarkdownImport->log( '</p>' );
	$selected_options = array();
	foreach( $MarkdownImport->options_defs as $option_key => $option )
	{
		if( $option['group'] != 'options' )
		{	// Skip option from different group:
			continue;
		}
		if( param( $option_key, $option['type'], 0 ) )
		{
			$selected_options[ $option_key ] = array(
					// Option title and note:
					( empty( $option['disabled'] ) ? $option['title'] : '<span class="grey">'.$option['title'].'</span>' )
						.( isset( $option['note'] ) ? ' <span class="note">'.$option['note'].'</span>' : '' ),
					// Indent value:
					isset( $option['indent'] ) ? $option['indent'] : 0
				);
		}
	}
	if( $selected_options_count = count( $selected_options ) )
	{
		$MarkdownImport->log( '<b>'.T_('Options').':</b> ' );
		if( $selected_options_count == 1 )
		{
			$MarkdownImport->log( $selected_options[0] );
		}
		else
		{
			$MarkdownImport->log( '<ul class="list-default">' );
			foreach( $selected_options as $option_key => $option )
			{
				$MarkdownImport->log( '<li'.( $option[1] ? ' style="margin-left:'.( $option[1] * 10 ).'px"' : '' ).'>'.$option[0].'</li>' );
			}
			$MarkdownImport->log( '</ul>' );
		}
	}

	if( $MarkdownImport->get_data( 'errors' ) === false )
	{	// Import the data and display a report on the screen:
		$MarkdownImport->execute();
	}
	else
	{	// Display errors if import cannot be done:
		$MarkdownImport->log( '<p class="text-danger">'.T_('Import failed.').'</p>' );
	}

	// End log:
	$MarkdownImport->end_log();

$Form->end_fieldset();

$Form->buttons( array(
		array( 'button', 'button', T_('Go to collection').' >>', 'SaveButton', 'onclick' => 'location.href=\''.$Blog->get( 'url' ).'\'' ),
	) );

$Form->end_form();

?>