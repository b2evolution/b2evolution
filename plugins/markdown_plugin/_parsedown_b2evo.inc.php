<?php
/**
 * Parsedown b2evolution extession
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


class ParsedownB2evo extends ParsedownExtra
{
	protected $b2evo_parse_font_styles = true;
	protected $b2evo_parse_links = true;
	protected $b2evo_parse_images = true;


	/**
	 * Set flag to parse font styles
	 *
	 * @param boolean
	 */
	public function set_b2evo_parse_font_styles( $parse )
	{
		$this->b2evo_parse_font_styles = $parse;
	}


	/**
	 * Set flag to parse inline links
	 *
	 * @param boolean
	 */
	public function set_b2evo_parse_links( $parse )
	{
		$this->b2evo_parse_links = $parse;
	}


	/**
	 * Set flag to parse inline images
	 *
	 * @param boolean
	 */
	public function set_b2evo_parse_images( $parse )
	{
		$this->b2evo_parse_images = $parse;
	}


	/**
	 * Parse bold & italic font styles
	 *
	 * @param string Text
	 * @return string
	 */
	protected function inlineEmphasis( $Excerpt )
	{
		if( $this->b2evo_parse_font_styles )
		{	// Allow to parse font styles:
			return parent::inlineEmphasis( $Excerpt );
		}
		else
		{	// Don't parse font styles:
			return;
		}
	}


	/**
	 * Parse inline links
	 *
	 * @param string Text
	 * @return string
	 */
	protected function inlineLink( $Excerpt )
	{
		if( $this->b2evo_parse_links )
		{	// Allow to parse inline links:
			return parent::inlineLink( $Excerpt );
		}
		else
		{	// Don't parse inline links:
			return;
		}
	}


	/**
	 * Parse inline images
	 *
	 * @param string Text
	 * @return string
	 */
	protected function inlineImage( $Excerpt )
	{
		if( $this->b2evo_parse_images )
		{	// Allow to parse inline images:

			// Force a link parsing, because "inlineLink()" is used inside of "inlineImage()":
			$current_b2evo_parse_links = $this->b2evo_parse_links;
			$this->set_b2evo_parse_links( true );

			// Parse images:
			$r = parent::inlineImage( $Excerpt );

			// Revert a link parsing setting back:
			$this->set_b2evo_parse_links( $current_b2evo_parse_links );

			return $r;
		}
		else
		{	// Don't parse inline images:
			return;
		}
	}


	/**
	 * Parse text
	 *
	 * @param string Text
	 * @return string
	 */
	function text( $text )
	{
		if( strpos( $text, '&gt;' ) !== false )
		{	// Fix the encoded chars ">" to correct parsing of blockquote:
			$text = preg_replace_callback( '/(^|\n)((&gt; ?)+)/', array( $this, 'b2evo_html_decode_blockquote' ), $text );
		}

		if( strpos( $text, '&quot;' ) !== false )
		{	// Fix the encoded chars '"' to correct parsing of links and images:
			$text = preg_replace( '/\((.+)&quot;(.+)&quot;\)/', '($1"$2")', $text );
		}

		// Parse markdown code:
		$text = parent::text( $text );

		return $text;
	}


	/**
	 * Callback function to replace all encoded chars ">" from "&gt;"
	 *
	 * @param array Matches
	 * @return string
	 */
	function b2evo_html_decode_blockquote( $matches )
	{
		return str_replace( '&gt;', '>', $matches[0] );
	}
}

?>