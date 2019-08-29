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
		$selected_options['convert_md_links'] = T_('Convert Markdown links to b2evolution ShortLinks');
		if( param( 'check_links', 'integer', 0 ) )
		{
			$selected_options['check_links'] = T_('Check all internal links (slugs) to see if they link to a page of the same language (if not, log a Warning)');
			if( param( 'diff_lang_suggest', 'integer', 0 ) )
			{
				$selected_options['diff_lang_suggest'] = T_('If different language, use the "linked languages/versions" table to find the equivalent in the same language (and log the suggestion)');
				if( param( 'same_lang_replace_link', 'integer', 0 ) )
				{
					$selected_options['same_lang_replace_link'] = T_('If a same language match was found, replace the link slug in the post while importing');
				}
				if( param( 'same_lang_update_file', 'integer', 0 ) )
				{
					$selected_options['same_lang_update_file'] = T_('If a same language match was found, replace the link slug in the original <code>.md</code> file on disk so it doesn’t trigger warnings next times (and can be versioned into Git). This requires using a directory to import, not a ZIP file.');
				}
			}
		}
	}
	if( param( 'force_item_update', 'integer', 0 ) )
	{
		$selected_options['force_item_update'] = T_('Force Item update, even if file hash has not changed');
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
			foreach( $selected_options as $option_key => $option_title )
			{
				switch( $option_key )
				{
					case 'check_links':
						$indent = 1;
						break;
					case 'diff_lang_suggest':
						$indent = 2;
						break;
					case 'same_lang_replace_link':
					case 'same_lang_update_file':
						$indent = 3;
						break;
					default:
						$indent = false;
						break;
				}
				echo '<li'.( $indent ? ' style="margin-left:'.( $indent * 10 ).'px"' : '' ).'>'.$option_title.'</li>';
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
		echo $MarkdownImport->get_data( 'errors' );
		echo '<br /><p class="text-danger">'.T_('Import failed.').'</p>';
	}

$Form->end_fieldset();

$Form->buttons( array(
		array( 'button', 'button', T_('Go to collection').' >>', 'SaveButton', 'onclick' => 'location.href=\''.$Blog->get( 'url' ).'\'' ),
	) );

$Form->end_form();

?>