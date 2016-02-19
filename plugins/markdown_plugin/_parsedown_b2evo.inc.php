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
	public function text( $text )
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
	private function b2evo_html_decode_blockquote( $matches )
	{
		return str_replace( '&gt;', '>', $matches[0] );
	}


	/**
	 * Handle paragraph line
	 *
	 * @param string Line
	 */
	protected function paragraph( $Line )
	{
		if( $Line['text'] == '[teaserbreak]' || $Line['text'] == '[pagebreak]' )
		{	// Don't apply <p> around item content separators:
			return array(
					'element' => array(
						'name' => 'notag',
						'text' => $Line['text']
				) );
		}

		// Use standard preparing for other cases:
		return parent::paragraph( $Line );
	}


	/**
	 * Mark up element
	 *
	 * @param array Element
	 */
	protected function element( array $Element )
	{
		$r = '';

		if( isset( $Element['before'] ) )
		{	// Prepend additional text:
			$r .= $Element['before'];
		}

		if( isset( $Element['name'] ) && $Element['name'] == 'notag' )
		{	// Don't apply any html tag, Use simple text:
			if( isset( $Element['handler'] ) )
			{	// Use handler function:
				$r .= $this->{$Element['handler']}($Element['text']);
			}
			else
			{	// No handler, just text:
				$r .= $Element['text'];
			}
		}
		else
		{	// Use standard preparing for other cases:
			$r .= parent::element( $Element );
		}

		if( isset( $Element['after'] ) )
		{	// Append additional text:
			$r .= $Element['after'];
		}

		return $r;
	}


	/**
	 * Prepare each line on a list element
	 *
	 * @param array Line
	 * @param array Block
	 * @return array
	 */
	protected function blockListContinue( $Line, array $Block )
	{
		if( ! empty( $Block['interrupted'] ) )
		{	// If a list is interrupted now(for example, new line after list line),
			// then we should end this list in order to start one new if that exists:
			// Do nothing here in order to end the current list element.
		}
		else
		{	// Call standard preparing:
			return parent::blockListContinue( $Line, $Block );
		}
	}


	/**
	 * Callback after code block is completed
	 *
	 * @param array Block
	 * @return array Block
	 */
	protected function blockCodeComplete( $Block )
	{
		if( ! isset( $Block['element']['attributes'] ) )
		{	// Initialize attributes:
			$Block['element']['attributes'] = array();
		}

		// Append class "codeblock":
		if( isset( $Block['element']['attributes']['class'] ) )
		{
			$Block['element']['attributes']['class'] .= ' codeblock';
		}
		else
		{
			$Block['element']['attributes']['class'] = 'codeblock';
		}

		// Add these params for correct code detecting by codehighlight plugin:
		$element_attrs = 'line=1'; // set this param because codehighlight plugin doesn't detect language without this
		if( isset( $Block['element']['text']['attributes']['class'] ) &&
		    preg_match( '/language-([a-z]+)/i', $Block['element']['text']['attributes']['class'], $lang_match ) )
		{
			$element_attrs .= ' lang='.$lang_match[1];
			// Unset this because codehighlight plugin doesn't detect codeblock when tag <code> has any attributes:
			unset( $Block['element']['text']['attributes']['class'] );
		}
		$Block['element']['before'] = '<!-- codeblock '.$element_attrs.' -->';
		$Block['element']['after'] = '<!-- /codeblock -->'."\n";

		return $Block;
		// Don't call parent function because it encodes HMTL entities,
		// but we don't need this in b2evolution, because we have plugin "escape_code" for such purpose.
	}


	/**
	 * Callback after fenced code block is completed
	 *
	 * @param array Block
	 * @return array Block
	 */
	protected function blockFencedCodeComplete( $Block )
	{
		// Do same preparing as for normal code blocks:
		return $this->blockCodeComplete( $Block );
	}


	/**
	 * Inline code preparing
	 * NOTE: We rewrite this function completely and don't use of parent
	 *       because we don't want "htmlspecialchars()" and we need class "codespan"
	 *
	 * @param array Excerpt
	 * @return array Excerpt
	 */
	protected function inlineCode( $Excerpt )
	{
		$marker = $Excerpt['text'][0];

		if( preg_match( '/^('.$marker.'+)[ ]*(.+?)[ ]*(?<!'.$marker.')\1(?!'.$marker.')/s', $Excerpt['text'], $matches ) )
		{
			$text = $matches[2];
			$text = preg_replace( '/[ ]*\n/', ' ', $text );

			return array(
					'extent'  => strlen( $matches[0] ),
					'element' => array(
						'name'       => 'code',
						'text'       => $text,
						'attributes' => array( 'class' => 'codespan' )
					),
				);
		}
	}
}

?>