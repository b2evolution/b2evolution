<?php
/**
 * This file implements the SafeHtmlChecker class.
 *
 * Checks HTML against a subset of elements to ensure safety and XHTML validation.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2003 by Nobuo SAKIYAMA - {@link http://www.sakichan.org/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * {@internal Origin:
 * This file was inspired by Simon Willison's SafeHtmlChecker released in
 * the public domain on 23rd Feb 2003.
 * {@link http://simon.incutio.com/code/php/SafeHtmlChecker.class.php.txt}
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author sakichan: Nobuo SAKIYAMA.
 * @author Simon Willison.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * SafeHtmlChecker
 *
 * checks HTML against a subset of elements to ensure safety and XHTML validation.
 *
 * @package evocore
 */
class SafeHtmlChecker
{
	var $tags;      // Array showing allowed attributes for tags
	var $tagattrs;  // Array showing URI attributes
	var $uri_attrs;
	var $allowed_uri_scheme;

	// Internal variables
	var $parser;
	var $stack = array();
	var $last_checked_pos;
	var $error;

	/**
	 * Constructor
	 *
	 * {@internal This gets tested in _libs.misc.simpletest.php}}
	 *
	 * @param array
	 * @param array
	 * @param array
	 * @param array
	 * @param string Input encoding to use ('ISO-8859-1', 'UTF-8', 'US-ASCII' or '' for auto-detect)
	 */
	function SafeHtmlChecker( & $allowed_tags, & $allowed_attributes, & $uri_attrs, & $allowed_uri_scheme, $encoding = '' )
	{
		$this->tags = & $allowed_tags;
		$this->tagattrs = & $allowed_attributes;
		$this->uri_attrs = & $uri_attrs;
		$this->allowed_uri_scheme = & $allowed_uri_scheme;


		$encoding = strtoupper($encoding); // we might get 'iso-8859-1' for example
		$this->encoding = $encoding;
		if( ! in_array( $encoding, array( 'ISO-8859-1', 'UTF-8', 'US-ASCII' ) ) )
		{ // passed encoding not supported by xml_parser_create()
			$this->xml_parser_encoding = ''; // auto-detect (in PHP4, in PHP5 anyway)
		}
		else
		{
			$this->xml_parser_encoding = $this->encoding;
		}
		$this->parser = xml_parser_create( $this->xml_parser_encoding );

		$this->last_checked_pos = 0;
		$this->error = false;

		// Creates the parser
		xml_set_object( $this->parser, $this);

		// set functions to call when a start or end tag is encountered
		xml_set_element_handler($this->parser, 'tag_open', 'tag_close');
		// set function to call for the actual data
		xml_set_character_data_handler($this->parser, 'cdata');

		xml_set_default_handler($this->parser, 'default_handler');
		xml_set_external_entity_ref_handler($this->parser, 'external_entity');
		xml_set_unparsed_entity_decl_handler($this->parser, 'unparsed_entity');

		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
	}

	function default_handler( $parser, $data)
	{
		// echo 'default handler: '.$data.'<br />';
	}

	function external_entity( $parser, $open_entity_names, $base, $system_id, $public_id)
	{
		// echo 'external_entity<br />';
	}


	function unparsed_entity( $parser, $entity_name, $base, $system_id, $public_id, $notation_name)
	{
		// echo 'unparsed_entity<br />';
	}


	/**
	 * check(-)
	 */
	function check($xhtml)
	{
		// Convert encoding:
		if( empty($this->xml_parser_encoding) || $this->encoding != $this->xml_parser_encoding )
		{ // we need to convert encoding:
			if( function_exists( 'mb_convert_encoding' ) )
			{ // we can convert encoding to UTF-8
				$this->encoding = 'UTF-8';

				// Convert XHTML:
				$xhtml = mb_convert_encoding( $xhtml, 'UTF-8' );
			}
			elseif( ($this->encoding == 'ISO-8859-1' || empty($this->encoding)) && function_exists('utf8_encode') )
			{
				$this->encoding = 'UTF-8';

				$xhtml = utf8_encode( $xhtml );
			}
		}

		// Open comments or '<![CDATA[' are dangerous
		$xhtml = str_replace('<!', '', $xhtml);

		// Convert isolated & chars
		$xhtml = preg_replace( '#(\s)&(\s)#', '\\1&amp;\\2', $xhtml );

		$xhtml_head = '<?xml version="1.0"';
		if( ! empty($this->encoding) )
		{
			$xhtml_head .= ' encoding="'.$this->encoding.'"';
		}
		$xhtml_head .= '?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';

		$xhtml = $xhtml_head.'<body>'.$xhtml.'</body>';

		if( !xml_parse($this->parser, $xhtml) )
		{
			$xml_error_code = xml_get_error_code( $this->parser );
			$xml_error_string = xml_error_string( $xml_error_code );
			switch( $xml_error_code )
			{
				case XML_ERROR_TAG_MISMATCH:
					$xml_error_string .= ': <code>'.$this->stack[count($this->stack)-1].'</code>';
					break;
			}
			$pos = xml_get_current_byte_index($this->parser);
			$xml_error_string .= ' near <code>'.htmlspecialchars( substr( $xhtml, $this->last_checked_pos, $pos-$this->last_checked_pos+20 ) ).'</code>';

			$this->html_error( T_('Parser error: ').$xml_error_string );
		}
	}

	/**
	 * tag_open(-)
	 *
	 * Called when the parser finds an opening tag
	 */
	function tag_open($parser, $tag, $attrs)
	{
		// echo "processing tag: $tag <br />\n";
		$this->last_checked_pos = xml_get_current_byte_index($this->parser);

		if ($tag == 'body')
		{
			if( count($this->stack) > 0 )
				$this->html_error( T_('Tag <code>body</code> can only be used once!') );
			$this->stack[] = $tag;
			return;
		}
		$previous = $this->stack[count($this->stack)-1];

		// If previous tag is illegal, no point in running tests
		if (!in_array($previous, array_keys($this->tags))) {
			$this->stack[] = $tag;
			return;
		}
		// Is tag a legal tag?
		if (!in_array($tag, array_keys($this->tags))) {
			$this->html_error( T_('Illegal tag'). ": <code>$tag</code>" );
			$this->stack[] = $tag;
			return;
		}
		// Is tag allowed in the current context?
		if (!in_array($tag, explode(' ', $this->tags[$previous]))) {
			if ($previous == 'body') {
				$this->html_error(	sprintf( T_('Tag &lt;%s&gt; must occur inside another tag'), '<code>'.$tag.'</code>' ) );
			} else {
				$this->html_error(	sprintf( T_('Tag &lt;%s&gt; is not allowed within tag &lt;%s&gt;'), '<code>'.$tag.'</code>', '<code>'.$previous.'</code>') );
			}
		}
		// Are tag attributes valid?
		foreach( $attrs as $attr => $value )
		{
			if (!isset($this->tagattrs[$tag]) || !in_array($attr, explode(' ', $this->tagattrs[$tag])))
			{
				$this->html_error( sprintf( T_('Tag &lt;%s&gt; may not have attribute %s'), '<code>'.$tag.'</code>', '<code>'.$attr.'</code>' ) );
			}
			if (in_array($attr, $this->uri_attrs))
			{ // Must this attribute be checked for URIs
				$matches = array();
				$value = trim($value);
				if( $error = validate_url( $value, $this->allowed_uri_scheme ) )
				{
					$this->html_error( T_('Found invalid URL: ').$error );
				}
			}
		}
		// Set previous, used for checking nesting context rules
		$this->stack[] = $tag;
	}

	/**
	 * cdata(-)
	 */
	function cdata($parser, $cdata)
	{
		$this->last_checked_pos = xml_get_current_byte_index($this->parser);

		// Simply check that the 'previous' tag allows CDATA
		$previous = $this->stack[count($this->stack)-1];
		// If previous tag is illegal, no point in running test
		if (!in_array($previous, array_keys($this->tags))) {
			return;
		}
		if (trim($cdata) != '') {
			if (!in_array('#PCDATA', explode(' ', $this->tags[$previous]))) {
				$this->html_error(	sprintf( T_('Tag &lt;%s&gt; may not contain raw character data'), '<code>'.$previous.'</code>' ) );
			}
		}
	}

	/**
	 * tag_close(-)
	 */
	function tag_close($parser, $tag)
	{
		$this->last_checked_pos = xml_get_current_byte_index($this->parser);

		// Move back one up the stack
		array_pop($this->stack);
	}

	function html_error( $string )
	{
		global $Messages;
		$this->error = true;
		$Messages->add( $string, 'error' );
	}

	/**
	 * isOK(-)
	 */
	function isOK()
	{
		return ! $this->error;
	}

}


/*
 * $Log$
 * Revision 1.7  2006/05/17 09:56:56  blueyed
 * Handle default (empty) "encoding" better
 *
 * Revision 1.6  2006/05/02 22:19:27  blueyed
 * whitespace
 *
 * Revision 1.5  2006/04/28 18:07:20  blueyed
 * Simplified, removed PHP5 dependency
 *
 * Revision 1.4  2006/04/28 16:04:27  blueyed
 * Fixed encoding for SafeHtmlChecker; added tests
 *
 * Revision 1.3  2006/03/20 00:25:45  blueyed
 * fix
 *
 * Revision 1.2  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.9  2006/01/16 00:35:12  blueyed
 * Fallback to UTF-8 encoding for not-supported encodings.
 *
 * Revision 1.8  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.7  2005/10/09 19:31:15  blueyed
 * Spelling (*allowed_attribues => *allowed_attributes)
 *
 * Revision 1.6  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.5  2005/06/03 15:12:33  fplanque
 * error/info message cleanup
 *
 * Revision 1.4  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.3  2004/11/15 18:57:05  fplanque
 * cosmetics
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.13  2004/10/12 16:12:17  fplanque
 * Edited code documentation.
 *
 */
?>