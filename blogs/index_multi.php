<?php
/**
 * This is an alternative index.php file.
 * This one is optimized for a multiblog setup where each blog can be identified by its URL.
 * This file will ignore any ?blog= parameter.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal Note: we need at least one file in the main package}}
 *
 * @package main
 */

/**
 * First thing: Do the minimal initializations required for b2evo:
 */
require_once dirname(__FILE__).'/conf/_config.php';

require_once $inc_path.'_main.inc.php';

if( !init_requested_blog() )
{ // No specific blog to be displayed:
	echo 'No default blog is set.';
	exit();
}

// Memorize that blog param as DEFAULT so that it doesn't get passed in regenerate_url()
memorize_param( 'blog', 'integer', $blog );

// A blog has been requested... Let's set a few default params:

# You could *force* a specific skin here with this setting:
# $skin = 'basic';

# This setting retricts posts to those published, thus hiding drafts.
# You should not have to change this.
$show_statuses = array();

# Additionnaly, you can set other values (see URL params in the manual)...
# $order = 'ASC'; // This for example would display the blog in chronological order...

// That's it, now let b2evolution do the rest! :)
require $inc_path.'_blog_main.inc.php';

?>