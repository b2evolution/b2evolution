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
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../renderer.class.php';

/**
 * @package plugins
 */
class gmcode_Rendererplugin extends RendererPlugin
{
	var $code = 'b2evGMco';
	var $name = 'GM code';
	var $priority = 40;
	var $apply_when = 'opt-out';
	var $apply_to_html = true; 
	var $apply_to_xml = false; // Leave the GMcode markup
	var $short_desc;
	var $long_desc;

	/**
	 * GreyMatter formatting search array
	 *
	 * @access private
	 */
	var $search = array(
											'#( ^ | \s ) \*\* (.+?) \*\* #sx',		// **bold**
											'#( ^ | \s ) \x5c\x5c (.+?) \x5c\x5c #sx',		// \\italics\\
											'#( ^ | \s ) \x2f\x2f (.+?) \x2f\x2f #x',		// //italics//
											'#( ^ | \s ) __ (.+?) __ #sx',		// __underline__
											'/( ^ | \s ) \#\# (.+?) \#\# /sx'		// ##code##
											);
	
	/**
	 * HTML replace array
	 *
	 * @access private
	 */
	var $replace = array(
											'$1<strong>$2</strong>',
											'$1<em>$2</em>',
											'$1<em>$2</em>',
											'$1<span style="text-decoration:underline">$2</span>',
											'$1<code>$2</code>'
											);


	/**
	 * Constructor
	 *
	 * {@internal gmcode_Rendererplugin::gmcode_Rendererplugin(-)}}
	 */
	function gmcode_Rendererplugin()
	{
		$this->short_desc = T_('GreyMatter style formatting');
		$this->long_desc = T_('**bold** \\italics\\ //italics// __underline__ ##code##
Markup must be preceeded with white space or newline.
Take care not to run a plugin such as AutoP before this one. <p>** for example will not work.');
	}


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