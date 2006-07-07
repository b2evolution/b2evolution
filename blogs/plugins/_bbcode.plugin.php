<?php
/**
 * This file implements the BBcode plugin for b2evolution
 *
 * BB style formatting, like [b]bold[/b]
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class bbcode_plugin extends Plugin
{
	var $code = 'b2evBBco';
	var $name = 'BB code';
	var $priority = 50;
	var $version = '1.9-dev';
	var $apply_rendering = 'opt-in';
	var $short_desc;
	var $long_desc;


	/**
	 * BBcode formatting search array
	 *
	 * @access private
	 */
	var $search = array(
			'#\[b](.+?)\[/b]#is',		// Formatting tags
			'#\[i](.+?)\[/i]#is',
			'#\[u](.+?)\[/u]#is',
			'#\[s](.+?)\[/s]#is',
			'!\[color=(#?[A-Za-z0-9]+?)](.+?)\[/color]!is',
			'#\[size=([0-9]+?)](.+?)\[/size]#is',
			'#\[font=([A-Za-z0-9 ;\-]+?)](.+?)\[/font]#is',
			// Following lines added by Georges (iznogoudmc)
			'#\[code](.+?)\[/code]#is',
			'#\[quote](.+?)\[/quote]#is',
			'#\[list=1](.+?)\[/list]#is',
			'#\[list=a](.+?)\[/list]#is',
			'#\[list](.+?)\[/list]#is',
			'#\[\*](.+?)\n#is',
			// End of Georges' add
			// (Remove comment if modification validated)
			// The following are dangerous, until we security check resulting code.
			//	'#\[img](.+?)\[/img]#is',		// Image
			//	'#\[url](.+?)\[/url]#is',		// URL
			//	'#\[url=(.+?)](.+?)\[/url]#is',
			//	'#\[email](.+?)\[/email]#eis',		// E-mail
			//	'#\[email=(.+?)](.+?)\[/email]#eis'
		);

	/**
	 * HTML replace array
	 *
	 * @access private
	 */
	var $replace = array(
			'<strong>$1</strong>',		// Formatting tags
			'<em>$1</em>',
			'<span style="text-decoration:underline">$1</span>',
			'<span style="text-decoration:line-through">$1</span>',
			'<span style="color:$1">$2</span>',
			'<span style="font-size:$1px">$2</span>',
			'<span style="font-family:$1">$2</span>',
			// Following lines added by Georges (iznogoudmc)
			'<pre>$1</pre>',
			'&laquo;&nbsp;$1&nbsp;&raquo;',
			'<ol type="1">$1</ol>',
			'<ol type="a">$1</ol>',
			'<ul>$1</ul>',
			'<li>$1</li>',
			// End of Georges' add
			// (Remove comment if modification validated)
			//	'<img src="$1" alt="" />',		// Image
			//	'<a href="$1">$1</a>',		// URL
			//	'<a href="$1" title="$2">$2</a>',
			//	'<a href=\"mailto:$1\">$1</a>',		// E-mail
			//	'<a href="mailto:$1">$2</a>'
		);


	/**
	 * Init
	 */
	function PluginInit()
	{
		$this->short_desc = T_('BB formatting e-g [b]bold[/b]');
		$this->long_desc = T_('Available tags are: [b] [i] [u] [s] [color=...] [size=...] [font=...] [code] [quote] [list=1] [list=a] [list] [*]');
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$content = preg_replace( $this->search, $this->replace, $content );

		return true;
	}


	/**
	 * Do the same as for HTML.
	 *
	 * @see RenderItemAsHtml()
	 */
	function RenderItemAsXml( & $params )
	{
		$this->RenderItemAsHtml( $params );
	}

}


/*
 * $Log$
 * Revision 1.14  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.13  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.12  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.11  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.10  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>