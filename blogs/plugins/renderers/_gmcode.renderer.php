<?php
/**
 * This file implements the GMcode plugin for b2evolution
 *
 * GreyMatter style formatting
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
require_once dirname(__FILE__).'/../renderer.class.php';

class gmcode_Rendererplugin extends RendererPlugin
{
	var $code = 'b2evGMco';
	var $name = 'GM code';
	var $priority = 41;
	var $apply_when = 'opt-out';
	var $apply_to_html = true; 
	var $apply_to_xml = false; // Leave the GMcode markup
	var $short_desc = 'GreyMatter style formatting';
	var $long_desc = 'No description available';

	/**
	 * GreyMatter formatting search array
	 *
	 * @access private
	 */
	var $search = array(
											'#\*\*(.+?)\*\*#s',		// **bold**
											'#\x5c\x5c(.+?)\x5c\x5c#s',		// \\italic\\
											'#\x2f\x2f(.+?)\x2f\x2f#',		// //italic//
											'#__(.+?)__#s'		// __underline__
											);
	
	/**
	 * HTML replace array
	 *
	 * @access private
	 */
	var $replace = array(
											'<strong>$1</strong>',
											'<em>$1</em>',
											'<em>$1</em>',
											'<span style="text-decoration:underline">$1</span>'
											);

	/**
	 * Perform rendering
	 *
	 * {@internal gmcode_Rendererplugin::render(-)}} 
	 *
	 * @param string content to render (by reference) / rendered content
	 * @param string Output format, see {@link format_to_output()}
	 * @return boolean true if we can render something for the required output format
	 */
	function render( & $content, $format )
	{
		if( ! parent::render( $content, $format ) )
		{	// We cannot render the required format
			return false;
		}
	
		$content = preg_replace( $this->search, $this->replace, $content );
		
		return true;
	}
}

// Register the plugin:
$this->register( new gmcode_Rendererplugin() );

?>