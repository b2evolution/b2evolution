<?php
	/*
	 * This is the template that displays (recursive) list of (sub)categories
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 */
	if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

	# You can customize the following as you wish:
	if(!isset($cat_all)) $cat_all = 'All';	// Set to empty to hide
	# global category list delimiters:
	if(!isset($cat_main_start)) $cat_main_start = '';
	if(!isset($cat_main_end)) $cat_main_end = '';
	# Category delimiters:
	if(!isset($cat_line_start)) $cat_line_start = '<li>';
	if(!isset($cat_line_end)) $cat_line_end = '</li>';
	if(!isset($cat_line_checkbox)) $cat_line_checkbox = true;
	# Category group delimiters:
	if(!isset($cat_group_start)) $cat_group_start = '<ul>';
	if(!isset($cat_group_end)) $cat_group_end = '</ul>';
	# When multiple blogs are listed on same page:
	if(!isset($cat_blog_start)) $cat_blog_start = '<h4>';
	if(!isset($cat_blog_end)) $cat_blog_end = '</h4>';

	/*
	 * We now call the default categories handler...
	 * However you can replace this file with the full handler (in /blogs) and customize it!
	 */
	require get_path('skins').'/_categories.php';

?>