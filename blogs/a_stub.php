<?php
/**
 * This file is a stub file for displaying a blog, using evoSkins.
 *
 * This file will set some display parameters and then let b2evolution handle
 * the display by calling an evoSkin. (skins are in the /skins folder.)
 *
 * Note: You only need to use this stub file for advanced use of b2evolution.
 * Most of the time, calling your blog through index.php will be enough.
 *
 * Same display without using skins: a_noskin.php
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 */

# First, select which blog you want to display here!
# You can find these numbers in the back-office under the Blogs section.
# You can also create new blogs over there. If you do, you may duplicate this file for the new blog.
$blog = 2;   	// 2 is for "demo blog A" or your upgraded blog (depends on your install)

# You could *force* a specific skin here with this setting:
# $skin = 'basic';

# This setting retricts posts to those published, thus hiding drafts.
# You should not have to change this.
$show_statuses = array();

# This is the blog to be used as a linkblog (set to 0 if you don't want to use this feature)
$linkblog_blog = 4;

# This is the list of categories to restrict the linkblog to (cats will be displayed recursively)
# Example: $linkblog_cat = '4,6,7';
$linkblog_cat = '';

# This is the array if categories to restrict the linkblog to (non recursive)
# Example: $linkblog_catsel = array( 4, 6, 7 );
$linkblog_catsel = array( );

# Here you can set a limit before which posts will be ignored
# You can use a unix timestamp value or 'now' which will hide all posts in the past
$timestamp_min = '';

# Here you can set a limit after which posts will be ignored
# You can use a unix timestamp value or 'now' which will hide all posts in the future
$timestamp_max = 'now';

# Additionnaly, you can set other values (see URL params in the manual)...
# $order = 'ASC'; // This for example would display the blog in chronological order...

# That's it, now let b2evolution do the rest! :)
require(dirname(__FILE__)."/b2evocore/_blog_main.php");
?>