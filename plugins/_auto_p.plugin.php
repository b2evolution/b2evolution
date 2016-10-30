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
	var $priority = 80;
	var $version = '6.7.8';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'auto-p-plugin';
	var $number_of_installs = 1;

	/**
	 * List of block elements (we want a paragraph before and after), excludes: address, added: td, th
	 * @var string
	 */
	var $block_tags = 'blockquote|dd|div|dl|dt|fieldset|form|h[1-6]|hr|li|object|ol|p|pre|select|script|table|td|th|ul';


	var $p_allowed_in = array('address', 'applet', 'blockquote', 'body', 'button', 'center', 'dd', 'del', 'div', 'fieldset', 'form', 'iframe', 'ins', 'li', 'map', 'noframes', 'noscript', 'object', 'td', 'th' );


	var $br_allowed_in = array(
		// Block level:
		'address', 'center', 'dd', 'dir', 'div', 'dt', 'fieldset', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'isindex', 'menu', 'noframes', 'noscript', 'p', 'pre',
		// Inline:
		'a', 'abbr', 'acronym', 'applet', 'b', 'basefont', 'bdo', 'big', 'button', 'cite', 'code', 'dfn', 'em', 'font', 'i', 'img', 'input', 'iframe', 'kbd', 'label', 'li', 'map', 'object', 'q', 'samp', 'select', 'small', 'span', 'strong', 'sub', 'sup', 'textarea', 'td', 'th', 'tt', 'var' );


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Automatic &lt;P&gt; and &lt;BR&gt; tags');
		$this->long_desc = T_('This renderer will automatically detect paragraphs on double line-breaks and mark them with appropriate HTML &lt;P&gt; tags.<br />
Optionally, it will also mark single line breaks with HTML &lt;BR&gt; tags.');
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
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
					'type' => 'text',
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

		$this->use_auto_br = $this->Settings->get('br');
		$this->add_p_in_block = $this->Settings->get('add_p_in_block');
		$this->skip_tags = preg_split( '~\s+~', $this->Settings->get('skip_tags'), -1, PREG_SPLIT_NO_EMPTY );

		$content = & $params['data'];

		$content = preg_replace( "~(\r\n|\r)~", "\n", $content ); // cross-platform newlines

		// Handle blocks, splitted by content separators: [teaserbreak] or [pagebreak]
		$content_parts = split_outcode( array( '[teaserbreak]', '[pagebreak]' ), $content, true );
		$content_parts[] = '';

		$content = '';
		for( $i = 0; $i < count( $content_parts ); $i = $i + 2 )
		{
			$content .= $this->handle_blocks( $content_parts[ $i ] );
			$content .= $content_parts[ $i + 1 ];
		}

		return true;
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params,
			array(
				'default_comment_rendering' => 'stealth',
				'default_post_rendering' => 'opt-out'
			)
		);
		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Define here default message settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_msg_setting_definitions( & $params )
	{
		// set params to allow rendering for messages by default
		$default_params = array_merge( $params, array( 'default_msg_rendering' => 'stealth' ) );
		return parent::get_msg_setting_definitions( $default_params );
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		// set params to allow rendering for emails by default:
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'stealth' ) );
		return parent::get_email_setting_definitions( $default_params );
	}


	/**
	 * - Split text into blocks, using $block_tags pattern.
	 *
	 * @param string Text
	 * @param string The HTML tag where $text is in
	 * @return string
	 */
	function handle_blocks( $text, $in_tag = '' )
	{
		#echo '<h1>HANDLE_BLOCKS</h1>'; pre_dump( $text, $in_tag );

		$new_text = '';

		if( preg_match( '~^(.*?)(<\s*('.$this->block_tags.')(\b[^>]*)?>)~is', $text, $match ) )
		{ // there's a block tag:
			$tag = $match[3];
			$before_tag = $match[1];

			if( ! empty($before_tag) )
			{ // Recurse (one pattern/callback deeper):
				$new_text .= $this->handle_pre_blocks( $before_tag, $in_tag );
			}

			$text_after_tag = substr( $text, strlen($match[0]) );

			if( empty($match[4]) || substr(rtrim($match[4]), -1) != '/' )
			{ // Opening tag (and not self-closing): handle text in tag:
				$new_text .= $match[2];

				// Find closing tag:
				list( $text_in_tag, $closing_tag, $NL_before, $NL_after ) = $this->split_text_for_tag($tag, $text_after_tag);

				if( ! empty($text_in_tag) )
				{ // Recurse (same level):
					$text_in_tag = $this->handle_blocks( $text_in_tag, $tag );
				}

				$new_text .= $NL_before.$text_in_tag.$NL_after;

				$new_text .= $closing_tag;
			}
			else
			{ // self-closing tag:
				$new_text .= $match[2];
			}

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
	 * @param string Text
	 * @param string Tag where $text is in
	 * @return string
	 */
	function handle_pre_blocks( $text, $in_tag )
	{
		#echo '<h2>HANDLE_PRE_BLOCKS</h2>'; pre_dump( $text, $in_tag );

		if( $in_tag )
		{
			if( in_array($in_tag, $this->skip_tags) )
			{
				return $text;
			}

			if( ! in_array($in_tag, $this->p_allowed_in) )
			{ // we're in a tag, where no P tags are allowed, so just do the BRs:
				return $this->handle_br( $text, $in_tag );
			}
		}

		$text_lines = preg_split( '~(\n\n+)~', $text, -1, /*PREG_SPLIT_NO_EMPTY |*/ PREG_SPLIT_DELIM_CAPTURE );
		$text_lines[] = ''; // dummy

		#echo '<strong>text_lines</strong><br />'; pre_dump( $text_lines, $in_tag );

		$new_blocks = array();
		$count_new_blocks = 0;
		for( $i = 0, $n = count($text_lines); $i < $n; $i = $i+2 /* every second block is a real one */ )
		{
			if( ! isset($new_blocks[$count_new_blocks]) )
			{
				$new_blocks[$count_new_blocks] = '';
			}
			if( $text_lines[$i] == '' )
			{
				$new_blocks[$count_new_blocks] .= $text_lines[$i+1];
				$new_blocks[$count_new_blocks+1] = ''; // dummy
				continue;
			}
			$new_blocks[$count_new_blocks] .= $text_lines[$i];
			$new_blocks[$count_new_blocks+1] = $text_lines[$i+1];
			$count_new_blocks = $count_new_blocks+2;
		}

		$text_lines = $new_blocks;
		#echo '<strong>new text_lines</strong><br />'; pre_dump( $new_blocks );

		if( trim($text) == '' )
		{ // there's only whitespace
			return $text;
		}


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

				while( preg_match( '~^(.*?)(<\s*/\s*(\w+)(\s+[^>]*?)?>)~is', $line_copy, $match )
					&& !(preg_match( '~^(.*?)(<\s*'.$match[3].'(\s+[^>]*?(\s*/\s*)?)?>)~is', $new_blocks[$count_new_blocks].$match[1] )) )
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

			if( preg_match( '~^(.*?)(<\s*(\w+)(\s+[^>/]*)?(\s*/\s*)?>)~is', $line, $match ) )
			{ // a opening tag:
				$tag = $match[3];
				$pos_after_tag = strlen($match[0]);

				while( ! empty($match[5]) /* "/" */ )
				{ // self-closing tag, find next:
					$tag = false;
					if( preg_match( '~^(.*?)(<\s*(\w+)(\s+[^>/]*)?(\s*/\s*)?>)~is', substr($line, $pos_after_tag), $match ) )
					{
						$tag = $match[3];
						$pos_after_tag += strlen($match[0]);
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
			else
			{ // the whole block should not get wrapped!
				$new_blocks_nowrap[] = $count_new_blocks;
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
			#echo '<h2>--new_blocks['.$i.']: '; pre_dump( $new_blocks[$i] ); echo '</h2>';

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

			list($new_block, $has_p) = $this->handle_pre_blocks_helper( $new_blocks[$i], $in_tag, $this_wrap_in_p );


			$new_text .= $before_block_wp.$new_block.$after_block_wp.$new_blocks[$i+1];
		}

		#pre_dump( 'HANDLE_PRE_BLOCKS return: ', $new_text, $in_tag );
		return $new_text;
	}


	/**
	 * This is a helper for handling blocks from {@link handle_pre_blocks_helper()}.
	 *
	 * What comes here is supposed to have no block tags.
	 *
	 * @return array array( $text, $has_p )
	 */
	function handle_pre_blocks_helper( $block, $in_tag, $wrap_in_p, $ignore_NL = true )
	{
		#pre_dump( 'HANDLE_PRE_BLOCKS_HELPER begin', $block, $in_tag );
		$has_p = NULL;
		$r = '';

		if( $in_tag == 'blockquote' )
		{ // XHTML strict: blockquote content needs to be in block tag
			$in_tag = 'p';
			$wrap_in_p = true; // at the end
		}

		// Remove newlines at start and end (will get re-applied later):
		$NL_start = '';
		$NL_end = '';
		if( $ignore_NL )
		{
			while( $block{0} == "\n" )
			{
				$NL_start .= $block{0};
				$block = substr($block, 1);
			}
			while( substr($block, -1) == "\n" )
			{
				$NL_end .= substr($block, -1);
				$block = substr($block, 0, -1);
			}
		}

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
			{ // Recurse (same level) - with the optional newlines at start and end, because in an inline tag every linebreak should become a BR:
				list($text_in_tag, $sub_has_p) = $this->handle_pre_blocks_helper( $NL_before.$text_in_tag.$NL_after, $tag, false, false );
			}
			$r .= $text_in_tag;

			$r .= $closing_tag;

			if( ! empty($text_after_tag) )
			{
				#echo '<h1>RECURSE: text_after_tag (handle_pre_blocks)</h1>';
				// Recurse (same level):
				list( $text_after_tag, $sub_has_p ) = $this->handle_pre_blocks_helper( $text_after_tag, $in_tag, false, false );
				$r .= $text_after_tag;
			}
		}
		else
		{ // No tags in this $text:
			$r .= $this->handle_br( $block, $in_tag );
		}

		if( ! empty($wrap_in_p) )
		{
			$r = '<p>'.$r.'</p>';
			$has_p = true;
		}

		// re-apply newlines from start and end:
		$r = $NL_start.$r.$NL_end;

		#pre_dump( 'HANDLE_PRE_BLOCKS_HELPER return: ', $r, $has_p );
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
			#echo '<hr />loop_text:'; pre_dump( $loop_text );
			if( preg_match( '~^(.*?)(<\s*(/)?\s*'.preg_quote( $tag, '~' ).'\s*(/\s*)?>)~is', $loop_text, $after_match ) )
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
					else
					{ // this closing tag is part of the outer:
						$text_in_tag .= $found_tag;
					}
				}
				else
				{ // found the same, but opening tag (nested)
					$text_in_tag .= $found_tag;
					$depth++;
				}

				// skip what we've matched:
				$loop_text = substr($loop_text, strlen($after_match[0]) );
			}
			else
			{ // did not find the closing tag.. :/
				$closing_tag = '';
				return array( false, false, false, false );
			}
		}

		// remove newline at start and end:
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

		#pre_dump( 'return: ', $r, $text_after_tag );
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

?>