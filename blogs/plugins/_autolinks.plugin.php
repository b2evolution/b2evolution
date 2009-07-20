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
	var $version = '3.3.1';
	var $apply_rendering = 'opt-out';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $number_of_installs = null;	// Let admins install several instances with potentially different word lists

	/**
	 * Lazy loaded from txt files
	 *
	 * @var array
	 */
	var $link_array = null;
	var $current_link_array;

	var $previous_word = null;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Make URLs clickable');
		$this->long_desc = T_('This renderer will detect URLs in the text and automatically transform them into clickable links.');
	}

	/**
	 * Lazy load
	 *
	 */
	function load_link_array()
	{
		global $plugins_path;

		if( !is_null($this->link_array) )
		{
			return;
		}

		$this->link_array = array();

		// Load defaults:
		$this->read_csv_file( $plugins_path.'autolinks_plugin/definitions.default.txt' );
		// Load local user defintions:
		$this->read_csv_file( $plugins_path.'autolinks_plugin/definitions.local.txt' );
	}

	/**
 	 *
	 *
	 * @param string $filename
	 */
	function read_csv_file( $filename )
	{
		if( ! $handle = @fopen( $filename, 'r') )
		{	// File could not be opened:
			return;
		}

		while( ($data = fgetcsv($handle, 1000, ';', '"')) !== false )
		{
			if( empty($data[0]) || empty($data[2]) )
			{	// Skip empty and comment lines
				continue;
			}
			$this->link_array[$data[0]] = array( $data[1], $data[2] );
		}
		fclose($handle);
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

		$this->load_link_array();

		// Start with a fresh link array:
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
		$this->previous_lword = null;

		// Find word with 3 characters at least:
		$text = preg_replace_callback( '/(^|\s)([@a-z0-9_\-]{3,})/i', array( $this, 'replace_callback' ), $text );

		// pre_dump($text);

		// Cleanup words to be deleted:
		$text = preg_replace( '/[@a-z0-9_\-]+\s*==!#DEL#!==/i', '', $text );

		return $text;
	}

	/**
	 * This is the 2nd level of callback!!
	 */
	function replace_callback( $matches )
	{
		$sign = $matches[1];
		$word = $matches[2];
		$lword = strtolower($word);
		$r = $sign.$word;

		if( isset( $this->current_link_array[$lword] ) )
		{
			$previous = $this->current_link_array[$lword][0];
			$url = 'http://'.$this->current_link_array[$lword][1];

			if( !empty($previous) )
			{
				if( $this->previous_lword != $previous )
				{	// We do not have the required previous word
					return $r;
				}
				$r = '==!#DEL#!==<a href="'.$url.'">'.$this->previous_word.' '.$word.'</a>';
			}
			else
			{
				$r = $sign.'<a href="'.$url.'">'.$word.'</a>';
			}
			// Make sure we don't make the same word clickable twice in the same text/post:
			unset( $this->current_link_array[$lword] );
		}

		$this->previous_word = $word;
		$this->previous_lword = $lword;

		return $r;
	}
}


/*
 * $Log$
 * Revision 1.22  2009/07/20 23:12:56  fplanque
 * more power to autolinks plugin
 *
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