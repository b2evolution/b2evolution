<?php
/**
 * This file implements the Wiki Tables plugin for b2evolution
 *
 * Wiki Tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 * @ignore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class wikitables_plugin extends Plugin
{
	var $code = 'b2evWiTa';
	var $name = 'Wiki Tables';
	var $priority = 15;
	var $version = '6.7.5';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'wiki-tables-plugin';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		// Load the parsers for wiki tables
		require_once( dirname( __FILE__ ).'/_sanitizer.inc.php' );
		require_once( dirname( __FILE__ ).'/_string_utils.inc.php' );
		require_once( dirname( __FILE__ ).'/_utf_normal_util.inc.php' );

		$this->short_desc = T_('Wiki Tables converter');
		$this->long_desc = T_('You can create tables with accepted format:<br />
{| table start<br />
<br />
|- table row<br />
<br />
|} table end<br />
See manual for more.');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array(
				'default_post_rendering' => 'opt-in'
			);

		if( isset( $params['blog_type'] ) && $params['blog_type'] == 'manual' )
		{ // Set the default settings depends on blog type
			$default_params['default_post_rendering'] = 'opt-out';
		}

		$tmp_params = array_merge( $params, $default_params );
		return parent::get_coll_setting_definitions( $tmp_params );
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 *   'data': the data (by reference). You probably want to modify this.
	 *   'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		// Parse wiki tables
		if( stristr( $content, '<code' ) !== false || stristr( $content, '<pre' ) !== false || strstr( $content, '```' ) !== false )
		{ // Call replace_content() on everything outside code/pre:
			$content = callback_on_non_matching_blocks( $content,
				'~(```|<(code|pre)[^>]*>).*?(\1|</\2>)~is',
				array( $this, 'parse_tables' ) );
		}
		else
		{ // No code/pre blocks, replace on the whole thing
			$content = $this->parse_tables( $content );
		}

		return true;
	}


	/**
	 * Parse tables
	 *
	 * @param string Content
	 * @return string Content
	 */
	function parse_tables( $content )
	{
		$lines = explode( "\n", $content );
		$out = '';
		$td_history = array(); // Is currently a td tag open?
		$last_tag_history = array(); // Save history of last lag activated (td, th or caption)
		$tr_history = array(); // Is currently a tr tag open?
		$tr_attributes = array(); // history of tr attributes
		$has_opened_tr = array(); // Did this table open a <tr> element?
		$indent_level = 0; // indent level of the table

		foreach( $lines as $outLine )
		{
			$line = trim( $outLine );

			if( $line === '' )
			{ // empty line, go to next line
				$out .= $outLine . "\n";
				continue;
			}

			$first_character = $line[0];
			$matches = array();

			if( preg_match( '/^(:*)\{\|(.*)$/', $line, $matches ) )
			{ // First check if we are starting a new table
				$indent_level = strlen( $matches[1] );

				$attributes = Sanitizer::fixTagAttributes( $matches[2], 'table' );

				$outLine = str_repeat( '<dl><dd>', $indent_level ) . "<table{$attributes}>";
				array_push( $td_history, false );
				array_push( $last_tag_history, '' );
				array_push( $tr_history, false );
				array_push( $tr_attributes, '' );
				array_push( $has_opened_tr, false );
			}
			elseif( count( $td_history ) == 0 )
			{ // Don't do any of the following
				$out .= $outLine . "\n";
				continue;
			}
			elseif( substr( $line, 0, 2 ) === '|}' )
			{ // We are ending a table
				$line = '</table>' . substr( $line, 2 );
				$last_tag = array_pop( $last_tag_history );

				if( !array_pop( $has_opened_tr ) )
				{
					$line = "<tr><td></td></tr>{$line}";
				}

				if( array_pop( $tr_history ) )
				{
					$line = "</tr>{$line}";
				}

				if( array_pop( $td_history ) )
				{
					$line = "</{$last_tag}>{$line}";
				}
				array_pop( $tr_attributes );
				$outLine = $line . str_repeat( '</dd></dl>', $indent_level );
			}
			elseif( substr( $line, 0, 2 ) === '|-' )
			{ // Now we have a table row
				$line = preg_replace( '#^\|-+#', '', $line );

				// Whats after the tag is now only attributes
				$attributes = Sanitizer::fixTagAttributes( $line, 'tr' );
				array_pop( $tr_attributes );
				array_push( $tr_attributes, $attributes );

				$line = '';
				$last_tag = array_pop( $last_tag_history );
				array_pop( $has_opened_tr );
				array_push( $has_opened_tr, true );

				if ( array_pop( $tr_history ) )
				{
					$line = '</tr>';
				}

				if( array_pop( $td_history ) )
				{
					$line = "</{$last_tag}>{$line}";
				}

				$outLine = $line;
				array_push( $tr_history, false );
				array_push( $td_history, false );
				array_push( $last_tag_history, '' );
			}
			elseif ( $first_character === '|' || $first_character === '!' || substr( $line, 0, 2 ) === '|+' )
			{ // This might be cell elements, td, th or captions
				if( substr( $line, 0, 2 ) === '|+' )
				{
					$first_character = '+';
					$line = substr( $line, 1 );
				}

				$line = substr( $line, 1 );

				if( $first_character === '!' )
				{
					$line = str_replace( '!!', '||', $line );
				}

				// Split up multiple cells on the same line.
				$cells = explode( '||', $line );

				$outLine = '';

				// Loop through each table cell
				foreach( $cells as $cell )
				{
					$previous = '';
					if( $first_character !== '+' )
					{
						$tr_after = array_pop( $tr_attributes );
						if( !array_pop( $tr_history ) )
						{
							$previous = "<tr{$tr_after}>\n";
						}
						array_push( $tr_history, true );
						array_push( $tr_attributes, '' );
						array_pop( $has_opened_tr );
						array_push( $has_opened_tr, true );
					}

					$last_tag = array_pop( $last_tag_history );

					if( array_pop( $td_history ) )
					{
						$previous = "</{$last_tag}>\n{$previous}";
					}

					if( $first_character === '|' )
					{
						$last_tag = 'td';
					}
					elseif ( $first_character === '!' )
					{
						$last_tag = 'th';
					}
					elseif ( $first_character === '+' )
					{
						$last_tag = 'caption';
					}
					else
					{
						$last_tag = '';
					}

					array_push( $last_tag_history, $last_tag );

					// A cell could contain both parameters and data
					$cell_data = explode( '|', $cell, 2 );

					if( strpos( $cell_data[0], '[[' ) !== false )
					{
						$cell = "{$previous}<{$last_tag}>{$cell}";
					}
					elseif ( count( $cell_data ) == 1 )
					{
						$cell = "{$previous}<{$last_tag}>{$cell_data[0]}";
					}
					else
					{
						$attributes = Sanitizer::fixTagAttributes( $cell_data[0], $last_tag );
						$cell = "{$previous}<{$last_tag}{$attributes}>{$cell_data[1]}";
					}

					$outLine .= $cell;
					array_push( $td_history, true );
				}
			}
			$out .= $outLine . "\n";
		}

		// Closing open td, tr && table
		while( count( $td_history ) > 0 )
		{
			if( array_pop( $td_history ) )
			{
				$out .= "</td>\n";
			}
			if( array_pop( $tr_history ) )
			{
				$out .= "</tr>\n";
			}
			if( !array_pop( $has_opened_tr ) )
			{
				$out .= "<tr><td></td></tr>\n";
			}

			$out .= "</table>\n";
		}

		if( substr( $out, -1 ) === "\n" )
		{ // Remove trailing line-ending (b/c)
			$out = substr( $out, 0, -1 );
		}

		if( $out === "<table>\n<tr><td></td></tr>\n</table>" )
		{ // special case: don't return empty table
			$out = '';
		}

		return $out;
	}


	/**
	 * Event handler: Called at the beginning of the skin's HTML HEAD section.
	 *
	 * Use this to add any HTML HEAD lines (like CSS styles or links to resource files (CSS, JavaScript, ..)).
	 *
	 * @param array Associative array of parameters
	 */
	function SkinBeginHtmlHead( & $params )
	{
		global $Blog;

		if( ! isset( $Blog ) || (
		    $this->get_coll_setting( 'coll_apply_rendering', $Blog ) == 'never' &&
		    $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog ) == 'never' ) )
		{ // Don't load css/js files when plugin is not enabled
			return;
		}

		$this->require_css( 'wikitables.css' );
	}


	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		$this->SkinBeginHtmlHead( $params );
	}
}

?>