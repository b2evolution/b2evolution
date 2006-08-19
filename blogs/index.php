<?php
/**
 * This is the main public interface file!
 *
 * This file is NOT mandatory. You can delete it if you want.
 * You can also replace the contents of this file with contents similar to the contents
 * of a_stub.php, a_noskin.php, multiblogs.php, etc.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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

// Check if a specific blog has been requested in the URL:
param( 'blog', 'integer', '', true );

if( empty($blog) )
{ // No blog requested by URL param, let's try to match something in the URL
	$Debuglog->add( 'No blog param received, checking extra path...', 'detectblog' );

	$BlogCache = & get_Cache( 'BlogCache' );

	if( preg_match( '#^(.+?)index.php/([^/]+)#', $ReqHost.$ReqPath, $matches ) )
	{ // We have an URL blog name:
		$Debuglog->add( 'Found a potential URL blog name: '.$matches[2], 'detectblog' );
		if( (($Blog = & $BlogCache->get_by_urlname( $matches[2], false )) !== false) )
		{ // We found a matching blog:
			$blog = $Blog->ID;
		}
	}

	if( empty($blog) )
	{ // No blog identified by URL name, let's try to match the absolute URL
		if( preg_match( '#^(.+?)index.php#', $ReqHost.$ReqPath, $matches ) )
		{ // Remove what's not part of the absolute URL
			$ReqAbsUrl = $matches[1];
		}
		else
		{
			$ReqAbsUrl = $ReqHost.$ReqPath;
		}
		$Debuglog->add( 'Looking up absolute url : '.$ReqAbsUrl, 'detectblog' );

		if( (($Blog = & $BlogCache->get_by_url( $ReqAbsUrl, false )) !== false) )
		{ // We found a matching blog:
			$blog = $Blog->ID;
			$Debuglog->add( 'Found matching blog: '.$blog, 'detectblog' );
		}
	}

	if( empty($blog) )
	{ // Still no blog requested, use default
		$blog = $Settings->get('default_blog_ID');
		$Debuglog->add( 'Using default blog '.$blog, 'detectblog' );
	}

	if( empty($blog) )
	{ // No specific blog to be displayed:
		// we are going to display the default page:
		require dirname(__FILE__).'/default.php';
		exit();
	}
}

// A blog has been requested... Let's set a few default params:

# You could *force* a specific skin here with this setting:
# $skin = 'basic';

# This setting retricts posts to those published, thus hiding drafts.
# You should not have to change this.
$show_statuses = array();

# You could *force* a specific link blog here with this setting: (otherwise, default will be used)
# $linkblog = 4;

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

// That's it, now let b2evolution do the rest! :)
require $inc_path.'_blog_main.inc.php';

?>