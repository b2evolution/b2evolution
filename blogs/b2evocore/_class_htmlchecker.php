<?php
/**
 * SafeHtmlChecker
 * 
 * checks HTML against a subset of elements to ensure safety and XHTML validation.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 * @author Simon Willison, 23rd Feb 2003, modified by fplanque, sakichan
 */
class SafeHtmlChecker 
{
    var $tags;    		// Array showing allowed attributes for tags
    var $tagattrs;    // Array showing URI attributes
    var $uri_attrs;
    var $allowed_uri_scheme;
		
    // Internal variables
   	var $parser;
    var $stack = array();
		var $last_checked_pos;
		var $error;
		
		/* 
		 * SafeHtmlChecker(-)
		 */
    function SafeHtmlChecker( & $allowed_tags, & $allowed_attribues, & $uri_attrs, & $allowed_uri_scheme ) 
		{
				$this->tags = & $allowed_tags;
				$this->tagattrs = & $allowed_attribues;
				$this->uri_attrs = & $uri_attrs;
				$this->allowed_uri_scheme = & $allowed_uri_scheme;
        $this->parser = xml_parser_create();
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
		

		/* 
		 * check(-)
		 */
    function check($xhtml) 
		{
			// Open comments or '<![CDATA[' are dangerous 
			$xhtml = str_replace('<!', '', $xhtml);

			// Convert isolated & chars
			$xhtml = preg_replace( '#(\s)&(\s)#', '\\1&amp;\\2', $xhtml );

			$xhtml = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><body>'.$xhtml.'</body>';
			
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
    
		/* 
		 * tag_open(-)
		 *
		 * Called when the parser finds an opening tag
		 */
		function tag_open($parser, $tag, $attrs) 
		{
				//echo "processing tag: $tag <br />\n";
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
            $this->html_error(  T_('Illegal tag'). ": <code>$tag</code>" );
            $this->stack[] = $tag;
            return;
        }
        // Is tag allowed in the current context?
        if (!in_array($tag, explode(' ', $this->tags[$previous]))) {
            if ($previous == 'body') {
                $this->html_error(  sprintf( T_('Tag %s must occur inside another tag'), '<code>'.$tag.'</code>' ) );
            } else {
                $this->html_error(  sprintf( T_('Tag %s is not allowed within tag %s'), '<code>'.$tag.'</code>', '<code>'.$previous.'</code>') );
            }
        }
        // Are tag attributes valid?
        foreach ($attrs as $attr => $value) 
				{
					if (!isset($this->tagattrs[$tag]) || !in_array($attr, explode(' ', $this->tagattrs[$tag]))) 
					{
							$this->html_error( sprintf( T_('Tag %s may not have attribute %s'), '<code>'.$tag.'</code>', '<code>'.$attr.'</code>' ) );
					}
					if (in_array($attr, $this->uri_attrs)) 
					{	// Must this attribute be checked for URIs
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
    
		/* 
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
                $this->html_error(  sprintf( T_('Tag %s may not contain raw character data'), '<code>'.$previous.'</code>' ) );
            }
        }
    }

		/* 
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
			$Messages->add( $string );
		}
		
		/* 
		 * isOK(-)
		 */
		function isOK() 
		{
        return ! $this->error;
    }

}

?>
