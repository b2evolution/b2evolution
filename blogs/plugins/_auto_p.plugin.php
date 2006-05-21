<?php
/**
 * This file implements the Auto P plugin for b2evolution
 *
 * @author blueyed: Daniel HAHLER - {@link http://daniel.hahler.de/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * The Auto-P Plugin.
 *
 * It wraps text blocks, which are devided by newline(s) into HTML P tags (paragraphs)
 * and optionally replaces single newlines with BR tags (line breaks).
 *
 * @package plugins
 */
class auto_p_plugin extends Plugin
{
	var $code = 'b2WPAutP';
	var $name = 'Auto P';
	var $priority = 70;

	var $apply_rendering = 'opt-out';
	var $short_desc;
	var $long_desc;

	/**
	 * @var string List of block elements (we want a paragraph before and after)
	 */
	var $block_tags = 'table|thead|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|p|code|hr|fieldset|h[1-6]';


	/**
	 * Constructor
	 */
	function auto_p_plugin()
	{
		$this->short_desc = T_('Automatic &lt;P&gt; and &lt;BR&gt; tags');
		$this->long_desc = T_('No description available');
	}


	/**
	 * @return array
	 */
	function GetDefaultSettings()
	{
		return array(
				'br' => array(
					'label' => T_('Line breaks'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
					'note' => T_('Make line breaks (&lt;br /&gt;) for single newlines.'),
				),
			);
	}


	/**
	 * Perform rendering
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$content = preg_replace( "~(\r\n|\r)~", "\n", $content ); // cross-platform newlines

		$content = callback_on_non_matching_blocks( $content, '~<\s*(pre)[^>]*>.*?<\s*/\s*pre\s*>~is', array( &$this, 'autop' ) );

		return true;
	}


	/**
	 * This creates the P and BR tags. Used as callback and recurses.
	 *
	 * @return string
	 */
	function autop( $text, $recurse_info = array() )
	{
		$new_text = '';

		if( preg_match( '~^([^<]*)(<\s*('.$this->block_tags.')\b[^>]*>)~i', $text, $match ) )
		{
			$before_tag = $match[1];
			$tag = $match[3];

			if( ! empty($before_tag) )
			{
				$new_text .= $this->autop_text( $before_tag, $recurse_info );
			}

			// Opening tag:
			$new_text .= $match[2];

			$text_after_tag = substr( $text, strlen($match[0]) );

			// Find closing tag:
			if( preg_match( '~^(.*?)(<\s*/\s*'.$tag.'\s*>)~is', $text_after_tag, $after_match ) )
			{
				$text_in_tag = $after_match[1];
				$closing_tag = $after_match[2];

				$new_text .= $this->autop( $text_in_tag, array( 'tag' => $tag ) );

				$new_text .= $closing_tag;

				$text_after_tag = substr( $text_after_tag, strlen($text_in_tag)+strlen($closing_tag) );
			}

			if( trim($text_after_tag) != '' )
			{
				$new_text = $new_text.$this->autop( $text_after_tag );
			}
		}
		else
		{
			// make paragraphs in parts, splitted by opening or closing tags (messed up markup)
			$new_text = callback_on_non_matching_blocks( $text, '~<\s*/?\s*('.$this->block_tags.')\s*>~', array(&$this, 'autop_text'), array($recurse_info) );
		}

		return $new_text;
	}


	/**
	 * Callback that adds P tags to blocks around $text (exploded by \n\n) and lets
	 * {@link auto_p_plugin::autobr()} handle the text before.
	 *
	 * @return string
	 */
	function autop_text( $text, $recurse_info )
	{
		if( isset($recurse_info['tag']) && strtoupper($recurse_info['tag']) == 'P' )
		{ // Do not create P tags when we are already in a P tag
			return $text;
		}

		$text_lines = preg_split( '~\n\n+~', $text, -1, PREG_SPLIT_NO_EMPTY );

		if( count($text_lines) == 1 && ! preg_match( '~\n\s*\n$~', $text ) )
		{ // single block (without two or more newlines at the end): peeify, if it's in a blockquote or on the outer level:
			if( ! isset($recurse_info['tag']) || strtoupper($recurse_info['tag']) == 'BLOCKQUOTE' )
			{
				return '<p>'.$this->autobr( $text ).'</p>';
			}

			return $text;
		}

		// More than one line:
		$new_text = '';
		foreach( $text_lines as $k => $text_line )
		{
			if( ! empty($text_line) )
			{
				$new_text .= '<p>'.$this->autobr( $text_line ).'</p>';
			}
		}

		return $new_text;
	}


	/**
	 * Add "<br />" to the end of newlines, which do not end with "<br />" already and which aren't
	 * the last line, if the "Auto-BR" setting is enabled.
	 *
	 * @return string
	 */
	function autobr( $text )
	{
		if( ! $this->Settings->get('br') )
		{ // don't make <br />'s
			return $text;
		}

		return preg_replace( '~(?<!<br />)\s*\n(?!\z)~i', "<br />\n", $text );
	}


}


/*
 * $Log$
 * Revision 1.11  2006/05/21 01:42:39  blueyed
 * Fixed Auto-P Plugin
 *
 * Revision 1.10  2006/04/22 01:30:26  blueyed
 * Fix for HR, CODE and FIELDSET by balupton (http://forums.b2evolution.net/viewtopic.php?p=35709#35709)
 *
 * Revision 1.9  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>