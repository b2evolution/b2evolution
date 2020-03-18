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
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if( isset( $this->unmarkedBlockTypes ) &&
		    is_array( $this->unmarkedBlockTypes ) &&
		    ( $code_index = array_search( 'Code', $this->unmarkedBlockTypes ) ) !== false )
		{	// Don't parse code blocks by indent spaces or tab:
			unset( $this->unmarkedBlockTypes[ $code_index ] );
		}
	}


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
			$link_data = parent::inlineLink( $Excerpt );
			if( is_array( $link_data ) && isset( $link_data['element']['attributes']['href'] ) &&
			    preg_match( '/^(https?:\/\/|\/)/i', $link_data['element']['attributes']['href'] ) )
			{	// Allow link ONLY with URL which starts with "http://", "https://" or "/":
				return $link_data;
			}
			else
			{	// Deny wrong URL, because it may contains vulnerability code like "javascript:alert()":
				return;
			}
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
		if( ! empty( $Block['interrupted'] ) && empty( $Line['indent'] ) )
		{	// If a list is interrupted now(for example, new line after list line),
			// then we should end this list in order to start one new if that exists:
			// Do nothing here in order to end the current list element.
		}
		else
		{	// Call standard preparing:
			$before_list_items_num = ( isset( $Block['li']['text'] ) && is_array( $Block['li']['text'] ) ? count( $Block['li']['text'] ) : 0 );
			$Block = parent::blockListContinue( $Line, $Block );

			if( isset( $Line['body'] ) &&
			    isset( $Block['li']['text'] ) &&
			    is_array( $Block['li']['text'] ) )
			{	// If list has at least one item:
				$after_list_items_num = count( $Block['li']['text'] );
				if( $after_list_items_num > $before_list_items_num &&
				    $Block['li']['text'][ $after_list_items_num - 1 ] !== $Line['body'] )
				{	// If new list item was added in parent::blockListContinue() above,
					// We should use indent = 2 spaces instead of 4 spaces from parent class Parsedown:
					$Block['li']['text'][ $after_list_items_num - 1 ] = preg_replace( '/^[ ]{0,2}/', '', $Line['body'] );
				}
			}

			return $Block;
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

		// Append class "codeblock" and "line-numbers" for prism plugin:
		$Block['element']['attributes']['class'] = isset( $Block['element']['attributes']['class'] )
			? $Block['element']['attributes']['class'].' codeblock'
			: 'codeblock';

		// Add these params for correct code detecting by codehighlight plugin:
		$element_attrs = 'line=1'; // set this param because codehighlight plugin doesn't detect language without this
		if( isset( $Block['element']['text']['attributes']['class'] ) &&
		    preg_match( '/language-([a-z]+)/i', $Block['element']['text']['attributes']['class'], $lang_match ) )
		{
			$element_attrs .= ' lang='.$lang_match[1];
			// Unset class like "language-XXXX" to avoid rendering by Js of prism even when this plugin is not selected for rendered content:
			unset( $Block['element']['text']['attributes']['class'] );
		}
		$Block['element']['before'] = '<!-- codeblock '.$element_attrs.' -->';
		$Block['element']['after'] = '<!-- /codeblock -->'."\n";

		// Use special handler instead of parent::escape() to avoid htmlspecialchars():
		$Block['element']['text']['handler'] = 'noescapeCodeHandler';

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
	 * NOTE: We don't want "htmlspecialchars()" because we apply for all content by default in b2evo core
	 *       Also we need default class "codespan" for all <code> tags
	 *
	 * @param array Excerpt
	 * @return array Element data
	 */
	protected function inlineCode( $Excerpt )
	{
		// Use parent function and add two params below:
		$element_data = parent::inlineCode( $Excerpt );

		if( isset( $element_data['element'] ) )
		{	// Use special handler instead of parent::escape() to avoid htmlspecialchars():
			$element_data['element']['handler'] = 'noescapeCodeHandler';
			// Add default class for all <code> tags:
			$element_data['element']['attributes'] = array( 'class' => 'codespan' );
		}

		return $element_data;
	}


	/**
	 * Special handler for inline and block tags <code> to avoid default htmlspecialchars() from parent::escape()
	 *
	 * @param string Text
	 * @param array
	 * @return string
	 */
	function noescapeCodeHandler( $text, $nonNestables )
	{
		return $text;
	}


	/**
	 * Disable blocks/inlines in order to don't render them by parent classes
	 *
	 * @param array Names of the disabled blocks/inlines, e.g. 'Tables', 'Header', 'List' and etc.
	 */
	function disable_options( $disabled_options )
	{
		if( empty( $disabled_options ) )
		{	// No blocks to disable:
			return;
		}

		// Search and disable Block by name:
		foreach( $this->BlockTypes as $type_char => $types )
		{
			if( isset( $this->BlockTypes[ $type_char ] ) )
			{
				foreach( $types as $i => $type )
				{
					if( in_array( $type, $disabled_options ) )
					{
						unset( $this->BlockTypes[ $type_char ][ $i ] );
					}
				}
				if( empty( $this->BlockTypes[ $type_char ] ) )
				{
					unset( $this->BlockTypes[ $type_char ] );
				}
			}
		}

		// Search and disable Inline by name:
		foreach( $this->InlineTypes as $type_char => $types )
		{
			if( isset( $this->InlineTypes[ $type_char ] ) )
			{
				foreach( $types as $i => $type )
				{
					if( in_array( $type, $disabled_options ) )
					{
						unset( $this->InlineTypes[ $type_char ][ $i ] );
					}
				}
				if( empty( $this->InlineTypes[ $type_char ] ) )
				{
					unset( $this->InlineTypes[ $type_char ] );
				}
			}
		}
	}

	protected function li( $lines )
	{
		$markup = parent::li( $lines );
		// Remove newlines before embedded list,
		// in order to don't add unnecessary <p> e.g. by plugin "Auto P":
		return preg_replace( '#[\n\r]+<ul>#i', '<ul>', $markup );
	}


	/**
	 * Parse inline URL
	 *
	 * @param array Excerpt
	 * @return string
	 */
	protected function inlineUrl( $Excerpt )
	{
		$Inline = parent::inlineUrl( $Excerpt );

		if( isset( $Inline['element'] ) )
		{	// Force element type from <a> to simple text without html tag to avoid rendering of URLs as html links:
			$Inline['element']['name'] = 'notag';
			// NOTE: We cannot use `Parsedown->setUrlsLinked( false )` to disable rendering of URLs, because
			// in such case URLs may be broken by char `_` to `<em>` or by `__` to `<strong>` that we don't like.
		}

		return $Inline;
	}
}

?>