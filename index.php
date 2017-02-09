<?php
/**
 * This is the main public interface file. It will try to detect which collection is being requested
 * or display the default collection. If there is none, it will call default.php.
 *
 * ---------------------------------------------------------------------------------------------------------------
 * IF YOU ARE READING THIS IN YOUR WEB BROWSER, IT MEANS THAT YOU DID NOT LOAD THIS FILE THROUGH A PHP WEB SERVER. 
 * TO GET STARTED, GO TO THIS PAGE: http://b2evolution.net/man/getting-started
 * ---------------------------------------------------------------------------------------------------------------
 *
 * This file is NOT mandatory. You may replace it with one or several stub files.
 * More info: {@link http://b2evolution.net/man/stub-file}
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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

// initialize which blog should be displayed, and display default page if blog could not be initialized
if( !init_requested_blog( false ) )
{	// No specific blog to be displayed:
	if( $Settings->get( 'default_blog_ID' ) == -1 )
	{	// we are going to display the admin page:
		if( ! is_logged_in() )
		{	// user must be logged in and his/her account must be validated before access to admin:
			$login_required = true;
			$validate_required = true;
			require $inc_path.'_init_login.inc.php';
		}
		require dirname(__FILE__).'/admin.php';
	}
	else
	{	// we are going to display the default page:
		require dirname(__FILE__).'/default.php';
	}
	exit();
}

// A blog has been requested... Let's set a few default params:

# You could *force* a specific skin here with this setting:
# $skin = 'basic';

# Additionnaly, you can set other values (see URL params in the manual)...
# $order = 'ASC'; // This for example would display the blog in chronological order...

$Timer->pause('index.php');

// That's it, now let b2evolution do the rest! :)
require $inc_path.'_blog_main.inc.php';

?>
