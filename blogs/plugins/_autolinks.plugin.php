<?php
/**
 * This file implements the Automatic Links plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Automatic links plugin.
 *
 * @todo dh> Provide a setting for:
 *   - marking external and internal (relative URL or on the blog's URL) links with a HTML/CSS class
 *   - add e.g. 'target="_blank"' to external links
 *
 * @package plugins
 */
class autolinks_plugin extends Plugin
{
	var $code = 'b2evALnk';
	var $name = 'Auto Links';
	var $priority = 60;
	var $version = '1.9-dev';
	var $apply_rendering = 'opt-out';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Make URLs clickable');
		$this->long_desc = T_('This renderer will detect URLs in the text and automatically transform them into clickable links.');
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 * 							(Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$content = make_clickable( $content );

		return true;
	}
}


/*
 * $Log$
 * Revision 1.15  2007/01/17 21:41:05  blueyed
 * todo for useful settings/features
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
 * Revision 1.9  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.8  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>