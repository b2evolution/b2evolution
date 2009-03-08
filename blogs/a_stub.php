<?php
/**
 * THIS IS ACTUALLY A DUPLICATE OF blog1.php . We leave this file here because some ancient doc still refers to it.
 *
 * This is a stub file for displaying a b2evolution blog.
 *
 * A stub file is used to anchor a particular blog in a particular location of your website.
 * More info: {@link http://manual.b2evolution.net/Stub_file}
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 */

# First, select which blog you want to display here!
# You can find these numbers in the back-office under the Blogs section.
# You can also create new blogs over there. If you do, you may duplicate this file for the new blog.
$blog = 1;

# You could *force* a specific skin here with this setting: (otherwise, default will be used)
# $skin = 'custom';

# This setting retricts posts to those published, thus hiding drafts.
# You should not have to change this.
$show_statuses = array();

# Here you can set a limit before which posts will be ignored
# You can use a unix timestamp value or 'now' which will hide all posts in the past
$timestamp_min = '';

# Here you can set a limit after which posts will be ignored
# You can use a unix timestamp value or 'now' which will hide all posts in the future
$timestamp_max = 'now';

# Additionnaly, you can set other values (see URL params in the manual)...
# $order = 'ASC'; // This for example would display the blog in chronological order...

/**
 * That's it, now let b2evolution do the rest! :)
 * Note: if you put this file in a subdirectory, you will need to adjust the path below, for example:
 * require_once dirname(__FILE__).'/../conf/_config.php';
 */
require_once dirname(__FILE__).'/conf/_config.php';

require $inc_path.'_blog_main.inc.php';
?>