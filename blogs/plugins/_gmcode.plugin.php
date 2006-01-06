<?php
/**
 * This file implements the GMcode plugin for b2evolution
 *
 * GreyMatter style formatting
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Replaces GreyMatter markup in HTML (not XML).
 *
 * @package plugins
 */
class gmcode_plugin extends Plugin
{
	var $code = 'b2evGMco';
	var $name = 'GM code';
	var $priority = 45;
	var $apply_rendering = 'opt-out';
	var $short_desc;
	var $long_desc;
	var $version = '$Revision$';


	/**
	 * GreyMatter formatting search array
	 *
	 * @access private
	 */
	var $search = array(
			'# \*\* (.+?) \*\* #x',                // **bold**
			'# \x5c\x5c (.+?) \x5c\x5c #x',        // \\italics\\
			'# (?<!:) \x2f\x2f (.+?) \x2f\x2f #x', // //italics// (not preceded by : as in http://)
			'# __ (.+?) __ #x',                    // __underline__
			'/ \#\# (.+?) \#\# /x',                // ##tt##
			'/ %%
				( \s*? \n )?      # Eat optional blank line after %%%
				(.+?)
				( \n \s*? )?      # Eat optional blank line before %%%
				%%
			/sx'                                   // %%codeblock%%
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
			'<span style="text-decoration:underline">$1</span>',
			'<tt>$1</tt>',
			'<div class="codeblock"><pre><code>$2</code></pre></div>'
		);


	/**
	 * Constructor
	 */
	function gmcode_plugin()
	{
		$this->short_desc = T_('GreyMatter style formatting');
		$this->long_desc = T_('**bold** \\italics\\ //italics// __underline__ ##tt## %%codeblock%%');
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$content = preg_replace( $this->search, $this->replace, $content );

		return true;
	}
}
?>