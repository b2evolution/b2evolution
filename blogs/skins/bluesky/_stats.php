<?php
	/**
	 * This is the template that displays stats for a blog
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display the stats, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?disp=stats
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 * @subpackage bluesky
	 */
	if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

	/**
	 * We now call the default stats handler...
	 * However you can replace this file with the full handler (in /blogs) and customize it!
	 */
	require get_path('skins').'_stats.php';
?>