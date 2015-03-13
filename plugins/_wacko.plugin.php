<?php
/**
 * This file implements the Wacko plugin for b2evolution
 *
 * Wacko style formatting
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 * @ignore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class wacko_plugin extends Plugin
{
	var $code = 'b2evWcko';
	var $name = 'Wacko formatting';
	var $priority = 30;
	var $version = '5.0.0';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'wacko-plugin';
	var $number_of_installs = 1;

	/**
	 * GreyMatter formatting search array
	 *
	 * @access private
	 */
	var $search = array(
			'#( ^ | [\s\S] ) ====== (.+?) ====== #x',
			'#( ^ | [\s\S] ) ===== (.+?) ===== #x',
			'#( ^ | [\s\S] ) ==== (.+?) ==== #x',
			'#( ^ | [\s\S] ) === (.+?) === #x',
			'#( ^ | [\s\S] ) == (.+?) == #x',
			'#^ \s* --- \s* $#xm',	// multiline start/stop checking
		);

	/**
	 * HTML replace array
	 *
	 * @access private
	 */
	var $replace = array(
			'$1<h6>$2</h6>',
			'$1<h5>$2</h5>',
			'$1<h4>$2</h4>',
			'$1<h3>$2</h3>',
			'$1<h2>$2</h2>',
			'<hr />',
		);

	/**
	 * Minimum heading level
	 */
	var $min_h_level = 2;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Wacko style formatting');
		$this->long_desc = T_('Accepted formats:<br />
== h2 ==<br />
=== h3 ===<br />
==== h4 ====<br />
===== h5 =====<br />
====== h6 ======<br />
--- (horizontal rule)<br />
%%%codeblock%%%<br />');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_post_rendering' => 'opt-in' ) );
		return array_merge( parent::get_coll_setting_definitions( $default_params ),
			array(
				'min_h_level' => array(
						'label' => T_( 'Top Heading Level' ),
						'type' => 'integer',
						'size' => 1,
						'maxlength' => 1,
						'note' => T_( 'This plugin will adjust headings so they always start at the level you want: 2 for &lt;H2&gt;, 3 for &lt;H3&gt;, etc.' ),
						'defaultvalue' => 2,
						'valid_range' => array(
							'min' => 2, // from <h2>
							'max' => 6, // to <h6>
						),
					),
			)
		);
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

		if( !empty( $params['Item'] ) )
		{ // Get Item from params
			$Item = & $params['Item'];
		}
		elseif( !empty( $params['Comment'] ) )
		{ // Get Item from Comment
			$Comment = & $params['Comment'];
			$Item = & $Comment->get_Item();
		}

		if( ! empty( $Item ) )
		{ // We are rendering Item or Comment now, Get a setting depending on Blog
			$item_Blog = & $Item->get_Blog();
			$this->min_h_level = $this->get_coll_setting( 'min_h_level', $item_Blog );
		}

		if( $this->min_h_level > 2 && $this->min_h_level <= 6 )
		{ // Restrict <h_> tags by minimum heading level
			foreach( $this->replace as $r => $replace )
			{ // Do replace
				$this->replace[ $r ] = preg_replace_callback( '#([^<]*<)(h[2-6])(>[^<]*</)\2(>)#i',
					array( $this, 'restrict_min_h_level' ), $replace );
			}
		}

		$content = replace_content_outcode( $this->search, $this->replace, $content );

		// Find bullet lists
		if( stristr( $content, '<code' ) !== false || stristr( $content, '<pre' ) !== false )
		{	// Call replace_content() on everything outside code/pre:
			$content = callback_on_non_matching_blocks( $content,
				'~<(code|pre)[^>]*>.*?</\1>~is',
				array( $this, 'find_bullet_lists' ) );
		}
		else
		{	// No code/pre blocks, replace on the whole thing
			$content = $this->find_bullet_lists( $content );
		}

		return true;
	}


	/**
	 * Restrict <h_> tags by minimum heading level
	 *
	 * @param array Match array
	 * @return string
	 */
	function restrict_min_h_level( $match )
	{
		$h_tag = $match[2];
		$h_tag_level = substr( $match[2], 1 );

		$h_tag_level += $this->min_h_level - 2;
		if( $h_tag_level > 6 )
		{ // Max level is 6
			$h_tag_level = 6;
		}

		return $match[1].'h'.$h_tag_level.$match[3].'h'.$h_tag_level.$match[4];
	}


	/**
	 * Find bullet lists
	 *
	 * @param string Content
	 * @return string Content
	 */
	function find_bullet_lists( $content )
	{
		// Find and parse the code blocks to html view
		$content = $this->escape_codeblock( $content );

		$lines = explode( "\n", $content );
		$lines_count = count( $lines );
		$lists = array();
		$current_depth = 0;
		$content = '';
		foreach( $lines as $l => $line )
		{
			if( ! preg_match( '#^ /s $#xm', $line ) )
			{	 // If not blank line
				$matches = array();

				if( preg_match( '#^((  )+)\*(.*)$#m', $line, $matches ) )
				{	// We have a list item
					$req_depth = strlen( $matches[1] ) / 2;
					while( $current_depth < $req_depth )
					{	// We must indent
						$content .= "<ul>\n";
						array_push( $lists, 'ul' );
						$current_depth++;
					}

					while( $current_depth > $req_depth )
					{	// We must close lists
						$content .= '</'.array_pop( $lists ).">\n";
						$current_depth--;
					}

					$content .= $matches[1].'<li>'.$matches[3]."</li>\n";
					continue;
				}

				if( preg_match( '#^((  )+)([0-9]+)(.*)$#m', $line, $matches ) )
				{	// We have an ordered list item
					$req_depth = strlen( $matches[1] ) / 2;
					while( $current_depth < $req_depth )
					{	// We must indent
						$content .= '<ol start="'.$matches[3].'">'."\n";
						array_push( $lists, 'ol' );
						$current_depth++;
					}

					while( $current_depth > $req_depth )
					{	// We must close lists
						$content .= '</'.array_pop( $lists ).">\n";
						$current_depth--;
					}

					$content .= $matches[1].'<li>'.$matches[4]."</li>\n";
					continue;
				}

				// Normal line.

				if( $current_depth )
				{ // We must go back to 0
					$content .= '</'.implode( ">\n</", $lists ).">\n";
					$lists = array();
					$current_depth = 0;
				}

				$content .= $line;
				if( $l < $lines_count - 1 )
				{	// Don't append a newline at the end, because it will create an unnecessary newline that didn't exist in source content
					$content .= "\n";
				}

			}
		}

		if( $current_depth )
		{ // We must go back to 0
			$content .= '</'.implode( ">\n</", $lists ).">\n";
		}

		return $content;
	}


	/**
	 * Parse code blocks to html view
	 *
	 * @param string Content
	 * @param string
	 */
	function escape_codeblock( $content )
	{
		$search = '/ %%%
			( \s*? \n )? 				# Eat optional blank line after %%%
			(.+?)
			( \n \s*? )? 				# Eat optional blank line before %%%
			%%%
		/sx'; // %%%escaped codeblock%%%

		return preg_replace_callback( $search, array( $this, 'escape_codeblock_callback' ), $content );
	}


	/**
	 * Callback function for code block parsing
	 *
	 * @param array Result of preg_replace function, @see $this->escape_codeblock()
	 * @return string
	 */
	function escape_codeblock_callback( $match )
	{
		return '<div class="codeblock"><pre><code>'
				.htmlspecialchars( stripslashes( $match[2] ), ENT_NOQUOTES )
			.'</code></pre></div>';
	}
}

?>