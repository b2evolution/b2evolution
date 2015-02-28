<?php
/**
 * This is the main public interface file.
 *
 * IF YOU ARE READING THIS IN YOUR WEB BROWSER, IT MEANS THAT PHP IS NOT PROPERLY INSTALLED
 * ON YOUR WEB SERVER. IF YOU DON'T KNOW WHAT THIS MEANS, CONTACT YOUR SERVER ADMINISTRATOR
 * OR YOUR HOSTING COMPANY.
 *
 * This file is NOT mandatory. You can delete it if you want.
 * You can also replace the contents of this file with contents similar to the contents
 * of a_stub.php, a_noskin.php, multiblogs.php, etc.
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

$Timer->resume('index.php');

if( ! isset($collections_Module) )
{	// The evocore framework is not used as a blog app here / we don't know how to display a public interface...
	header_redirect( 'admin.php', 302 );
	exit(0);
}

// initialize which blog should be displayed, and display default page if blog couldn't been initialized
if( !init_requested_blog() )
{ // No specific blog to be displayed:
	// we are going to display the default page:
	require dirname(__FILE__).'/default.php';
	exit();
}

// A blog has been requested... Let's set a few default params:

# You could *force* a specific skin here with this setting:
# $skin = 'basic';

# This setting retricts posts to those published, thus hiding drafts.
# You should not have to change this.
$show_statuses = array();

# Additionnaly, you can set other values (see URL params in the manual)...
# $order = 'ASC'; // This for example would display the blog in chronological order...

$Timer->pause('index.php');

// That's it, now let b2evolution do the rest! :)
require $inc_path.'_blog_main.inc.php';

?>
