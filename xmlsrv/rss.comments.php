<?php
/**
 * This file is a stub file for displaying a blog, using an RSS skin.
 *
 * This file will set some display parameters and then let b2evolution handle
 * the display by calling an evoSkin. (skins are in the /skins folder.)
 *
 * Note: You only need to use this stub file for advanced use of b2evolution.
 * Most of the time, calling your blog through index.php will be enough.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package xmlsrv
 */

# We're not forcing a specific blog here, but we tell b2evo to expect
# a blog in the URL params:
$resolve_extra_path = false;	// We don't want extra path resolution on this page

# Let's force the use of the RSS skin:
$tempskin = '_rss';

# Inside that skin, let's force the use of the lastcomments display:
$disp = 'comments';

/**
 * That's it, now let b2evolution do the rest! :)
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require $inc_path.'_blog_main.inc.php';
?>