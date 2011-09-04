<?php
/**
 * This file implements the GMcode plugin for b2evolution
 *
 * GreyMatter style formatting
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Replaces GreyMatter markup in HTML (not XML).
 *
 * @todo dh> Do not replace in tags, it matches e.g. the following for italic:
 *           """<img src="//url" /> [...] http://"""!
 *
 * @package plugins
 */
class gmcode_plugin extends Plugin
{
	var $code = 'b2evGMco';
	var $name = 'GM code';
	var $priority = 45;
	var $apply_rendering = 'opt-out';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '3.0';
	var $number_of_installs = 1;


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
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('GreyMatter style formatting');
		$this->long_desc = T_('**bold** \\italics\\ //italics// __underline__ ##tt## %%codeblock%%');
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$content = callback_on_non_matching_blocks( $content, '~<[^>]*>~s', array($this, 'replace_callback') );

		return true;
	}


	/**
	 * Replace callback
	 * @param string
	 * @return string
	 */
	function replace_callback($text)
	{
		return preg_replace($this->search, $this->replace, $text);
	}

}


/*
 * $Log$
 * Revision 1.23  2011/09/04 22:13:23  fplanque
 * copyright 2011
 *
 * Revision 1.22  2010/02/08 17:55:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.21  2009/03/08 23:57:47  fplanque
 * 2009
 *
 * Revision 1.20  2009/01/23 17:23:09  fplanque
 * doc/minor
 *
 * Revision 1.19  2009/01/22 23:56:03  blueyed
 * gmcode_plugin: use callback_on_non_matching_blocks to not replace GM code in HTML tags. Change plugin version to 1.9.1.
 *
 * Revision 1.18  2008/10/10 14:14:38  blueyed
 * todo
 *
 * Revision 1.17  2008/01/21 09:35:41  fplanque
 * (c) 2008
 *
 * Revision 1.16  2007/04/26 00:11:04  fplanque
 * (c) 2007
 *
 * Revision 1.15  2007/04/20 02:53:13  fplanque
 * limited number of installs
 *
 * Revision 1.14  2006/12/26 03:19:12  fplanque
 * assigned a few significant plugin groups
 *
 * Revision 1.13  2006/07/10 20:19:30  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.12  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.11  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.10  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.9  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>