<?php
	/*
	 * This file is a stub for displaying a blog, using evoSkins.
	 * This file will fix some display parameters and then let b2evolution handle
	 * the display by calling an evoSkin. (skins are in the /skins folder.)
	 *
	 * Same display without using skins: noskin_all.php
	 */

	# First, select which blog you want to display here!
	# You can find these numbers in the back-office under the Blogs section.
	# You can also create new blogs over there. If you do, duplicate this file for the new blog.
	$blog = 3;   	// 3 is for "demo blog B"

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


	# That's it, now let b2evolution do the rest! :)
	require(dirname(__FILE__)."/b2evocore/_blog_main.php");
?>