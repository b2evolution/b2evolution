<?php 
/**
 * This is the main public interface file!
 *
 * This file is NOT mandatory. You can delete it if you want.
 * You can also replace the contents of this file with contents similar to the contents
 * of noskin_a.php or multiblogs.php or those of a stub file (see stub.model)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evolution
 */

// First thing: Do the minimal initializations required for b2evo:
require_once dirname(__FILE__).'/b2evocore/_main.php';

// Check if a specific blog has been requested in the URL:
param( 'blog', 'integer', $Settings->get('default_blog_ID'), true );

if( empty($blog) )
{	// No blog requested, we are going to display the default page:
	require dirname(__FILE__).'/default.php';
	exit();
}

// A blog has been requested... Let's set a few default params:
$selected_Blog = $BlogCache->get_by_ID( $blog );

# Select the default skin to be used to display this blog.
$default_skin = $selected_Blog->get('default_skin');
# You can *force* a skin with this setting:
# $skin = 'fplanque2002';
# This setting refers to a subfolder name in the '/skins' folder 

# This setting retricts posts to those published, thus hiding drafts.
# You should not have to change this.
$show_statuses = array();

# This is the blog to be used as a blogroll (set to 0 if you don't want to use this feature)
$blogroll_blog = 4;

# This is the list of categories to restrict the blogroll to (cats will be displayed recursively)
# Example: $blogroll_cat = '4,6,7';
$blogroll_cat = '';

# This is the array if categories to restrict the blogroll to (non recursive)
# Example: $blogroll_catsel = array( 4, 6, 7 );
$blogroll_catsel = array( );

# Here you can set a limit before which posts will be ignored
# You can use a unix timestamp value or 'now' which will hide all posts in the past
$timestamp_min = '';

# Here you can set a limit after which posts will be ignored
# You can use a unix timestamp value or 'now' which will hide all posts in the future
$timestamp_max = 'now';

# Additionnaly, you can set other values (see URL params in the manual)...
# $order = 'ASC'; // This for example would display the blog in chronological order...

// That's it, now let b2evolution do the rest! :)
require dirname(__FILE__)."/$core_subdir/_blog_main.php";

?>