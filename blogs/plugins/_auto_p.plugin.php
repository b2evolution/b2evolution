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
	var $version = '1.8.1-dev';
	var $apply_rendering = 'opt-out';
	var $short_desc;
	var $long_desc;

	/**
	 * @var string List of block elements (we want a paragraph before and after), excludes: address, added: td, th
	 */
	var $block_tags = 'blockquote|dd|div|dl|dt|fieldset|form|h[1-6]|hr|li|ol|p|pre|select|table|td|th|ul';


	var $p_allowed_in = array('address', 'applet', 'blockquote', 'body', 'button', 'center', 'dd', 'del', 'div', 'fieldset', 'form', 'iframe', 'ins', 'li', 'map', 'noframes', 'noscript', 'object', 'td', 'th' );


	var $br_allowed_in = array(
		// Block level:
		'address', 'center', 'dl', 'dir', 'div', 'fieldset', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'isindex', 'menu', 'noframes', 'noscript', 'ol', 'p', 'pre', 'ul',
		// Inline:
		'a', 'abbr', 'acronym', 'applet', 'b', 'basefont', 'bdo', 'big', 'button', 'cite', 'code', 'dfn', 'em', 'font', 'i', 'img', 'input', 'iframe', 'kbd', 'label', 'map', 'object', 'q', 'samp', 'script', 'select', 'small', 'span', 'strong', 'sub', 'sup', 'textarea', 'td', 'th', 'tt', 'var' );


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Automatic &lt;P&gt; and &lt;BR&gt; tags');
		$this->long_desc = T_('This renderer will automatically detect paragraphs on double line-breaks. and mark them with appropriate HTML &lt;P&gt; tags.<br />
			Optionally, it will also mark single line breaks with HTML &lt;BR&gt; tags.');
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
				'add_p_in_block' => array(
					'label' => T_('Add P tags in blocks (e.g. DIV)'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
					'note' => '',
				),
				'skip_tags' => array(
					'label' => T_('Ignore tags'),
					'type' => 'input',
					'defaultvalue' => 'pre',
					'note' => T_('A list of tags, in which no P or BR tags should get added.'),
				),
			);
	}


	/**
	 * Perform rendering
	 */
	function RenderItemAsHtml( & $params )
	{
		#echo '<hr style="border:1em solid blue;" />';

		$content = & $params['data'];

		$this->use_auto_br = $this->Settings->get('br');
		$this->add_p_in_block = $this->Settings->get('add_p_in_block');
		$this->skip_tags = preg_split( '~\s+~', $this->Settings->get('skip_tags'), -1, PREG_SPLIT_NO_EMPTY );

		$content = preg_replace( "~(\r\n|\r)~", "\n", $content ); // cross-platform newlines

		$pre = '';
		if( substr($content, 0, 1) == "\n" )
		{ // a leading newline is always a paragraph:
			if( substr($content, 0, 2) == "\n\n" )
			{ // two newlines are a full-ass paragraph:
				$pre = "<p></p>\n\n";
				$content = substr($content, 2);
			}
			else
			{
				$pre = "<p></p>\n";
				$content = substr($content, 1);
			}
		}

		$content = $pre.$this->handle_blocks( $content, NULL );

		return true;
	}


	/**
	 * - Split text into blocks, using $block_tags pattern.
	 *
	 * @return string
	 */
	function handle_blocks( $text, $in_tag )
	{
		#echo '<h1>LEVEL=0 (block)</h1>'; pre_dump( $text, $in_tag );

		$new_text = '';

		if( preg_match( '~^(.*?)(<\s*('.$this->block_tags.')(\s+[^>]*)?>)~is', $text, $match ) )
		{ // there's a block tag:
			$tag = $match[3];
			$before_tag = $match[1];

			if( ! empty($before_tag) )
			{ // Recurse (one pattern/callback deeper):

				if( $before_tag != "\n" && substr($before_tag, -1) == "\n" )
				{ // a newline before the block tag gets preserved
					$NL_before_tag = "\n";
					$before_tag = substr($before_tag, 0, -1);
				}
				else
				{
					$NL_before_tag = '';
				}

				$new_text .= $this->handle_pre_blocks( $before_tag, $in_tag );
				$new_text .= $NL_before_tag;
			}


			// Opening tag:
			$new_text .= $match[2];

			$text_after_tag = substr( $text, strlen($match[0]) );

			// Find closing tag:
			list( $text_in_tag, $closing_tag, $NL_before, $NL_after ) = $this->split_text_for_tag($tag, $text_after_tag);

			if( ! empty($text_in_tag) )
			{ // Recurse (same level):
				$text_in_tag = $this->handle_blocks( $text_in_tag, $tag );
			}

			$new_text .= $NL_before.$text_in_tag.$NL_after;

			$new_text .= $closing_tag;

			if( ! empty($text_after_tag) )
			{
				#echo '<h1>RECURSE: text_after_tag (block)</h1>';
				// Recurse (same level):
				$new_text .= $this->handle_blocks( $text_after_tag, $in_tag );
			}
		}
		else
		{ // No BLOCKS in this $text:
			$new_text = $this->handle_pre_blocks($text, $in_tag);

		}

		#pre_dump( 'HANDLE_BLOCKS return: ', $new_text, $in_tag );
		return $new_text;
	}


	/**
	 * Handle text which may contain inline tags
	 *
	 * - Explode by \n\n
	 * - Merge blocks that span over multiple tags
	 * - Apply BR to blocks
	 * - Wrap block in P
	 *
	 * @return string
	 */
	function handle_pre_blocks( $text, $in_tag )
	{
		#echo '<h2>HANDLE_PRE_BLOCKS</h2>'; pre_dump( $text );

		if( $in_tag )
		{
			if( in_array($in_tag, $this->skip_tags) )
			{
				return $text;
			}

			if( ! in_array($in_tag, $this->p_allowed_in) )
			{ // we're in a tag, where no P tags are allowed, so just do the BRs:
				if( in_array($in_tag, $this->br_allowed_in) )
				{
					return $this->handle_br( $text, $in_tag );
				}
				else
				{
					return $text;
				}
			}
		}

		$text_lines = preg_split( '~(\n\n)~', $text, -1, /*PREG_SPLIT_NO_EMPTY |*/ PREG_SPLIT_DELIM_CAPTURE );
		$text_lines[] = ''; // dummy

		#echo '<strong>text_lines</strong><br />'; pre_dump( $text_lines, $in_tag );

		// fix it, so no (inline) tags span across multiple blocks:
		$new_blocks = array();
		$new_blocks_nowrap = array(); // blocks that should not be wrapped in P (opening without closing tag or vice versa)
		$count_new_blocks = 0;
		$looking_for_close_tag = array();

		for( $i = 0, $n = count($text_lines); $i < $n; $i = $i+2 /* every 2nd line is a real block */ )
		{
			$line = $text_lines[$i];
			if( ! isset($new_blocks[$count_new_blocks]) )
			{
				$new_blocks[$count_new_blocks] = '';

				$line_copy = $line;
				preg_match( '~^(.*?)(<\s*/\s*(\w+)(\s+[^>]*?)?>)~is', $line_copy, $match );

				while( preg_match( '~^(.*?)(<\s*/\s*(\w+)(\s+[^>]*?)?>)~is', $line_copy, $match )
					&& ! (preg_match( '~^(.*?)(<\s*'.$match[3].'(\s+[^>]*?(\s*/\s*)?)?>)~is', $new_blocks[$count_new_blocks].$match[1] )) )
				{ // a closing tag:
					$new_blocks[$count_new_blocks] .= $match[0];
					$line_copy = substr($line_copy, strlen($match[0]));
				}
				if( ! empty($new_blocks[$count_new_blocks]) )
				{ // we've found a closing tag with no opening tag, this must not get wrapped in P:
					$new_blocks_nowrap[] = $count_new_blocks;
					$new_blocks[$count_new_blocks+1] = ''; // dummy
					$new_blocks[$count_new_blocks+2] = ''; // init new
					$line = substr( $line, strlen($new_blocks[$count_new_blocks]) );
					$count_new_blocks = $count_new_blocks+2;
				}
			}
			else
			{ // we're looking for a closing tag:
				// Find closing tag:
				$line_copy = $line;
				list( $text_in_tag, $closing_tag, $NL_before, $NL_after ) = $this->split_text_for_tag( $looking_for_close_tag['tag'], $line_copy /* by ref */ );
				if( empty($closing_tag) )
				{ // not in this whole block:
					$new_blocks[ $count_new_blocks ] .= $line.$text_lines[$i+1];
					continue;
				}

				// Tag has been found:
				$looking_for_close_tag = array();
			}

			if( preg_match( '~^(.*?)(<\s*(\w+)(\s+[^>]*?(\s*/\s*)?)?>)~is', $line, $match ) )
			{ // a opening tag:
				$tag = $match[3];
				$pos_after_tag = strlen($match[0]);
				if( ! empty($match[5]) )
				{ // self-closing tag, find next:
					$tag = false;
					while( ! empty($match[5]) )
					{
						if( preg_match( '~^(.*?)(<\s*(\w+)(\s+[^>]*)?(\s*/\s*)?>)~is', substr($line, $pos_after_tag), $match ) )
						{
							$tag = $match[3];
							$pos_after_tag += strlen($match[0]);
						}
					}
				}
				$text_after_tag = substr($line, $pos_after_tag);

				if( $tag )
				{
					// Find closing tag:
					list( $text_in_tag, $closing_tag, $NL_before, $NL_after ) = $this->split_text_for_tag($tag, $text_after_tag /* by ref */ );
					if( empty($closing_tag) )
					{
						$looking_for_close_tag = array(
								'tag' => $tag,
								'block' => $i,
								'pos' => strlen($new_blocks[ $count_new_blocks ])+strlen($match[1]), // position where the unclosed tag begins
							);
						$new_blocks[ $count_new_blocks ] .= $line.$text_lines[$i+1];
						continue;
					}
				}
			}
			$new_blocks[ $count_new_blocks ] .= $line;
			$new_blocks[ ++$count_new_blocks ] = $text_lines[$i+1];

			$count_new_blocks++;
		}
		if( $looking_for_close_tag )
		{
			#echo '<h1>looking_for_close_tag</h1>'; pre_dump( $looking_for_close_tag );
			$new_blocks[ $count_new_blocks+1 ] = ''; // dummy

			if( $looking_for_close_tag['pos'] > 0 )
			{ // move part of last block without closing tag to an own block:
				$new_blocks[ $count_new_blocks+2 ] = substr($new_blocks[ $count_new_blocks ], $looking_for_close_tag['pos']);
				$new_blocks[ $count_new_blocks ] = substr($new_blocks[ $count_new_blocks ], 0, $looking_for_close_tag['pos']);
				$new_blocks[ $count_new_blocks+3 ] = ''; // dummy

				$new_blocks_nowrap[] = $count_new_blocks+2;
			}
		}

		#echo '<h1>new_blocks:</h1>'; pre_dump( $new_blocks, $new_blocks_nowrap );

		$after_block_wp = '';
		$before_block_wp = '';
		if( empty($in_tag) )
		{
			$wrap_in_p = true;
		}
		elseif( in_array($in_tag, $this->p_allowed_in) )
		{
			if( ! $this->add_p_in_block )
			{
				$wrap_in_p = false;
			}
			elseif( count($new_blocks) > 2 )
			{
				$wrap_in_p = true;
			}
			else
			{
				$wrap_in_p = false;
				if( substr( $new_blocks[0], 0, 1 ) == "\n" )
				{
					$before_block_wp = "\n";
					$new_blocks[0] = substr( $new_blocks[0], 1 );
					$wrap_in_p = true;
				}
				if( substr( $new_blocks[0], -1 ) == "\n" )
				{
					$after_block_wp = "\n";
					$new_blocks[0] = substr( $new_blocks[0], 0, -1 );
					$wrap_in_p = true;
				}
			}
		}
		else
		{
			$wrap_in_p = false;
		}

		if( $new_blocks[count($new_blocks)-2] == '' )
		{
			array_pop($new_blocks);
			array_pop($new_blocks);
		}

		$new_text = '';

		for( $i = 0, $n = count($new_blocks); $i < $n; $i = $i+2 )
		{
			#echo '<h2>new_blocks['.$i.']: '.htmlspecialchars($new_blocks[$i]).'</h2>';

			$this_wrap_in_p = $wrap_in_p && ! in_array( $i, $new_blocks_nowrap ); // only wrap this, if it's a valid block

			if( empty($new_blocks[$i]) )
			{
				if( $this_wrap_in_p )
				{ // not the last one
					$block = '<p></p>';
				}
				else
				{
					$block = '';
				}
				$new_text .= $before_block_wp.$block.$after_block_wp.$new_blocks[$i+1];
				continue;
			}

			list($new_block, $has_p) = $this->handle_pre_blocks_helper( $new_blocks[$i], $in_tag );

			if( ! $has_p && $this_wrap_in_p )
			{
				$new_block = '<p>'.$new_block.'</p>';
			}

			$new_text .= $before_block_wp.$new_block.$after_block_wp.$new_blocks[$i+1];
		}

		#pre_dump( 'HANDLE_PRE_BLOCKS return: ', $new_text, $in_tag );
		return $new_text;
	}


	/**
	 *
	 *
	 * @return
	 */
	function handle_pre_blocks_helper( $block, $in_tag )
	{
		$has_p = NULL;
		$r = '';

		if( preg_match( '~^(.*?)(<\s*(\w+)(\s+[^>]*)?>)~is', $block, $match ) )
		{ // a tag:
			$tag = $match[3];
			$before_tag = $match[1];

			if( ! empty($before_tag) )
			{ // Delegate to handle_br:
				$r .= $this->handle_br( $before_tag, $in_tag );
			}

			// Opening tag:
			$r .= $match[2];

			$text_after_tag = substr( $block, strlen($match[0]) );

			// Find closing tag:
			list( $text_in_tag, $closing_tag, $NL_before, $NL_after ) = $this->split_text_for_tag($tag, $text_after_tag);

			if( ! empty($text_in_tag) )
			{ // Recurse (same level):
				if( $in_tag == 'blockquote' /* XHTML strict: blockquote content needs to be in block tag */ )
				{
					$text_in_tag = '<p>'.$text_in_tag.'</p>';
					$has_p = true;
					$tag = 'p';
				}
				list($text_in_tag, $sub_has_p) = $this->handle_pre_blocks_helper( $text_in_tag, $tag );
			}
			$r .= $NL_before.$text_in_tag.$NL_after;

			$r .= $closing_tag;

			if( ! empty($text_after_tag) )
			{
				#echo '<h1>RECURSE: text_after_tag (handle_pre_blocks)</h1>';
				// Recurse (same level):
				list( $text_after_tag, $sub_has_p ) = $this->handle_pre_blocks_helper( $text_after_tag, $in_tag );
				$r .= $text_after_tag;
			}
		}
		else
		{ // No tags in this $text:
			if( $in_tag == 'blockquote' /* XHTML strict: blockquote content needs to be in block tag */ )
			{
				$block = '<p>'.$block.'</p>';
				$has_p = true;
				$r .= $this->handle_br( $block, 'p' );
			}
			else
			{
				$r .= $this->handle_br( $block, $in_tag );
			}
		}

		return array( $r, $has_p );
	}


	/**
	 * Handles adding BR.
	 *
	 * @return string
	 */
	function handle_br( $text, $in_tag )
	{
		#echo '<h3>LEVEL>1 (BR)</h3>'; pre_dump( $text );

		if( empty($in_tag) || in_array($in_tag, $this->br_allowed_in) )
		{
			$new_text = $this->autobr($text);
		}
		else
		{
			$new_text = $text;
		}

		#pre_dump( 'HANDLE_BR return: ', $new_text, $in_tag );
		return $new_text;
	}


	/**
	 * Split the text for a given tag, mainly to find the closing tag.
	 *
	 * @return array
	 */
	function split_text_for_tag($tag, & $text_after_tag)
	{
		#echo '<strong>split_text_for_tag</strong><br />'; pre_dump( $tag, $text_after_tag );
		$depth = 1;
		$text_in_tag = '';

		$loop_text = $text_after_tag;
		while( 1 )
		{
			if( preg_match( '~^(.*?)(<\s*(/)?\s*'.$tag.'\s*(/\s*)?>\n?)~is', $loop_text, $after_match ) )
			{
				#pre_dump( 'after_match', $after_match );
				$text_in_tag .= $after_match[1];
				$found_tag = $after_match[2];
				$is_closing = ( ! empty($after_match[3]) || ! empty($after_match[4]) /* self-closing */ );

				if( $is_closing )
				{
					$depth--;
					if( $depth == 0 )
					{ // found the matching closing tag:
						$closing_tag = $found_tag;
						break;
					}
				}
				else
				{ // found the same, but opening tag (nested)
					$text_in_tag .= $found_tag;
					$depth++;
				}

				// skip what we've matched:
				$loop_text = substr($loop_text, strlen($after_match[0])-1 );
			}
			else
			{ // did not find the closing tag.. :/
				$closing_tag = '';
				return array( false, false, false, false );
			}
		}

		if( substr($text_in_tag, 0, 1) == "\n" )
		{
			$NL_before = "\n";
			$text_in_tag = substr($text_in_tag, 1);
		}
		else
		{
			$NL_before = '';
		}
		if( substr($text_in_tag, -1) == "\n" )
		{
			$NL_after = "\n";
			$text_in_tag = substr($text_in_tag, 0, -1);
		}
		else
		{
			$NL_after = '';
		}

		$text_after_tag = substr( $text_after_tag, strlen($NL_before.$text_in_tag.$NL_after)+strlen($closing_tag) );
		$r = array( $text_in_tag, $closing_tag, $NL_before, $NL_after );

		return $r;
	}


	/**
	 * Add "<br />" to the end of newlines, which do not end with "<br />" already and which aren't
	 * the last line, if the "Auto-BR" setting is enabled.
	 *
	 * @return string
	 */
	function autobr( $text, $replace_last = true )
	{
		if( ! $this->use_auto_br )
		{ // don't make <br />'s
			return $text;
		}

		return preg_replace( '~(?<!<br />)\n'.( $replace_last ? '' : '(?!\z)' ).'~i', "<br />\n", $text );
	}

}


/*
 * $Log$
 * Revision 1.21  2006/07/27 21:17:29  blueyed
 * Fixed Auto-P plugin
 *
 * Revision 1.20  2006/07/10 20:19:30  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.19  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.18  2006/07/05 21:41:17  blueyed
 * fixes
 *
 * Revision 1.17  2006/07/05 20:10:17  blueyed
 * Merge/Parse error fixed
 *
 * Revision 1.16  2006/07/05 19:54:02  blueyed
 * Auto-P-plugin: respect newlines to create empty paragraphs
 *
 * Revision 1.15  2006/06/19 19:25:28  blueyed
 * Fixed auto-p plugin: <code> is an inline element
 *
 * Revision 1.14  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.13  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.12  2006/05/22 18:14:16  blueyed
 * Fixed the fixed auto-p-plugin
 *
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