<?php
/**
 * This file is a stub file for displaying a blog, using an RSS2 skin.
 *
 * This file will set some display parameters and then let b2evolution handle
 * the display by calling an evoSkin. (skins are in the /skins folder.)
 *
 * Note: You only need to use this stub file for advanced use of b2evolution.
 * Most of the time, calling your blog through index.php will be enough.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package xmlsrv
 */

# We're not forcing a specific blog here, but we tell b2evo to expect
# a blog in the URL params:
$resolve_extra_path = false;	// We don't want extra path resolution on this page

# Let's force the use of the RSS skin:
$skin = '_rss2';

# This setting retricts posts to those published, thus hiding drafts.
# You should not have to change this.
$show_statuses = array();

# Here you can set a limit before which posts will be ignored
# You can use a unix timestamp value or 'now' which will hide all posts in the past
$timestamp_min = '';

# Here you can set a limit after which posts will be ignored
# You can use a unix timestamp value or 'now' which will hide all posts in the future
$timestamp_max = 'now';

/**
 * That's it, now let b2evolution do the rest! :)
 */
require dirname(__FILE__).'/../evocore/_blog_main.inc.php';
?>