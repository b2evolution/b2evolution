<?php
/**
 * This file implements the RendererPlugin class (EXPERIMENTAL)
 *
 * This is the base class from which you should derive all rendering plugins.
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
require_once dirname(__FILE__).'/renderer.class.php';

/**
 * RendererPlugin Class
 *
 * @package plugins
 * @abstract
 */
class RendererPlugin extends Plugin
{
	/**
	 * When should this plugin apply?
	 * Possible values:
	 * - 'stealth'
	 * - 'always'
	 * - 'opt-out'
	 * - 'opt-in'
	 * - 'lazy'
	 * - 'never'
	 */
	var $apply_when = 'opt-out';
	/**
	 * Should this plugin apply to HTML?
	 */
	var $apply_to_html = true; 
	/**
	 * Should this plugin apply to XML?
	 * It should actually only apply when:
	 * - it generates some content that is visible without HTML tags
	 * - it removes some dirty markup when generating the tags (which will get stripped afterwards)
	 * Note: htmlentityencoded is not considered as XML here.
	 */
	var $apply_to_xml = false; 
	
	/**
	 * Perform rendering
	 *
	 * Well, this one does nothing but checking if Rendering applies to the output format.
	 * You need to derive this function.
	 *
	 * {@internal Rendererplugin::render(-)}} 
	 *
	 * @param string content to render (by reference) / rendered content
	 * @param string Output format, see {@link format_to_output()}
	 * @return boolean true if we can render something for the required output format
	 */
	function render( & $content, $format )
	{
		switch( $format )
		{
			case 'raw':
				// do nothing!
				return false;
	
			case 'htmlbody':
				// display in HTML page body: allow full HTML
				return $this->apply_to_html;
	
			case 'entityencoded':
				// Special mode for RSS 0.92: apply renders and allow full HTML but escape it
				return $this->apply_to_html;
	
			case 'htmlhead':
				// strips out HTML (mainly for use in Title)
			case 'htmlattr':
				// use as an attribute: strips tags and escapes quotes
			case 'formvalue':
				// use as a form value: escapes quotes and < > but leaves code alone
				return false;
	
			case 'xml':
				// use in an XML file: strip HTML tags
				return $this->apply_to_xml;

			case 'xmlattr':
				// use as an attribute: strips tags and escapes quotes
				return false;

			default:
				die( 'Output format ['.$format.'] not supported by RendererPlugin.' );
		}
	}
}
?>