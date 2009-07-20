<?php
/**
 * This file implements the Automatic Links plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @todo Add "max. displayed length setting" and add full title + dots in the middle to shorten it.
 *       (e.g. plain long URLs with a lot of params and such). This should not cause the layout to
 *       behave ugly. This should only shorten non-whitespace strings in the link's innerHTML of course.
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
	var $number_of_installs = null;	// Let admins install several instances with potentially different word lists

	var $link_array;
	var $current_link_array;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Make URLs clickable');
		$this->long_desc = T_('This renderer will detect URLs in the text and automatically transform them into clickable links.');

		// fp> TODO: Make editable. Textarea/DB and/or .txt files(s)
		// Dummy test data waiting for admin interface: (these words should work like crazy on the default contents)
		$this->link_array = array(
				'blog' => 'b2evolution.net',
				'post' => 'b2evo.net',
				'settings' => 'evocore.net',
				'@b2evolution' => 'twitter.com/b2evolution', // maybe we should integrate that one into the generic make_clickable?
			);
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

		// Start with a fresh link array:
		// fp> TODO: setting to edit list
		$this->current_link_array = $this->link_array;

		// First, make the URLs clickable:
		// fp> TODO: setting to enable/disable this
		$content = make_clickable( $content );

		// Now, make the desired remaining terms clickable:
		// fp> TODO: setting to enable/disable this
		$content = make_clickable( $content, '&amp;', array( $this, 'make_clickable_callback' ) );

		return true;
	}


	/**
	 * Callback function for {@link make_clickable()}.
	 *
	 * @return string The clickable text.
	 */
	function make_clickable_callback( $text, $moredelim = '&amp;' )
	{
		// Find word with 3 characters at least:
		$text = preg_replace_callback( '/(^|[^&])(@?[a-z0-9_\-]{3,})/i', array( $this, 'replace_callback' ), $text );

		return $text;
	}

	/**
	 * This is the 2nd level of callback!!
	 */
	function replace_callback( $matches )
	{
		$word = $matches[2];
		$lword = strtolower($word);

		if( isset( $this->current_link_array[$lword] ) )
		{
			$word = '<a href="http://'.$this->current_link_array[$lword].'" target="_blank">'.$word.'</a>';
			// Make sure we don't make the same word clickable twice in the same text/post:
			unset( $this->current_link_array[$lword] );
		}

		return $matches[1].$word;
	}
}


/*
 * $Log$
 * Revision 1.21  2009/07/20 02:15:10  fplanque
 * fun with tags, regexps & the autolink plugin
 *
 * Revision 1.20  2009/03/08 23:57:47  fplanque
 * 2009
 *
 * Revision 1.19  2008/01/21 09:35:38  fplanque
 * (c) 2008
 *
 * Revision 1.18  2007/06/16 20:20:53  blueyed
 * Added todo for ... in links
 *
 * Revision 1.17  2007/04/26 00:11:04  fplanque
 * (c) 2007
 *
 * Revision 1.16  2007/04/20 02:53:13  fplanque
 * limited number of installs
 *
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