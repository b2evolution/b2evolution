<?php
	/*
	 * This file is a stub for displaying a blog, using evoSkins.
	 * This file will fix some display parameters and then let b2evolution handle
	 * the display by calling an evoSkin. (skins are in the /skins folder.)
	 *
	 * Same display without using skins: noskin_all.php
	 */

	# First, select which blog you want to display here!
	# You can find thess numbers in the back-office under the Blogs section.
	# You can also create new blogs over there. If you do, duplicate this file for the new blog.
	$blog = 2;   	// 2 is for "demo blog A" or your upgraded blog (depends on your install)

	# Now, select the default skin you want to display for this blog.
	# This setting refers to a subfolder name in the '/skins' folder 
	$default_skin = 'fplanque2002';
	# You can *force* a skin with this setting:
	# $skin = 'fplanque2002';
	
	# This setting retricts posts to those published, thus hiding drafts.
	# You should not have to change this.
	$show_statuses = "'published'";
	
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

	# That's it, now let b2evolution do the rest! :)
	require(dirname(__FILE__)."/b2evocore/_blog_main.php");
?>