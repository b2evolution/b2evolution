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
$plugin_code = 'b2evGMco';

class gmcode_Rendererplugin
{
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
	 */
	function render( & $content )
	{
		$content = preg_replace( $this->search, $this->replace, $content );
	}
}

// Register the plugin:
$this->Plugins[$plugin_code] = & new gmcode_Rendererplugin();

?>
