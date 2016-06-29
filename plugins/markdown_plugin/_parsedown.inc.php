<?php

#
#
# Parsedown
# http://parsedown.org
#
# (c) Emanuil Rusev
# http://erusev.com
#

/*
Modifications by yura:
	1. Fix <li> with second line
	2. Fix to properly work with <table>
	3. Remove "goto" operator because it starts since PHP >= 5.3.0
	4. Fix to display title of images and links
	5. Don't apply <p> around item content separators: [teaserbreak] and [pagebreak]
	6. Support code block with ```, example:
	    ```php
	    $x = $obj->method();
	    ```
	7. Split a list in two lists by paragraph after list element
	8. Support additional params(id and class) for headers. Examples:
	    - ###header {#header-id-value}                     => <h3 id="header-id-value">header</h3>
	    - ##header {.header-class-value}                   => <h2 class="header-class-value">header</h2>
	    - #header {.header-class-value#header-id-value}    => <h1 id="header-id-value" class="header-class-value">header</h1>
	    - ####header {#header-id-value.header-class-value} => <h4 class="header-class-value" id="header-id-value">header</h4>
	9. Don't apply <p> around list and already existing paragraph tags
	10. Don't convert HTML entities inside <code> html tags because the "Escape code" plugin does this
	11. Fix the missed empty lines in code blocks which are started and ended with ```
*/

class Parsedown
{
	#
	# Multiton (http://en.wikipedia.org/wiki/Multiton_pattern)
	#

	static function instance($name = 'default')
	{
		if (isset(self::$instances[$name]))
			return self::$instances[$name];

		$instance = new Parsedown();

		self::$instances[$name] = $instance;

		return $instance;
	}

	private static $instances = array();

	#
	# Fields
	#

	private $reference_map = array();
	private $escape_sequence_map = array();

	public $parse_links = true;
	public $parse_images = true;
	public $parse_font_styles = true;

	#
	# Public Methods
	#

	function parse($text)
	{
		# Removes UTF-8 BOM and marker characters.
		$text = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $text);

		# Removes \r characters.
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);

		# Replaces tabs with spaces.
		$text = str_replace("\t", '    ', $text);

		# Encodes escape sequences.
		/* erhsatingin> This is what causes the escaping issues later down the line
		if (strpos($text, '\\') !== FALSE)
		{
			$escape_sequences = array('\\\\', '\`', '\*', '\_', '\{', '\}', '\[', '\]', '\(', '\)', '\>', '\#', '\+', '\-', '\.', '\!');

			foreach ($escape_sequences as $index => $escape_sequence)
			{
				if (strpos($text, $escape_sequence) !== FALSE)
				{
					$code = "\x1A".'\\'.$index;

					$text = str_replace($escape_sequence, $code, $text);

					$this->escape_sequence_map[$code] = $escape_sequence;
				}
			}
		}
		*/

		# Extracts link references.

		if (preg_match_all('/^[ ]{0,3}\[(.+)\][ ]?:[ ]*\n?[ ]*(.+)$/m', $text, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $matches)
			{
				$this->reference_map[strtolower($matches[1])] = $matches[2];

				$text = str_replace($matches[0], '', $text);
			}
		}

		# ~

		$text = preg_replace('/\n\s*\n/', "\n\n", $text);
		$text = trim($text, "\n");

		$lines = explode("\n", $text);

		$text = $this->parse_block_elements($lines);

		# Decodes escape sequences (leaves out backslashes).

		foreach ($this->escape_sequence_map as $code => $escape_sequence)
		{
			$text = str_replace($code, $escape_sequence[1], $text);
		}

		$text = rtrim($text, "\n");

		return $text;
	}

	#
	# Private Methods
	#

	private function parse_block_elements( array $lines, $context = '', & $return_data = NULL )
	{
		$elements = array();

		$element = array(
			'type' => '',
		);

		foreach ($lines as $line)
		{
			# Empty

			if( $line === '' )
			{
				$element['interrupted'] = true;

				if( $element['type'] === 'code' )
				{	// Don't miss empty lines in code blocks where each line is started with 4 spaces:
					$element['text'] .= "\n";
				}

				if( $element['type'] === 'codeblock' )
				{	// Don't miss empty lines in code blocks which are started and ended with ```:
					$element['lines'][] = $line;
				}

				continue;
			}

			# Codeblock
			if( $element['type'] != 'codeblock' && preg_match('/^```([a-z0-9]+)*$/i', $line, $matches ) )
			{ // Codeblock is opening
				if( ! empty( $element['type'] ) )
				{ // Save any previous element that has been started but not finished yet
					$elements[] = $element;
				}
				// Start new codeblock element
				$element = array(
						'type' => 'codeblock',
						'lang' => empty( $matches[1] ) ? '' : strtolower( $matches[1] ),
						'lines' => array(),
					);
				continue;
			}
			if( $element['type'] == 'codeblock' )
			{
				if( preg_match('/^```$/i', $line ) )
				{ // Codeblock is closing
					$elements[] = $element;
					$element = array( 'type' => '' );
				}
				else
				{ // Save each line of codeblock
					$element['lines'][] = $line;
				}
				continue;
			}

			# Lazy Blockquote

			if ($element['type'] === 'blockquote' and ! isset($element['interrupted']))
			{
				$line = preg_replace('/^[ ]*(>|&gt;)[ ]?/', '', $line);

				$element['lines'] []= $line;

				continue;
			}

			# Lazy List Item

			if ($element['type'] === 'li')
			{
				if (preg_match('/^([ ]{0,3})(\d+[.]|[*+-])[ ](.*)/', $line, $matches))
				{
					if ($element['indentation'] !== $matches[1])
					{
						$element['lines'] []= $line;
					}
					else
					{
						unset($element['last']);

						$elements []= $element;

						$element = array(
							'type' => 'li',
							'indentation' => $matches[1],
							'last' => true,
							'lines' => array(
								preg_replace('/^[ ]{0,4}/', '', $matches[3]),
							),
						);
					}

					continue;
				}

				if (isset($element['interrupted']))
				{
					if ($line[0] === ' ')
					{
						$element['lines'] []= '';

						$line = preg_replace('/^[ ]{0,4}/', '', $line);;

						$element['lines'] []= $line;

						continue;
					}
				}
				elseif( ! preg_match( '/^[\s\t]*<\//', $line ) )
				{ // Don't append the second line if it is an end of HTML tag
					$line = preg_replace('/^[ ]{0,4}/', '', $line);;

					$element['lines'] []= $line;

					continue;
				}
			}

			# Quick Paragraph

			if( ! ( $line[0] >= 'A' and $line['0'] !== '_' ) )
			{ //

				# Setext Header (---)

				if ($element['type'] === 'p' and ! isset($element['interrupted']) and preg_match('/^[-]+[ ]*$/', $line))
				{
					$element['type'] = 'h.';
					$element['level'] = 2;

					continue;
				}

				# Horizontal Rule

				if (preg_match('/^[ ]{0,3}([-*_])([ ]{0,2}\1){2,}[ ]*$/', $line))
				{
					$elements []= $element;

					$element = array(
						'type' => 'hr',
					);

					continue;
				}

				# List Item

				if (preg_match('/^([ ]{0,3})(\d+[.]|[*+-])[ ](.*)/', $line, $matches))
				{
					$elements []= $element;

					$element = array(
						'type' => 'li',
						'ordered' => isset($matches[2][1]),
						'indentation' => $matches[1],
						'last' => true,
						'lines' => array(
							preg_replace('/^[ ]{0,4}/', '', $matches[3]),
						),
					);

					continue;
				}

				# Code

				if (preg_match('/^[ ]{4}(.*)/', $line, $matches))
				{
					if ($element['type'] === 'code')
					{
						$element['text'] .= "\n".$matches[1];
					}
					else
					{
						$elements []= $element;

						$element = array(
							'type' => 'code',
							'text' => $matches[1],
						);
					}

					continue;
				}

				# Atx Header (#)

				if ($line[0] === '#' and preg_match('/^(#{1,6}) (.+?)[ ]*#*$/', $line, $matches))
				{
					$elements []= $element;

					$level = strlen($matches[1]);

					$element = array(
						'type' => 'h.',
						'text' => $matches[2],
						'level' => $level,
					);

					continue;
				}

				# Blockquote

				if (preg_match('/^[ ]*(>|&gt;)[ ]?(.*)/', $line, $matches))
				{
					if ($element['type'] === 'blockquote')
					{
						if (isset($element['interrupted']))
						{
							$element['lines'] []= '';

							unset($element['interrupted']);
						}

						$element['lines'] []= $matches[2];
					}
					else
					{
						$elements []= $element;

						$element = array(
							'type' => 'blockquote',
							'lines' => array(
								$matches[2],
							),
						);
					}

					continue;
				}

				# Setext Header (===)

				if ($element['type'] === 'p' and ! isset($element['interrupted']) and preg_match('/^[=]+[ ]*$/', $line))
				{
					$element['type'] = 'h.';
					$element['level'] = 1;

					continue;
				}

			}

			if ($element['type'] === 'p')
			{
				if (isset($element['interrupted']))
				{
					$elements []= $element;

					$element['text'] = $line;

					unset($element['interrupted']);
				}
				else
				{
					$element['text'] .= "\n".$line;
				}
			}
			else
			{
				$elements []= $element;

				$element = array(
					'type' => 'p',
					'text' => $line,
				);
			}
		}

		$elements []= $element;

		array_shift($elements);

		#
		# ~
		#

		$markup = '';

		foreach ($elements as $index => $element)
		{
			switch ($element['type'])
			{
				case 'li':

					if( isset( $element['ordered'] ) || isset( $return_data['last'] ) ) # first
					{ // Start new list if it is new list or if previous was interrupted with paragraph
						$list_type = ! empty( $element['ordered'] ) ? 'ol' : 'ul';

						$markup .= '<'.$list_type.'>'."\n";
					}

					if( isset( $element['interrupted'] ) && ! isset( $element['last'] ) )
					{
						$element['lines'] []= '';
					}

					$return_data = array();
					$text = $this->parse_block_elements( $element['lines'], 'li', $return_data );

					$markup .= '<li>'.$text.'</li>'."\n";

					if( isset( $element['last'] ) || isset( $return_data['last'] ) )
					{ // End list tag if it is last item or it is interrupted list with paragraph
						$markup .= '</'.$list_type.'>'."\n";
					}

					break;

				case 'p':

					$text = $this->parse_inline_elements($element['text']);

					$text = preg_replace('/[ ]{2}\n/', '<br />'."\n", $text);

					if( $text == '[teaserbreak]' || $text == '[pagebreak]' )
					{ // Don't apply <p> around item content separators
						$markup .= $text."\n";
					}
					elseif( preg_match( '~^<(ul|ol|li|p)~i', $text ) || preg_match( '~</(ul|ol|li|p)>$~i', $text ) )
					{ // Don't apply <p> around list and already existing paragraph tags
						$markup .= $text;
					}
					elseif( $context === 'li' && $index === 0 )
					{
						$markup .= $text;
						if( isset( $element['interrupted'] ) )
						{ // End current list instead of <p> inside <li>
							$return_data = array( 'last' => true );
						}
					}
					else
					{
						if( preg_match( '/^<table.+<\\/table>$/is', $text ) )
						{ // Apply <p> tag around full table
							$markup .= '<p>'.$text.'</p>'."\n";
						}
						elseif( preg_match( '/(.*<td>)(.+)/is', $text, $text_match ) &&
						      ! preg_match( '#(</?table|</?tr|</?th|</?td)#is', $text_match[2] ) )
						{ // Apply <p> tag for content of <td>
							$markup .= $text_match[1].'<p>'.$text_match[2].'</p>'."\n";
						}
						elseif( ! preg_match( '#(</?table|</?tr|</?th|</?td)#i', $text ) )
						{ // Apply <p> tag for text that is not a part of a <table>
							if( count( $elements ) > 1 )
							{ // Only if a content contains many elements
								$markup .= '<p>'.$text.'</p>'."\n";
							}
							else
							{ // Don't add <p> tag around whole content
								$markup .= $text;
							}
						}
						else
						{ // Don't apply <p> tag
							$markup .= $text."\n";
						}
					}

					break;

				case 'code':

					$text = rtrim($element['text'], "\n");

					strpos($text, "\x1A\\") !== FALSE and $text = strtr($text, $this->escape_sequence_map);

					$markup .= '<pre class="codeblock"><code>'.$text.'</code></pre>'."\n";

					break;

				case 'blockquote':

					$text = $this->parse_block_elements($element['lines']);

					$markup .= '<blockquote>'."\n".$text.'</blockquote>'."\n";

					break;

				case 'codeblock':
					// Codeblock
					$attrs = empty( $element['lang'] ) ? '' : ' lang='.$element['lang'];
					$attrs .= ' line=1'; // set this param because codehighlight plugin doesn't detect language without this
					$text = implode( "\n", $element['lines'] );
					$markup .= '<!-- codeblock '.$attrs.'--><pre class="codeblock"><code>'."\n".$text."\n".'</code></pre><!-- /codeblock -->'."\n";
					break;

				case 'h.':

					$text = $this->parse_inline_elements($element['text']);

					$markup .= '<h'.$element['level'].'>'.$text.'</h'.$element['level'].'>'."\n";

					break;

				case 'hr':

					$markup .= '<hr />'."\n";

					break;
			}
		}

		return $markup;
	}

	private function parse_inline_elements($text)
	{
		$map = array();

		$index = 0;

		# Code Span

		if (strpos($text, '`') !== FALSE and preg_match_all('/`(.+?)`/', $text, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $matches)
			{
				$element_text = $matches[1];

				# Decodes escape sequences.

				$this->escape_sequence_map
					and strpos($element_text, "\x1A") !== FALSE
					and $element_text = strtr($element_text, $this->escape_sequence_map);

				# Composes element.

				$element = '<code class="codespan">'.$element_text.'</code>';

				# Encodes element.

				$code = "\x1A".'$'.$index;

				$text = str_replace($matches[0], $code, $text);

				$map[$code] = $element;

				$index ++;
			}
		}

		if( $this->parse_images || $this->parse_links )
		{ // Parse images or links

			# Inline Link / Image
			if( strpos($text, '](') !== FALSE ) # inline
			{
				$text = str_replace( '&quot;', '"', $text ); // revert from html entity
				if( preg_match_all( '/(!?)(\[((?:[^][]+|(?2))*)\])\(([^"]*?)( "([^"]+)")?\)/', $text, $matches, PREG_SET_ORDER ) )
				{
					foreach ($matches as $matches)
					{
						if ($matches[1]) # image
						{
							if( $this->parse_images )
							{ // Parse images only if it is enabled
								$element = '<img src="'.$matches[4].'" alt="'.$matches[3].'"'.( ! empty( $matches[6] ) ? ' title="'.$matches[6].'"' : '' ).'>';
							}
						}
						else
						{
							if( $this->parse_links )
							{ // Parse links only if it is enabled
								$element_text = $this->parse_inline_elements($matches[3]);
								$element = '<a href="'.$matches[4].'"'.( ! empty( $matches[6] ) ? ' title="'.$matches[6].'"' : '' ).'>'.$element_text.'</a>';
							}
						}

						if( ! isset( $element ) )
						{
							continue;
						}

						$element_text = $this->parse_inline_elements($matches[1]);

						# ~

						$code = "\x1A".'$'.$index;

						$text = str_replace($matches[0], $code, $text);

						$map[$code] = $element;

						$index ++;
						unset( $element );
					}
				}
			}

			# Reference(d) Link / Image

			if ($this->reference_map and strpos($text, '[') !== FALSE and preg_match_all('/(!?)\[(.+?)\](?:\n?[ ]?\[(.*?)\])?/ms', $text, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as $matches)
				{
					$link_difinition = isset($matches[3]) && $matches[3]
						? $matches[3]
						: $matches[2]; # implicit

					$link_difinition = strtolower($link_difinition);

					if (isset($this->reference_map[$link_difinition]))
					{
						$url = $this->reference_map[$link_difinition];

						if ($matches[1]) # image
						{
							if( $this->parse_images )
							{ // Parse images only if it is enabled
								$element = '<img alt="'.$matches[2].'" src="'.$url.'">';
							}
						}
						else # anchor
						{
							if( $this->parse_links )
							{ // Parse links only if it is enabled
								$element_text = $this->parse_inline_elements($matches[2]);
								$element = '<a href="'.$url.'">'.$element_text.'</a>';
							}
						}

						if( ! isset( $element ) )
						{
							continue;
						}

						# ~

						$code = "\x1A".'$'.$index;

						$text = str_replace($matches[0], $code, $text);

						$map[$code] = $element;

						$index ++;
						unset( $element );
					}
				}
			}

			if( $this->parse_links )
			{ // Parse links only if it is enabled
				if (strpos($text, '<') !== FALSE and preg_match_all('/<((https?|ftp|dict):[^\^\s]+?)>/i', $text, $matches, PREG_SET_ORDER))
				{
					foreach ($matches as $matches)
					{
						$element = '<a href=":href">:text</a>';
						$element = str_replace(':text', $matches[1], $element);
						$element = str_replace(':href', $matches[1], $element);

						# ~

						$code = "\x1A".'$'.$index;

						$text = str_replace($matches[0], $code, $text);

						$map[$code] = $element;

						$index ++;
					}
				}
			}
		}

		if( $this->parse_font_styles )
		{ // Parse bold & italic styles only if it is enabled
			// Render the font style out side html tag attributes
			$text = callback_on_non_matching_blocks( $text,
				'~<[^>]+>~i',
				array( $this, 'render_font_styles_callback' ) );
		}

		$text = strtr( $text, $map );

		return $text;
	}


	/**
	 * Callback functiob to render the font style out side html tag attributes
	 *
	 * @param string Text
	 * @return string Text
	 */
	function render_font_styles_callback( $text )
	{
		$text = preg_replace( '/(__|\*\*)(?=\S)(.+?)(?<=\S)\1/', '<strong>$2</strong>', $text );
		$text = preg_replace( '/(_|\*)(?=\S)(.+?)(?<=\S)\1/', '<em>$2</em>', $text );
		return $text;
	}
}

?>