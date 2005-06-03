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
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * {@internal
 * This file was inspired by Simon Willison's SafeHtmlChecker released in
 * the public domain on 23rd Feb 2003.
 * {@link http://simon.incutio.com/code/php/SafeHtmlChecker.class.php.txt}
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 * @author sakichan: Nobuo SAKIYAMA.
 * @author Simon Willison.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

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
	 * SafeHtmlChecker(-)
	 */
	function SafeHtmlChecker( & $allowed_tags, & $allowed_attribues, & $uri_attrs, & $allowed_uri_scheme, $encoding = 'ISO-8859-1' )
	{
		$this->tags = & $allowed_tags;
		$this->tagattrs = & $allowed_attribues;
		$this->uri_attrs = & $uri_attrs;
		$this->allowed_uri_scheme = & $allowed_uri_scheme;
		$this->encoding = $encoding;
		$this->parser = xml_parser_create( $this->encoding );
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
		// Open comments or '<![CDATA[' are dangerous
		$xhtml = str_replace('<!', '', $xhtml);

		// Convert isolated & chars
		$xhtml = preg_replace( '#(\s)&(\s)#', '\\1&amp;\\2', $xhtml );

		$xhtml = '<?xml version="1.0" encoding="'.$this->encoding.'"?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
				.'<body>'.$xhtml.'</body>';

		if (!xml_parse($this->parser, $xhtml))
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