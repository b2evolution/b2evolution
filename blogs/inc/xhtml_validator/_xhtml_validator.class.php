<?php
/**
 * This file implements the XHTML_Validator class.
 *
 * Checks HTML against a subset of elements to ensure safety and XHTML validation.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
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
 * @version $Id: _xhtml_validator.class.php 6274 2014-03-20 11:23:11Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 *  Load required funcs
 */
load_funcs('_core/_url.funcs.php');


/**
 * XHTML_Validator
 *
 * checks HTML against a subset of elements to ensure safety and XHTML validation.
 *
 * @package evocore
 */
class XHTML_Validator
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
	 * @param string Context
	 * @param boolean Allow CSS tweaks?
	 * @param boolean Allow IFrames?
	 * @param boolean Allow Javascript?
	 * @param boolean Allow Objects?
	 * @param string Input encoding to use ('ISO-8859-1', 'UTF-8', 'US-ASCII' or '' for auto-detect)
	 * @param string Message type for errors
	 */
	function XHTML_Validator( $context = 'posting', $allow_css_tweaks = false, $allow_iframes = false, $allow_javascript = false, $allow_objects = false, $encoding = NULL, $msg_type = 'error' )
	{
		global $inc_path;

		require $inc_path.'xhtml_validator/_xhtml_dtd.inc.php';

		$this->context = $context;

		switch( $context )
		{
			case 'posting':
			case 'xmlrpc_posting':
				$this->tags = & $allowed_tags;
				$this->tagattrs = & $allowed_attributes;
				break;

			case 'commenting':
				$this->tags = & $comments_allowed_tags;
				$this->tagattrs = & $comments_allowed_attributes;
				break;

			case 'head_extension':
				$this->tags = array(
					'body' => 'meta link style script',
					'meta' => '',
					'link' => '',
					'style' => '#PCDATA',
					'script' => '#PCDATA'
				);
				$this->tagattrs = array(
					'meta' => 'name content charset',
					'link' => 'charset href hreflang media rel sizes type',
					'style' => 'media scoped type',
					'script' => 'async charset defer src type'
				);
				break;

			default:
				debug_die( 'unknown context: '.$context );
		}

		// Attributes that need to be checked for a valid URI:
		$this->uri_attrs = array
		(
			'xmlns',
			'profile',
			'href',
			'src',
			'cite',
			'classid',
			'codebase',
			'data',
			'archive',
			'usemap',
			'longdesc',
			'action'
		);

		$this->allowed_uri_scheme = get_allowed_uri_schemes( $context );

		$this->msg_type = $msg_type;

		if( empty($encoding) )
		{
			global $io_charset;
			$encoding = $io_charset;
		}
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

		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
	}


	/**
	 * check(-)
	 */
	function check($xhtml)
	{
		// Convert encoding:
		// TODO: use convert_encoding()
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

		$xhtml_head .= '?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"';

		// Include entities:
		$xhtml_head .= '[';
		// Include latin1 entities (http://www.w3.org/TR/xhtml1/DTD/xhtml-lat1.ent):
		$xhtml_head .= file_get_contents( dirname(__FILE__).'/_xhtml-lat1.ent' );
		// Include symbol entities (http://www.w3.org/TR/xhtml1/DTD/xhtml-symbol.ent):
		$xhtml_head .= file_get_contents( dirname(__FILE__).'/_xhtml-symbol.ent' );
		// Include special entities (http://www.w3.org/TR/xhtml1/DTD/xhtml-special.ent):
		$xhtml_head .= file_get_contents( dirname(__FILE__).'/_xhtml-special.ent' );
		$xhtml_head .= ']>';

		$xhtml = $xhtml_head.'<body>'.$xhtml.'</body>';
		unset($xhtml_head);

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
			$xml_error_string .= ' near <code>'.evo_htmlspecialchars( evo_substr( $xhtml, $this->last_checked_pos, $pos-$this->last_checked_pos+20 ) ).'</code>';

			$this->html_error( T_('Parser error: ').$xml_error_string );
		}

		return $this->isOK();
	}


	/**
	 * tag_open(-)
	 *
	 * Called when the parser finds an opening tag
	 */
	function tag_open($parser, $tag, $attrs)
	{
		global $debug;

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
				$this->html_error( sprintf( T_('Tag &lt;%s&gt; may not have attribute %s="..."'), '<code>'.$tag.'</code>', '<code>'.$attr.'</code>' ) );
			}

			if (in_array($attr, $this->uri_attrs))
			{ // This attribute must be checked for URIs
				$matches = array();
				$value = trim($value);
				if( $error = validate_url( $value, $this->context, false ) ) //Note: We do not check for spam here, should be done on whole message in check_html_sanity()
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
		$Messages->add( $string, $this->msg_type );
	}

	/**
	 * isOK(-)
	 */
	function isOK()
	{
		return ! $this->error;
	}

}

?>